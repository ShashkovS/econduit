<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}

require_once('FillTeachersList.inc.php');
require_once('FillPupilsList.inc.php');

?>
        <section class="bar">
            <ul>
                <li class="tool">
                    <label for="autoCaption">Метка:</label>
                    <input id="autoCaption" type="text" maxlength=10 />
                </li>
                <li class="tool">
                    <span id="togglePanel" data-state="0"></span>
                    <div id="markPanel">
                        <button>+</button>
                        <button>+.</button>
                        <button>&#177;</button>
                        <button value="+/2"><math style="font-size:70%;"><mfrac><mn>+</mn><mn>2</mn></mfrac></math></button>
                        <button>&#8723;</button>
                        <button>&#8722;.</button>
                        <button>&#8722;</button>
                        <button>0</button>
                    </div>
                </li>
                <li class="tool">
                    <span id="mode" data-state=0 title="Режим ввода"> Обычный ввод</span>
                </li>
                <li class="tool">
                    <button id="undoButton" type="button" title="Отмена последнего действия">Отменить</button>
                </li>
                <li class="tool">
                    <label for="teacher">Учитель:</label>
<?php fillTeachersList($Class['ID']); ?>
                </li>
                <li class="tool">
                    <label for="pupil">Школьник:</label>
<?php fillPupilsList($Class['ID']); ?>
                </li>
            </ul>
        </section>
