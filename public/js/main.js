var maandTekst = ["Januari", "Februari", "Maart", "April", "Mei", "Juni",
  "Juli", "Augustus", "September", "Oktober", "November", "December"
];

var search_timeout = null;

$( document ).ready(function() {
    bindMaandplanning();

    $('.calendar .next').click(function() {
        var current = new Date($(this).parent().children('.month').attr('datetime'));
        current.setMonth(current.getMonth()+1);
        goToMonth(current);
    });

    $('.calendar .previous').click(function() {
        var current = new Date($(this).parent().children('.month').attr('datetime'));
        current.setMonth(current.getMonth()-1);
        goToMonth(current);
    });

    $('.calendar input.search').bind("propertychange change click keyup input paste", function(event){
        var needle = $(this).val();

        if (needle.length == 0) {
            $('.calendar .search-box .results-anchor').css('display', 'none');
            return;
        } else {
            $('.calendar .search-box .results').html('<article class="result last"><h1>Bezig met zoeken...</h1></article>');
            $('.calendar .search-box .results-anchor').css('display', 'block');
        }

        if (search_timeout) {
            clearTimeout(search_timeout);
        }
        
        search_timeout = setTimeout(function() {
            searchEvents(needle);
        }, 200);
    });

    $('.calendar input.search').blur(function() {
        $('.calendar .search-box .results-anchor').css('display', 'none');
    });

    $('.calendar input.search').focus(function() {
        if ($(this).val().length > 0) {
            $('.calendar .search-box .results-anchor').css('display', 'block');
        }
    });
    
});

Date.prototype.deep_copy = function()
{
    var dat = new Date(this.valueOf());
    return dat;
}
Date.prototype.addDays = function(days)
{
    var dat = new Date(this.valueOf());
    dat.setDate(dat.getDate() + days);
    return dat;
}
Date.prototype.getMonday = function()
{
    var d = new Date(this.valueOf());
    var day = d.getDay(),
    diff = d.getDate() - day + (day == 0 ? -6:1); // adjust when day is sunday
    d.setDate(diff);
    return d;
}

function bindMaandplanning() {
    $('#calendar-weeks .row').click(function() {
        $('.calendar .row.selected').removeClass('selected');
        $(this).addClass('selected');
        // Eerste en laatste bepalen
        // Bij laatste een dag optellen
        var first = $(this).children('.column:first');
        var last = $(this).children('.column:last');

        var first_date = new Date(first.attr('datetime'));
        var last_date = new Date(last.attr('datetime')).addDays(1);

        console.log('Van '+first_date+' tot '+last_date);

        goToWeek(first_date, last_date);
    });
}

function goToMonth(firstday) {
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
    var month = firstday.getMonth();

    var data = [];

    // Blijf herhalen tot we aan een dag komen in een week zonder dagen in deze maand
    while (week < 4 || day.getMonth() == month || weekday != 0) {
        if (weekday == 0) {
            week++;
            data.push({'is_selected': (week == 0), 'days': []});
        }

        var is_today = (today == day.toDateString());

        data[data.length-1]['days'].push({
            'day': day.getDate(),
            'is_today': is_today,
            'is_current_month': (day.getMonth() == month),
            'datetime': day.toISOString()
        });

        if (is_today) {
            data[0]['is_selected'] = false;
            data[data.length-1]['is_selected'] = true;

            firstday_calendar = new Date().getMonday();
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

function searchEvents(needle) {
    $.ajax({
      url: "/api/maandplanning/search?q="+encodeURIComponent(needle),
      dataType: 'html',
    }).done(function(data, textStatus, jqXHR) {
        var results = $('.calendar .search-box .results');
        results.html(data);
        // Kijken of het onderste deel van de resultaten in beeld is, en bijscrollen indien nodig
        var scrollPosition = $(window).scrollTop();
        var viewHeight = $(window).height();

        var resultsHeight = $(results).outerHeight() + 20;
        var resultsOffset = results.offset().top;

        // Als meer dan de helft onzichtbaar is
        if (scrollPosition + viewHeight < resultsHeight + resultsOffset) {
            $('body, html').animate({scrollTop: Math.min($('.calendar .search-box').offset().top, resultsOffset + resultsHeight - viewHeight)}, 'fast');
        }
    }).fail(function() {
        $('.calendar .search-box .results').html('<h1>Er ging iets fout</h1>');
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