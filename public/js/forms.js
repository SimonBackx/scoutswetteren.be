var did_click_popup = false;

$( document ).ready(function() {
    $(document).mouseup(function() {
        if (!did_click_popup) {
             $('#menu .admin').removeClass('open');
        }
        did_click_popup = false;
    });

    $('#menu .admin').on('mousedown touch', function(event) {
        if (!did_click_popup) {
            did_click_popup = true;
            $(this).toggleClass('open');
        }
    });

    $('#menu .admin .admin-menu').mousedown(function() {
        did_click_popup = true;
    });

    $('#smartphone-menu-button').on('click touch', function(event) {
        event.preventDefault();
        $('#smartphone-menu-items').html($('#menu .items .visible').html());
         $('#smartphone-menu').fadeIn(250);
    });
    $('#smartphone-menu .close').on('click touch', function(event) {
        event.preventDefault();
         $('#smartphone-menu').fadeOut(250);
    });

    // Dropdown aanpassen als getypt wordt
    $('form .dropdown').bind("propertychange change click keyup input paste focus", function(event){
        // Zoeken naar overeenkomstige
        var value = $(this).val().toLowerCase();
        var list =  $(this).next().children('.option');
        list.each(function() {
            var text = $(this).text().toLowerCase();
            if (text.startsWith(value) && text != value) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Input aanpassen als op een item geklikt wordt
    $('form .dropdown-list .option').mousedown(function(event) {
        event.preventDefault();
        var value = $(this).text();
        var input = $(this).parent().prev();
        input.val(value);
        input.focus();
    });

    $('.datepicker').pickadate({
        monthsFull: ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
        weekdaysShort: ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'],
        format: 'dd-mm-yyyy',
        firstDay: 1,
        today: '',
        clear: '',
        close: 'Sluiten',
        selectYears: true,
        selectMonths: true,
    });

    $('textarea').bind("propertychange change click keyup input paste focus", function(event){
        var min = $(this).css('min-height');
        $(this).css({'height': 'auto', 'min-height': 0});
        $(this).css({'height': $(this)[0].scrollHeight + 20, 'min-height': min});
    });

    // Alle selects met 1e optie geselecteerd = gray maken
    $('select').each(function() {
        checkSelect.call(this);
    });

    $('select').bind("propertychange change click keyup input paste focus", function(event){
        checkSelect.call(this);
    });
    
});

function checkSelect() {
    if ($(this)[0].selectedIndex == 0) {
        $(this).addClass('empty');
    } else {
        $(this).removeClass('empty');
    }
}

String.prototype.startsWith = function(needle)
{
    return(this.indexOf(needle) == 0);
};
