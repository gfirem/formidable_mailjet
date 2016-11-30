jQuery(document).ready(function ($) {
    if (formidable_mailjet_notification) {
        if (formidable_mailjet_notification.message && formidable_mailjet_notification.type) {
            var notification_content = {
                message: formidable_mailjet_notification.message
            };
            if (formidable_mailjet_notification.title) {
                notification_content["title"] = formidable_mailjet_notification.title;
            }
            $.notify(notification_content, {
                type: formidable_mailjet_notification.type,
                newest_on_top: true,
                animate: {
                    enter: 'animated fadeInRight',
                    exit: 'animated fadeOutRight'
                },
                placement: {
                    from: 'bottom',
                    align: 'right'
                },
                offset: {
                    x: 50,
                    y: 100
                },
                template: '<div data-notify="container" class="col-xs-11 col-sm-3 alert-fmj alert-{0}" role="alert">' +
                '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' +
                '<span data-notify="icon"></span> ' +
                '<span data-notify="title">{1}</span> ' +
                '<span data-notify="message">{2}</span>' +
                '<div class="progress" data-notify="progressbar">' +
                '<div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>' +
                '</div>' +
                '<a href="{3}" target="{4}" data-notify="url"></a>' +
                '</div>'
            });
        }
    }
});