(function() {

    function Conduit() {

        // private properties:
        var AreaMode = false;
        var AreaCorner = {};
        var RequestStack = [];
        var ShowFloatingHeader = true;
        var DateMarkRegExp = /^\d{2}\/\d{2}\/\d{4}$/;
        var todayCaption = '';

        // Адаптация jQuery.FloatHeader для целей кондуита
        $.fn.floatHeader = function() {
            return this.each(function() {
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
                            'top': 0,
                            'left': self.offset().left - $(window).scrollLeft()
                        });
                    } else {
                        self.floatBox.hide();
                    }
                });

                $(window).resize(function() {
                    if (self.floatBox.is(':visible')) {
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
                target.css('width', '');
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
                    var Left = x;
                    var Right = AreaCorner.x;
                } else {
                    var Left = AreaCorner.x;
                    var Right = x;
                }
                if (y < AreaCorner.y) {
                    var Top = y;
                    var Bottom = AreaCorner.y;
                } else {
                    var Top = AreaCorner.y;
                    var Bottom = y;
                }
                // Посвечиваем заголовки строк и сами ячейки
                $(this).closest('tbody').children().slice(Top, Bottom + 1).each(function() {
                    $(this).children('.pupilName').addClass('mouseOver').end().children().slice(Left, Right + 1).addClass('mouseOver');
                });
                // Посвечиваем заголовки столбцов
                $(this).closest('.conduit_container').find('.headerRow').each(function() {
                    $(this).children().slice(Left, Right + 1).addClass('mouseOver');
                });
            } else {
                // Подсвечиваем заголовок строки и саму ячейку
                $(this).siblings('.pupilName').andSelf().addClass('mouseOver');
                // Подсвечиваем заголовок столбца
                $(this).closest('.conduit_container').find('.headerRow').children(':nth-child(' + (this.cellIndex + 1) + ')').addClass('mouseOver');
            }
        }

        function MouseOverRow() {
            // Подсвечиваем всю строку
            $(this).parent().addClass('mouseOver');
        }

        function MouseOverCol() {
            // Подсвечиваем весь столбец
            $(this).closest('.conduit_container').find('tr').children(':nth-child(' + (this.cellIndex + 1) + ')').addClass('mouseOver');
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
                        type: 'POST',
                        url: 'ajax/GetConduit.php',
                        data: { Class: ClassID, List: ListID },
                        dataType: 'html',
                        success: function(response) {
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
                        error: function(jqXHR, textStatus, errorThrown) {
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
            $.cookie(key, opened.join(','), { expires: 30 });
        }

        // ========================================= Teacher's features ========================================= //

        // Стоимость ячейки
        function Price(Mark) {
            if (Mark == '+' || DateMarkRegExp.test(Mark)) {
                return 1;
            } else if (Mark == '+.' || Mark == String.fromCharCode(10789)) {
                return 0.99;
            } else if (Mark == '+-' || Mark == String.fromCharCode(177)) {
                return 0.7;
            } else if (Mark == '+/2' || Mark == String.fromCharCode(10791)) {
                return 0.45;
            } else if (Mark == '-+' || Mark == String.fromCharCode(8723)) {
                return 0.2;
            } else if (Mark == '-.' || Mark == String.fromCharCode(10794)) {
                return 0.01;
            } else {
                return 0;
            }
        }

        // Цвет итога
        function TotalColor(value) {
            if (value >= 1) {
                return "rgb(0,255,0)";
            } else if (value <= 0) {
                return "rgb(255,0,0)";
            } else if (value <= 0.5) {
                value = value * 2;
                return "rgb(" + Math.round(248 + (255 - 248) * value).toString() + ", " +
                    Math.round(105 + (235 - 105) * value).toString() + ", " +
                    Math.round(107 + (132 - 107) * value).toString() + ")";
            } else {
                value = (value - 0.5) * 2;
                return "rgb(" + Math.round(255 - (255 - 99) * value).toString() + ", " +
                    Math.round(235 + (240 - 235) * value).toString() + ", " +
                    Math.round(132 - (132 - 123) * value).toString() + ")";
            }
        }

        // Добавление в массив Request запроса на обновление ещё одной ячейки
        function Add2Request(Request, $conduit, X, Y, Mark) {
            // Проверяем, что ячейка видима. В противном случае ничего не делаем.
            // console.log(Request, $conduit, X, Y, Mark);
            $row = $conduit.find('tbody tr').eq(Y);
            if ($row.is(':visible')) {
                Request.push({
                    Pupil: $row.attr('data-pupil'),
                    Problem: $conduit.find('.headerRow').eq(0).children().eq(X).attr('data-problem'),
                    Mark: Mark
                });
                var $Cell = $row.children().eq(X);
                $Cell.removeClass('loading_error');
                $Cell.addClass('loading_mark');
            }
        }

        // Отправка на сервер запроса на обновление значений набора ячеек.
        // Для варианта update запрос передаётся входным параметром; для варианта rollback подтягивается из стека запросов
        function SendRequest(Type, Request) {
            if (Type === 'update') {
                // Добавляем запрос в стек
                RequestStack.push(Request);
            } else {
                Request = RequestStack[RequestStack.length - 1];
            }
            $.ajax({
                type: 'POST',
                url: 'ajax/UpdateMark.php',
                data: { Request: JSON.stringify(Request), Type: Type },
                dataType: 'json',
                context: $('.conduit_container[data-id="' + Request.List + '"]>.conduit'),
                success: function(Response) {
                    // console.log(Response);
                    for (var i = 0, l = Response.length; i < l; i++) {
                        var $headerRow = this.find('.headerRow').eq(0);
                        var x = $headerRow.children('[data-problem="' + Response[i].Problem + '"]')[0].cellIndex;
                        var $Cell = this.find('tr[data-pupil="' + Response[i].Pupil + '"]').children().eq(x);
                        var $table = $Cell.closest("table");
                        var $li = $Cell.closest("li");
                        var mf3 = parseFloat($li.attr('data-mf3'));
                        var mf4 = parseFloat($li.attr('data-mf4'));
                        var mf5 = parseFloat($li.attr('data-mf5'));

                        var $colgroup = $table.children("colgroup");
                        $Cell.html(Response[i].Text);
                        $Cell.removeClass('loading_mark');
                        if (typeof(Response[i].Hint) !== 'undefined') {
                            $Cell.attr({
                                'data-mark': Response[i].Mark,
                                'title': Response[i].Hint
                            });
                        } else {
                            $Cell.removeAttr('data-mark').removeAttr('title');
                        }
                        //                                if ($('#autoCaption').val() === Response[i].Mark && Response[i].Mark !== '') {
                        // "Свои" отметки выделяем отдельно
                        if (Response[i].Mark !== '') {
                            $Cell.addClass('mymarks');
                        } else {
                            $Cell.removeClass('mymarks');
                            $Cell.removeClass('highlighted');
                        }
                        // Теперь нужно пересчитать сумму. Пока просто пройдёмся по всей строке и просуммируем
                        var $ThisRow = this.find('tr[data-pupil="' + Response[i].Pupil + '"]');
                        var sum = 0;
                        var points = 0;
                        var cur_price = 0;
                        var cur_val = 0;
                        var cur_pen = 0;
                        var max_points = 0;
                        var cur_mark = 0;
                        $ThisRow.children("[data-mark]").each(function() {
                            if ($headerRow.children().eq($(this).index()).html().indexOf('Оценка') < 0) {
                                cur_price = Price($(this).attr('data-mark'));
                                sum += cur_price;
                                // Также считаем стоимость данной задачи и штраф за отсутствие решения
                                cur_val = parseFloat($colgroup.children().eq($(this).index()).attr('data-probvalue'));
                                cur_pen = parseFloat($colgroup.children().eq($(this).index()).attr('data-notsolvedpen'));
                                max_points += cur_val;
                                if (cur_price > 0) {
                                    points += cur_val * cur_price;
                                } else {
                                    points -= cur_pen;
                                }
                            }
                        });
                        // Ячейка с кол-вом задач
                        var $TotalCell = $ThisRow.children(":nth-last-child(3)")
                        $TotalCell.text(+(Math.round(sum + "e+2") + "e-2")); // Здесь хитрый трюк для правильного округления
                        var obligatory = parseInt($TotalCell.attr('data-obligatoryproblems'));
                        $TotalCell.css("background-color", TotalColor(sum / obligatory));
                        // Ячейка с кол-вом баллов
                        var $TotalCell = $ThisRow.children(":nth-last-child(2)")
                        $TotalCell.text(+(Math.round(points + "e+2") + "e-2")); // Здесь хитрый трюк для правильного округления
                        $TotalCell.css("background-color", TotalColor(points / max_points));
                        // Ячейка с оценкой
                        if (mf5 < 0) { mf5 = obligatory; }
                        if (mf3 < 0) { mf3 = mf5 * 3 / 7; }
                        if (mf4 < 0) { mf4 = (mf3 + mf5) / 2; }
                        if (points > mf4) {
                            cur_mark = (points - mf4) / (mf5 - mf4) + 3.5;
                        } else {
                            cur_mark = (points - mf3) / (mf4 - mf3) + 2.5;
                        }
                        cur_mark = Math.round(cur_mark * 10) / 10;
                        var $TotalCell = $ThisRow.children(":nth-last-child(1)")
                        $TotalCell.text(+(Math.round(cur_mark + "e+2") + "e-2")); // Здесь хитрый трюк для правильного округления
                        $TotalCell.css("background-color", TotalColor(cur_mark / 5));
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
                error: MarkLoadingError(Request)
            });
        }
        // Функция для отметки ячеек, по которым свалился ajax-запрос
        function MarkLoadingError(Request) {
            return function(jqXHR, textStatus, errorThrown) {
                var msg = 'Не удалось обновить данные на сервере: ';
                var cur_Request = Request.slice()
                alert(msg + jqXHR.status + ' ' + textStatus);
                for (var i = 0, l = cur_Request.length; i < l; ++i) {
                    var x = this.find('.headerRow').eq(0).children('[data-problem="' + cur_Request[i].Problem + '"]')[0].cellIndex;
                    var $Cell = this.find('tr[data-pupil="' + cur_Request[i].Pupil + '"]').children().eq(x);
                    $Cell.removeClass('loading_mark');
                    $Cell.addClass('loading_error');
                }
            };
        }


        // Откат последнего изменения
        function Undo() {
            if (RequestStack.length > 0) {
                // Отсылаем на сервер запрос об откате последнего запроса
                SendRequest('rollback');
            }
        }

        function SetModeState(i) {
            $('#mode').attr('data-state', i).text(['Обычный ввод', 'Удалить один раз', 'Удалять всегда'][i]);
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

            // Выделяем текущую строчку (для удобства на телефоне)
            $(this).parent().addClass('mouseOver');

            // Метка, которая будет проставляться
            var Mark;
            if (event.altKey || +$('#mode').attr('data-state')) {
                Mark = ''; // При зажатом ALT производится очистка ячейки/диапазона
            } else {
                Mark = $('#autoCaption').val(); // В обычном режиме проставляется текст из поля "Метка"
            }

            if (AreaMode) { // Если уже было начато выделение области, то отсылаем метку по всем ячейкам
                if (x < AreaCorner.x) {
                    var Left = x;
                    var Right = AreaCorner.x;
                } else {
                    var Left = AreaCorner.x;
                    var Right = x;
                }
                if (y < AreaCorner.y) {
                    var Top = y;
                    var Bottom = AreaCorner.y;
                } else {
                    var Top = AreaCorner.y;
                    var Bottom = y;
                }
                for (var i = Left; i <= Right; i++) {
                    for (var j = Top; j <= Bottom; j++) {
                        Add2Request(Request, $conduit, i, j, Mark);

                        $row = $conduit.find('tbody tr').eq(j);
                        var $Cell = $row.children().eq(i);
                        $('td[class~="active"]').removeClass('active');
                        $Cell.addClass('active');
                    }
                }
                QuitAreaMode();
                // Отсылаем запрос на сервер. Он обновит данные в базе и вернёт новое содержимое ячеек.
                Request.List = ListID;
                SendRequest('update', Request);
            } else if (event.shiftKey) { // Если нажат SHIFT, стартуем выделение области
                AreaMode = true;
                AreaCorner = { 'x': x, 'y': y };
            } else { // В противном случае просто отсылаем метку по текущей ячейке
                // Добавляем в запрос единственную ячейку
                Add2Request(Request, $conduit, x, y, Mark);

                $row = $conduit.find('tbody tr').eq(y);
                var $Cell = $row.children().eq(x);
                $('td[class~="active"]').removeClass('active');
                $Cell.addClass('active');

                Request.List = ListID;
                // Отсылаем запрос на сервер. Он обновит данные в базе и вернёт новое содержимое ячеек.
                SendRequest('update', Request);
            }

            // Если был включён режим однократного удаления, сбрасываем его
            if ($('#mode').attr('data-state') == 1) {
                SetModeState(0);
            }
            UpdateActives();
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
            if (Pupil === '') { // `All` selected
                if (Teacher === '') { // `All` selected
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

        function SelectPupil($PupilID, PupilRow) {
            if ($PupilID === $('#pupil').val()) { // Данный школьник и так уже выбран
                $PupilID = '';
            }
            $('#pupil').val($PupilID);
            var OldPosition = PupilRow.offset().top - $(window).scrollTop();
            FilterPupils();
            $('html, body').animate({ scrollTop: PupilRow.offset().top - OldPosition }, 0);
        }

        // Пользователь дважды кликнул по ФИО ученика
        function MouseDoubleClickName() {
            var PupilID = $(this).closest('tr').attr('data-pupil');
            var PupilRow = $(this).closest('tr');
            SelectPupil(PupilID, PupilRow);
        }
        // Пользователь один раз кликнул по ФИО. Ловим долгое нажатие
        function MouseClickName() {
            if (longpress) { // if detect hold, stop onclick function
                return false;
            };
        }

        function MouseDownName() {
            var PupilID = $(this).closest('tr').attr('data-pupil');
            var PupilRow = $(this).closest('tr');
            longpress = false; //longpress is false initially
            pressTimer = window.setTimeout(function(PupilID) {
                SelectPupil(PupilID, PupilRow);
                longpress = true; //if run hold function, longpress is true
            }, 750, PupilID)
        }

        function MouseUpName() {
            clearTimeout(pressTimer); //clear time on mouseup
        }

        function onkey(e) {
            var keychar = String.fromCharCode(e.which);
            //alert(e.which)
            //alert(keychar)
            if (e.ctrlKey && keychar === 'Z') {
                Undo();
            } else if (e.which === 27) { // Escape
                QuitAreaMode();
            }
            if (!$('#autoCaption').is(":focus")) {
                // hotkeys
                if (keychar >= '1' && keychar <= '8') {
                    // быстрая метка
                    $('.combobox select').prop('selectedIndex', keychar - 1).change();
                } else if (e.which >= 97 && e.which <= 104) {
                    // быстрая метка на дополнительной клавиатуре
                    $('.combobox select').prop('selectedIndex', e.which - 97).change();
                }
            }
            return key_work(e);
        }

        function PrintConduit() {
            window.print();
        }

        onkeydown = undo_actions;

        function undo_actions(e) {
            if ($('td').is('.active')) {
                if (e.keyCode >= 37 && e.keyCode <= 40 || e.keyCode == 13 || e.keyCode == 9 || e.keyCode == 32) {
                    return false;
                }
            }
        }

        function key_work(e) {
            var Request = [];
            var $ActiveTd = $('td[class~="active"]');
            var ListID = $ActiveTd.closest('.conduit_container').attr('data-id');
            var $ActiveRow = $ActiveTd.parent();
            var $conduit = $ActiveTd.closest('.conduit');
            var IndexActiveTd = $ActiveRow.children().index($ActiveTd);
            var IndexActiveRow = $ActiveRow.parent().children().index($ActiveRow);
            var move = false;
            if (e.keyCode == 37) { // стрелочка влево
                if (IndexActiveTd > 1) {
                    $ActiveRow.children().eq(IndexActiveTd - 1).addClass('active');
                } else {
                    $ActiveRow.children().eq($ActiveRow.children().length - 4).addClass('active');
                }
                $ActiveTd.removeClass('active');
                UpdateActives();
            } else if (e.keyCode == 39) { // стрелочка вправо
                if (IndexActiveTd < $ActiveRow.children().length - 4) {
                    $ActiveRow.children().eq(IndexActiveTd + 1).addClass('active');
                } else {
                    $ActiveRow.children().eq(1).addClass('active');
                }
                $ActiveTd.removeClass('active');
                UpdateActives();
            } else if (e.keyCode == 40) { // стрелочка вниз
                if (IndexActiveRow < $ActiveRow.parent().children().length - 1) {
                    $ActiveRow.parent().children().eq(IndexActiveRow + 1).children().eq(IndexActiveTd).addClass('active');
                    $ActiveTd.removeClass('active');
                }
                UpdateActives();
            } else if (e.keyCode == 38) { // стрелочка вверх
                if (IndexActiveRow > 0) {
                    $ActiveRow.parent().children().eq(IndexActiveRow - 1).children().eq(IndexActiveTd).addClass('active');
                    $ActiveTd.removeClass('active');
                }
                UpdateActives();
            } else if (e.keyCode == 13) { // enter
                if (IndexActiveRow < $ActiveRow.parent().children().length - 1) {
                    $ActiveTd.removeClass('active');
                    $ActiveRow.parent().children().eq(IndexActiveRow + 1).children().eq(1).addClass('active');
                    UpdateActives();
                }
            } else if (e.keyCode == 9) { // Tab
                if (IndexActiveTd < $ActiveRow.children().length - 3 &&
                    !($ActiveRow.parent().parent().children('thead').children('.headerRow').children().eq(IndexActiveTd).html().includes('Оценка'))) {

                    if (!($ActiveRow.parent().parent().children('thead').children('.headerRow').children().eq(IndexActiveTd + 1).html().includes('Оценка')) &&
                        !($ActiveRow.parent().parent().children('thead').children('.headerRow').children().eq(IndexActiveTd + 1).html().includes('Зд'))) {
                        $ActiveRow.children().eq(IndexActiveTd + 1).addClass('active');
                    } else {
                        $ActiveRow.children().eq(1).addClass('active');
                    }
                    $ActiveTd.removeClass('active');
                }
                UpdateActives();
            } else if (e.keyCode == 8 || e.keyCode == 46) { //backspace or delete

                Add2Request(Request, $conduit, IndexActiveTd, IndexActiveRow, '');
                Request.List = ListID;
                // Отсылаем запрос на сервер. Он обновит данные в базе и вернёт новое содержимое ячеек.
                SendRequest('update', Request);
            } else if (e.keyCode == 27) { // escape
                $('td[class~="active"]').removeClass('active');
                UpdateActives();
            } else if (e.keyCode >= 48 && e.keyCode <= 57) { // от 0 до 9
                var pluses = ['+', '+.', '+-', '+/2', '-+', '-.', '-', '0', todayCaption];
                var marks = ['2; 4', '1', '2', '3', '4', '5', '5; 5', '4; 5', '3; 5', '2; 5'];
                if ($ActiveRow.parent().parent().children('thead').children('.headerRow').children().eq(IndexActiveTd).html().includes('Оценка')) {
                    Add2Request(Request, $conduit, IndexActiveTd, IndexActiveRow, marks[e.keyCode - 48]);
                    Request.List = ListID;
                    // Отсылаем запрос на сервер. Он обновит данные в базе и вернёт новое содержимое ячеек.
                    SendRequest('update', Request);
                    move = true;
                } else if (e.keyCode >= 49 && e.keyCode <= 57) {
                    if (e.keyCode == 57) {
                        $('#autoCaption').val(todayCaption);
                    }
                    Add2Request(Request, $conduit, IndexActiveTd, IndexActiveRow, pluses[e.keyCode - 49]);
                    Request.List = ListID;
                    // Отсылаем запрос на сервер. Он обновит данные в базе и вернёт новое содержимое ячеек.
                    SendRequest('update', Request);
                    move = true;
                }
            } else if (e.keyCode == 32) { // space
                console.log($('.combobox input').value);
                Add2Request(Request, $conduit, IndexActiveTd, IndexActiveRow, $('#autoCaption').val());
                Request.List = ListID;
                // Отсылаем запрос на сервер. Он обновит данные в базе и вернёт новое содержимое ячеек.
                SendRequest('update', Request);
                move = true;
            }
            if (move) {
                if (!($ActiveRow.parent().parent().children('thead').children('.headerRow').children().eq(IndexActiveTd).html().includes('Оценка'))) {
                    if (!($ActiveRow.parent().parent().children('thead').children('.headerRow').children().eq(IndexActiveTd + 1).html().includes('Оценка')) &&
                        !($ActiveRow.parent().parent().children('thead').children('.headerRow').children().eq(IndexActiveTd + 1).hasClass('total'))) {
                        $ActiveRow.children().eq(IndexActiveTd + 1).addClass('active');
                    } else {
                        if (IndexActiveRow < $ActiveRow.parent().children().length - 1) {
                            $ActiveRow.parent().children().eq(IndexActiveRow + 1).children().eq(1).addClass('active');
                        } else {
                            $ActiveRow.children().eq(1).addClass('active');
                        }
                    }
                    $ActiveTd.removeClass('active');
                } else if (IndexActiveRow < $ActiveRow.parent().children().length - 1) {
                    $ActiveRow.parent().children().eq(IndexActiveRow + 1).children().eq(IndexActiveTd).addClass('active');
                    $ActiveTd.removeClass('active');
                }
                UpdateActives();
            }
        }

        function UpdateActives() {
            var $ActiveTd = $('td[class~="active"]');
            var $ActiveRow = $ActiveTd.parent();
            var IndexActiveTd = $ActiveRow.children().index($ActiveTd);
            var IndexActiveRow = $ActiveRow.parent().children().index($ActiveRow);
            $('.conduit th[class~="active-person"]').removeClass('active-person');
            $('.conduit th[class~="active-problem"]').removeClass('active-problem');
            $ActiveRow.children('.pupilName').addClass('active-person');
            $ActiveRow.parent().parent().children('tfoot').children('.headerRow').children().eq(IndexActiveTd).addClass('active-problem');
            $ActiveRow.parent().parent().children('thead').children('.headerRow').children().eq(IndexActiveTd).addClass('active-problem');
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
            todayCaption = $('#autoCaption').val();


            // Кнопка отмены
            $('#undoButton').click(Undo).attr('disabled', 'disabled');

            // Режим ввода
            $('#mode').click(function() {
                SetModeState(($(this).attr('data-state') + 1) % 3);
            });

            $('.combobox select').change(function() {
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
            $conduits.on({ 'mouseover': MouseOverCol, 'mouseout': MouseUnselect }, ".conduit .problemName:not('.total')");
            // Для заголовков строк (имена школьников)
            $conduits.on({ 'mouseover': MouseOverRow, 'mouseout': MouseUnselect, 'dblclick': MouseDoubleClickName }, '.conduit .pupilName');
            $conduits.on({ 'click': MouseClickName, 'mousedown': MouseDownName, 'mouseup': MouseUpName }, '.conduit .pupilName');
            // Для ячеек с отметками
            $conduits.on({ 'mouseover': MouseOverCell, 'mouseout': MouseUnselect, 'click': MouseClickCell }, ".conduit td:not('.total')");
            // Для строк с результатом
            $conduits.on({ 'mouseover': MouseOverRow, 'mouseout': MouseUnselect }, '.conduit .total');
            // Для кондуитов в целом
            $conduits.on({ 'mouseleave': QuitAreaMode }, '.conduit');
            // Для спойлеров
            $conduits.on({ 'click': MouseClickSploiler }, '.conduit_spoiler');
            // Печать
            $conduits.on({ 'click': PrintConduit }, '.printButton');

            // Добавляем подсветку текущих меток
            AddHighlight();

            // Инициализируем плавающие шапки для предзагруженных кондуитов
            $('.conduit_container>.conduit').floatHeader();

            // Применяем фильтрацию для предзагруженных кондуитов
            FilterPupils();
        }
    }


    window.Conduit = new Conduit();

})();