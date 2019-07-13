var geboortedatums = {{ takken | raw }};

function check_birthday() {
    var gender = $('input[name=lid-geslacht]:checked').val();
    var year = parseInt($(this).val());
    var input_box = $(this).parent().parent();
    input_box.siblings('aside').hide();

    if (isNaN(year)) {
        return;
    }

    if (gender != 'M' && gender != 'V') {
        return;
    }

    if (typeof geboortedatums[gender][year] === 'undefined') {
        input_box.siblings('aside.ongeldig').show();
    } else {
        input_box.siblings('aside.'+geboortedatums[gender][year]).show();
    }
}

$( document ).ready(function() {
    $('.lid-geboortejaar').change(function() {
        check_birthday.call(this);
    });
    $('input[name=lid-geslacht]').change(function() {
        check_birthday.call($('.lid-geboortejaar')[0]);
    });
    $('.lid-geboortejaar').each(function() {
        check_birthday.call(this);
    });
});