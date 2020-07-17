/*
 * This script handles all javascript functions for the activity creation form
 */

Activity = {
    /**
     * Adds an optional field to the activity form, at the end of the list.
     */
    addField: function () {
        var currentCount = $('fieldset.additional-fields div.field').length;
        var template = $('fieldset.additional-fields span.template').data('template');

        if (currentCount === 0) {
            $(".additional-fields-header").show();
        }

        template = template.replace(/__index__/g, currentCount);
        $(template).insertBefore('fieldset.additional-fields div.field-controls');
        Activity.updateForm();
        return false;
    },

    /**
     * Removes the last field from the list.
     */
    removeField: function () {
        var currentCount = $('fieldset.additional-fields div.field').length - 1;
        if (currentCount >= 0){
            $('#additionalField' + currentCount).remove();
        }

        if (currentCount === 0) {
            $(".additional-fields-header").hide();
        }

        return false;
    },

    /**
     * Toggles the availability of some dependent fields.
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

    updateFieldset: function (index) {
        $('#additionalField' + index + ' .field-dependant').hide();
        var type = $('[name="fields[' + index + '][type]"]').val();
        $('#additionalField' + index + ' .type-' + type).show();
        Activity.updateForm();
    }
};
