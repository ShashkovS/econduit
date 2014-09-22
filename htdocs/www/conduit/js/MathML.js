(function() {
    function MathML() {
        // Проверка поддержки MathML браузером (идея позаимствована из пакета jqMath)
        function Supported() {  /* requires document.body */
            // нарисуем невидимую дробь и проверим, что её высота больше чем у простого текста
            var $container = $('<div/>'),
                $math  = $('<div/>').html(self.Frac('1','2')).appendTo($container);
                $plain = $('<div/>').html('12').appendTo($container);
            $container.css('visibility', 'hidden').appendTo('body');
            var res = $math.height() > $plain.height() + 2;
            $container.remove();
            return res;
        };
        
        this.Frac = function(n, d) {
            return '<math xmlns="http://www.w3.org/1998/Math/MathML">' + 
                       '<mfrac>' + 
                            '<mn>' + n + '</mn>' + 
                            '<mn>' + d + '</mn>' + 
                       '</mfrac>' + 
                       '</math>';
        }

        this.CheckSupport = function() {
            if (!Supported()) {
                $('body').attr('data-MathML-RenderEngine', 'CSS');
            }
        }
        
        var self = this;
    }

    window.MathML = new MathML();

})();