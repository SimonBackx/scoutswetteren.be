$( document ).ready(function() {
    bindMaandplanning();
});

Date.prototype.addDays = function(days)
{
    var dat = new Date(this.valueOf());
    dat.setDate(dat.getDate() + days);
    return dat;
}

function bindMaandplanning() {
    $('.calendar .column').click(function() {
        $('.calendar .row.selected').removeClass('selected');
        $(this).parent().addClass('selected');
        // Eerste en laatste bepalen
        // Bij laatste een dag optellen
        var first = $(this).parent().children('.column:first');
        var last = $(this).parent().children('.column:last');

        var first_date = new Date(first.attr('datetime'));
        var last_date = new Date(last.attr('datetime')).addDays(1);

        console.log('Van '+first_date+' tot '+last_date);

        $('#maandplanning-events').children('div').css({'opacity': 0});
        $height = $('#maandplanning-events').outerHeight();

        // Kijken of de maand is veranderd en die eventueel updaten
        // 
        
        // Download scherm tonen
        
        // Downloaden starten
        goToWeek(first_date, last_date);
    });
}

function goToWeek(start, end) {
    console.log('start');
    // Start download
    var req = $.ajax({
      url: "/api/maandplanning/events-between/"+dateToString(start)+"/"+dateToString(end)+"/",
      dataType: 'html',
    }).done(function(data, textStatus, jqXHR) {
        console.log('Geslaagd');

        var divOud = $('#maandplanning-events').children('div');
        $height = divOud.height();
        // Hoogte berekenen van toe te voegen deel door snel toevoegen en verwijderen
        $('#maandplanning-events').append(data);
        divOud.remove();

        var divNieuw = $('#maandplanning-events').children('div');
        divNieuw.css({'opacity': 0});
        heightTo = $('#maandplanning-events').height();
        divNieuw.css('height', $height+'px');
        
        divNieuw.animate({ height: heightTo}, 300 , function() {
            divNieuw.css('height', '');
            divNieuw.animate({ opacity: 1}, 200);
        });

    }).fail(function() {
        console.error('Faal');
        $('#maandplanning-events').html('<h1>Er ging iets fout</h1>');
    });

}

// Date naar leesbare string voor onze REST api
// Gebruik hier dateToString niet omdat deze functie haar output niet mag veranderen moesten we dateToString wijzigen
function dateToString(date) {
    if (!date) {
        return '';
    }
    var day = pad(date.getDate());
    var month = pad(date.getMonth()+1);
    var year = pad(date.getFullYear());
    return year+"-"+month+"-"+day;
}

function pad(str){
    str = '' + str;
    if(str.length < 2){
        return "0" + str;
    }
    return str;
}