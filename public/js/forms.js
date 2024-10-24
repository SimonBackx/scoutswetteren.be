var did_click_popup = false;

function toggleMenu(e) {
    if (!did_click_popup) {
        console.log("click "+e.currentTarget.className);
        did_click_popup = true;
        if (e.currentTarget.className == 'admin') {
            console.log("open");
            e.currentTarget.className = 'admin open';
        } else {
            console.log("close");
            e.currentTarget.className = 'admin';
        }
    }
}

var admin = document.getElementById('menu').getElementsByClassName('admin')[0];

if (admin) {
    admin.addEventListener('mousedown', toggleMenu);
    admin.addEventListener('touch', toggleMenu);
    
    document.getElementById('menu').getElementsByClassName('admin-menu')[0].addEventListener('mousedown', function(e) {
        did_click_popup = true;
    });
    document.getElementById('menu').getElementsByClassName('admin-menu')[0].addEventListener('touch', function(e) {
        did_click_popup = true;
    });
}

function closeAll() {
    // Close all dropdowns
    if (admin) {
        admin.className = 'admin';
    }

    var dropdown_menus = document.getElementById('menu').getElementsByClassName('dropdown-menu');
    for(var i = 0; i < dropdown_menus.length; i++){
        var element = dropdown_menus[i];
        element.className = 'dropdown-menu';
    }
}

document.addEventListener('mouseup', function(e) {
    if (!did_click_popup) {
        closeAll();
   }
   did_click_popup = false;
});

// Dropdowns
function toggleDropdownMenu(e) {
    e.preventDefault();
    if (did_click_popup) {
        return;
    }
    var menu = e.currentTarget.nextElementSibling;
    did_click_popup = true;

    if (menu.className == 'dropdown-menu') {
        closeAll();
        console.log("open");
        menu.className = 'dropdown-menu open';
    } else {
        console.log("close");
        menu.className = 'dropdown-menu';
    }

}

var dropdown_menus = document.getElementById('menu').getElementsByClassName('dropdown-menu');
for(var i = 0; i < dropdown_menus.length; i++){
    var element = dropdown_menus[i];
    element.addEventListener('mousedown', function(e) {
        did_click_popup = true;
    });
    element.addEventListener('touch', function(e) {
        did_click_popup = true;
    });
}

var dropdowns = document.getElementById('menu').getElementsByClassName('dropdown');
for(var i = 0; i<dropdowns.length; i++){
    var element = dropdowns[i];
    element.addEventListener('mousedown', toggleDropdownMenu);
    element.addEventListener('touch', toggleDropdownMenu);
}

// Smartphone menu:
function openSmartphoneMenu(e) {
    e.preventDefault();
    document.getElementById('smartphone-menu').className = 'open';
}

function closeSmartphoneMenu(e) {
    e.preventDefault();
    document.getElementById('smartphone-menu').className = 'close';
    //$('#smartphone-menu').fadeOut(250);
}

document.getElementById('smartphone-menu-button').addEventListener('click', openSmartphoneMenu);
document.getElementById('smartphone-menu-button').addEventListener('touch', openSmartphoneMenu);

var elements = document.getElementById('smartphone-menu').getElementsByClassName('close');
for(var i = 0; i<elements.length; i++){
    var element = elements[i];
    element.addEventListener('click', closeSmartphoneMenu);
    element.addEventListener('touch', closeSmartphoneMenu);
}

$( document ).ready(function() {


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

        var label = $(this).next();

        if (!label.hasClass('image-file-preview')) {
            if (!label.attr('data-default-text')) {
                label.attr('data-default-text', label.text());
            }
    
            var info = $(this).nextAll(".file_info:first");
            if (files.length == 0) {
                info.text("");
                label.text(label.attr('data-default-text'));
                label.removeClass('selected');
            } 
            if (files.length == 1) {
                info.text("Eén bestand geselecteerd: "+names);
                label.text(names);
                label.addClass('selected');
            } 
            if (files.length > 1) {
                info.text(files.length+" bestanden geselecteerd: "+names);
                label.text(files.length+" bestanden");
                label.addClass('selected');
            } 
        } else {
            if (files.length > 0) {
                var image = label[0];
    
                var reader = new FileReader();
                reader.onload = function (e) {
                    image.style.backgroundImage = 'url(' + e.target.result+')';
                };
                reader.readAsDataURL(files[0]);
            }
        }

        var next = $(this).nextAll(".show-on-file-selected");
        if (files.length == 0) {
            next.css("display", "");
        } else {
            next.css("display", "inline-block");
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
