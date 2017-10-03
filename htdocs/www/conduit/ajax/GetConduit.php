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
    echo fillConduit($Class['ID'], $_POST['List']);
} catch (Exception $e) {
    triggerAjaxError(404);
}

?>