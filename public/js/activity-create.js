/* 
 * This script handles all javascript functions for the activity creation form
 */

Activity = {
    /**
     * Adds an optional field to the activity form, at the end of the list.
     */
    addField: function () {
        var currentCount = $('#extraFields > fieldset').length;
        var template = $('form > div > div > fieldset > span').data('template');
        template = template.replace(/__index__/g, currentCount);
        //Add an id to the field.
        template = template.replace(/<fieldset/g, '<fieldset id="'+'fieldset'+ currentCount + '"');
        //Add a some dynamic stuff to the combobox
        template = template.replace(/[type]"/g, '[type]"'+ ' onchange="disable_field(' + currentCount + ')"');
        $('#extraFields').append(template);

        return false;
    },

    /**
     * Removes the last field from the list.
     */
    removeField: function () {
        var currentCount = $('form > fieldset > fieldset').length - 1;
        if (currentCount >= 0){
            $('#fieldset'+currentCount).remove();
        }
        return false;
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
    }
};