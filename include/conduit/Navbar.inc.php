<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}

global $Settings;

?>
        <nav class="navbar">

            <ul class="linklist rightside">
                <li id="logout"><?php echo $ConduitUser->login_logout_link(); ?></li>
            </ul>
            
            <ul class="linklist leftside">
                <li id="Forum"><a title="Форум" href="<?php echo $Settings['forum_absolute_link'];?>">Форум</a></li>
                <li id="Conduits"><a title="Кондуиты" href="./">Кондуиты</a></li>
<?php if ($ConduitUser->may_manage('Lists') || $ConduitUser->may_manage('Classes')) { ?>
                <li id="UploadManager"><a title="Загрузка данных" href="UploadManager.php">Загрузка данных</a></li>
<?php } ?>
<?php if ($ConduitUser->may_manage('Marks')) { ?>
                <li id="Stats"><a title="Статистика" href="Statistics.php">Статистика</a></li>
<?php } ?>
<?php if ($ConduitUser->may_manage('Marks')) { ?>
                <li id="Console"><a title="Консоль" href="Console.php">Консоль</a></li>
<?php } ?>
<?php if ($ConduitUser->may_manage('Marks')) { ?>
                <li id="Help"><a title="Помощь" href="Help.php">Помощь</a></li>
<?php } ?>
            </ul>

        </nav>