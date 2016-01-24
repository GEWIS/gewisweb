Activity = {};

Activity.Touch = {};
Activity.Touch.activities = [];

Activity.Touch.init = function () {
    $('.btn-keypad').each(function (index) {
        $(this).click(function () {
            var number = $(this).val();
            var lidnr = $('#lidnrInput').val();
            var pincode = $('#pinInput').val();
            if (number < 0) {
                //backspace
                if (pincode.length > 0) {
                    $('#pinInput').val(pincode.substr(0, pincode.length - 1));
                } else if (lidnr.length > 0) {
                    $('#lidnrInput').val(lidnr.substr(0, lidnr.length - 1));
                }
            } else {
                if (lidnr.length < 4 || (lidnr.length < 5 && lidnr.charAt(0) == '1')) {
                    $('#lidnrInput').val(lidnr + number);
                } else if (pincode.length < 4) {
                    $('#pinInput').val(pincode + number);
                }

                if (pincode.length == 3) {
                    Activity.Touch.login(lidnr, pincode + number);
                }
            }
        });
    });

    $('#loginModal').on('hidden.bs.modal', function () {
        $('#lidnrInput').val('');
        $('#pinInput').val('');
        $("#loginFailed").hide();
    });
    $('#logoutModal').on('hidden.bs.modal', function () {
        clearTimeout(Activity.Touch.logoutTickTimeout);
        Activity.Touch.resetLogoutTimeout();
    });
    setInterval(Activity.Touch.fetchActivities, 600000);
    Activity.Touch.fetchActivities();
    $('#activityList').on('tap', 'tr', function () {
        Activity.Touch.showActivity($(this).data('activity-index'));
    });
};

Activity.Touch.login = function (lidnr, pincode) {
    console.log(lidnr, pincode);
    $("#loginFailed").hide();
    $.post(URLHelper.url('user/pinlogin'), {'lidnr': lidnr, 'pincode': pincode}, function (data) {
        if (data.login) {
            Activity.Touch.user = data.user;
            $('#loginModal').modal('hide');
            $('#activityModal').modal('hide');
            Activity.Touch.resetLogoutTimeout();
            $('.not-logged-in').hide();
            $('.logged-in').show();
            $('#fullName').html(data.user.member.fullName);
            Activity.Touch.fetchSignedup();
        } else {
            $("#loginFailed").show();
        }
    });
};

Activity.Touch.logout = function () {
    Activity.Touch.logoutSeconds = 11;
    $('#logoutModal').modal('show');
    Activity.Touch.logoutTick();
};

Activity.Touch.logoutTick = function () {
    Activity.Touch.logoutSeconds--;
    $("#logoutSeconds").html(Activity.Touch.logoutSeconds);
    if (Activity.Touch.logoutSeconds == 0) {
        $.get(URLHelper.url('user/logout'), function (data) {
            $('#logoutModal').modal('hide');
            $('#activityModal').modal('hide');
            $('.logged-in').hide();
            $('.not-logged-in').show();
            $('#fullName').html('');
            Activity.Touch.clearSignedup();
            Activity.Touch.user = null;
            Activity.Touch.resetLogoutTimeout();
        });
    } else {
        Activity.Touch.logoutTickTimeout = setTimeout(Activity.Touch.logoutTick, 1000);
    }
};

Activity.Touch.resetLogoutTimeout = function () {
    if (Activity.Touch.logoutTimeout !== undefined) {
        clearTimeout(Activity.Touch.logoutTimeout);
    }

    if (Activity.Touch.user) {
        Activity.Touch.logoutTimeout = setTimeout(Activity.Touch.logout, 15000);
    }
};

Activity.Touch.fetchSignedup = function () {
    $.getJSON(URLHelper.url('activity_api/signedup'), function (data) {
        Activity.Touch.clearSignedup();
        Activity.Touch.signedUp = data.activities;
        for (var i = 0; i < data.activities.length; i++) {
            $('#activity' + data.activities[i]).addClass('success');
        }
    });
};

Activity.Touch.clearSignedup = function () {
    $('#activityList tr').removeClass('success');
};

Activity.Touch.fetchActivities = function () {
    $.getJSON(URLHelper.url('activity_api/list'), function (data) {
        Activity.Touch.activities = data;
        $('#activityList').html('');
        $.each(data, function (index, activity) {
            var now = new Date();
            var subscriptionDeadline = new Date(activity.subscriptionDeadline.date.replace(' ', 'T'));
            var deadlinePassed = now.getTime() > subscriptionDeadline.getTime();
            if (activity.canSignUp && !deadlinePassed) {
                $('#activityList').append(
                    '<tr id="activity' + activity.id + '" data-activity-index="' + index + '">'
                    + '<td>' + activity.beginTime.date.replace(':00.000000', '') + '</td>'
                    + '<td>' + activity.endTime.date.replace(':00.000000', '') + '</td>'
                    + '<td>' + activity.name + '</td>'
                    + '<td>' + activity.costs + '</td>'
                    + '</tr>'
                );
            }
        });
    });
};

Activity.Touch.showActivity = function (index) {
    Activity.Touch.resetLogoutTimeout();
    var activity = Activity.Touch.activities[index];
    console.log(activity);
    $('#subscribeFailed').hide();
    $('#unsubscribeFailed').hide();
    if (Activity.Touch.user) {
        $('#activitySubscribe').attr('onclick', 'Activity.Touch.subscribe(' + activity.id + ')');
        $('#activityUnsubscribe').attr('onclick', 'Activity.Touch.unsubscribe(' + activity.id + ')');
        if (Activity.Touch.signedUp.indexOf(activity.id) !== -1) {
            $('#activitySubscribe').hide();
            $('#activityUnsubscribe').show();
        } else {
            $('#activitySubscribe').show();
            $('#activityUnsubscribe').hide();
        }
    }

    $('#activityModal').modal('show');
    $('#activityModalLabel').html(activity.name);
    $('#activityDescription').html(activity.description);
    $('#activityDateTime').html(activity.beginTime.date.replace(':00.000000', ''));
    $('#activityLocation').html(activity.location);
    $('#activityCosts').html(activity.costs);
    $('#activityAttendeeCount').html(activity.attendees.length);
};

Activity.Touch.subscribe = function (id) {
    Activity.Touch.resetLogoutTimeout();
    $('#activitySubscribe').hide();
    $.post(URLHelper.url('activity_api/signup', {id: id}), function (data) {
        if (data.success) {
            $('#activityUnsubscribe').show();
            Activity.Touch.signedUp.push(id);
            $('#activity' + id).addClass('success');
        } else {
            $('#subscribeFailed').show();
        }
    });
};

Activity.Touch.unsubscribe = function (id) {
    Activity.Touch.resetLogoutTimeout();
    $('#activityUnsubscribe').hide();
    $.post(URLHelper.url('activity_api/signoff', {id: id}), function (data) {
        if (data.success) {
            $('#activitySubscribe').show();
            var index = Activity.Touch.signedUp.indexOf(id);
            Activity.Touch.signedUp.splice(index, 1);
            $('#activity' + id).removeClass('success');
        } else {
            $('#unsubscribeFailed').show();
        }
    });
};