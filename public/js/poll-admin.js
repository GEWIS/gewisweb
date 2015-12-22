Poll = {};

Poll.Admin = {};

Poll.Admin.deletePoll = function(id) {
    $("#deleteForm").attr('action', URLHelper.url('admin_poll/delete', {'poll_id': id}));
    $('#deleteModal div.modal-body p.options').html($('#admin-poll-' + id).data('options'));
};

Poll.Admin.approvePoll = function(id) {
    $('#approveModal div.modal-body p.options').html($('#admin-poll-' + id).data('options'));
    $("#approvalForm").attr('action', URLHelper.url('admin_poll/approve', {'poll_id': id}));
};
