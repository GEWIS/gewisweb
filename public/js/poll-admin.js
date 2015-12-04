Poll = {};

Poll.Admin = {};

Poll.Admin.deletePoll = function(id) {
    $("#deleteForm").attr('action', URLHelper.url('admin_poll/delete', {'poll_id': id}));
};

Poll.Admin.approvePoll = function(id) {
    $("#approvalForm").attr('action', URLHelper.url('admin_poll/approve', {'poll_id': id}));
};