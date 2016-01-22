/* 
 * This script handles all javascript functions for the activity creation form
 */

/**
 * Adds an optional field to the activity form, at the end of the list.
 */
function add_field() {
         var currentCount = $('#extraFields > fieldset').length;
         var template = $('form > div > div > fieldset > span').data('template');
         template = template.replace(/__index__/g, currentCount);
         //Add an id to the field.
         template = template.replace(/<fieldset/g, '<fieldset id="'+'fieldset'+ currentCount + '"');
         //Add a some dynamic stuff to the combobox
         template = template.replace(/[type]"/g, '[type]"'+ ' onchange="disable_field(' + currentCount + ')"');
         $('#extraFields').append(template);
         
         return false;
     }

/**
 * Removes the last field from the list.
 */
function remove_field() {
        var currentCount = $('form > fieldset > fieldset').length - 1;
        if (currentCount >= 0){
            $('#fieldset'+currentCount).remove();
        }
        
        return false;
}