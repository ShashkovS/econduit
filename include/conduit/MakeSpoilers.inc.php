<?php

if (!defined('IN_CONDUIT')){
    exit(0);
}
require_once('Connect.inc.php');
require_once('FillConduit.inc.php');

?>
<?php

function listDisplayName($number, $description) {
    return filter_var($number . ' - ' . $description, FILTER_SANITIZE_SPECIAL_CHARS);
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
function makeSpoilers($ClassID) {
    global $conduit_db;
    
    // Формируем список доступных листков на основе таблицы PList
    $sql = 'SELECT `ID`, `Number`, `ListTypeID` as `Type`, `Description` FROM `PList` ' . 
           'WHERE `ClassID`  = ? OR `ClassID` IS NULL';
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ClassID));
    $i = 0;
    $List = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $Text = listDisplayName($row['Number'], $row['Description']);
        $ID = $row['ID'];
        $List[$i++] = array(
            'ID'   => $ID,
            'Text' => $Text,
            'Type' => $row['Type']
        );
    }
    
    // Сортируем листки интеллектуально
    usort($List, 'compareList');
    
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
        } else {
            $conduit = '';
            $state   = 'empty';
        }
        echo(
<<<SPOILER
        <li>
            <span class="conduit_spoiler" data-id="${Entry['ID']}" data-state="$state">${Entry['Text']}</span>
            <div class="conduit_container" data-id="${Entry['ID']}">$conduit</div>
            <p class="loading" style="display: none;">Ждите. Производится загрузка данных с сервера&hellip;</p>
        </li>

SPOILER
        );
    }
}

?>