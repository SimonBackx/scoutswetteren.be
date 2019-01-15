var Ajax = {};

Ajax.getQueryString = function(data) {
    return Object.keys(data).map(function(key) {
        return key + '=' + params[key]
    }).join('&');
};

Ajax.handleResponse = function(request, onSuccess, onFailure) {
    // Parse JSON if headers
    var type = request.getResponseHeader("Content-Type");
    var data = request.responseText;

    if (type && type.indexOf('application/json') == 0) {
        try {
            data = JSON.parse(data);
        } catch (err) {
            console.error('Invalid JSON in response.');
            console.error(data);
            if (onFailure) {
                onFailure({});
            }
            return;
        }
    }

    if (request.status >= 200 && request.status < 300) {
        if (onSuccess) {
            onSuccess(data);
        }
    } else {
        console.log("Failure");

        if (onFailure) {
            onFailure(data);
        }
    }
};

Ajax.get = function(url, optionalData, onSuccess, onFailure) {
    // Move arguments
    if (typeof optionalData === 'function') {
        // Move
        if (onSuccess) {
            onFailure = onSuccess;
        } else {
            onFailure = null;
        }
        onSuccess = optionalData;
        optionalData = {};
    }

    if (!optionalData) {
        optionalData = {};
    }

    var queryString = this.getQueryString(optionalData, '', '', true);
    if (queryString != '') {
        queryString = '?' + queryString;
    }

    var request = new XMLHttpRequest();
    request.onreadystatechange = function() {
        if (request.readyState == 4) {
            this.handleResponse(request, onSuccess, onFailure);
        }
    }.bind(this);

    request.open("GET", url + queryString, true);
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    request.send(null);
    return request;
};

Ajax.upload = function(url, data, onSuccess, onFailure) {

    var request = new XMLHttpRequest();
    request.onreadystatechange = function() {
        if (request.readyState == 4) {
            this.handleResponse(request, onSuccess, onFailure);
        }
    }.bind(this);

    var formData = new FormData();
    for (var dk in data) {
        var dataVal = data[dk];

        if (!dataVal.length || dataVal.length == 0 || !dataVal[0] || !dataVal[0].type || !dataVal[0].type.match) {
            formData.append(dk, dataVal);
            continue;
        }

        var files = dataVal;

        // Loop through each of the selected files.
        for (var i = 0; i < files.length; i++) {
            var file = files[i];

            // Check the file type.
            if (!file.type.match('image.*')) {
                continue;
            }

            // Add the file to the request.
            formData.append(dk, file, file.name);

            // Only 1 file if key doesn't have [] at the end
            if (dk.indexOf('[]') === -1) {
                break;
            }
        }
    }

    request.open("POST", url, true);
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    request.send(formData);
    return request;
};

// json encoded body
Ajax.post = function(url, optionalPostData, optionalGetData, onSuccess, onFailure) {
    // Move arguments
    if (typeof optionalPostData === 'function') {
        if (optionalGetData) {
            onFailure = optionalGetData;
        } else {
            onFailure = null;
        }
        ;
        onSuccess = optionalPostData;
        optionalGetData = {};
        optionalPostData = {};

    } else if (typeof optionalGetData === 'function') {
        if (onSuccess) {
            onFailure = onSuccess;
        } else {
            onFailure = null;
        }
        ;
        onSuccess = optionalGetData;
        optionalGetData = {};
    }

    if (!optionalGetData) {
        optionalGetData = {};
    }

    if (!optionalPostData) {
        optionalPostData = {};
    }

    var queryString = this.getQueryString(optionalGetData, '', '', true);
    if (queryString != '') {
        queryString = '?' + queryString;
    }

    var postBody = JSON.stringify(optionalPostData);

    var request = new XMLHttpRequest();
    request.onreadystatechange = function() {
        if (request.readyState == 4) {
            this.handleResponse(request, onSuccess, onFailure);
        }
    }.bind(this);

    request.open("POST", url + queryString, true);
    request.setRequestHeader('Content-Type', 'application/json');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    request.send(postBody);
};