(function($) {
    function UploadManager() {

        var rulersize = 1;

        this.init = function() {
            
            var width = localStorage.getItem('XML_WIDTH');
            if (width != null) {
                $('#XML').width(width);
            }
            var height = localStorage.getItem('XML_HEIGHT');
            if (height != null) {
                $('#XML').height(height);
            }
            
            if (document.getElementById("XML").onscroll.name != '') {
                $('#XML').resizable({
                    resize: self.resizeRuler,
                    stop:   function() {
                                localStorage.setItem('XML_WIDTH', $("#XML").width());
                                localStorage.setItem('XML_HEIGHT', $("#XML").height());
                            },
                    handles: 'se'
                });
                $('.ui-wrapper').css('padding-bottom', '');
                $('#Ruler').removeAttr('hidden');
                self.resizeRuler();
                self.scrollRuler();
            } else {
                $('#XML').resizable(
                {
                    handles: 'se'
                });
            }
            
            $('#uploadForm').ajaxForm({
                dataType:   'json',
                success:    function(response){
                                if (response.code == 0) { // загрузка прошла успешно
                                    $('#XML').val('');
                                }
                                alert(response.message);
                            },
                error:      function(jqXHR, textStatus, errorThrown) {
                                var msg = "Не удалось получить ответ от сервера: ";
                                alert(msg + jqXHR.status + " " + textStatus);
                            }
            });
        }

        this.resizeRuler = function() {
            // var $Ruler = $('#Ruler');
            // $Ruler.height($('#XML').height());
            // // Проверяем, что вся линейка заполнена
            // var cur = $Ruler.scrollTop();
            // $Ruler.scrollTop(cur + 1);
            // while ($Ruler.scrollTop() <= cur) {
            //     var text = $Ruler.html();
            //     for(var i = 0; i < 50; i++) {
            //         text += "<br/>"+(++rulersize);
            //     }
            //     $Ruler.html(text).scrollTop(cur + 1);
            // }
            // $Ruler.scrollTop(cur);
        }
        
        this.scrollRuler = function() {
            // var $Ruler = $('#Ruler');
            // var desired = $('#XML').scrollTop();
            // $Ruler.scrollTop(desired);
            // while ($Ruler.scrollTop() < desired) {
            //     var text = $Ruler.html();
            //     for(var i = 0; i < 50; i++) {
            //         text += "<br/>"+(++rulersize);
            //     }
            //     $Ruler.html(text).scrollTop(desired);
            // }
        }
        
        // object itself (for access to public methods and properties)
        var self = this;
    }

    window.UploadManager = new UploadManager();

})(jQuery);