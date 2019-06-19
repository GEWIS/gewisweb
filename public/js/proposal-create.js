/*
 * This script handles all javascript functions for the proposal creation form
 */

Proposal = {
    /**
     * Adds an optional option to the proposal form, at the end of the list.
     */
    addOption: function () {
        var currentCount = $('#additionalOptions > div.option').length;
        var template = $('#additionalOptions span.template').data('template');
        template = template.replace(/__index__/g, currentCount);
        $(template).insertBefore('#additionalOptions div.add-option');
        Proposal.updateForm();
        return false;
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

    /**
     * Toggles the availability of some dependent options.
     */
    toggleExternal: function () {
        if ($('[name="onlyGEWIS"]').is(':checked')) {
            $('[name="canSignUp"]').attr('checked', true);
        }
    },

    updateForm: function () {
        if ($('[name="language_dutch"]').is(':checked')) {
            $('.form-control-dutch').removeAttr('disabled');
        } else {
            $('.form-control-dutch').attr('disabled', 'disabled');
        }

        if ($('[name="language_english"]').is(':checked')) {
            $('.form-control-english').removeAttr('disabled');
        } else {
            $('.form-control-english').attr('disabled', 'disabled');
        }
    },

    updateOption: function (index) {
        $('#additionalOption' + index + ' .option-dependant').hide();
        var type = $('[name="options[' + index + '][type]"]').val();
        $('#additionalOption' + index + ' .type-' + type).show();
        Proposal.updateForm();
    }
};
