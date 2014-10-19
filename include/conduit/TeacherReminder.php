<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);

require_once('Connect.inc.php'); 
require_once('SendMail.inc.php'); 

//if (isset($__GET['class'])) {
//    $Class = $__GET['class'];
//} else {
//    exit();
//}

$Class = 'd17';

$Log = "";
// Проверяем, что сегодня рабочий день
// Для этого должны одновременно выполняться два условия:
//      1. Правильный день недели
//      2. За этот день уже внесена хотя бы одна задача --- чтобы не надоедать всем в каникулы.
function work_day() {
    global $conduit_db, $Class;
    global $Log;
    
    // Проверяем день недели
    $sql = "SELECT 
                COUNT(1) AS `Cnt`
            FROM 
                `PWorkDays`
            WHERE 
                `PWorkDays`.`Day` = WEEKDAY(CURRENT_DATE)";
    $stmt = $conduit_db->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['Cnt'] == 0) {
        $Log .= "work_day: нерабочий день недели.<br/>"; 
        return false;
    }
    
    // Проверяем наличие сданных задач
    $sql = "SELECT 
                COUNT(1) AS `Cnt`
            FROM 
                `PResult` INNER JOIN `PPupil` ON `PResult`.`PupilID` = `PPupil`.`ID`
            WHERE 
                `PPupil`.`ClassID` = :class AND 
                TIMESTAMPDIFF(HOUR,`TS`,CURRENT_TIMESTAMP) <= 24";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array('class' => $Class));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['Cnt'] == 0) {
        $Log .= "work_day: не сдано ни одной задачи.<br/>"; 
        return false;
    }
    
    return true;
}

// Проверяем, что чувак не забыл внести задачи.
// Признаком этого может служить одно из двух:
//      1. Есть задачи, внесённые непосредственно им.
//      2. У его школьников в сумме внесено хотя бы N задач --- либо он вносил под чужим профайлом, либо его не было на уроке и кто-то подменял.
define('N', 7);
function check_teacher($User, $Name, $Email) {
    global $conduit_db, $Class;
    global $Log;
    
    $Log .= "check_teacher: Проверка учителя $User ($Name, $Email).<br/>";
    
    // Проверяем первый признак
    $sql = "SELECT 
                COUNT(1) AS `Cnt`
            FROM 
                `PResult` INNER JOIN `PPupil` ON `PResult`.`PupilID` = `PPupil`.`ID`
            WHERE 
                `PPupil`.`ClassID` = :class AND 
                `PResult`.`User`   = :user AND 
                TIMESTAMPDIFF(HOUR,`TS`,CURRENT_TIMESTAMP) <= 24";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array('class' => $Class, 'user' => $User));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['Cnt'] > 0) {
        $Log .= "check_teacher: сработал первый признак.<br/>";
        return;
    }
    
    // Проверяем второй признак
    $sql = "SELECT 
                COUNT(1) AS `Cnt`
            FROM 
                `PResult` INNER JOIN `PPupil` ON `PResult`.`PupilID` = `PPupil`.`ID`
            WHERE 
                `PPupil`.`ClassID` = :class AND 
                `PPupil`.`Teacher` = :user AND 
                TIMESTAMPDIFF(HOUR,`TS`,CURRENT_TIMESTAMP) <= 24";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array('class' => $Class, 'user' => $User));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['Cnt'] >= N) {
        $Log .= "check_teacher: сработал второй признак.<br/>";
        return;
    }
    
    // Всё-таки надо напомнить
    write_to_teacher($Name, $Email);
}

function write_to_teacher($FullName, $Email) {
    global $Class;
    global $Log;
    global $Settings;
     
    $Log .= "write_to_teacher: необходимо напомнить: $FullName, $Email.<br/>"; 
     
    $parts = explode(" ", $FullName, 2);
    $FirstName = $parts[0];
    $gender = ($FirstName == "Лена" || $FirstName == "Маша" || $FirstName == "Таня" || $FirstName == "Аня")?"а":"";
    
    // Собираем заголовки
    $to = array($FullName, $Email);
    $from = array("Электронный кондуит", $Settings['teacher_reminder_email']);
    $subject = "Напоминание";

    // Собираем тело
    $greeting = "$FirstName, привет!";
    $body = "<p>Сложнейший эвристический анализ выявил положительную вероятность того, что ты забыл$gender заполнить электронный кондуит :(</p>" . 
            "<p>Но ещё не поздно! Как и ранее, кондуит доступен по адресу <a href=\"{$Settings['conduit_absolute_link']}{$Class}/\">{$Settings['conduit_absolute_link']}{$Class}/</a>.</p>";
    $signature = "Данное сообщение сформировано автоматически и не требует ответа";
    $message = "$greeting<br/><br/>$body<br/><br/>$signature";

    $Log .= "write_to_teacher: отправляем письмо.<br/>"; 
    // Отправляем письмо
    send_mime_mail($from[0], $from[1], $to[0], $to[1], 'UTF-8', 'UTF-8', $subject, $message, TRUE);
    
}

function write_to_all_teachers() {
    global $conduit_db, $Class;
    global $Log;
    
    // Вначале определяем, учебный ли сегодня день
    if (!work_day()) {
        $Log .= "write_to_all_teachers: День не рабочий.<br/>";
        return;
    }
    $Log .= "write_to_all_teachers: День рабочий.<br/>";
    
    // Теперь пробегаемся по всем учителям и проверяем каждого
    $sql = "SELECT
                `PUser`.`User`, `PUser`.`DisplayName`, `PUser`.`Email`
            FROM
                `PUser`
            WHERE
                `PUser`.`User` IN (
                    SELECT 
                        `PPupil`.`Teacher` 
                    FROM 
                        `PPupil` 
                    WHERE 
                        `PPupil`.`ClassID` = :class
                ) AND
                `Disabled` = 'N' AND `Email` IS NOT NULL";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array('class' => $Class));
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        check_teacher($row['User'], $row['DisplayName'], $row['Email']);
    }
}

$Log .= "Работа начата.<br/>";
write_to_all_teachers();

//send_mime_mail("Электронный кондуит", "reminder@econduit.ru",  "Женя", "eugene57@yandex.ru", 'UTF-8', 'UTF-8', "Отчёт о работе", $Log, TRUE);

?>
