var map;
var initMap = function() {
    map = new google.maps.Map(document.getElementById('map'), {
      center: {lat: 51.00649122457492, lng: 3.8951897621154785},
      mapTypeControl: false,
      scrollwheel: false,
      streetViewControl: false,
      rotateControl: false,
      fullscreenControl: false,
      zoom: 15
    });

    new google.maps.Marker({
        position: {lat: 51.0026835, lng: 3.9018309},
        map: map,
        icon: {
            url: '/images/markers/scouts.svg',
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(10, 48)
        },
        title: 'Scouts Prins Boudewijn'
    });

    new google.maps.Marker({
        position: {lat: 51.00695, lng: 3.885319},
        map: map,
        icon: {
            url: '/images/markers/wetteren.svg',
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(10, 45)
        },
        title: 'Wetteren Centrum'
    });

    new google.maps.Marker({
        position: {lat: 51.009799062637796, lng: 3.902764320373535},
        map: map,
        icon: {
            url: '/images/markers/den-blakken.svg',
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(10, 45)
        },
        title: 'Den Blakken'
    });

    new google.maps.Marker({
        position: {lat: 51.00712580771406, lng: 3.903815746307373},
        map: map,
        icon: {
            url: '/images/markers/zandbergen.svg',
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(10, 45)
        },
        title: 'De Zandbergen'
    });

    new google.maps.Marker({
        position: {lat: 51.00470219345775, lng: 3.895597457885742},
        map: map,
        icon: {
            url: '/images/markers/warande.svg',
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(10, 45)
        },
        title: 'De warande'
    });

    new google.maps.Marker({
        position: {lat: 51.00190036458359, lng: 3.8829588890075684},
        map: map,
        icon: {
            url: '/images/markers/station.svg',
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(10, 45)
        },
        title: 'Station Wetteren'
    });
}

var maandTekst = ["Januari", "Februari", "Maart", "April", "Mei", "Juni",
  "Juli", "Augustus", "September", "Oktober", "November", "December"
];

var startAfwijkingX = 0;
var slider_click = false;

function getEventX(event) {
    var x = 0;
    if (event.hasOwnProperty('changedTouches')) {
        var touches = event.changedTouches;
        for (var i = 0; i < touches.length; i++) {
            x = touches[i].pageX;
        }
    }
    else {
        x = event.pageX;
    }
    return x;
}

function getPercentageForSliderValue(min, max, value) {
    return (value - min) / (max - min);
}

function getSliderValueForPercentage(min, max, percentage) {
    return Math.max(Math.min(max, Math.round(percentage * (max - min)) + min), min);
}

function sliderUpdate(event) {
    var width = slider_click.width();
    var pos = getEventX(event);

    var min = parseInt(slider_click.attr('data-min'));
    var max = parseInt(slider_click.attr('data-max'));

    var percentage = (pos - startAfwijkingX - slider_click.offset().left) / width;
    var value = getSliderValueForPercentage(min, max, percentage);
    var percentage = getPercentageForSliderValue(min, max, value)*100;

    var slide = slider_click.children('.slide');
    slide.css({'left': percentage+'%'});
    slide.children('.value').text(value);
    var input = slider_click.children('input');
    input.val(value);
    input.change();
    slider_click.find('.progress').css({'width': percentage+'%'});
}

function setMonthAndYear(month, year) {
    $(this).attr('data-year', year);
    $(this).attr('data-month', month);
    $(this).text(maandTekst[month-1]+' '+year);
    $('#verhuur-calendar-weeks').html('<p>Bezig met laden...</p>');
    $.ajax({
      url: "/api/verhuur/kalender/"+year+"/"+month+"/",
      dataType: 'html',
    }).done(function(data, textStatus, jqXHR) {
        $('#verhuur-calendar-weeks').html(data);
        bindDays();
    }).fail(function() {
        $('#verhuur-calendar-weeks').html('<p>Er ging iets mis</p>');
    });
}

var clicked_input = false;

$(document).ready(function() {
    $('.calendar .next').click( function(event) {
        clicked_input = true;
        event.preventDefault();
        // Maand optellen
        var year = $(this).prev().attr('data-year');
        var month = $(this).prev().attr('data-month');
        month++;
        if (month > 12) {
            month = 1;
            year++;
        }

        setMonthAndYear.call($(this).prev()[0], month, year);
    });
    $('.calendar .previous').click( function(event) {
        clicked_input = true;
        event.preventDefault();
        // Maand optellen
        var year = $(this).next().attr('data-year');
        var month = $(this).next().attr('data-month');
        month--;
        if (month < 1) {
            month = 12;
            year--;
        }

        setMonthAndYear.call($(this).next()[0], month, year);
    });

    $('.slider').on('mousedown touchstart', function(event) {
        event.preventDefault();
        var startSliderX = getEventX(event);
        var startSliderValue = parseInt($(this).children('input').val());

        var width = $(this).width();

        var min = parseInt($(this).attr('data-min'));
        var max = parseInt($(this).attr('data-max'));
        var startPercentage = getPercentageForSliderValue(min, max, startSliderValue);
        var startX = startPercentage * width + $(this).offset().left;

        startAfwijkingX = startSliderX - startX;
        slider_click = $(this);

        if (startAfwijkingX > 10 || startAfwijkingX < -10) {
            startAfwijkingX = 0;
            sliderUpdate(event);
        }
    });
    $(document).on('mousemove touchmove', function(event) {
        if (!slider_click) {
            return;
        }

        event.preventDefault();
        sliderUpdate(event);
    });

    $(document).on('mouseup touchend', function() {
        slider_click = false;
    });

    bindDays();

    
    $('.calendar_input').on('focus', function(evt) {
        $(this).blur();
        if (calendar_input) {
            calendar_input.removeClass('selected');
        }
        $(this).addClass('selected');
        calendar_input = $(this);
        openCalendarInput($(this).attr('data-text'));
    });

    $('.calendar_input').click(function(evt) {
        clicked_input = true;
    });

    $(document).click(function() {
        if (clicked_input) {
            clicked_input = false;
            return;
        }
        if (calendar_input)
            calendar_input.removeClass('selected');
        calendar_input = null;
        closeCalendarInput();
    });

    $('input').change(function() {
        calculatePrice();
        updateCalendarSelection();
    });
});

function bindDays() {
    $('#verhuur-calendar-weeks time').off();
    $('#verhuur-calendar-weeks time').click(function(event) {
        clicked_input = true;
        if (!calendar_input) {
            calendar_input = $('.calendar_input:first');
            resetVertrek();
        }
        var calendar_id = calendar_input.attr('data-id');
        $('#verhuur-calendar-weeks time').removeClass(calendar_id);
        $(this).addClass(calendar_id);

        calendar_input.val($(this).attr('datetime'));
        calendar_input.change();

        calendar_input.removeClass('selected');
        
        var next = $('#'+calendar_input.attr('data-next-id'));
        if (next.length > 0 && next.val() == '') {
            clicked_input = true;
            next.focus();
        } else {
            closeCalendarInput();
            calendar_input = null;
        }

    });
    
    updateCalendarSelection();
}



var calendar_input = null;

function parseDateString(strDate) {
    var dateParts = strDate.split("-");
    return new Date(dateParts[2], (dateParts[1] - 1), dateParts[0]);
}

function updateCalendarSelection() {
    var startdate_str = $('#aankomst-datum').val();
    var enddate_str = $('#vertrek-datum').val();

    if (startdate_str && enddate_str && startdate_str != '' && enddate_str != '') {
        startdate = parseDateString(startdate_str);
        enddate = parseDateString(enddate_str);

        var start = $('.calendar time.column[datetime='+startdate_str+']');
        var end = $('.calendar time.column[datetime='+enddate_str+']');

        if (start.length == 0 && end.length == 1) {
           start = $('.calendar time.column').first();
        } else {
            if (start.length == 1 && end.length == 0) {
               end = $('.calendar time.column').last();
            } else {
                if (start.length == 0 && end.length == 0) {
                    var year = $('.calendar .month').attr('data-year');
                    var month = $('.calendar .month').attr('data-month');

                    month = new Date(year, month - 1, 1);
                    if (month > startdate && month < enddate) {
                        start = $('.calendar time.column').first();
                        end = $('.calendar time.column').last();
                    }
                }
            }
        }

        start.addClass('aankomst-selected');
        end.addClass('vertrek-selected');
    }

    if ($('.calendar .vertrek-selected, .calendar .aankomst-selected').length != 2) {
        $('.calendar time.column').removeClass('in-between');
        return;
    }

    var isBetween = false;
    $('.calendar time.column').each(function() {
        $(this).removeClass('in-between');

        if ($(this).hasClass('aankomst-selected')) {
            isBetween = true;
        }
        if ($(this).hasClass('vertrek-selected')) {
            isBetween = false;
        }

        if (isBetween) {
            $(this).addClass('in-between');
        }
    });
}

function resetVertrek() {
    $('#vertrek-datum').val('');
    var vertrek = $('.calendar .vertrek-selected');
    vertrek.removeClass('vertrek-selected');
}

function calculatePrice() {
    // Loopen van begin tot einde:
    var startdate_str = $('#aankomst-datum').val();
    var enddate_str = $('#vertrek-datum').val();

    if (!startdate_str || !enddate_str || startdate_str == '' || enddate_str == '') {
        $('#price').text('€ 0');
        $('#borg').text('');
        $('#aantal-personen-tenten-box').hide();
        return;
    }

    startdate = parseDateString(startdate_str);
    enddate = parseDateString(enddate_str);

    if (enddate < startdate) {
        resetVertrek();
        $('#price').text('€ 0');
        $('#borg').text('');
        $('#aantal-personen-tenten-box').hide();
        
        return;
    }

    var timeDiff = Math.abs(startdate.getTime() - enddate.getTime());
    var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)); 

    if (diffDays <= 2) {
        $('#aantal-personen-tenten-box').hide();
    } else {
        $('#aantal-personen-tenten-box').show();
    }


    var persons_tenten = $('#aantal-personen-tenten').val();

    var prices_year = 2016;
    var prices = [95, 98, 100];

    var base_price = diffDays * prices[enddate.getFullYear()-prices_year];
    if (persons_tenten > 0) {
        base_price += (persons_tenten)*2*diffDays + 15*diffDays;
    }

    var borg = 400;

    if (diffDays > 2) {
        borg = 750;
    }

    $('#price').text('€ '+base_price);
    $('#borg').text('+ € '+borg+' borg');
}
function closeCalendarInput() {
    $('.calendar').removeClass('enabled');
    var hint = $('.calendar .selection-hint');
    hint.stop().slideUp('fast');
}
function openCalendarInput(text) {
    $('.calendar').addClass('enabled');
    var hint = $('.calendar .selection-hint');
    hint.text(text);
    hint.slideDown('fast');
} 