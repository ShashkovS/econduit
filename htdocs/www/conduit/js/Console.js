(function(){
    function Console() {

        // Текущий запрос
        var Request = [];
        var RequestStack = [];

        var today;
        var term;


        // Добавление в массив Request запроса на обновление ещё одной ячейки
        function Add2Request(Request, Pupil_ID, Problem_ID, Mark) {
            Request.push({
                Pupil:    Pupil_ID,
                Problem:  Problem_ID,
                Mark:     Mark  
            });
        }

        // Отправка на сервер запроса на обновление значений набора ячеек.
        // Для варианта update запрос передаётся входным параметром; для варианта rollback подтягивается из стека запросов
        function SendRequest(Type, Request, List_ID) {
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
                context: List_ID,
                success: function(Response){
                            term.echo("[[;blue;black]Успешно внесены " + Response.length + " задач. :)]");
                            if (Type === 'rollback') {
                                // Удаляем запрос из стека запросов
                                RequestStack.pop();
                            }
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
            } else {
                term.echo("[[;red;black]Откатывать больше нечего :(]");
            }
        }


        // Вывести подсказку
        function Help() {
            term.echo("[[;orange;black]Синтаксис: школьник листок задачи {стереть}  (или undo)]");
            term.echo("[[;orange;black]Пример: ива 14 2а,2в,13-15,17а-17г]");
            term.echo("[[;orange;black]Пример: пе 2д 14 стереть]");
        }


        // Валидировать школьника
        function ValidatePupil(pupil_name_part, pupil_array) {
            var nameLen = pupil_name_part.length,
                req_pupil_id = -1;
            for (var index = 0, len = pupil_array.length; index < len; index++) {
                if (pupil_name_part === pupil_array[index].Name.toLowerCase().substr(0, nameLen)) {
                    req_pupil_id = index;
                    break;
                }
            } 
            if (req_pupil_id === -1) {
                term.echo("[[;red;black]Школьник, начинающийся на " + pupil_name_part + " не найден.]");
                return -1;
            } else {
                term.echo("[[;green;black]Школьник: " + pupil_array[req_pupil_id].Name + ']');
                return pupil_array[req_pupil_id].ID;
            }
        }



        // Валидировать номер листка
        function ValidateListNumber(listNumber, list_array) {
            var req_list_id = -1;
            for (var index = 0, len = list_array.length; index < len; index++) {
                if (listNumber === list_array[index].Number) {
                    req_list_id = index;
                    break;
                }
            } 
            if (req_list_id === -1) {
                term.echo("[[;red;black]Листок " + listNumber + " не найден.]");
                return -1;
            } else {
                term.echo("[[;green;black]Листок:   " + listNumber + " - " + list_array[req_list_id].Description + ']');
                return req_list_id;
            }
        }


        // Обработать список задача
        function ValidateListOfProblems(problemsArray, req_list_id, list_array, problem_array) {
            var selected_probs = [],
                selected_prob_ids = [],
                cur_probs = problem_array['l' + list_array[req_list_id].ID],
                cur = '',
                cur0, cur1;
                for (var req_prob_num = 0, len = problemsArray.length; req_prob_num < len; req_prob_num++) {
                    cur = problemsArray[req_prob_num].split('-');
                    if (cur.length == 1) {
                        cur0 = ('0' + cur[0]);
                        if ("0" <= cur0.slice(-1) && cur0.slice(-1) <= "9") {
                            cur0 += '_';
                        }
                        cur0 = cur0.slice(-3);
                        for (var index = 0, len2 = cur_probs.length; index < len2; index++) {
                            if (cur_probs[index].Search.slice(-3) == cur0 || (cur0.slice(-1) == '_' && cur_probs[index].Search.slice(-3).slice(0,-1) == cur0.slice(0,-1))) {
                                selected_probs.push(cur_probs[index].Name);
                                selected_prob_ids.push(cur_probs[index].ID);
                            }
                        }
                    } else if (cur.length == 2) {
                        cur0 = ('0' + cur[0]);
                        if ("0" <= cur0.slice(-1) && cur0.slice(-1) <= "9") {
                            cur0 += '_';
                        }
                        cur0 = cur0.slice(-3);
                        cur1 = ('0' + cur[1]);
                        if ("0" <= cur1.slice(-1) && cur1.slice(-1) <= "9") {
                            cur1 += '_';
                        }
                        cur1 = cur1.slice(-3);
                        for (var index = 0, len2 = cur_probs.length; index < len2; index++) {
                            if (cur0 <= cur_probs[index].Search.slice(-3) && cur_probs[index].Search.slice(-3) <= cur1) {
                                selected_probs.push(cur_probs[index].Name);
                                selected_prob_ids.push(cur_probs[index].ID);
                            }
                        }
                    }
                }
                if (selected_probs.length === 0) {
                    term.echo("[[;red;black]Ни одной задачи, подходящей под '" + problemsArray + "' не найдено.]");
                    return [];
                } else {
                    term.echo("[[;green;black]Задачи:   " + selected_probs.join(', ') + ']');
                    return selected_prob_ids;
                }
        }


        // Валидировать метку
        function ValidateMark(dltText) {
            var Mark = '';
            if (dltText == 'стереть') {
                Mark = '';
            } else {
                Mark = today;
            }
            term.echo("[[;green;black]Метка:    " + Mark + ']');
            return Mark;
        }



        // Спросить, корректен ли запрос
        function AskConfirmationAndSendRequest(Mark, selected_pupil_id, selected_list_id, selected_prob_ids) {
            if (selected_pupil_id != -1 && selected_list_id != -1 && selected_prob_ids.length > 0) {
                term.push(function(command) {
                    if (command.match(/^да?$/i)) {
                        Request = [];
                        for (var index = 0, len = selected_prob_ids.length; index < len; index++) {
                            Add2Request(Request, selected_pupil_id, selected_prob_ids[index], Mark);
                        }
                        SendRequest('update', Request, selected_list_id);
                        term.pop();
                    } else if (command.match(/^н(ет?)?$/i)) {
                        term.pop();
                    }
                }, {
                    prompt: '[[;yellow;black]Всё верно (да/нет)?] '
                });
            }
        }


        // Обработать запрос, похожий на корректный
        function ProcessRequest(words, pupil_array, list_array, problem_array) {
            var selected_prob_ids = [],
                selected_list_id = -1;
            var selected_pupil_id = ValidatePupil(words[0], pupil_array);
            var req_list_id = ValidateListNumber(words[1], list_array);
            if (req_list_id != -1) {
                selected_list_id = list_array[req_list_id].ID
                selected_prob_ids = ValidateListOfProblems(words.slice(2), req_list_id, list_array, problem_array);
            }
            var Mark = ValidateMark(words.slice(-1));
            AskConfirmationAndSendRequest(Mark, selected_pupil_id, selected_list_id, selected_prob_ids);
        }


        // public methods:

        this.init = function(pupil_array, list_array, problem_array) {
            term = $('#coduit_terminal').terminal(function(command, term) {
                if (command === "undo") {
                    Undo();
                } else {
                    command = command.toLowerCase().replace(/\s*-\s*/g, '-').replace(/![а-я0-9- ]/g, '') + ' ';
                    var words = command.match(/[а-я0-9-]+/g);
                    if (!words || words.length < 3) {
                        Help();
                    } else {
                        ProcessRequest(words, pupil_array, list_array, problem_array);
                    }
                }
            }, 
            { 
                prompt: 'conduit> ', 
                name: 'Conduit', 
                greetings: ''
            });
            
            var now = new Date();
            var day = ("0" + now.getDate()).slice(-2);
            var month = ("0" + (now.getMonth() + 1)).slice(-2);
            today = (day)+ "/" + (month) + "/" + now.getFullYear();
        }
    }
    
    window.Console = new Console();
    
})();