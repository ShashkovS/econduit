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
                    <div class="combobox">
                        <select>
                            <option value="+">(1) +</option>
                            <option value="+.">(2) +.</option>
                            <option value="&#177;">(3) &#177;</option>
                            <option value="+/2">(4) +/2</option>
                            <option value="&#8723;">(5) &#8723;</option>
                            <option value="&#8722;.">(6) &#8722;.</option>
                            <option value="&#8722;">(7) &#8722;</option>
                            <option value="0">(8) 0</option>
                        </select>
                        <input id="autoCaption" type="text" maxlength=10 />
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
