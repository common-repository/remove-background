
jQuery(document).ready(function($) {
    $('.no-bg-rate .notice-dismiss').on('click', function() {
        dismissRateNoticeForDays(3, this);
    });

    $('.no-bg-rate-remind-later').on('click', function() {
        dismissRateNoticeForDays(3, this);
    });

    $('.no-bg-rate-dismiss-notice').on('click', function() {
        dismissRateNoticeForDays(10, this);
    });

    $('.no-bg-rate-action').on('click', function() {
        dismissRateNoticeForDays(60, this);
    });

    function dismissRateNoticeForDays( dismissDays, element ){
        var remindLaterTime = new Date();
        remindLaterTime.setDate(remindLaterTime.getDate() + ( dismissDays || 7 ));

        jQuery.post(ajaxurl, {
            action: 'no_bg_remind_rate_notice',
            remind_rate_time: remindLaterTime.getTime()
        });

        $(element).closest('.notice-info').hide();
    }
});
