/*
 * This script handles all javascript functions for the activity creation form
 */

Activity = {
    /**
     * Adds a new (empty) SignupList to the Activity.
     */
    addSignupList: function () {
        var currentCount = $('fieldset.signup-lists div.signup-list').length;
        var template = $('fieldset.signup-lists span.template').data('template');

        if (currentCount === 0) {
            $(".signup-lists-header").show();
        }

        template = template.replace(/__signuplist__/g, currentCount);
        $(template).insertBefore('fieldset.signup-lists div.signup-list-controls');
        $('[name="signuplists[' + currentCount + '][openDate]"]').datetimepicker();
        $('[name="signuplists[' + currentCount + '][closeDate]"]').datetimepicker();
        Activity.updateForm();

        return false;
    },

    /**
     * Removes the last added SignupList from this Activity.
     */
    removeSignupList: function () {
        var currentCount = $('fieldset.signup-lists div.signup-list').length - 1;

        if (currentCount >= 0){
            $('[name="signuplists[' + currentCount + '][openDate]"]').datetimepicker('destroy');
            $('[name="signuplists[' + currentCount + '][closeDate]"]').datetimepicker('destroy');
            $('#signupList' + currentCount).remove();
        }

        if (currentCount === 0) {
            $(".signup-lists-header").hide();
        }
    },

    /**
     * Adds an optional field to the SignupList `signupList`.
     */
    addField: function (signupList) {
        var currentCount = $('#signupList' + signupList + ' fieldset.signup-list-fields div.field').length;
        var template = $('#signupList' + signupList + ' fieldset.signup-list-fields span.template').data('template');

        template = template.replace(/__signuplist_field__/g, currentCount);
        $(template).insertBefore('#signupList' + signupList + ' fieldset.signup-list-fields div.signup-list-field-controls');
        Activity.updateForm();
    },

    /**
     * Removes the last field from the SignupList `signupList`.
     */
    removeField: function (signupList) {
        var currentCount = $('#signupList' + signupList + ' fieldset.signup-list-fields div.field').length - 1;

        if (currentCount >= 0){
            $('#signupList' + signupList + ' #additionalField' + currentCount).remove();
        }
    },

    /**
     * Updates the form to accomodate changes in the language checkboxes.
     */
    updateForm: function () {
        $('[data-toggle="tooltip"]').tooltip({container: 'body'});

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

    /**
     * Updates a SignupListField `index` of  SignupList `signupList`.
     *
     * @param integer signupList
     * @param integer index
     */
    updateField: function (signupList, index) {
        $('#signupList' + signupList + ' #additionalField' + index + ' .field-dependant').hide();
        var type = $('[name="signuplists[' + signupList + '][fields][' + index + '][type]"]').val();
        $('#signupList' + signupList + ' #additionalField' + index + ' .type-' + type).show();
        Activity.updateForm();
    },

    /**
     * Updates all SignupFields of all SignupLists.
     */
    updateAllFields: function () {
        var signupListCount = $('fieldset.signup-lists div.signup-list').length;

        for (var i = 0; i < signupListCount; i++) {
            var signupListFieldCount = $('#signupList' + i + ' fieldset.signup-list-fields div.field').length;

            for (var j = 0; j < signupListFieldCount; j++) {
                Activity.updateField(i, j);
            }
        }
    }
};
