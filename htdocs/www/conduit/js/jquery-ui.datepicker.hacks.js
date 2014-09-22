// Возвращаем дату "текущего" дня. Специально для полуночников считаем, что день заканчивается не в 00:00, а в 06:00.
$.fn.Today = function() {
    var today = new Date();
    today.setHours(today.getHours() - 6);
    return today;
};
// Небольшой хак. Кнопка today теперь действительно выбирает текущую дату.
$('html').on({'mouseup': function(e) {
    if (e.which === 1) { // left mouse button
        $.datepicker._curInst.input.datepicker('setDate', $.fn.Today()).datepicker('hide').blur().change();
    };
}}, '.ui-datepicker-current');