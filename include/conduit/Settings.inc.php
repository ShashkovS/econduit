<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}

$Settings = array();
$Settings['site_absolute_link']     = 'http://econduit.ru/';
$Settings['site_absolute_path']     = '/home/host1333511/econduit.ru/htdocs/www/';

$Settings['forum_relative']         = 'forum/';
$Settings['forum_absolute_path']    = $Settings['site_absolute_path'] . $Settings['forum_relative'];
$Settings['forum_absolute_link']    = $Settings['site_absolute_link'] . $Settings['forum_relative'];

$Settings['conduit_relative']       = 'conduit/';
$Settings['conduit_absolute_path']  = $Settings['site_absolute_path'] . $Settings['conduit_relative'];
$Settings['conduit_absolute_link']  = $Settings['site_absolute_link'] . $Settings['conduit_relative'];

$Settings['teacher_reminder_email'] = 'reminder@econduit.ru';

$Settings['timezone']               = 'Europe/Moscow';
$Settings['page_metrics']           = '';

$Settings['tmp_backup_storage']     = '/home/host1333511/econduit.ru/backups/';

?>