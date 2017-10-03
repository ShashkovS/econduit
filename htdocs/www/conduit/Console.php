<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);

require_once('UserManagement.inc.php');
require_once('FillConsoleData.inc.php');

// Пользователь должен иметь доступ к оценкам
$ConduitUser->must_manage('Marks');

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <title><?php echo $Class['Description'];?>: консоль</title>
    
    <!-- Подключаем jQuery -->
    <script type="text/javascript" src="//yastatic.net/jquery/2.1.1/jquery.min.js"></script>
    
    <!-- Подключаем консоль -->
    <script type="text/javascript">
    <?php
        echo "\n/* Terminal */\n";
        include("js/jquery.terminal-0.8.8.min.js");
    ?>
    </script>
    <script type="text/javascript">
    <?php
        echo "\n/* Console */\n";
        include("js/Console.min.js");
    ?>
    </script>
    
    <!-- Все необходимые стили -->
    <style type="text/css">
    <?php
        echo "\n/* Navbar */\n";
        include('css/Navbar.min.css');
        echo "\n/* Console */\n";
        include('css/Console.min.css');
    ?>
    </style>
</head>
<body>
    <header>
        <?php require('Navbar.inc.php'); ?>
    </header>
    <section>
        <article>
            <header>
                <h2>Conduit console</h2>
            </header>
            <div class="terminal" id="coduit_terminal">
            </div>
        </article>
    </section>
    <script>
        <?php FillConsoleData($Class['ID']); ?> 
        Console.init(pupil_array, list_array, problem_array);
        $('#Console').addClass('current');
    </script>

<?php 
    // Яндекс.Метрика
    echo $Settings['page_metrics'];
?>
</body>
</html>

