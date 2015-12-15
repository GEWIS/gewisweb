Activity= {};

Activity.Touch = {};

Activity.Touch.init = function () {
    $('.btn-keypad').each(function (index) {
        $(this).click(function() {
            var number = $(this).val();
            var lidnr = $('#lidnrInput').val();
            var pincode = $('#pinInput').val();
            if(number < 0) {
                //backspace
                if(pincode.length > 0) {
                    $('#pinInput').val(pincode.substr(0, pincode.length - 1));
                } else if(lidnr.length > 0) {
                    $('#lidnrInput').val(lidnr.substr(0, lidnr.length - 1));
                }
            } else {
                if(lidnr.length < 4 || (lidnr.length < 5 && lidnr.charAt(0) == '1')) {
                    $('#lidnrInput').val( lidnr + number);
                } else if (pincode.length < 4) {
                    $('#pinInput').val(pincode + number);
                }

                if (pincode.length == 3) {
                    Activity.Touch.Login(lidnr, pincode + number);
                }
            }
        });
    });

    $('#loginModal').on('hidden.bs.modal', function () {
        $('#lidnrInput').val('');
        $('#pinInput').val('');
    })
};

Activity.Touch.Login = function(lidnr, pincode) {
    console.log(lidnr, pincode);
    $("#loginFailed").hide();
    $.post(URLHelper.url('user/pinlogin'), {'lidnr': lidnr, 'pincode': pincode}, function (data) {
        if(data.login) {
            $('#loginModal').modal('hide')
        } else {
            $("#loginFailed").show();
        }
    });
};