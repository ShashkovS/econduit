<?php

define('IN_CONDUIT', true);

define('SQL', true);
define('WebDAV', true);
require('Credentials.inc.php');


$date = date('Y-m-d');
// Файл на сервере, перед отсылкой
$filename = "/home/host1333511/econduit.ru/backups/{$credentials['SQL']['db']}.$date.sql.gz";

$output = `mysqldump --user={$credentials['SQL']['login']} --password={$credentials['SQL']['password']} {$credentials['SQL']['db']} | gzip -f > $filename`;
if (!file_exists($filename)) {
    echo "Error: Dump {$credentials['SQL']['db']} failed: $output\n";
} else {
    echo "Success: DB {$credentials['SQL']['db']} dumped\n";
    echo "Files to upload: $filename\n";
    echo `curl --user {$credentials['WebDAV']['login']}:{$credentials['WebDAV']['password']} -T $file {$credentials['WebDAV']['url']}`;   //если ругается на сертификат, можно добавить ключ -k
    // Удаляем локальный файл
    // unlink($filename);
}
echo "\n";

unset($credentials);

?>