<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);
define('AJAX', true);

require_once('UserManagement.inc.php');
require_once('AjaxError.inc.php');
require_once('ShowStats.inc.php');

?>
<?php

try {
    echo ShowStats($_POST['StartDate'], $_POST['EndDate']);
} catch (Exception $e) {
    triggerAjaxError(404);
}

?>