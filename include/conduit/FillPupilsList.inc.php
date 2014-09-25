<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}
require_once('Connect.inc.php');

?>
<?php

// Формируем список школьников
function fillPupilsList($ClassID) {
    global $conduit_db, $ConduitUser;

    // Формируем список школьников класса
    $sql = 'SELECT DISTINCT
                `PPupil`.`Name1`, `PPupil`.`Name2`, `PPupil`.`ID`
            FROM `PPupil`
            WHERE 
                `PPupil`.`ClassID` = ?
            ORDER BY 1, 2
            ';
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ClassID));
    $Pupils = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Формируем html-код
    $select  = '<select id="pupil">';
    $select .= '<option value="" selected="selected">Все</option>';
    foreach ($Pupils as $Pupil) {
        $select .= '<option value="' . $Pupil['ID'] . '">' . $Pupil['Name1'] . ' ' . $Pupil['Name2'] . '</option>';
    }
    
    $select .= '</select>';
    
    echo $select;
}

?>