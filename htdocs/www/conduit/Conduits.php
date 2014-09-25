<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);

require_once('UserManagement.inc.php');
require_once('FillConduits.inc.php');
require_once('GetClass.inc.php');

?>
<!DOCTYPE HTML>
<html>
<head>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <title><?php echo $Class['Description'];?>: кондуиты</title>

    <!-- Подключаем jQuery -->
    <script type="text/javascript" src="http://yastatic.net/jquery/2.1.1/jquery.min.js"></script>

    <!-- Остальные скрипты -->
    <script type="text/javascript">
<?php
        echo "/* Datepicker */\n";
        include("js/jquery-ui.datepicker.min.js");
        include("js/jquery-ui.datepicker.ru.min.js");
        include("js/jquery-ui.datepicker.hacks.min.js");
        echo "\n/* MathML */\n";
        include("js/MathML.min.js");
        echo "\n/* Conduit */\n";
        if ($ConduitUser->may_manage('Marks')) {
            include("js/conduits.teacher.js");
        } else {
            include("js/conduits.pupil.min.js");
        }
?>
    </script>
    
    <!-- Все необходимые стили -->
    <style type="text/css">
<?php
        echo "/* Conduit */\n";
        include('css/Conduit.css');
        echo "\n/* Spoiler */\n";
        include('css/Spoiler.min.css');
        echo "\n/* MathML */\n";
        include('css/MathML.min.css');
        echo "\n/* Navbar */\n";
        include('css/Navbar.min.css');
        echo "\n/* Toolbar */\n";
        include('css/Toolbar.css');
        echo "\n/* Datepicker */\n";
        include('css/jquery-ui.datepicker.min.css');
?>
    </style>
    <style type="text/css" media="print">
<?php
        include('css/Print.min.css');
?>
    </style>
</head>

<body>
    <header>
<?php require('Navbar.inc.php'); ?>
<?php 
    if ($ConduitUser->may_manage('Marks')) {
        require('ConduitToolbar.inc.php'); 
    }        
?>
    </header>

    <ul id="conduits">
<?php fillConduits($Class['ID']); ?>
    </ul>
    
    <script type="text/javascript">
        window.Globals = {
            ClassID: '<?php echo $Class['ID']; ?>'
        };
        MathML.CheckSupport();
        Conduit.init();
        $('#Conduits').addClass('current');
    </script>
    <?php include('yandex.metrika.min.html'); ?>
</body>
</html>