<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);

require_once('UserManagement.inc.php');
// Пользователь должен иметь доступ к админке
$ConduitUser->must_manage('Lists');

?>
<!DOCTYPE HTML>
<html>
<head>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    
    <title><?php echo $Class['Description'];?>: загрузка данных</title>

    <!-- Подключаем jQuery -->
    <script type="text/javascript" src="//yastatic.net/jquery/2.1.1/jquery.min.js"></script>

    <!-- Остальные скрипты -->
    <script type="text/javascript">
<?php
        echo "\n/* Resizable */\n";
        include("js/jquery-ui.resizable.min.js");
        echo "\n/* Upload Manager */\n";
        include("js/UploadManager.min.js");
?>
    </script>
    
    <!-- Подключаем jQuery Form plugin -->
    <script type="text/javascript" src="//yastatic.net/jquery/form/3.14/jquery.form.min.js"></script>
    
    <!-- Все необходимые стили -->
    <style type="text/css">
    <?php
        include('css/UploadManager.min.css');
        include('css/Navbar.min.css');
        include('css/jquery-ui.resizable.css');
        include('css/XML.min.css');
    ?>
    </style>
</head>

<body>
    
    <header>
<?php require('Navbar.inc.php'); ?>
    </header>
    
    <form id="uploadForm" action="ajax/ParseXML.php" method="post"></form>
    
    <div>
        Введите XML:<br />
        <div>
            <code id="Ruler" hidden="hidden" data-size=1>1</code>
            <textarea id="XML" name="XML" form="uploadForm" required="required" 
                      rows="20" wrap="off" maxlength="100000" spellcheck="false"
                      onscroll="UploadManager.scrollRuler()"></textarea>
        </div>
    </div>
    
    <input type="submit" value="Отправить" form="uploadForm" />
    
    <div>
        <p>Пример XML для листка:</p>
<pre>
<code><span style="font: 10pt Courier New;"><span class="xml1-processinginstruction">&lt;?xml version='1.0'?&gt;
</span><span class="xml1-symbol">&lt;listok</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">number</span><span class="xml1-whitespace"> </span><span class="xml1-symbol">= '</span><span class="xml1-attributevalue">nn</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">description</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">List Name.</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">1</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">date</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">MM.YYYY</span><span class="xml1-symbol">'&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">1</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">0</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">1</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">2</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">0</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">2</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">3</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">0</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">3а</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">3</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">0</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">3б</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">3</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">0</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">3в</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">3</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">0</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">3г</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">3</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">1</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">3д</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">3</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">1</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">3е</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text">  </span><span class="xml1-symbol">&lt;problem</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">group</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">3</span><span class="xml1-symbol">'</span><span class="xml1-whitespace"> </span><span class="xml1-attributename">type</span><span class="xml1-symbol">='</span><span class="xml1-attributevalue">2</span><span class="xml1-symbol">'&gt;</span><span class="xml1-text">3ж</span><span class="xml1-symbol">&lt;/problem&gt;
</span><span class="xml1-text"></span><span class="xml1-symbol">&lt;/listok&gt;
</span></span>
</code></pre>

        <p>Для задач type 0 - обычная, 1 - со звёздочкой, 2 - с двумя звёздочками; Для листка type 1 - обычный, 2 - дополнительный.</p>
    </div>    

    <script type="text/javascript">
        UploadManager.init();
        $('#UploadManager').addClass('current');
    </script>

<?php 
    // Яндекс.Метрика
    echo $Settings['page_metrics'];
?>
</body>
</html>