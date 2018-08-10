function is_retina_device() {
    return window.devicePixelRatio > 1;
}

function isElementInViewport(el, grid) {
    var rect = el.getBoundingClientRect();

    if (grid.scrollBox) {
        // Als zichtbaar in de scrollbox...
        var rect2 = grid.scrollBox.getBoundingClientRect();
        var ok = (
            rect.bottom >= rect2.top &&
            rect.right >= rect2.left &&
            rect.top <= rect2.bottom && /*or $(window).height() */
            rect.left <= rect2.right /*or $(window).width() */
        );
        if (!ok) {
            return false;
        }
    }

    return (
        rect.bottom >= 0 &&
        rect.right >= 0 &&
        rect.top <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
        rect.left <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */
    );
}

var photos = photos || {};

// PHOTO
photos.Photo = function(width, height, id) {
    this.sources = [];
    this.width = 0;
    this.height = 0;
    this.grid = null;
    this.index = 0;
    this.id = id;
    this.title = "";

    this.source_width = width;
    this.source_height = height;

    this.element = null;
    this.isPlaceholder = true;
    this.isInViewport = false;
    this.marginRight = null;
};

photos.Photo.prototype = {
    widthForHeight: function(height) {
        return Math.floor(height / this.source_height * this.source_width);
    },

    setHeight: function(height) {
        var new_width = Math.floor(this.widthForHeight(height));
        var new_height = Math.floor(height);

        if (new_width != this.width || new_height != this.height) {
            this.width = new_width;
            this.height = new_height;
            this.updateElement();
        }
    },

    setMargin: function(margin) {
        this.toDOM().style.marginRight = margin+'px';
        this.marginRight = margin;
    },

    updateElement: function() {
        if (!this.element) {
            return;
        }
        this.element.style.width = this.width+'px';
        this.element.style.height = this.height+'px';
    },

    setSources: function(width, height, sources) {
        this.source_width = width;
        this.source_height = height;
        this.sources = sources;

        if (this.element) {
            this.setHeight(this.height);
            this.toDOM();
            grid.needsRecalculate = true;
            grid.refresh();
        }
    },

    getLargestSmallerThan: function(width, height) {
        if (this.sources.length == 0) {
            return null;
        }

        var largest = this.sources[0];

        for (var i = 1; i < this.sources.length; i++) {
            var source = this.sources[i];
            if (source.w >= largest.w && source.h >= largest.h && source.w <= width && source.h <= height) {
                largest = source;
            }
            // of als huidige largest kleiner is dan 50% van de gezochte width, maar deze wel groter is
            else if ((largest.w < 0.5*width || largest.h < 0.5*height) && source.w > largest.w && source.h > largest.h ){
                largest = source;
            }
        }

        return largest;
    },

    getSource: function() {
        var src;
        var factor = 1;
        if (is_retina_device()) {
            factor = 2;
        }

        this.sources.sort(function(a, b){return a.w - b.w });

        for (var i = 0; i < this.sources.length; i++) {
            if (!src || (this.sources[i].h > src.h && src.h < this.height*factor) || (this.sources[i].h < src.h && src.h >= this.height*factor)) {
                src = this.sources[i];
            }
        }
        return src;
    },

    getSourceSet: function() {
        var src;
        var srcset = '';

        this.sources.sort(function(a, b){return a.w - b.w });

        for (var i = 0; i < this.sources.length; i++) {
            if (i > 0) {
                srcset += ', ';
            }
            srcset += this.sources[i].url + ' ' + this.sources[i].w + 'w';
        }
        return srcset;
    },

    delete: function() {
        if (!confirm("Ben je zeker dat je deze foto wilt verwijderen?")) {
            return;
        }

        var me = this;
        $.ajax({
          url: "/api/photos/delete/"+this.id,
          type: "POST",
        }).done(function(data, textStatus, jqXHR) {
            me.grid.remove(me);
        }).fail(function(jqXHR, textStatus) {
            alert('Het verwijderen van deze foto is mislukt.');
        });
    },

    setCover: function() {
        var me = this;
        $.ajax({
          url: "/api/photos/set-cover/"+this.id,
          type: "POST",
        }).done(function(data, textStatus, jqXHR) {
            alert('Deze foto is ingesteld als de nieuwe cover foto.');
        }).fail(function(jqXHR, textStatus) {
            alert('Het instellen van de cover foto is mislukt.');
        });
    },

    setTitle: function() {
        var new_title = prompt("Vul een beschrijving in voor de foto", this.title);
        if (new_title == null) {
            return;
        }

        var me = this;
        $.ajax({
          type: "POST",
          url: "/api/photos/set-title/"+this.id,
          data: {t: new_title},
        }).done(function(data, textStatus, jqXHR) {
            alert('De beschrijving van de foto is aangepast.');
            this.title = new_title;

        }).fail(function(jqXHR, textStatus) {
            alert('Het instellen van de beschrijving is mislukt.');
        });
    },

    toDOM: function() {
        if (this.element && !(this.isPlaceholder && this.sources.length > 0 && this.isInViewport)) {
            return this.element;
        }

        var element;
        var has_img = false;
        if (this.element) {
            element = this.element;
            has_img = !this.isPlaceholder;
        } else {
            element = document.createElement('figure');

            if (is_admin) {
                var box = document.createElement('aside');
                var del = document.createElement('div');
                del.className = 'del';
                del.setAttribute('title', 'Deze foto verwijderen');
                
                var me = this;
                del.onclick = function(event) {
                    if (event) {
                        event.stopPropagation();
                    } else {
                        window.event.cancelBubble = true;
                    }
                    me.delete();

                    return false;
                };
                
                var cov = document.createElement('div');
                cov.className = 'cov';
                cov.setAttribute('title', 'Instellen als cover foto');
                cov.onclick = function(event) {
                    if (event) {
                        event.stopPropagation();
                    } else {
                        window.event.cancelBubble = true;
                    }
                    me.setCover();

                    return false;
                };

                var caption = document.createElement('div');
                caption.className = 'caption';
                caption.setAttribute('title', 'Beschrijving wijzigen');
                caption.onclick = function(event) {
                    if (event) {
                        event.stopPropagation();
                    } else {
                        window.event.cancelBubble = true;
                    }
                    me.setTitle();

                    return false;
                };

                box.appendChild(caption);
                box.appendChild(cov);
                box.appendChild(del);

                element.appendChild(box);
            }

            has_img = false;
        }

        if (this.sources.length == 0 || !this.isInViewport) {
            this.isPlaceholder = true;
            if (has_img) {
                element.removeChild(element.lastChild);
            }
        } else {
            this.isPlaceholder = false;

            var subelement;
            if (!has_img) {
                subelement = document.createElement('img');
            } else {
                subelement = element.lastChild;
            }

            subelement.setAttribute('alt', this.title);
            subelement.setAttribute('title', this.title);
            /*subelement.setAttribute('width', this.width);
            subelement.setAttribute('height', this.height);
            subelement.setAttribute('srcset', this.getSourceSet());*/
            subelement.setAttribute('src', this.getSource().url);

            if (!has_img) {
                element.appendChild(subelement);
            }
        }

        element.style.width = this.width+'px';
        element.style.height = this.height+'px';

        if (this.marginRight) {
            element.style.marginRight = this.marginRight+'px';
        }

        /*if (this.element) {
            // replace
            this.element.parentNode.insertBefore(element, this.element);
            this.element.parentNode.removeChild(this.element);
        }*/

        var me = this;
        element.onclick = function(e) {
            return me.onClick(e);
        };

        this.element = element;
        return element;
    },

    onClick: function(e) {
        // Geen PhotoSwipe tonen als we geen gallery hebben gekoppeld
        if (!this.grid.photoSwipeEnabled) {
            return;
        }

        console.log(this.index);

        this.grid.openPhotoSwipe(this.index);
        return false;
    }
};


// ROW
photos.Row = function(grid) {
    this.photos = [];
    this.isFull = false;
    this.height = 0;
    this.grid = grid;

    this.width_on_max_height = 0;
    this.margin_width = 0;

    this.element = null;
};

photos.Row.prototype = {
    getWidth() {
        return Math.ceil(this.width_on_max_height + this.margin_width);
    },

    recalculate: function() {
        this.margin_width = 0;
        this.width_on_max_height = 0;

        for (var i = 0; i < this.photos.length; i++) {
            var photo = this.photos[i];

            if (i > 0) {
                this.margin_width += this.grid.margin;
            }
            this.width_on_max_height += photo.widthForHeight(this.grid.max_height);
        }
    },

    add: function(photo) {
        // Only add an image if we haven't reached the required width
        if (this.isFull) {
            return false;
        }

        if (this.photos.length > 0) {
            this.margin_width += this.grid.margin;
        }

        this.width_on_max_height += photo.widthForHeight(this.grid.max_height);
        this.photos.push(photo);
        this.calculateImageSizes();
        this.toDOM().appendChild(photo.toDOM());
        return true;
    },

    prepend: function(photos) {
        // Only add an image if we haven't reached the required width
        for (var i = photos.length - 1; i >= 0; i--) {
            var photo = photos[i];

            if (this.photos.length > 0) {
                this.margin_width += this.grid.margin;
            }

            this.width_on_max_height += photo.widthForHeight(this.grid.max_height);
            this.photos.unshift(photo);

            if (this.photos.length > 0) {
                this.toDOM().insertBefore(photo.toDOM(), this.toDOM().firstChild);
            } else {
                this.toDOM().appendChild(photo.toDOM());
            }
        }
        return this.removeOverflow();
    },

    remove: function(index) {
        if (this.photos.length == 0) {
            return false;
        }

        var photo = this.photos[index];
        this.photos.splice(index, 1);

        this.width_on_max_height -= photo.widthForHeight(this.grid.max_height);
        if (this.photos.length > 0) {
            this.margin_width -=  this.grid.margin;
        }
        this.toDOM().removeChild(photo.toDOM());
        this.calculateImageSizes();

        return photo;
    },

    removeFirst: function() {
        if (this.photos.length == 0) {
            return false;
        }

        var photo = this.photos.shift();

        this.width_on_max_height -= photo.widthForHeight(this.grid.max_height);
        if (this.photos.length > 0) {
            this.margin_width -=  this.grid.margin;
        }
        this.toDOM().removeChild(photo.toDOM());

        return photo;
    },

    removeOverflow: function() {
        var dropped = [];

        // Keep dropping until we find a width smaller than the needed one
        for (var i = this.photos.length - 1; i >= 0; i--) {
            var photo = this.photos[i];

            var width = photo.widthForHeight(this.grid.max_height);
            var margin = 0;
            if (i > 0) {
                margin = this.grid.margin;
            }

            if (this.getWidth() - width - margin < this.grid.width) {
                break;
            }
            dropped.push(this.photos.pop());
            this.toDOM().removeChild(photo.toDOM());
            this.width_on_max_height -= width;
            this.margin_width -= margin;
        }

        this.calculateImageSizes();
        return dropped;
    },

    calculateImageSizes: function() {
        var scale = 1;

        // We'll scale the image down if they are to big to fit in the row
        if (this.getWidth() >= this.grid.width) {
            this.isFull = true;
            scale = (this.grid.width - this.margin_width) / this.width_on_max_height;
        } else {
            this.isFull = false;
        }

        var height = this.grid.max_height * scale;

        var actualWidth = 0;

        for (var i = 0; i < this.photos.length; i++) {
            this.photos[i].setHeight(height);
            actualWidth += this.photos[i].width;
            if (i > 0) {
                actualWidth += this.grid.margin;
            }
            if (i < this.photos.length - 1) {
                this.photos[i].setMargin(this.grid.margin);
            } else {
                this.photos[i].setMargin(0);
            }
        }

 
        if (this.isFull && this.photos.length > 1) {
            var diff = this.grid.width - actualWidth;
            var plusmargin = truncate(diff/(this.photos.length-1));
            var s = sign(diff/(this.photos.length-1));
            var remainder = Math.abs(diff%(this.photos.length-1));

            for (var i = 0; i < this.photos.length - 1; i++) {
                if (i < remainder) {
                    this.photos[i].setMargin(this.grid.margin + plusmargin + s);
                } else {
                    this.photos[i].setMargin(this.grid.margin + plusmargin);
                }
            }
        } 
    },

    toDOM: function() {
        if (this.element) {
            return this.element;
        }

        var element = document.createElement('div');
        element.className = 'photo-row';

        for (var i = 0; i < this.photos.length; i++) {
            element.appendChild(this.photos[i].toDOM());
        }

        this.element = element;
        return element;
    }
};

function truncate(float) {
    if (float < 0) {
        return Math.ceil(float);
    }
    return Math.floor(float);
}

function sign(float) {
    if (float < 0) {
        return -1;
    }
    return 1;
}

// GRID
photos.Grid = function(options) {
    this.rows = [];

    this.photo_count = 0;
    this.width = 500;
    this.max_height = 300;
    this.min_height = 100;
    this.margin = 8;
    this.photoSwipeEnabled = false;

    this.trackWidthElement = null;

    this.element = null;

    this.needsRecalculate = false;

    this.scrollBox = null;
    this.needsVisibleUpdateOnAdd = false;
}

photos.Grid.prototype = {
    add: function(photo) {
        if (this.width < 500 && this.max_height > 100) {
            this.max_height = 100;
        }
        
        photo.grid = this;

        if (!photo.index) {
            photo.index = this.photo_count;
            this.photo_count += 1;
        }

        if (this.rows.length == 0 || !this.rows[this.rows.length - 1].add(photo)) {
            var row = new photos.Row(this);
            row.add(photo);
            this.rows.push(row);
            this.toDOM().appendChild(row.toDOM());
        }

        if (this.needsVisibleUpdateOnAdd) {
            photo.isInViewport = isElementInViewport(photo.toDOM(), this);
            photo.toDOM();
        }
    },

    remove: function(photo) {
        for (var i = 0; i < this.rows.length; i++) {
            var row = this.rows[i];

            for (var j = 0; j < row.photos.length; j++) {
                if (row.photos[j] == photo) {
                    row.remove(j);
                    this.refresh();
                    return;
                }
            }
        }
    },

    toDOM: function() {
        if (this.element) {
            return this.element;
        }

        var element = document.createElement('div');
        element.className = 'photo-grid';

        for (var i = 0; i < this.rows.length; i++) {
            element.appendChild(this.rows[i].toDOM());
        }

        this.element = element;
        return element;
    },

    resize: function() {
        // Alle rijen overlopen en herschikken
        if (this.width == this.trackWidthElement.offsetWidth) {
            return;
        }

        this.width = this.trackWidthElement.offsetWidth;

        if (this.width < 500 && this.max_height > 100) {
            this.max_height = 100;
            this.needsRecalculate = true;
        }

        this.refresh();
    },

    enablePhotoSwipe: function() {
        this.photoSwipeEnabled = true;

        var photoswipeParseHash = function() {
            var hash = window.location.hash.substring(1),
            params = {};

            if(hash.length < 5) {
                return params;
            }

            var vars = hash.split('&');
            for (var i = 0; i < vars.length; i++) {
                if(!vars[i]) {
                    continue;
                }
                var pair = vars[i].split('=');  
                if(pair.length < 2) {
                    continue;
                }           
                params[pair[0]] = pair[1];
            }

            if(params.gid) {
                params.gid = parseInt(params.gid, 10);
            }

            return params;
        };

        // Parse URL and open gallery if it contains #&pid=3&gid=1
        var hashData = photoswipeParseHash();
        if(hashData.pid) {
            this.openPhotoSwipe(hashData.pid ,  true, true);
        }
    },

    refresh: function() {
        var dropped = [];

        for (var i = 0; i < this.rows.length; i++) {
            if (this.needsRecalculate) {
                this.rows[i].recalculate();
            }

            dropped = this.rows[i].prepend(dropped);

            if (dropped.length == 0) {
                while (!this.rows[i].isFull && i < this.rows.length - 1) {
                    var next = this.rows[i+1];
                    var removed = next.removeFirst();
                    if (removed) {
                        this.rows[i].add(removed);
                    } else {
                        // de volgende rij is leeg, dus verwijderen we die
                        this.rows.splice(i + 1, 1);
                        this.toDOM().removeChild(next.toDOM());
                    }
                }
            }
        }
        for (var i = 0; i < dropped.length; i++) {
            this.add(dropped[i]);
        }

        this.needsRecalculate = false;
        this.updateVisiblePhotos();
    },

    updateVisiblePhotos: function() {
        this.needsVisibleUpdateOnAdd = true;
        
        for (var i = 0; i < this.rows.length; i++) {
            var row = this.rows[i];

            for (var j = 0; j < row.photos.length; j++) {
                var photo = row.photos[j];

                photo.isInViewport = isElementInViewport(photo.toDOM(), this);
                photo.toDOM();
            }
        }
    },

    trackWidth: function(trackWidthElement) {
        this.trackWidthElement = trackWidthElement;
        this.width = this.trackWidthElement.offsetWidth;

        var grid = this;
        window.onresize = function(event) {
            grid.resize();
        };

        var grid = this;
        window.addEventListener('scroll', function(e) {
            grid.updateVisiblePhotos();
        });

        if (this.scrollBox) {
            this.scrollBox.addEventListener('scroll', function(e) {
                grid.updateVisiblePhotos();
            });
        }
    },

    getPhotoSwipeItems: function() {
        var items = []; 
        for (var i = 0; i < this.rows.length; i++) {
            var row = this.rows[i];

            for (var j = 0; j < row.photos.length; j++) {
                var photo = row.photos[j];

                var source = photo.getLargestSmallerThan($(window).width(), $(window).height());

                if (source) {
                    var item = {
                        src: source.url,
                        w: source.w,
                        h: source.h,
                        msrc: photo.getSource().url,
                        el: photo.toDOM(),
                        title: photo.title
                    };
                    items.push(item);
                }
            }
        }

        console.log(items);

        return items;
    },

    openPhotoSwipe: function(index, disableAnimation, fromURL) {
        var pswpElement = document.querySelectorAll('.pswp')[0];
        var items = this.getPhotoSwipeItems();

        console.log('test');

        // define options (if needed)
        var options = {
            getThumbBoundsFn: function(index) {
                // See Options -> getThumbBoundsFn section of documentation for more info
                var pageYScroll = window.pageYOffset || document.documentElement.scrollTop;
                var rect = items[index].el.getBoundingClientRect(); 

                return {x:rect.left, y:rect.top + pageYScroll, w:rect.width};
            }
        };

        // PhotoSwipe opened from URL
        if(fromURL) {
            options.index = parseInt(index, 10) - 1;
        } else {
            options.index = index;
        }

        // exit if index not found
        if( isNaN(options.index) ) {
            return;
        }

        if(disableAnimation) {
            options.showAnimationDuration = 0;
        }

        // Pass data to PhotoSwipe and initialize it
        var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
        gallery.init();
    }
};
