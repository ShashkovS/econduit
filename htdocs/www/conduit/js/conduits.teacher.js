(function(){

    function Conduit() {

        // private properties:
        var AreaMode = false;
        var AreaCorner = {};
        var RequestStack = [];
        var ShowFloatingHeader = true;

        // Адаптация jQuery.FloatHeader для целей кондуита
        $.fn.floatHeader = function() {
            return this.each(function () {
                var self = $(this);
                self.floatBox = self.siblings('.floatHeader');
                var table = self.floatBox.children('table');

                // bind to the scroll event
                $(window).scroll(function() {
                    if (showHeader(self, self.floatBox)) {
                        if (!self.floatBox.is(':visible')) {
                            recalculateColumnWidth(table, self);
                        }
                        self.floatBox.show().css({
                            'top' : 0,
                            'left': self.offset().left-$(window).scrollLeft()
                        });
                    } else {
                        self.floatBox.hide();
                    }
                });

                $(window).resize(function() {
                    if(self.floatBox.is(':visible')) {
                        recalculateColumnWidth(table, self);
                    }
                });

                this.fhRecalculate = function() {
                    recalculateColumnWidth(table, self);
                };
            });
        }

        // Recalculates the column widths of the floater.
        function recalculateColumnWidth(target, template) {
            var tableWidth = template.width();
            if (navigator.userAgent.indexOf("Firefox") > -1 && tableWidth < window.innerWidth) {
                target.css('width','');
            } else {
                target.width(tableWidth);
            }
            var dst = target.find('thead th:first-child');
            template.find('th').each(function(index, element) {
                dst = dst.width($(element).width()).next();
            });
        }

        // Determines if the element is visible
        function showHeader(element, floater) {
            if (!element.is(':visible') || !ShowFloatingHeader) {
                return false;
            }
            var top = $(window).scrollTop();
            var y0 = element.offset().top;
            var height = element.height() - floater.height();
            var foot = element.children('tfoot');
            if (foot.length > 0) {
                height -= foot.height();
            }
            return y0 <= top && top <= y0 + height;
        }

        // private methods:
        function MouseOverCell() {
            if (AreaMode) {
                var x = this.cellIndex;
                var y = $(this).parent()[0].sectionRowIndex;
                if (x < AreaCorner.x) {
                    var Left   = x;
                    var Right  = AreaCorner.x;
                } else {
                    var Left   = AreaCorner.x;
                    var Right  = x;
                }
                if (y < AreaCorner.y) {
                    var Top    = y;
                    var Bottom = AreaCorner.y;
                } else {
                    var Top    = AreaCorner.y;
                    var Bottom = y;
                }
                // Посвечиваем заголовки строк и сами ячейки
                $(this).closest('tbody').children().slice(Top, Bottom+1).each(function(){
                    $(this).children('.pupilName').addClass('mouseOver').end().children().slice(Left, Right+1).addClass('mouseOver');
                });
                // Посвечиваем заголовки столбцов
                $(this).closest('.conduit_container').find('.headerRow').each(function(){
                    $(this).children().slice(Left, Right+1).addClass('mouseOver');
                });
            } else {
                // Подсвечиваем заголовок строки и саму ячейку
                $(this).siblings('.pupilName').andSelf().addClass('mouseOver');
                // Подсвечиваем заголовок столбца
                $(this).closest('.conduit_container').find('.headerRow').children(':nth-child('+(this.cellIndex+1)+')').addClass('mouseOver');
            }
        }

        function MouseOverRow() {
            // Подсвечиваем всю строку
            $(this).parent().addClass('mouseOver');
        }

        function MouseOverCol() {
            // Подсвечиваем весь столбец
            $(this).closest('.conduit_container').find('tr').children(':nth-child('+(this.cellIndex+1)+')').addClass('mouseOver');
        }

        function MouseUnselect() {
            // Убираем всё выделение, связанное с курсором
            $(this).closest('.conduit_container').find('*').removeClass('mouseOver');
        }

        // Пользователь кликнул по спойлеру
        function MouseClickSploiler() {
            var ClassID = Globals.ClassID,
                $conduit_container = $(this).closest('.conduit_container'),
                ListID = $conduit_container.attr('data-id');
            if ($conduit_container.attr('data-state') === 'opened') {
                // Спойлер уже открыт
                // Закрываем его и скрываем из печати
                $conduit_container.attr('data-state', 'closed').removeClass('print');
                // Запоминаем, что он закрыт
                SaveSpoilerState(ClassID, ListID, false);
            } else {
                // Спойлер был закрыт
                if ($conduit_container.attr('data-state') === 'empty') {
                    // Этот кондуит до сих пор не запрашивался
                    var $loading = $conduit_container.children('.loading');
                    // Показываем заставку пока ждём ответа от сервера
                    $loading.show();
                    // Запрашиваем содержимое кондуита
                    $.ajax({
                        type:   'POST',
                        url:    'ajax/GetConduit.php',
                        data:   {Class: ClassID, List: ListID},
                        dataType: 'html',
                        success: function(response){
                                    // Прячем заставку
                                    $loading.hide();
                                    // Вставляем таблицу на место
                                    $conduit_container.append(response);
                                    // Приделываем к ней плавающую шапку
                                    $conduit_container.children('.conduit').floatHeader();
                                    // Добавляем подсветку сегодняшних меток
                                    AddHighlight($conduit_container);
                                    // Применяем фильтр по учителю
                                    FilterPupils($conduit_container);
                                 },
                        error:   function(jqXHR, textStatus, errorThrown) {
                                    alert('Не удалось получить ответ от сервера: ' + jqXHR.status + ' ' + textStatus);
                                 }
                    });
                }
                $conduit_container.attr('data-state', 'opened').addClass('print');
                // Запоминаем, что он открыт
                SaveSpoilerState(ClassID, ListID, true);
            }
        }

        // Добавляем/удаляем в список открытых спойлеров (в куках) текущий
        function SaveSpoilerState(ClassID, ListID, isOpened) {
            var key = 'ec_open',
                opened = ($.cookie(key) || '').split(','),
                pos = $.inArray(ListID, opened);
            if (isOpened && (pos == -1)) {
                opened.push(ListID);
            } else if (!isOpened && (pos != -1)) {
                opened.splice(pos, 1);
            }
            $.cookie(key, opened.join(','), {expires: 30});
        }

        // ========================================= Teacher's features ========================================= //

        // Добавление в массив Request запроса на обновление ещё одной ячейки
        function Add2Request(Request, $conduit, X, Y, Mark) {
            // Проверяем, что ячейка видима. В противном случае ничего не делаем.
            $row = $conduit.find('tbody tr').eq(Y);
            if ($row.is(':visible')) {
                Request.push({
                    Pupil:    $row.attr('data-pupil'),
                    Problem:  $conduit.find('.headerRow').eq(0).children().eq(X).attr('data-problem'),
                    Mark:     Mark
                });
            }
        }

        // Отправка на сервер запроса на обновление значений набора ячеек.
        // Для варианта update запрос передаётся входным параметром; для варианта rollback подтягивается из стека запросов
        function SendRequest(Type, Request) {
            if (Type === 'update') {
                // Добавляем запрос в стек
                RequestStack.push(Request);
            } else {
                Request = RequestStack[RequestStack.length-1];
            }
            $.ajax({
                type:   'POST',
                url:    'ajax/UpdateMark.php',
                data:   {Request: JSON.stringify(Request), Type: Type},
                dataType: 'json',
                context: $('.conduit_container[data-id="' + Request.List + '"]>.conduit'),
                success: function(Response){
                            for(var i = 0, l = Response.length; i < l; i++) {
                                var x = this.find('.headerRow').eq(0).children('[data-problem="'+Response[i].Problem+'"]')[0].cellIndex;
                                var $Cell = this.find('tr[data-pupil="'+Response[i].Pupil+'"]').children().eq(x);
                                $Cell.html(Response[i].Text);
                                if (typeof(Response[i].Hint) !== 'undefined'){
                                    $Cell.attr({
                                        'data-mark' : Response[i].Mark,
                                        'title'     : Response[i].Hint
                                    });
                                } else {
                                    $Cell.removeAttr('data-mark').removeAttr('title');
                                }
                                if ($('#autoCaption').val() === Response[i].Mark && Response[i].Mark !== '') {
                                    $Cell.addClass('highlighted');
                                } else {
                                    $Cell.removeClass('highlighted');
                                }
                            }
                            if (Type === 'rollback') {
                                // Удаляем запрос из стека запросов
                                RequestStack.pop();
                                if (RequestStack.length === 0) {
                                    $('#undoButton').attr('disabled', 'disabled');
                                }
                            } else {
                                $('#undoButton').removeAttr('disabled');
                            }
                            // Пересчитываем размеры плавающего заголовка
                            this[0].fhRecalculate();
                         },
                error:   function(jqXHR, textStatus, errorThrown) {
                            var msg = 'Не удалось обновить данные на сервере: ';
                            alert(msg + jqXHR.status + ' ' + textStatus);
                         }
           });
        }

        // Откат последнего изменения
        function Undo() {
            if(RequestStack.length > 0) {
                // Отсылаем на сервер запрос об откате последнего запроса
                SendRequest('rollback');
            }
        }

        function SetModeState (i) {
            $('#mode').attr('data-state', i).text(['Обычный ввод', 'Удалить один раз',  'Удалять всегда'][i]);
        }

        function QuitAreaMode() {
            AreaMode = false;
            delete AreaCorner;
            MouseUnselect();
        }

        function MouseClickCell(event) {
            // Координаты нажатия (реально область кондуита начинается с точки (1,0))
            var x = this.cellIndex;
            var y = $(this).parent()[0].sectionRowIndex;

            var ListID = $(this).closest('.conduit_container').attr('data-id');
            var $conduit = $(this).closest('.conduit');

            // Текущий запрос
            var Request = [];

            // Метка, которая будет проставляться
            var Mark;
            if (event.altKey || +$('#mode').attr('data-state')) {
                Mark = '';                      // При зажатом ALT производится очистка ячейки/диапазона
            } else {
                Mark = $('#autoCaption').val(); // В обычном режиме проставляется текст из поля "Метка"
            }

            if (AreaMode) {                     // Если уже было начато выделение области, то отсылаем метку по всем ячейкам
                if (x < AreaCorner.x) {
                    var Left   = x;
                    var Right  = AreaCorner.x;
                } else {
                    var Left   = AreaCorner.x;
                    var Right  = x;
                }
                if (y < AreaCorner.y) {
                    var Top    = y;
                    var Bottom = AreaCorner.y;
                } else {
                    var Top    = AreaCorner.y;
                    var Bottom = y;
                }
                for (var i = Left; i <= Right; i++) {
                    for (var j = Top; j<= Bottom; j++) {
                        Add2Request(Request, $conduit, i, j, Mark);
                    }
                }
                QuitAreaMode();
                // Отсылаем запрос на сервер. Он обновит данные в базе и вернёт новое содержимое ячеек.
                Request.List = ListID;
                SendRequest('update', Request);
            } else if (event.shiftKey) {    // Если нажат SHIFT, стартуем выделение области
                AreaMode = true;
                AreaCorner = {'x':x, 'y':y};
            } else {                        // В противном случае просто отсылаем метку по текущей ячейке
                // Добавляем в запрос единственную ячейку
                Add2Request(Request, $conduit, x, y, Mark);
                Request.List = ListID;
                // Отсылаем запрос на сервер. Он обновит данные в базе и вернёт новое содержимое ячеек.
                SendRequest('update', Request);
            }

            // Если был включён режим однократного удаления, сбрасываем его
            if ($('#mode').attr('data-state') == 1) {
                SetModeState(0);
            }
        }


        function AddHighlight($conduit_container) {
            $conduit_container = $conduit_container || $('#conduits');

            var mark = $('#autoCaption').val();
            if (mark !== '') {
                $conduit_container.find('.conduit td[data-mark="' + mark + '"]').addClass('highlighted');
            }
        }

        function RemoveHighlight() {
            $('.conduit td').removeClass('highlighted');
        }

        function MarkChanged() {
            RemoveHighlight();
            AddHighlight();
        }


        function FilterPupils($conduit_container) {
            $conduit_container = $conduit_container || $('#conduits');

            var Teacher = $('#teacher').val();
            var Pupil = $('#pupil').val();
            if (Pupil === '' && Teacher === '') {
                $('#conduits').find('.conduit tfoot').show();
                ShowFloatingHeader = true;
            } else {
                $('#conduits').find('.conduit tfoot').hide();
                ShowFloatingHeader = false;
            }
            if (Pupil === '') {         // `All` selected
                if (Teacher === '') {   // `All` selected
                    $conduit_container.find('.conduit tbody tr').show();
                } else {
                    $conduit_container.find('.conduit tbody tr:not([data-teacher="' + Teacher + '"])').hide();
                    $conduit_container.find('.conduit tbody tr[data-teacher="' + Teacher + '"]').show();
                }
            } else {
                $conduit_container.find('.conduit tbody tr:not([data-pupil="' + Pupil + '"])').hide();
                $conduit_container.find('.conduit tbody tr[data-pupil="' + Pupil + '"]').show();
            }
        }

        function TeacherChanged() {
            $('#pupil').val(''); // Сбрасываем выбор конкретного школьника
            FilterPupils();
            RequestStack.length = 0; // Сбрасываем список отката
            $('#undoButton').attr('disabled', 'disabled');
        }

        function PupilChanged() {
            FilterPupils();
            RequestStack.length = 0; // Сбрасываем список отката
            $('#undoButton').attr('disabled', 'disabled');
        }

        function SelectPupil($PupilID) {
            if ($PupilID === $('#pupil').val()) { // Данный школьник и так уже выбран
                $PupilID = '';
            }
            $('#pupil').val($PupilID);
            FilterPupils();
        }

        // Пользователь дважды кликнул по ФИО ученика
        function MouseDoubleClickName() {
            var PupilID = $(this).closest('tr').attr('data-pupil');
            SelectPupil(PupilID);
        }
        // Пользователь один раз кликнул по ФИО. Ловим долгое нажатие
        function MouseClickName() {
            if(longpress) { // if detect hold, stop onclick function
                return false;
            };
        }
        function MouseDownName() {
            var PupilID = $(this).closest('tr').attr('data-pupil');
            longpress = false; //longpress is false initially
            pressTimer = window.setTimeout(function(PupilID){
                SelectPupil(PupilID);
                longpress = true; //if run hold function, longpress is true
            },750, PupilID)}
        function MouseUpName() {
            clearTimeout(pressTimer); //clear time on mouseup
        }

        function onkey(e) {
            var keychar = String.fromCharCode(e.which);
            //alert(e.which)
            //alert(keychar)
            if (e.ctrlKey && keychar === 'Z') {
                Undo();
            } else if (e.which === 27){     // Escape
                QuitAreaMode();
            }
            if (!$('#autoCaption').is(":focus")) {
                // hotkeys
                if (keychar >= '1' && keychar <= '8') {
                    // быстрая метка
                    $('.combobox select').prop('selectedIndex', keychar - 1).change();
                }
                if (e.which >= 97 && e.which <= 104) {
                    // быстрая метка на дополнительной клавиатуре
                    $('.combobox select').prop('selectedIndex', e.which - 97).change();
                }
            }
        }

        function PrintConduit() {
            window.print();
        }

        // public methods:

        this.init = function() {

            // Метка
            $('#autoCaption').change(MarkChanged).keyup(MarkChanged);

            // Привязываем datepicker
            var today = $.fn.Today();
            $('#autoCaption').datepicker({
                constrainInput: false,
                showButtonPanel: true,
                showOtherMonths: true,
                navigationAsDateFormat: true,
                changeMonth: true,
                defaultDate: today
            })
            .datepicker('setDate', today);

            // Кнопка отмены
            $('#undoButton').click(Undo).attr('disabled', 'disabled');

            // Режим ввода
            $('#mode').click(function(){
                SetModeState(($(this).attr('data-state')+1)%3);
            });

            $('.combobox select').change(function(){
                // Копируем выбранное значение в метку
                $(this.nextElementSibling).val(this.value).change();
                // Небольшой хак. Чтобы можно было повторно выбрать то же значение.
                this.selectedIndex = -1;
            }).prop('selectedIndex', -1);

            // Обработчик клавиатуры
            $(window).keyup(onkey);

            // Фильтр по учителю
            $('#teacher').change(TeacherChanged);

            // Фильтр по школьнику
            $('#pupil').change(PupilChanged);

            // Устанавливаем обработчики событий кондуита
            var $conduits = $('#conduits');
            // Для заголоков столбцов (в том числе в плавающих шапках)
            $conduits.on({'mouseover': MouseOverCol, 'mouseout': MouseUnselect}, '.conduit .problemName');
            // Для заголовков строк (имена школьников)
            $conduits.on({'mouseover': MouseOverRow, 'mouseout': MouseUnselect, 'dblclick': MouseDoubleClickName}, '.conduit .pupilName');
            $conduits.on({'click': MouseClickName, 'mousedown': MouseDownName, 'mouseup': MouseUpName}, '.conduit .pupilName');
            // Для ячеек с отметками
            $conduits.on({'mouseover': MouseOverCell, 'mouseout': MouseUnselect, 'click': MouseClickCell}, '.conduit td');
            // Для кондуитов в целом
            $conduits.on({'mouseleave': QuitAreaMode}, '.conduit');
            // Для спойлеров
            $conduits.on({'click': MouseClickSploiler}, '.conduit_spoiler');
            // Печать
            $conduits.on({'click': PrintConduit}, '.printButton');

            // Добавляем подсветку текущих меток
            AddHighlight();

            // Инициализируем плавающие шапки для предзагруженных кондуитов
            $('.conduit_container>.conduit').floatHeader();
        }
    }

    window.Conduit = new Conduit();

})();