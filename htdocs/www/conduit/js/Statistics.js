(function() {
    function Stats() {
        
        // private methods:
        function ShowStats(){
            $.ajax({
                type: 'POST',
                url: 'ajax/GetStats.php',
                dataType: 'html',
                data: {
                    StartDate: $('#StartDate').val(), 
                    EndDate: $('#EndDate').val()
                },
                context: $('.stats_container'),
                success: function(data) {
                    this.html(data);
                    
                    var headers = { 0: { lockedOrder:  'asc'} };
                    for (var i = 1, header_len = this.find('.headerRow').eq(0).children().length; i < header_len; ++i) {
                        headers[i] = {lockedOrder: 'desc'};
                    }
                    
                    this.children('.stats').tablesorter({
                        cssAsc: 'sortIndex',
                        cssDesc: 'sortIndex',
                        sortList: [[1, 1]],
                        sortAppend : [[0, 0]],
                        headers: headers,
                        widgets: [ 'stickyHeaders' ],
                    });
                    
                    this.find('thead th').attr('title', 'Отсортировать');
                },
                error:   function(jqXHR, textStatus, errorThrown) {
                    alert('Не удалось получить ответ от сервера: ' + jqXHR.status + ' ' + textStatus);
                }
            });
        }

        function MouseOverCell() {
            // Подсвечиваем заголовок строки и саму ячейку
            $(this).siblings('.pupilName').andSelf().addClass('mouseOver');
            // Подсвечиваем заголовок столбца
            $('.stats_container .headerRow').children(':nth-child('+(this.cellIndex+1)+')').addClass('mouseOver');
        }

        function MouseOverRow() {
            // Подсвечиваем всю строку
            $(this).parent().addClass('mouseOver');
        }

        function MouseOverCol() {
            // Подсвечиваем весь столбец
            $('.stats_container tr').children(':nth-child('+(this.cellIndex+1)+')').addClass('mouseOver');
        }

        function MouseUnselect() {
            // Убираем всё выделение, связанное с курсором
            $('.stats_container *').removeClass('mouseOver');
        }
        
        // public methods:

        this.init = function() {
            
            // Устанавливаем обработчики событий
            var $container = $('.stats_container');
            // Для заголоков столбцов (в том числе пока не созданных в плавающей шапке)
            $container.on({'mouseover': MouseOverCol, 'mouseout': MouseUnselect}, '.stats .list');
            // Для заголовков строк (имена школьников)
            $container.on({'mouseover': MouseOverRow, 'mouseout': MouseUnselect}, '.stats .pupilName');
            // Для ячеек с отметками
            $container.on({'mouseover': MouseOverCell, 'mouseout': MouseUnselect}, '.stats td');
            
            $('#StartDate, #EndDate').datepicker({
                constrainInput: true,
                showButtonPanel: true,
                showOtherMonths: true,
                navigationAsDateFormat: true,
                changeMonth: true
            });
            
            var today = $.fn.Today();
            $('#EndDate').datepicker('setDate', today);
            
            today.setDate(1);
            $('#StartDate').datepicker('setDate', today);
            
            $('#Submit').click(ShowStats);
            
            ShowStats();
        }
    }

    window.Stats = new Stats();
    
})();
