/*
 * This script handles all javascript functions for the company creation form
 */

Company = {
    /**
     * Updates the form to accommodate changes in the language checkboxes.
     */
    updateForm: function () {
        if ($('[name="language_dutch"]').is(':checked')) {
            $('.form-control-dutch').removeAttr('disabled');
            $('label[for$="-nl"]').addClass('label-required');
        } else {
            $('.form-control-dutch').attr('disabled', 'disabled');
            $('label[for$="-nl"]').removeClass('label-required');
        }

        if ($('[name="language_english"]').is(':checked')) {
            $('.form-control-english').removeAttr('disabled');
            $('label[for$="-en"]').addClass('label-required');
        } else {
            $('.form-control-english').attr('disabled', 'disabled');
            $('label[for$="-en"]').removeClass('label-required');
        }
    },
};
