<?php

define('IN_CONDUIT', true);

function dump_db($filename, $credentials, &$work_log) {
    $work_log .= "Starting database dump: {$credentials['db']}\n";
    
    //$mysqldump_error = shell_exec("mysqldump --user={$credentials['login']} --password={$credentials['password']} {$credentials['db']} 2>tmp.err | gzip --best --force > \"$filename\" && cat tmp.err && rm tmp.err");
    $mysqldump_error = shell_exec("/bin/bash <<< \"mysqldump --user={$credentials['login']} --password=wrong {$credentials['db']} 2>&1 > >(gzip --best --force > \\\"$filename\\\")\"");
    
    // Проверяем успешность дампа
    if ($mysqldump_error === "") {
        $work_log .= "DB successfully dumped.\n";
        return true;
    } else {
        $work_log .= "Error:\n$mysqldump_error\n";
        return false;
    }
}

function upload_file($filename, $credentials, &$work_log) {
    $work_log .= "Starting file transfer: $filename ==> {$credentials['url']}\n";
    $curl_output = shell_exec("curl --user {$credentials['login']}:{$credentials['password']} --upload-file \"$filename\" --url \"{$credentials['url']}\" --silent --verbose 2>&1");
    // Анализируем ответ
    preg_match_all('@HTTP/[0-9|.]+\s(\d{3})\s.*?\n@i', $curl_output, $matches);
    foreach ($matches as $match) {
        if ($match[1] === '201') {
            $work_log .= "File successfully uploaded\n";
            return true;
        }
    }
    $work_log .= "Error:\n$curl_output\n";
    return false;
}

function backup() {
    define('SQL', true);
    define('WebDAV', true);
    require('Credentials.inc.php');
    require('Settings.inc.php');

    $date = date('Y-m-d');
    // Файл на сервере, перед отсылкой
    $filename = "{$Settings['tmp_backup_storage']}$date.{$credentials['SQL']['db']}.sql.gz";

    $work_log = "$date. Starting backup process.\n";
    if (
        dump_db($filename, $credentials['SQL'], $work_log) and 
        upload_file($filename, $credentials['WebDAV'], $work_log)
    ) {         // Всё выполнено успешно
        // Удаляем локальный файл
        unlink($filename);
    } else {    // Произошла какая-то ошибка. Выводим весь лог в stdout.
        echo $work_log;
    }
}

// Запускаем всё
backup();

?>