var photos = photos || {};

// PHOTO
photos.Photo = function(width, height) {
    this.sources = [];
    this.width = 0;
    this.height = 0;
    this.grid = null;

    this.source_width = width;
    this.source_height = height;

    this.element = null;
    this.isPlaceholder = true;
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

    toDOM: function() {
        if (this.element && !(this.isPlaceholder && this.sources.length > 0)) {
            return this.element;
        }

        if (this.sources.length == 0) {
            this.isPlaceholder = true;
            var element = document.createElement('div');
            element.className = 'preview';
        } else {

            this.isPlaceholder = false;
            var element = document.createElement('img');

            // Calculate the best source file (without using dpi)
            var src;
            var srcset = '';

            this.sources.sort(function(a, b){return a.width - b.width });

            for (var i = 0; i < this.sources.length; i++) {
                if (!src || (this.sources[i].height > src.height && src.height < this.height) || (this.sources[i].height < src.height && src.height >= this.height)) {
                    src = this.sources[i];
                }
                if (i > 0) {
                    srcset += ', ';
                }
                srcset += this.sources[i].url + ' ' + this.sources[i].width + 'w';
            }
            
            /*element.setAttribute('srcset', srcset);
            element.setAttribute('sizes', this.width+'px');*/
            element.setAttribute('src', src.url);

            element.setAttribute('alt', "");
        
        }

        element.style.width = this.width+'px';
        element.style.height = this.height+'px';

        if (this.element) {
            // replace
            this.element.parentNode.insertBefore(element, this.element);
            this.element.parentNode.removeChild(this.element);
        }

        this.element = element;
        return element;
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

    this.width = 500;
    this.max_height = 300;
    this.min_height = 100;
    this.margin = 8;

    this.trackWidthElement = null;

    this.element = null;

    this.needsRecalculate = false;
};

photos.Grid.prototype = {
    add: function(photo) {
        photo.grid = this;

        if (this.rows.length == 0 || !this.rows[this.rows.length - 1].add(photo)) {
            var row = new photos.Row(this);
            row.add(photo);
            this.rows.push(row);
            this.toDOM().appendChild(row.toDOM());
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

        this.refresh();
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
    },

    trackWidth: function(trackWidthElement) {
        this.trackWidthElement = trackWidthElement;
        this.width = this.trackWidthElement.offsetWidth;

        var grid = this;
        window.onresize = function(event) {
            grid.resize();
        };
    }
};
