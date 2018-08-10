var geboortedatums = {{ takken | raw }};

function check_birthday() {
    var year = parseInt($(this).val());
    var input_box = $(this).parent().parent();
    input_box.siblings('aside').hide();

    if (isNaN(year)) {
        return;
    } 

    if (typeof geboortedatums[year] === 'undefined') {
        input_box.siblings('aside.ongeldig').show();
    } else {
        input_box.siblings('aside.'+geboortedatums[year]).show();
    }
}

$( document ).ready(function() {
    $('.lid-geboortejaar').change(function() {
        check_birthday.call(this);
    });
    $('.lid-geboortejaar').each(function() {
        check_birthday.call(this);
    });
});