<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}

if (!defined('IN_PHPBB')){
    // Попытка прямого доступа к файлу
    exit();
}

$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '/home/host1333511/econduit.ru/htdocs/www/forum/';
$phpEx = 'php';
include_once($phpbb_root_path . 'common.' . $phpEx);

$phpbb_forum_link = 'http://econduit.ru/forum/';
$conduit_webroot = '/conduit';


// Параметры SQL-соединения
require_once('Credentials.inc.php');

// Коннект к базе
$conduit_db = new PDO(
    "{$sql_credentials['driver']}:host={$sql_credentials['host']};dbname={$sql_credentials['db']}" ,
    $sql_credentials['user'] ,
    $sql_credentials['password'] ,
    array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
    )
);

// Теперь логин и пароль можно забыть навсегда
unset($sql_credentials);

// Часовой пояс
date_default_timezone_set('Europe/Moscow');

?>