/*
 * This script handles all javascript functions for the job creation form
 */

Job = {

    /**
     * Adds an optional label to the job form, at the end of the list.
     */
    addLabel: function () {
        var currentCount = $('#additionalLabels > div.label').length;
        var template = $('#additionalLabels span.template').data('template');
        template = template.replace(/__index__/g, currentCount);
        $(template).insertBefore('#additionalLabels div.add-label');
        Job.updateForm();
        return false;
    },

    /**
     * Removes the last label from the list.
     */
    removeLabel: function () {
        var currentCount = $('#additionalLabels > div.label').length - 1;
        if (currentCount >= 0){
            $('#additionalLabel' + currentCount).remove();
        }
        return false;
    },

    updateForm: function () {
    },

    updateLabel: function (index) {
        $('#additionalLabel' + index + ' .label-dependant').hide();
        var type = $('[name="labels[' + index + '][type]"]').val();
        $('#additionalLabel' + index + ' .type-' + type).show();
        Job.updateForm();
    }
};
