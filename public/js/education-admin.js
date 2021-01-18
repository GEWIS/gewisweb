Education = {};

Education.Admin = {};

Education.Admin.deleteTemp = function(type, filename, sender) {
    $.post(URLHelper.url('admin_education/delete_temp', {'type': type, 'filename' : filename})).done(function( data ) {
        if(data.success) {
            $(sender).parents().eq(2).remove();
        } else {
            $(sender).remove();
        }
    });
};
