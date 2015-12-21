module.exports.register = function (handlebars, config) {
    config = config || {};

    handlebars.registerHelper('ifEqual', function (a, b, options) {
        console.log(a == b, a, b);
        return (a == b) ? options.fn(this) : options.inverse(this);
    });
};
