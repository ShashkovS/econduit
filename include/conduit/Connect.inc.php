<?php

if (!defined('IN_CONDUIT') || !defined('IN_PHPBB')){
    // Попытка прямого доступа к файлу
    exit();
}


$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '/home/host1333511/econduit.ru/htdocs/www/forum/';
$phpEx = 'php';
include_once($phpbb_root_path . 'common.' . $phpEx);

$phpbb_forum_link = 'http://econduit.ru/forum/';
$conduit_webroot = '/conduit';


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
date_default_timezone_set('Europe/Moscow');

?>