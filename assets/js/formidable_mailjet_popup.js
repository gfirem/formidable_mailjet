jQuery(document).ready(function ($) {
    var entry;
    var field;
    var target;
    var newsletter;
    var dlg = $(".mailjet_status_popup_container").dialog({
        'modal': true,
        'autoOpen': false,
        'closeOnEscape': true,
        'resizable': false,
        'width': 650,
        'buttons': [
            {
                'text': 'Refresh',
                'click': function () {
                    $("#status-loading").show();
                    $("#mailjet_status_popup_content").hide();
                    var data = {
                        'action': 'fmj_update_overview',
                        'request_part': 'full',
                        'entry_id': entry,
                        'field_id': field,
                        'target_id': target,
                        'newsletter_id': newsletter,
                        '_ajax_nonce': formidable_mj.nonce
                    };
                    $.ajax({
                        type: 'POST',
                        url: formidable_mj.ajax_url,
                        data: data,
                        success: function (response) {
                            $("#status-loading").hide();
                            if(response) {
                                response =  JSON.parse(response);
                                if (response.error == "") {
                                    replace_content(response.status);
                                }
                                else{
                                    replace_content(response.error);
                                }
                                $("#mailjet_status_popup_content").show();
                            }
                        }
                    });
                }
            },
            {
                'text': 'Close',
                'click': function () {
                    $(this).dialog('close');
                }
            }
        ]
    });

    function replace_content($content) {
        if($content) {
            $("#mailjet_status_popup_content").html("");
            var str = "";
            if (typeof $content == "string") {
                str = JSON.stringify(JSON.parse($content), undefined, 4);
            }
            else {
                str = JSON.stringify($content, undefined, 4);
            }
            $("#mailjet_status_popup_content").append(str);
        }
    }

    $(".mailjet_status_open_popup").click(function (e) {
        e.preventDefault();
        target = $(this).attr('target');
        entry = $(this).attr('entry');
        field = $(this).attr('field');
        newsletter = $(this).attr('newsletter');
        var content = $("#" + target).val();
        replace_content(content);
        dlg.dialog('open');
    });

});