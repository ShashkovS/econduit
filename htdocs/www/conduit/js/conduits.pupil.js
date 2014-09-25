// Адаптация jQuery.FloatHeader для целей кондуита
(function(){
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
        if (!element.is(':visible')) {
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
})();

(function() {
    function Conduit() {
        
        // private methods:
        function MouseOverCell() {
            // Подсвечиваем заголовок строки и саму ячейку
            $(this).siblings('.pupilName').andSelf().addClass('mouseOver');
            // Подсвечиваем заголовок столбца
            $(this).closest('.conduit_container').find('.headerRow').children(':nth-child('+(this.cellIndex+1)+')').addClass('mouseOver');
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
                ListID = $(this).attr('data-id');
            if ($(this).attr('data-state') === 'opened') {
                // Спойлер уже открыт
                // Закрываем его
                $(this).attr('data-state', 'closed');
                // Запоминаем, что он закрыт
                SaveSpoilerState(ClassID, ListID, false);
            } else {
                // Спойлер был закрыт
                if ($(this).attr('data-state') === 'empty') {
                    // Этот кондуит до сих пор не запрашивался
                    var $conduit_container = $(this).siblings('.conduit_container'),
                        $loading = $(this).siblings('.loading');
                    // Показываем заставку пока ждём ответа от сервера
                    $loading.show();
                    // Запрашиваем содержимое кондуита
                    $.ajax({
                        type:   'POST',
                        url:    'ajax/FillConduit.php',
                        data:   {Class: ClassID, List: ListID},
                        dataType: 'html',
                        success: function(response){
                                    // Прячем заставку
                                    $loading.hide();
                                    // Вставляем таблицу на место
                                    $conduit_container.html(response);
                                    // Приделываем к ней плавающую шапку
                                    $conduit_container.children('.conduit').floatHeader();
                                 },
                        error:   function(jqXHR, textStatus, errorThrown) {
                                    alert('Не удалось получить ответ от сервера: ' + jqXHR.status + ' ' + textStatus);
                                 }
                    });
                }
                $(this).attr('data-state', 'opened');
                // Запоминаем, что он открыт
                SaveSpoilerState(ClassID, ListID, true);
            }
        }
        
        // Добавляем/удаляем в список открытых спойлеров (в localStorage) текущий
        function SaveSpoilerState(ClassID, ListID, isOpened) {
            var key = 'SPOILER:' + ClassID,
                opened = (localStorage.getItem(key) || '').split(','),
                pos = $.inArray(ListID, opened);
            if (isOpened && (pos == -1)) {
                opened.push(ListID);
            } else if (!isOpened && (pos != -1)) {
                opened.splice(pos,1);
            }
            localStorage.setItem(key, opened.join(','));
        }
        
        
        // public methods:

        this.init = function() {
            
            // Устанавливаем обработчики событий кондуита
            var $conduits = $('#conduits');
            // Для заголоков столбцов (в том числе в плавающих шапках)
            $conduits.on({'mouseover': MouseOverCol, 'mouseout': MouseUnselect}, '.conduit .problemName');
            // Для заголовков строк (имена школьников)
            $conduits.on({'mouseover': MouseOverRow, 'mouseout': MouseUnselect}, '.conduit .pupilName');
            // Для ячеек с отметками
            $conduits.on({'mouseover': MouseOverCell, 'mouseout': MouseUnselect}, '.conduit td');
            // Для спойлеров
            $conduits.on({'click': MouseClickSploiler}, '.conduit_spoiler');
            
            // Раскрываем те спойлеры, с которыми пользователь работал в прошлый раз.
            // Первым элементом в массиве opened выступает пустая строка. Её естественно пропускаем.
            var key = 'SPOILER:' + Globals.ClassID,
                opened = (localStorage.getItem(key) || '').split(',');
            for (var i = 1, l = opened.length; i < l; ++i) {
                $('.conduit_spoiler[data-id='+opened[i]+']').click();
            }
          
        }
        
    }

    window.Conduit = new Conduit();
    
})();