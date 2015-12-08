function addOption() {
    var currentCount = $('form > fieldset > fieldset').length;
    var template = $('form > fieldset > span').data('template');
    template = template.replace(/__index__/g, currentCount);

    $('form > fieldset').append(template);

    return false;
}