jQuery(document).ready(function ($) {
    var checked = false;
    if (formidable_mailjet_date_time.print_value) {
        $("#field_" + formidable_mailjet_date_time.field_id).parent().show();
        checked = true;
    }
    var switch_options = {
        checked: checked,
        width: 50,
        height: 20,
        button_width: 25,
        show_labels: true,
        labels_placement: "both",
        on_label: "ON",
        off_label: "OFF"

    };

    $(".mj_schedule_enabled").switchButton(switch_options);
    $(".mj_schedule_picker").datetimepicker({
        format: 'Y/m/d H:i',
        inline: true,
        defaultDate: formidable_mailjet_date_time.now_date,
        defaultTime: formidable_mailjet_date_time.now_time,
        lang: 'en'
    });

    $(".mj_schedule_enabled").change(function () {
        var mj_schedule_picker_container = $(this).attr("target");
        if (mj_schedule_picker_container) {
            mj_schedule_picker_container = $("#" + mj_schedule_picker_container);
            if (!mj_schedule_picker_container.parent().is(":visible")) {
                mj_schedule_picker_container.parent().show();
            }
            else {
                mj_schedule_picker_container.parent().hide();
                mj_schedule_picker_container.val("");
            }
        }
    });
});