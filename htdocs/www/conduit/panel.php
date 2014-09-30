<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);

require_once('UserManagement.inc.php');
// Пользователь должен иметь доступ к админке
$ConduitUser->must_manage('Classes');

?>
<!DOCTYPE HTML>
<html>
<head>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <title><?php echo $Class['Description'];?>: управление школьниками</title>

    <!-- Подключаем jQuery -->
    <script type="text/javascript" src="http://yastatic.net/jquery/2.1.1/jquery.min.js"></script>

    <!-- Остальные скрипты -->
    <script type="text/javascript">
<?php
        //echo "\n/* Resizable */\n";
        //include("js/jquery-ui.panel.min.js");
        include("js/jquery-ui-1.10.4.custom.min.js");
        //include("js/jquery.edittable.js");
        include("js/jquery.appendGrid-1.4.1.js");
        echo "\n/* Panel */\n";
        include("js/panel.js");
?>
    </script>
    
    <!-- Подключаем jQuery Form plugin -->
    <script type="text/javascript" src="http://yastatic.net/jquery/form/3.14/jquery.form.min.js"></script>
    
    <!-- Все необходимые стили -->
    <style type="text/css">
    <?php
        //include('css/panel.css');
        include('css/Navbar.min.css');
        //include('css/jquery-ui.panel.css');
        include('css/ui-lightness/jquery-ui-1.10.4.custom.css');
        //include("css/jquery.edittable.css");
        include("css/jquery.appendGrid-1.4.1.css");
    ?>
    </style>
</head>

<body>
    
    <header>
<?php require('Navbar.inc.php'); ?>
    </header>
    
    <table id="tst"></table>
    
<?php    
    $body = "";

    // Готовим массив школьников
    $sql = "SELECT 
                `PPupil`.`ID` AS `ID`, 
                TRIM(CONCAT(`PPupil`.`Name1`,' ',`PPupil`.`Name2`,' ',`PPupil`.`Name3`)) AS `Name`,
                `PPupil`.`Teacher` AS `Teacher`
            FROM `PPupil`
            WHERE
                `PPupil`.`ClassID` = ?
            ORDER BY 
                2, 1
           ";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($Class['ID']));
    while ($pupil = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $body .= "<tr data-pupil=\"{$pupil['ID']}\"><td></td><td></td></tr>";
    }
    
    $hrow = "<tr><th scope=\"col\">Фамилия Имя</th><th scope = \"col\">Учитель</th></tr>";
    $table = "<table id=\"pupils\"><thead>$hrow</thead><tfoot>$hrow</tfoot><tbody>$body</tbody></table>";
    
    //echo $table;
?>

    <script type="text/javascript">
        Panel.init();
        $('#UploadManager').addClass('current');
    </script>
    
<?php 
    // Яндекс.Метрика
    echo $Settings['page_metrics'];
?>
</body>
</html>