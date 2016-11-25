jQuery(document).ready(function ($) {
    $(".fmj_refresh_btn").click(function (e) {
        e.preventDefault();
        $(this).hide();
        var
            form = $(this).attr('form'),
            entry = $(this).attr('entry'),
            field = $(this).attr('field'),
            targets = $("#fmj_refresh_targets_" + entry).val(),
            button_refresh = $(this)
            ;
        $("#status_loading_" + entry).show();
        var data = {
            'action': 'fmj_refresh_overview',
            'request_part': 'partial',
            'request_target': targets,
            'entry_id': entry,
            'field_id': field,
            '_ajax_nonce': formidable_mailjet_refresh.nonce
        };
        $.ajax({
            type: 'POST',
            url: formidable_mailjet_refresh.ajax_url,
            data: data,
            success: function (response) {
                if (response) {
                    response = JSON.parse(response);
                    if (response && response.error == "") {
                        replace_content(response);
                    }
                    $("#status_loading_" + entry).hide();
                }
            }
        }).always(function () {
            button_refresh.show();
        });
    });

    function replace_content($content) {
        if ($content) {
            if ($content.target_id && $content.status) {
                var targets = JSON.parse($content.target_id);
                var lowerJson = JSON.stringify($content.status).toLowerCase();
                var status = JSON.parse(lowerJson);
                $.each(targets, function (index, value) {
                    $("#fmj_status_" + value + "_" + $content.entry_id).html("").append(status[value]);
                });
            }
        }
    }
});