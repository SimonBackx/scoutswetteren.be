var geboortedatums = {{ takken | raw }};
var alle_takken = {{ alle_takken | json_encode() | raw }};

function check_birthday() {
    {% if new or not is_ingeschreven %}
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

        if (alle_takken.akabe) {
            if ($('input[name=lid-akabe]').is(':checked')) {
                if (year >= alle_takken.akabe.min_year && year <= alle_takken.akabe.max_year) {
                    $('#tak-akabe').show();

                    if (alle_takken.akabe.optional_mobile) {
                        $('.optional_mobile').show();
                    } else {
                        $('.optional_mobile').hide();
                    }

                    if (alle_takken.akabe.require_mobile) {
                        $('.require_mobile').show();
                    }
                    
                    return;
                } else {
                    $('#tak-ongeldig').show();
                    $('.optional_mobile').hide();
                    return;
                }
                
            }
        }

        if (typeof geboortedatums[gender][year] === 'undefined') {
            $('#tak-ongeldig').show();
            $('.optional_mobile').hide();
        } else {
            $('#tak-'+geboortedatums[gender][year]).show();

            if (alle_takken[geboortedatums[gender][year]].optional_mobile) {
                $('.optional_mobile').show();
            } else {
                $('.optional_mobile').hide();
            }

            if (alle_takken[geboortedatums[gender][year]].require_mobile) {
                $('.require_mobile').show();
            }
        }
    {% endif %}

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