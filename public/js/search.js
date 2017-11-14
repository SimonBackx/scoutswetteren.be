var search_timeout = null;

$( document ).ready(function() {
    // Search
    $('input.search').bind("propertychange change click keyup input paste", function(event){
        var needle = $(this).val();

        if (needle.length == 0) {
            $(this).parent().children('.results-anchor').css('display', 'none');
            return;
        } 

        if (needle == $(this).attr('data-last-search')) {
            $(this).parent().children('.results-anchor').css('display', 'block');
        } else {
            $(this).parent().find('.results').html('<article class="result last"><h1>Bezig met zoeken...</h1></article>');
            $(this).attr('data-last-search', '');
        }

        $(this).parent().children('.results-anchor').css('display', 'block');
        
        if (search_timeout) {
            clearTimeout(search_timeout);
        }
        var source = $(this);

        search_timeout = setTimeout(function() {
            startSearch(needle, source);
        }, 200);
    });

    $('input.search').blur(function() {
        if (did_click_popup) {
            $(this).focus();
        } else {
            $(this).parent().children('.results-anchor').css('display', 'none');
        }
    });

    $('input.search').focus(function() {
        if ($(this).val().length > 0) {
            $(this).parent().children('.results-anchor').css('display', 'block');
        }
    });

    $('.search-box').mousedown(function() {
        did_click_popup = true;
    });
    
});

function startSearch(needle, source) {
    var sail = source.attr('data-sail');
    var search_box = source.parent();
    var results = search_box.find('.results');
    $.ajax({
      url: "/api/"+sail+"/search?q="+encodeURIComponent(needle),
      dataType: 'html',
    }).done(function(data, textStatus, jqXHR) {
        
        results.html(data);

        source.attr('data-last-search', needle);
        // Kijken of het onderste deel van de resultaten in beeld is, en bijscrollen indien nodig
        var scrollPosition = $(window).scrollTop();
        var viewHeight = $(window).height();

        var resultsHeight = $(results).outerHeight() + 20;
        var resultsOffset = results.offset().top;

        // Als meer dan de helft onzichtbaar is
        if (scrollPosition + viewHeight < resultsHeight + resultsOffset) {
            $('body, html').animate({scrollTop: Math.min(search_box.offset().top, resultsOffset + resultsHeight - viewHeight)}, 'fast');
        }
    }).fail(function() {
        results.html('<h1>Er ging iets fout</h1>');
    });
}