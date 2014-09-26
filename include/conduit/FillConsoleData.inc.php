<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}
require_once('Connect.inc.php');

?>
<?php

// Формируем список школьников
function FillConsoleData($ClassID) {
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

    // Формируем список листков у данного класса
    $sql = 'SELECT DISTINCT
                `PList`.`ID`, `PList`.`Number`, `PList`.`Description`
            FROM `PList`
            WHERE 
                `PList`.`ClassID` = ?
            ORDER BY 2
            ';
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ClassID));
    $Lists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Формируем список всех задач
    $sql = 'SELECT DISTINCT
                `PProblem`.`ListID` , `PProblem`.`ID` , `PProblem`.`Name`
            FROM `PProblem`
            JOIN `PList` ON `PProblem`.`ListID` = `PList`.`ID`
            WHERE 
                `PList`.`ClassID` = ?
            ORDER BY `PProblem`.`ListID` , `PProblem`.`Group` , `PProblem`.`Name`
            ';
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ClassID));
    $Problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Формируем html-код
    $html  = '    var pupil_array = [';
    foreach ($Pupils as $Pupil) {
        $html .= '{ID:"' . $Pupil['ID'] . '", Name:"' . $Pupil['Name1'] . ' ' . $Pupil['Name2'] . '"}, ';
    }
    $html = substr($html, 0, -2) . '];' . PHP_EOL;
    echo $html;

    $html  = '    var list_array = [';
    foreach ($Lists as $List) {
        $html .= '{ID:"' . $List['ID'] . '", Number:"' . $List['Number'] . '", Description:"' . $List['Description'] . '"}, ';
    }
    $html = substr($html, 0, -2) . '];' . PHP_EOL;
    echo $html;

    $html  = '    var problem_array = {';
    $prev_list = '';
    $started = False;
    foreach ($Problems as $Problem) {
        if ($prev_list !== $Problem['ListID']) {
            if ($started) {
                $html = substr($html, 0, -2) . '],'. PHP_EOL . '    ';
            } else {
                $started = True;
            }
            $html .= 'l' . $Problem['ListID'] . ':[';
        }
        $name = '0' . $Problem['Name'];
        if (is_numeric(substr($name, -1))) {
            $name = $name . '_';
        }
        $html .= '{ID:"' . $Problem['ID'] . '", Search:"' . $name . '", Name:"' . $Problem['Name'] .  '"}, ';
        $prev_list = $Problem['ListID'];
    }
    $html = substr($html, 0, -2) . ']'. PHP_EOL . '    };' . PHP_EOL;
    echo $html;

}

?>