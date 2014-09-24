<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}

require_once('FillTeachersList.inc.php');

?>
        <section class="bar">
            <ul>
                <li class="tool">
                    <label for="autoCaption">Метка:</label>
                    <div class="select-editable">
                        <select onchange="this.nextElementSibling.value=this.value"> <!-- You have a bug in the implementation: If you choose the 115x175 mm from the list, then insert manually some text - 12222 - then try to choose again 115x175 mm - nothing will happen - this is due to the fact that the list wasn't changed although the input was changed. -->
                            <option value="+">+</option>
                            <option value="+.">+.</option>
                            <option value="&#177;">&#177;</option>
                            <option value="+/2">+/2</option>
                            <option value="&#8723;">&#8723;</option>
                            <option value="-.">-.</option>
                            <option value="-">-</option>
                            <option value="0">0</option>
                        </select>
                        <input id="autoCaption" type="text" maxlength=10 />
                    </div>
                    <!--<span id="changeMarkType" data-state="0"></span>-->
                    <!--<datalist id="marks">
                        <option value="+">
                        <option value="+.">
                        <option value="+-">
                        <option value="+/2">
                        <option value="-+">
                        <option value="-.">
                        <option value="-">
                        <option value="0">
                    </datalist>-->
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
            </ul>
        </section>
