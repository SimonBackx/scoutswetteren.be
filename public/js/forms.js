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

        var clone = $(this).clone();
        $(this).after(clone);
        clone.val($(this).val());
        clone.css({'height': '0', 'min-height': 0});
        $(this).css({'height': clone[0].scrollHeight + 20, 'min-height': min});
        clone.remove();
    });

    // Alle selects met 1e optie geselecteerd = gray maken
    $('select').each(function() {
        checkSelect.call(this);
    });

    $('select').bind("propertychange change click keyup input paste focus", function(event){
        checkSelect.call(this);
    });

    $('input.file_upload').change(function () {
        var files = document.getElementById('file').files;
        var names = "";
        for (var i = 0; i < files.length; ++i) {
            var name = files[i].fileName ? files[i].fileName : files[i].name;
            if (names != "") {
                names += ", ";
            }

            names += name;
        }

        var info = $(this).nextAll(".file_info:first");
        if (files.length == 0) {
            info.text("");
        } 
        if (files.length == 1) {
            info.text("Eén bestand geselecteerd: "+names);
        } 
        if (files.length > 1) {
            info.text(files.length+" bestanden geselecteerd: "+names);
        } 

        var next = $(this).nextAll(".show-on-file-selected");
        if (files.length == 0) {
            next.css("display", "");
        } else {
            next.css("display", "block");
        }

        return false;
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
