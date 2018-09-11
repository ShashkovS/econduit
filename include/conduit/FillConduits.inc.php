<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}
require_once('Connect.inc.php');

?>
<?php

function listDisplayName($number, $description, $min5, $min4, $min3) {
    $dname = 'жужа' . $number . ' — ' . $description;
    $nname = ' з. на 5';
    if $min5 > 0 {
        $dname = $dname . ' (' . $min5;
        if $min4 > 0 {
            $dname = $dname . '/' . $min4;
            $nname = $nname . '/4';
        }
        if $min3 > 0 {
            $dname = $dname . '/' . $min3;
            $nname = $nname . '/3';
        }
        $dname = $dname . $nname . ")";
    }
    return filter_var($dname, FILTER_SANITIZE_SPECIAL_CHARS);
}


function compareList(&$a, &$b) {
    if ($a['Type'] !== $b['Type']) {
        return $a['Type'] - $b['Type'];
    } 
    if ((int)($a['Text']) !== (int)($b['Text'])) {
        return (int)($b['Text']) - (int)($a['Text']);
    }
    return strcmp($a['Text'], $b['Text']);
}

// Формируем список спойлеров с кондуитам внутри.
// В списке присутствуют кондуиты всех листков данного класса, упорядоченные от самых последних к самым старым.
function fillConduits($ClassID) {
    global $conduit_db;
    
    // Формируем список доступных листков на основе таблицы PList
    $sql = 'SELECT `ID`, `Number`, `ListTypeID` as `Type`, `Description`, `MinFor3`, `MinFor4`, `MinFor5` FROM `PList` ' . 
           'WHERE `ClassID`  = ? OR `ClassID` IS NULL';
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ClassID));
    $i = 0;
    $List = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $Text = listDisplayName($row['Number'], $row['Description'], $row['MinFor5'], $row['MinFor4'], $row['MinFor3']);
        $ID = $row['ID'];
        $List[$i++] = array(
            'ID'   => $ID,
            'Text' => $Text,
            'Type' => $row['Type']
            'MinFor3' => $row['MinFor3'],
            'MinFor4' => $row['MinFor4'],
            'MinFor5' => $row['MinFor5']
        );
    }
    
    // Сортируем листки интеллектуально
    usort($List, 'compareList');
    
    // Формируем html-код
    foreach ($List as $Entry) {
        echo(
<<<SPOILER
        <li>
            <span class="conduit_spoiler" data-id="${Entry['ID']}" data-mf3="${Entry['MinFor3']}" data-mf4="${Entry['MinFor4']}" data-mf5="${Entry['MinFor5']}"> data-state="empty">${Entry['Text']}</span>
            <div class="conduit_container" data-id="${Entry['ID']}</div>
            <p class="loading">Ждите. Производится загрузка данных с сервера&hellip;</p>
        </li>

SPOILER
        );
    }
}

?>