<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);

require_once('UserManagement.inc.php');
require_once('ShowStats.inc.php');

// Пользователь должен иметь доступ к оценкам
$ConduitUser->must_manage('Marks');

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <title><?php echo $Class['Description'];?>: статистика</title>
    
    <!-- Подключаем jQuery -->
    <script type="text/javascript" src="//yastatic.net/jquery/2.1.1/jquery.min.js"></script>

    <!-- Остальные скрипты -->
    <script type="text/javascript">
    <?php
        echo "/* Datepicker */\n";
        include("js/jquery-ui.datepicker.min.js");
        include("js/jquery-ui.datepicker.ru.min.js");
        include("js/jquery-ui.datepicker.hacks.min.js");
        echo "/* TableSorter */\n";
        include("js/jquery.tablesorter.min.js");
        include("js/jquery.tablesorter.widgets.min.js");
        echo "\n/* Statistics */\n";
        include("js/Statistics.min.js");
    ?>
    </script>
    
    <!-- Все необходимые стили -->
    <style type="text/css">
    <?php
        echo "/* Statistics */\n";
        include('css/Statistics.min.css');
        echo "\n/* Navbar */\n";
        include('css/Navbar.min.css');
        echo "\n/* Toolbar */\n";
        include('css/Toolbar.min.css');
        echo "\n/* Datepicker */\n";
        include('css/jquery-ui.datepicker.min.css');
    ?>
    </style>
</head>

<body>
    <header>
<?php require('Navbar.inc.php'); ?>
        <section class="bar">
            <ul>
                <li class="tool">
                    <label for="StartDate">С:</label>
                    <input id="StartDate" name="StartDate" type="text" maxlength=10 />
                </li>
                <li class="tool">
                    <label for="EndDate">По:</label>
                    <input id="EndDate" name="EndDate" type="text" maxlength=10 />
                </li>
                <li class="tool">
                    <input id="Submit" type=button value="Пересчитать" />
                </li>
            </ul>
        </section>
    </header>

    <section class="stats_container"></section>

    <script type="text/javascript">
        Stats.init();
        $('#Stats').addClass('current');
    </script>
    
<?php 
    // Яндекс.Метрика
    echo $Settings['page_metrics'];
?>
</body>
</html>

