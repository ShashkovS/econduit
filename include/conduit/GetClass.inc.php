<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}

require_once('Connect.inc.php');

// Задача: определить, к какому классу обращался пользователь.
// Решение: имя класса (PClass.Name) содержится в адресе исходного запроса ($_SERVER['REQUEST_URI']).
// Пример: $_SERVER['REQUEST_URI'] = '/conduit/d17/Conduits.php'

function GetClass() {
    global $conduit_db, $Settings;

    if (preg_match("@^{$Settings['conduit_relative_link']}(.*?)/@i", $_SERVER['REQUEST_URI'], $matches)) {
        $ClassName = $matches[1];
        // Определяем класс по его названию
        $stmt = $conduit_db->prepare('SELECT `ID`, `Description` FROM `PClass` WHERE `ID` = ? LIMIT 1');
        if (!$stmt->execute(array($ClassName))) {
            trigger_error('Selection error: ' . $stmt->errorInfo());
        }
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }
    }

    if (!defined('AJAX')) {
        header("HTTP/1.1 404 Not Found");
        require_once('404.html');
    }
    exit();
}

?>
