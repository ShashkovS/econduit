<?php

if (!defined('IN_CONDUIT') || !defined('IN_PHPBB')){
    // Попытка прямого доступа к файлу
    exit();
}

// Всякие настройки
require_once('Settings.inc.php');

$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : $Settings['forum_absolute_path'];
$phpEx = 'php';
include_once($phpbb_root_path . 'common.' . $phpEx);

// Параметры SQL-соединения
define('SQL', true);
require_once('Credentials.inc.php');

// Коннект к базе
$conduit_db = new PDO(
    "{$credentials['SQL']['driver']}:host={$credentials['SQL']['host']};dbname={$credentials['SQL']['db']}" ,
    $credentials['SQL']['login'] ,
    $credentials['SQL']['password'] ,
    array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
    )
);

// Теперь логин и пароль можно забыть навсегда
unset($credentials);

// Часовой пояс
date_default_timezone_set($Settings['timezone']);

?>