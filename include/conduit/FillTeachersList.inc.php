<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}
require_once('Connect.inc.php');

?>
<?php

// Формируем список учителей
function fillTeachersList($ClassID) {
    global $conduit_db, $ConduitUser;

    // Формируем список учителей класса
    $sql = 'SELECT DISTINCT
                `PUser`.`User`, `PUser`.`DisplayName` 
            FROM `PUser` INNER JOIN `PPupil` ON `PUser`.`User` = `PPupil`.`Teacher`
            WHERE 
                `PUser`.`Disabled` = "N" AND `PPupil`.`ClassID` = ?
            ORDER BY 2
            ';
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ClassID));
    $Teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Формируем html-код
    $select  = '<select id="teacher">';
    $select .= '<option value="" selected="selected">Все</option>';
    foreach ($Teachers as $Teacher) {
        if ($Teacher['User'] === $ConduitUser->name) {
            $class = ' class="current"';
        } else {
            $class = '';
        }
        $select .= '<option value="' . $Teacher['User'] . '"' . $class . '>' . $Teacher['DisplayName'] . '</option>';
    }
    
    $select .= '</select>';
    
    echo $select;
}

?>