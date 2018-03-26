if (![].min) {
    Array.prototype.min = function() {
        return Math.min.apply(Math, this);
    };

    Array.prototype.max = function() {
        return Math.max.apply(Math, this);
    };
}

serialize = function(data) {
    if (!angular.isObject(data)) {
        return data == null ? '' : data.toString();
    }

    var buffer = [];

    for (var name in data) {
        if (!data.hasOwnProperty(name)) {
            continue;
        }

        var value = data[name];

        buffer.push(
            encodeURIComponent(name) +
            "=" +
            encodeURIComponent(value == null ? '' : value)
        );
    }

    return buffer.join("&").replace(/%20/g, "+");
};
