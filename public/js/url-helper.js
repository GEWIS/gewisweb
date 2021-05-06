URLHelper = {}
URLHelper.urls = [];

URLHelper.url = function (name, params) {
    if (name in this.urls) {
        var url = this.urls[name];
    } else {
        return '';
    }
    if (typeof params !== 'undefined') {
        $.each(params, function (key, value) {
            url = url.replace('{' + key + '}', value);
        });
    }
    // Remove unused params
    var regEx = new RegExp("\\{[a-zA-Z_]+}", "gm");
    url = url.replace(regEx, '');

    return url;
};

URLHelper.addUrl = function (name, url) {
    this.urls[name] = url;
}