/*
 * This script handles all javascript functions for the proposal creation form
 */

const maxCount = 3;

Proposal = {

    /**
     * Adds an optional option to the proposal form, at the end of the list.
     */
    addOption: function () {
        var currentCount = $('#additionalOptions > div.option').length;
        if (currentCount < maxCount) {
            var template = $('#additionalOptions span.template').data('template');
            template = template.replace(/__index__/g, currentCount);
            $(template).insertBefore('#additionalOptions div.add-option');
            Proposal.updateForm();
            return false;
        }
    },

    /**
     * Removes the last option from the list.
     */
    removeOption: function () {
        var currentCount = $('#additionalOptions > div.option').length - 1;
        if (currentCount >= 0){
            $('#additionalOption' + currentCount).remove();
        }
        return false;
    },

    updateForm: function () {
    },

    updateOption: function (index) {
        $('#additionalOption' + index + ' .option-dependant').hide();
        var type = $('[name="options[' + index + '][type]"]').val();
        $('#additionalOption' + index + ' .type-' + type).show();
        Proposal.updateForm();
    }
};
