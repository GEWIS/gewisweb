function addOption() {
    var currentCount = $('#poll-options > div.option').length;
    var template = $('#poll-options span.template').data('template');
    template = template.replace(/__index__/g, currentCount);

    $(template).insertBefore('#poll-options div.add-option');

    return false;
}
