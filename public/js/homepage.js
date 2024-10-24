var maandTekst = ["Januari", "Februari", "Maart", "April", "Mei", "Juni",
  "Juli", "Augustus", "September", "Oktober", "November", "December"
];


$( document ).ready(function() {
    bindMaandplanning();

    $('.calendar .next').click(function() {
        var current = new Date($(this).parent().children('.month').attr('datetime'));
        current.setMonth(current.getMonth()+1, true);
        goToMonth(current);
    });

    $('.calendar .previous').click(function() {
        var current = new Date($(this).parent().children('.month').attr('datetime'));
        current.setMonth(current.getMonth()-1, true);
        goToMonth(current);
    });

    $('.articles .button-bar a.button').click(function(event) {
        event.preventDefault();
        nextPage();
    });
});

Date.prototype.deep_copy = function()
{
    var dat = new Date(this.valueOf());
    return dat;
};
Date.prototype.addDays = function(days)
{
    var dat = new Date(this.valueOf());
    dat.setDate(dat.getDate() + days);
    return dat;
};
Date.prototype.getMonday = function()
{
    var d = new Date(this.valueOf());
    var day = d.getDay(),
    diff = d.getDate() - day + (day == 0 ? -6:1); // adjust when day is sunday
    d.setDate(diff);
    return d;
};

function bindMaandplanning() {
    $('#calendar-weeks .row').click(function() {
        $('.calendar .row.selected').removeClass('selected');
        $(this).addClass('selected');
        // Eerste en laatste bepalen
        // Bij laatste een dag optellen
        var first = $(this).children('.column:first');
        var last = $(this).children('.column:last');

        console.log('Van '+first.attr('datetime')+' tot '+last.attr('datetime'));

        var first_date = stringToDate(first.attr('datetime'));
        var last_date = stringToDate(last.attr('datetime')).addDays(1);

        console.log('Van '+first_date+' tot '+last_date);

        goToWeek(first_date, last_date);
    });
}

function nextWeek() {
    var current_week = $('.calendar .row.selected');
    var selected_days = $('.calendar .row.selected').find('time');
    var next_week = current_week.next('.row');
    var current = stringToDate(selected_days.first().attr('datetime'));

    var first_date = current.addDays(7);
    console.log(first_date);

    goToMonth(first_date, false);
}

function previousWeek() {
    var current_week = $('.calendar .row.selected');
    var selected_days = $('.calendar .row.selected').find('time');
    var next_week = current_week.next('.row');
    var current = stringToDate(selected_days.first().attr('datetime'));

    var first_date = current.addDays(-7);
    console.log(first_date);

    goToMonth(first_date, false);
}

function goToMonth(selected_day, jump_today) {
    var firstday = new Date(selected_day.getTime());
    firstday.setDate(1);
    console.log(firstday);
    console.log(dateToString(firstday));

    // keep running back until we reach a monday
    var day = firstday.getMonday();
    console.log(dateToString(day));

    var firstday_calendar = day.deep_copy();

    // 0 = maandag, 6 = zondag
    // i.p.v. elke keer te berekenen
    var weekday = 0;

    var week = -1;

    var today = new Date().toDateString();
    var selected_day_string = selected_day.toDateString();
    var month = firstday.getMonth();

    var data = [];

    // Blijf herhalen tot we aan een dag komen in een week zonder dagen in deze maand
    while (week < 4 || day.getMonth() == month || weekday != 0) {
        if (weekday == 0) {
            week++;
            data.push({'is_selected': (week == 0), 'days': []});
        }

        var is_today = (today == day.toDateString());
        var is_selected_day = (selected_day_string == day.toDateString()) && week != 0;

        data[data.length-1]['days'].push({
            'day': day.getDate(),
            'is_today': is_today,
            'is_current_month': (day.getMonth() == month),
            'datetime': day.getFullYear()+'-'+(day.getMonth()+1)+'-'+day.getDate()
        });

        if (is_today && jump_today) {
            data[0]['is_selected'] = false;
            data[data.length-1]['is_selected'] = true;

            firstday_calendar = new Date().getMonday();
        }

        if (is_selected_day && !jump_today) {
            data[0]['is_selected'] = false;
            data[data.length-1]['is_selected'] = true;

            firstday_calendar = selected_day.getMonday();
        }


        // Volgende dag
        day.setDate(day.getDate()+1);
        weekday = (weekday + 1)%7;
    }


    var template = Twig.twig({
        data: $('#template-calendar').html()
    });

    var text = template.render({
        calendar: { weeks: data }
    });

    $('#calendar-weeks').html(text);
    var title = $('.calendar header .month');
    title.attr('datetime', firstday.toISOString());
    title.text(maandTekst[month]);

    bindMaandplanning();

    goToWeek(firstday_calendar, firstday_calendar.addDays(7));
}


function goToWeek(start, end) {
    $('#maandplanning-events').children('div').animate({'opacity': 0}, 200);
    $height = $('#maandplanning-events').outerHeight();
    console.log("/api/maandplanning/events-between/"+dateToString(start)+"/"+dateToString(end)+"/");
    // Start download
    $.ajax({
      url: "/api/maandplanning/events-between/"+dateToString(start)+"/"+dateToString(end)+"/",
      dataType: 'html',
    }).done(function(data, textStatus, jqXHR) {
        var divOud = $('#maandplanning-events').children('div');
        height = divOud.outerHeight();
        // Hoogte berekenen van toe te voegen deel door snel toevoegen en verwijderen
        $('#maandplanning-events').append(data);
        divOud.remove();

        var divNieuw = $('#maandplanning-events').children('div');
        divNieuw.css({'opacity': 0});
        heightTo = divNieuw.outerHeight();
        divNieuw.css({'height': height});
        
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

function stringToDate(strDate) {
    var dateParts = strDate.split("-");
    return new Date(dateParts[0], (dateParts[1] - 1), dateParts[2]);
}

function pad(str){
    str = '' + str;
    if(str.length < 2){
        return "0" + str;
    }
    return str;
}

function nextPage() {
    // Huidige pagina bepalen
    var last = $('.article-bundle:last');
    var page = parseInt(last.attr('data-page')) + 1;
    var button = $('.articles .button-bar a.button');

    var message = $('<div class="article-message"><h1>Bezig met laden...</h1></div>');

    last.after(message);
    message.hide();

    var remove_if_done = false;
    var finished = false;
    
    message.slideDown(500, function() {
        finished = true;
        if (remove_if_done) {
            message.slideUp(500, function() {
                $(this).remove();
            });
        }
    });

    button.prop("disabled", true);

    // Start download
    $.ajax({
      url: "/api/blog/get-page/"+page+"/",
      dataType: 'html',
    }).done(function(data, textStatus, jqXHR) {       
        message.after(data);
        var added = $('.article-bundle:last');
        added.hide();
        var has_more = (parseInt(added.attr('data-has-more')) === 1);

        if (!has_more) {
            button.remove();
        }

        added.slideDown(700);

    }).fail(function() {
        last.after('<div class="article-message"><h1>Er ging iets fout</h1></div>');
    }).always(function() {
        if (finished) {
            message.slideUp(500, function() {
                $(this).remove();
            });
        } else {
            remove_if_done = true;
        }

        button.prop("disabled", false);
    });
}





