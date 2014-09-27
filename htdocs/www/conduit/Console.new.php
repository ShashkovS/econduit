<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);

require_once('UserManagement.inc.php');
require_once('FillConduits.inc.php');
require_once('GetClass.inc.php');
require_once('FillConsoleData.inc.php');

?>
<!DOCTYPE HTML>
<html>
<head>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <title><?php echo $Class['Description'];?>: кондуиты</title>

    <!-- Подключаем jQuery -->
    <script type="text/javascript" src="http://yastatic.net/jquery/2.1.1/jquery.min.js"></script>
    <script type="text/javascript" src="http://www.shashkovs.ru/forum179/conduit/js/jquery.terminal-0.8.8.min.js"></script>

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
            include("js/conduits.teacher.new.js");
        } else {
            include("js/conduits.pupil.min.js");
        }
        if ($ConduitUser->may_manage('Marks')) {
            echo "\n/* Console */\n";
            include("js/Console.new.js");
        }
?>
    </script>
    
    <!-- Все необходимые стили -->
    <style type="text/css">
<?php
        echo "/* Conduit */\n";
        include('css/Conduit.new.css');
        echo "\n/* Spoiler */\n";
        include('css/Spoiler.min.css');
        echo "\n/* MathML */\n";
        include('css/MathML.min.css');
        echo "\n/* Navbar */\n";
        include('css/Navbar.min.css');
        if ($ConduitUser->may_manage('Marks')) {
            echo "\n/* Toolbar */\n";
            include('css/Toolbar.min.css');
            echo "\n/* Datepicker */\n";
            include('css/jquery-ui.datepicker.min.css');
            echo "\n/* Console */\n";
            include('css/Console.new.css');
        }
?>
    </style>
    <style type="text/css" media="print">
<?php
        include('css/Print.min.css');
?>
    </style>
</head>

<body>


<div class="terminal" id="coduit_terminal"></div>
    <script>
        jQuery(function($, undefined) {
        <?php FillConsoleData($Class['ID']); ?> 
        Console.init(pupil_array, list_array, problem_array);
        });
    </script>

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

    <div style="width:100%; min-height: 114; max-height: 20%; height:162px;">
    </div>
    <?php include('yandex.metrika.min.html'); ?>
</body>
</html>