function addOption() {
    var currentCount = $('#poll-options > div.option').length;
    var template = $('#poll-options span.template').data('template');
    template = template.replace(/__index__/g, currentCount);

    $(template).insertBefore('#poll-options div.add-option');
    $("#removeButton").show();
    return false;
}

function removeOption() {
    var currentCount = $("#poll-options > .option").length - 1;
    if (currentCount >= 2){
        $('#option'+currentCount).remove();
    }

    if (currentCount == 2) {
        $("#removeButton").hide();
    }
}