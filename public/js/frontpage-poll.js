function addOption() {
    let currentCount = $('fieldset.poll-options div.option').length + 1;
    let template = $('fieldset.poll-options span.template').data('template');
    template = template.replaceAll(/__option__/g, currentCount);

    $(template).insertBefore('fieldset.poll-options div.poll-option-controls');

    return false;
}

function removeOption() {
    let currentCount = $("fieldset.poll-options div.option").length;

    if (currentCount > 2){
        $('#option'+currentCount).remove();
    }
}
