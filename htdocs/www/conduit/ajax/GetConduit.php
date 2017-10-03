<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);
define('AJAX', true);
require_once('UserManagement.inc.php');
require_once('FillConduit.inc.php');
require_once('AjaxError.inc.php');

?>
<?php
try {
    $toJSON = isset($_POST['toJSON']);
    if ($toJSON) {
        header('Content-type: application/json');
    }
    echo fillConduit($Class['ID'], $_POST['List'], $toJSON);
} catch (Exception $e) {
    triggerAjaxError(404);
}

?>
