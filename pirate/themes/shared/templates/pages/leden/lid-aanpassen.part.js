var geboortedatums = {{ takken | raw }};

function check_birthday() {
    var gender = $('input[name=lid-geslacht]:checked').val();
    var year = parseInt($(this).val());
    var input_box = $(this).parent().parent();
    $('.tak-text').hide();

    if (isNaN(year)) {
        return;
    }

    if (gender != 'M' && gender != 'V') {
        return;
    }

    {% if alle_takken.akabe is defined %}
        if ($('input[name=lid-akabe]').is(':checked')) {
            $('#tak-akabe').show();
            return;
        }
    {% endif %}

    if (typeof geboortedatums[gender][year] === 'undefined') {
        $('#tak-ongeldig').show();
    } else {
        $('#tak-'+geboortedatums[gender][year]).show();
    }
}

$( document ).ready(function() {
    $('.lid-geboortejaar').change(function() {
        check_birthday.call(this);
    });
    $('input[name=lid-akabe]').change(function() {
        check_birthday.call($('.lid-geboortejaar')[0]);
    });
    $('input[name=lid-geslacht]').change(function() {
        check_birthday.call($('.lid-geboortejaar')[0]);
    });
    $('.lid-geboortejaar').each(function() {
        check_birthday.call(this);
    });
});