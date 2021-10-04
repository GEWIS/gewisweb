/*
 * This script handles all javascript functions for the job creation form
 */

Job = {
    /**
     * Toggles the styling of a selected job label `label`.
     */
    toggleLabel: function (label) {
        var labelLabel = $('label[for="labels-' + label + '"]');

        if ($('#labels-' + label).is(':checked')) {
            labelLabel.prev().addClass('hidden');
            labelLabel.parent().addClass('chip-outlined');
        } else {
            labelLabel.prev().removeClass('hidden');
            labelLabel.parent().removeClass('chip-outlined');
        }
    },

    /**
     * Updates all job labels.
     */
    updateAllLabels: function() {
        var checked = null;

        $('input[id^="labels-"]').each(function() {
            checked = $(this).attr('checked');

            if (typeof checked !== typeof undefined && checked !== false) {
                $(this).prop('checked', false);
                Job.toggleLabel($(this).attr('value'));
                $(this).prop('checked', true);
            }
        });
    }
};
