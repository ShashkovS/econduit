(function($) {
    function Panel() {

        function ChangeName(evt, rowIndex) {
            var $caller = $(evt.target).closest('.appendGrid');
            var columns = $caller.appendGrid('getColumns');
            
            var ci = $(evt.target).closest('td')[0].cellIndex;
            var name = columns[ci]['name'];
            
            var id = $caller.appendGrid('getCtrlValue', 'id', rowIndex);
            var value = $caller.appendGrid('getCtrlValue', name, rowIndex);
            alert('У школьника номер ' + id + ' значение поля `' + name + '` изменено на "' + value + '"');
        }
        
        function ChangeTeacher(evt, rowIndex) {
            alert('You have changed value of Teacher at row ' + rowIndex);
        }
    
        function ConfirmPupilDeletion(caller, rowIndex) {
            var pupil = $(caller).appendGrid('getCtrlValue', 'name', rowIndex);
            return confirm('Школьник "' + pupil + '" будет удалён. Продолжить?');
        }
    
        this.init = function() {

            // Initialize table example 1
            /*var eTable = $('#edittable').editTable({
                data: [
                    ["Пупкин Вася", "1"],
                    ["Рабинович Изя", ""],
                    ["Смит Джон", "2"]
                ],
                
                headerCols: [
                    'Фамилия Имя',
                    'Учитель'
                ],
                
                field_templates: {
                    /*'checkbox' : {
                        html: '<input type="checkbox"/>',
                        getValue: function (input) {
                            return $(input).is(':checked');
                        },
                        setValue: function (input, value) {
                            if ( value ){
                                return $(input).attr('checked', true);
                            }
                            return $(input).removeAttr('checked');
                        }
                    },* /
                    'select' : {
                        html: '<select><option value="">None</option><option value="1">Женя</option><option value="2">Хайдар</option></select>',
                        getValue: function (input) {
                            return $(input).val();
                        },
                        setValue: function (input, value) {
                            var select = $(input);
                            select.find('option').filter(function() {
                                return $(this).val() == value; 
                            }).attr('selected', true);
                            return select;
                        }
                    }
                },
                
                row_template: ['text', 'select'],
                
                first_row: false,

                
            }); */

/*
            // Load json data trough an ajax call
            $('.loadjson').click(function () {
                var _this = $(this),text = $(this).text();
                $(this).text('Loading...');
                $.ajax({
                    url: 	'output.php',
                    type: 	'POST',
                    data: 	{
                        ajax: true
                    },
                    complete: function (result) {
                        _this.text(text);
                        eTable.loadJsonData(result.responseText);
                    }
                });
                return false;
            });

            // Send JSON data through an ajax call
            $('.sendjson').click(function () {
                $.ajax({
                    url: 	'output.php',
                    type: 	'POST',
                    data: 	{
                        ajax: true,
                        data: eTable.getJsonData()
                    },
                    complete: function (result) {
                        console.log(JSON.parse(result.responseText));
                    }
                });
                return false;
            });*/
            
            $('#tst').appendGrid({
                initRows: 1,
                columns: [
                    { 
                        name: 'id', 
                        //display: 'Фамилия Имя', 
                        //type: 'text', 
                        invisible: true
                    },
                    { 
                        name: 'name', 
                        display: 'Фамилия Имя', 
                        type: 'text', 
                        ctrlAttr: { maxlength: 100 }, 
                        ctrlCss: { width: '160px'},
                        onChange: ChangeName
                    },
                    { 
                        name: 'teacher', 
                        display: 'Учитель', 
                        type: 'select', 
                        ctrlOptions: { 
                            0: 'None', 
                            1: 'Женя', 
                            2: 'Хайдар'
                        },
                        onChange: ChangeTeacher
                    },
                ],
                initData: [
                    { 'id': 1, 'name': 'Пупкин Вася', 'teacher': 1 },
                    { 'id': 2, 'name': 'Смит Джон', 'teacher': 0 },
                    { 'id': 3, 'name': 'Рабинович Изя', 'teacher': 2 }
                ],
                hideRowNumColumn: true,
                hideButtons: {
                    insert: true,
                    moveUp: true,
                    moveDown: true,
                    removeLast: true
                },
                beforeRowRemove: ConfirmPupilDeletion
            });
            
            //$('#tst').on({'change': ChangeName}, 'td[id^="tst_name"]');
            //$('#tst').on({'change': ChangeTeacher}, 'td[id^="tst_teacher"]');
            
            //$conduits.on({'mouseover': MouseOverCol, 'mouseout': MouseUnselect}, '.conduit .problemName');
        }
    }
    window.Panel = new Panel();
})(jQuery);