<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);
define('AJAX', true);

require_once('UserManagement.inc.php');
require_once('AjaxError.inc.php');
require_once('RenderCell.inc.php');

// Пользователь должен иметь доступ к оценкам
$ConduitUser->must_manage('Marks');

?>
<?php

try {
    $Request = json_decode($_POST['Request'], true);
} catch (Exception $e) {
    triggerAjaxError(404);
}
$L = count($Request);
if ($L === 0) {
    echo '[]';
    exit(0);
}

// Проверим, что все ученики, упоминаемые в $Request, принадлежат к текущему классу
function extract_pupil($requestEntry) {
    return (int)($requestEntry['Pupil']);
}

try {
    $pupils = array_values(array_unique(array_map('extract_pupil', $Request)));
} catch (Exception $e) {
    triggerAjaxError(500);
}

$sql = 'SELECT COUNT(1)
            FROM `PPupil`
        WHERE `PPupil`.`ID` IN (' . str_repeat('?,', count($pupils) - 1) . '?) AND
              `PPupil`.`ClassID` = ?';
$stmt = $conduit_db->prepare($sql);
$pupils[] = $Class['ID'];
$stmt->execute($pupils);
$result = $stmt->fetch(PDO::FETCH_NUM);

// Результат должен в точности соответствовать количеству учеников. Если это не так, в баню такого учителя. 
if (!$result || $result[0] != count($pupils)-1) {
    error_log('Попытка обновления чужих данных под пользователем ' . $ConduitUser->name);
    triggerAjaxError(500);
}

// Обрабатываем запрос 
if ($_POST['Type'] === 'update') {
   
    $Mark = new Mark(null, $ConduitUser->display_name, strftime('%Y-%m-%d %T'));    // REM: на некоторых серверах strftime может возвращать false вместо даты
    
    $sql = 'INSERT INTO `PResult` (`PupilID`, `ProblemID`, `Mark`, `User`) 
                VALUES (:pupil, :problem, :mark, :user)
                ON DUPLICATE KEY UPDATE `Mark` = VALUES(`Mark`), `User` = VALUES(`User`)';
    $stmt = $conduit_db->prepare($sql);
    $stmt->bindParam(':pupil', $Pupil, PDO::PARAM_INT);
    $stmt->bindParam(':problem', $Problem, PDO::PARAM_INT);
    $stmt->bindParam(':mark', $Text);
    $stmt->bindValue(':user', $ConduitUser->name);

    $sql = 'INSERT INTO `PResultHistory` (`PupilID`, `ProblemID`, `Mark`, `User`) 
                VALUES (:pupil, :problem, :mark, :user)';
    $stmtH = $conduit_db->prepare($sql);
    $stmtH->bindParam(':pupil', $Pupil, PDO::PARAM_INT);
    $stmtH->bindParam(':problem', $Problem, PDO::PARAM_INT);
    $stmtH->bindParam(':mark', $Text);
    $stmtH->bindValue(':user', $ConduitUser->name);
    
    $conduit_db->beginTransaction();
    for ($i = 0; $i < $L; ++$i) {
        $Pupil = $Request[$i]['Pupil'];
        $Problem = $Request[$i]['Problem'];
        $Text = $Request[$i]['Mark'];
        $stmt->execute();
        $stmtH->execute();
        $Mark->text = $Text;
        $Cell = new Cell($Mark);
        $Response[$i] = array(
            'Pupil'   => $Pupil,
            'Problem' => $Problem,
            'Text'    => $Cell->content,
            'Hint'    => $Cell->hint,
            'Mark'    => $Cell->mark
        );
    }
    if(!$conduit_db->commit()) {
        throw new Exception('SQL error');
    }
    
    echo json_encode($Response);    

} elseif ($_POST['Type'] === 'rollback') {
    // Начало транзакции
    $conduit_db->beginTransaction();
    
    // Для каждой из ячеек делаем следующее:
        // Если последнее изменение сделано текущим пользователем, то:
            // Вытаскиваем из истории предыдущее состояние
            // Прописываем его в PResult
            // Удаляем отменённую операцию из истории
    $sql = 'SELECT `PResultHistory`.`ID`, `PResultHistory`.`Mark`, `PResultHistory`.`User`, `PResultHistory`.`TS`, 
                    COALESCE(`PUser`.`DisplayName`, `PResultHistory`.`User`) AS `DisplayName`
                FROM `PResultHistory` LEFT JOIN `PUser` 
                                      ON `PResultHistory`.`User` = `PUser`.`User` 
                WHERE   `PupilID` = :pupil AND 
                        `ProblemID` = :problem 
                ORDER BY `ID` DESC LIMIT 2';
    $stmtFind = $conduit_db->prepare($sql);
    $stmtFind->bindParam(':pupil', $Pupil, PDO::PARAM_INT);
    $stmtFind->bindParam(':problem', $Problem, PDO::PARAM_INT);
    
    $sql = 'DELETE FROM `PResult` 
            WHERE   `PupilID` = :pupil AND 
                    `ProblemID` = :problem AND
                    `User` = :user'; 
    $stmtDel = $conduit_db->prepare($sql);
    $stmtDel->bindParam(':pupil', $Pupil, PDO::PARAM_INT);
    $stmtDel->bindParam(':problem', $Problem, PDO::PARAM_INT);
    $stmtDel->bindValue(':user', $ConduitUser->name);
    
    $sql = 'DELETE FROM `PResultHistory` 
            WHERE   `ID` = :id';
    $stmtDelH = $conduit_db->prepare($sql);
    $stmtDelH->bindParam(':id', $id, PDO::PARAM_INT);
    
    $sql = 'UPDATE `PResult` 
                SET     `Mark` = :mark,
                        `TS`   = :ts,
                        `User` = :prevuser 
                WHERE   `PupilID` = :pupil AND 
                        `ProblemID` = :problem AND
                        `User` = :user'; 
    $stmtUpd = $conduit_db->prepare($sql);
    $stmtUpd->bindParam(':mark', $Text);
    $stmtUpd->bindParam(':ts', $TS);
    $stmtUpd->bindParam(':prevuser', $PrevUser);
    $stmtUpd->bindParam(':pupil', $Pupil, PDO::PARAM_INT);
    $stmtUpd->bindParam(':problem', $Problem, PDO::PARAM_INT);
    $stmtUpd->bindValue(':user', $ConduitUser->name);
    
    $Response = array();
    try {
        for ($i = 0; $i < $L; $i++) {
            $Pupil = $Request[$i]['Pupil'];
            $Problem = $Request[$i]['Problem'];
            
            $stmtFind->execute();
            $result = $stmtFind->fetchAll(PDO::FETCH_ASSOC);
            $count = count($result);
            if ($count === 0) { // Изменений вообще не было; запрос ошибочный
                continue;
            } elseif ($result[0]['User'] !== $ConduitUser->name) { // Последнее изменение сделал кто-то другой; откат не нужен
                continue;
            }
            $id = $result[0]['ID'];
            if ($count === 1) { // До этого метки не было вообще. Просто её удаляем
                $stmtDel->execute();
                $stmtDelH->execute();
                $Response[] = array(
                    'Pupil'     => $Pupil,
                    'Problem'   => $Problem,
                    'Text'      => ''
                );
            } else { // Метка была. Восстанавливаем её состояние
                $Text = $result[1]['Mark'];
                $TS = $result[1]['TS'];
                $PrevUser = $result[1]['User'];
                
                $stmtUpd->execute();
                $stmtDelH->execute();
                                
                $Cell = new Cell(new Mark($Text, $result[1]['DisplayName'], $TS));
                $Response[] = array(
                    'Pupil'   => $Pupil,
                    'Problem' => $Problem,
                    'Text'    => $Cell->content,
                    'Hint'    => $Cell->hint,
                    'Mark'    => $Cell->mark
                );
            }
        }
        if(!$conduit_db->commit()) {
            throw new Exception('SQL error');
        }
    } catch (PDOException $e) {
        $conduit_db->rollBack();
        triggerAjaxError(500);
    }
    echo json_encode($Response);
}
?>