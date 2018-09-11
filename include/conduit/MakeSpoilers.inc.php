<?php

if (!defined('IN_CONDUIT')){
    exit(0);
}
require_once('Connect.inc.php');
require_once('FillConduit.inc.php');

?>
<?php

// function listDisplayName($number, $description) {
//     return filter_var($number . ' - ' . $description, FILTER_SANITIZE_SPECIAL_CHARS);
// }
function listDisplayName($number, $description, $MinFor5, $MinFor4, $MinFor3) {
    $dname = $number . ' — ' . $description;
    $nname = ' з. на ';
    if ($MinFor5 > 0) {
        $dname = $dname . ' (' . (int)($MinFor5);
        $nname = $nname . '5';
        if ($MinFor4 > 0) {
            $dname = $dname . '/' . (int)($MinFor4);
            $nname = $nname . '/4';
        }
        if ($MinFor3 > 0) {
            $dname = $dname . '/' . (int)($MinFor3);
            $nname = $nname . '/3';
        }
        $dname = $dname . $nname . ")";
    }
    $dname = filter_var($dname, FILTER_SANITIZE_SPECIAL_CHARS);
    // $dname = str_replace('_span_start_here', '<span>', $dname);
    // $dname = str_replace('_span_end_here', '</span>', $dname);
    return $dname;
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
function makeSpoilers($ClassID, $toJSON = false) {
    global $conduit_db;
    
    // Формируем список доступных листков на основе таблицы PList
    $sql = 'SELECT `ID`, `Number`, `ListTypeID` as `Type`, `Description`, `MinFor3`, `MinFor4`, `MinFor5`  FROM `PList` ' . 
           'WHERE `ClassID`  = ? OR `ClassID` IS NULL';
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ClassID));
    $i = 0;
    $List = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // $Text = listDisplayName($row['Number'], $row['Description']);
        $Text = listDisplayName($row['Number'], $row['Description'], $row['MinFor5'], $row['MinFor4'], $row['MinFor3']);
        $ID = $row['ID'];
        $List[$i++] = array(
            'ID'   => $ID,
            'Text' => $Text,
            'Type' => $row['Type'],
            'MinFor3' => $row['MinFor3'],
            'MinFor4' => $row['MinFor4'],
            'MinFor5' => $row['MinFor5']
        );
    }
    
    // Сортируем листки интеллектуально
    usort($List, 'compareList');
    
    // Выводим данные в JSON, если требуется
    if ($toJSON) {
        $data = array(
            'ClassID' => $ClassID,
            'List'    => $List
            );
        echo json_encode($data);
        return;
    }

    // Определяем, какие из спойлеров должны быть открыты
    if (isset($_COOKIE['ec_open'])) {
        $opened_spoilers = explode(',', $_COOKIE['ec_open']);
    } else {
        $opened_spoilers = array();
    }
    
    // Формируем html-код
    foreach ($List as $Entry) {
        if (in_array($Entry['ID'], $opened_spoilers)) {
            $conduit = fillConduit($ClassID, $Entry['ID']);
            $state   = 'opened';
            $print_class = 'print';
        } else {
            $conduit = '';
            $state   = 'empty';
            $print_class = '';
        }
        echo(
<<<SPOILER
        <li class="conduit_container $print_class" data-id="${Entry['ID']}" data-state="$state" data-mf3="${Entry['MinFor3']}" data-mf4="${Entry['MinFor4']}" data-mf5="${Entry['MinFor5']}">
            <span class="conduit_spoiler">${Entry['Text']}</span>
            <p class="loading" style="display: none;">Ждите. Производится загрузка данных с сервера&hellip;</p>
            $conduit
        </li>
SPOILER
        );
    }
}

?>
