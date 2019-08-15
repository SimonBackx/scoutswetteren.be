function is_retina_device() {
    return window.devicePixelRatio > 1;
}

function getSourceForSize(sources, width, height) {
    var factor = 1;
    if (is_retina_device()) {
        factor = 2;
    }

    // Sort from small to biggest
    sources.sort(function(a, b){return a.w - b.w });

    // Return the first source with width and height >= given width and height
    for (var i = 0; i < sources.length; i++) {
        if (sources[i].h >= height*factor && sources[i].w >= width*factor) {
            return sources[i];
        }
    }
    return sources[sources.length - 1];
}

function isElementInViewport(el) {
    var rect = el.getBoundingClientRect();

    return (
        rect.bottom >= 0 &&
        rect.right >= 0 &&
        rect.top <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
        rect.left <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */
    );
}

var LazyLoading = {};
LazyLoading.queue = [];
LazyLoading.listener = false;
LazyLoading.loadIfNeeded = function() {
    for (var index = 0; index < this.queue.length; index++) {
        var data = this.queue[index];
        if (!data.loaded && isElementInViewport(data.element)) {
            var source = getSourceForSize(data.sources, data.element.offsetWidth, data.element.offsetHeight);
            data.element.style.backgroundImage = "url('"+source.url+"')";
            data.loaded = true;
        }
    }
}

LazyLoading.lazyLoadBackground = function(element, sources) {
    if (!element) {
        console.warn("Cant load inexisting element.");
        return;
    }
    LazyLoading.queue.push({
        element: element,
        sources: sources,
        loaded: false,
    });
    this.loadIfNeeded();

    if (!this.listener) {
        this.listener = true;
        window.addEventListener('scroll', function(e) {
            this.loadIfNeeded();
        }.bind(this));
        window.addEventListener('resize', function(e) {
            this.loadIfNeeded();
        }.bind(this));
    }
}