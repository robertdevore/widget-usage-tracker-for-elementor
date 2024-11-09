jQuery(document).ready(function ($) {
    $('.view-details').on('click', function (e) {
        e.preventDefault();

        var widgetName = $(this).data('widget-name');

        $.ajax({
            url: WidgetUsageTracker.ajax_url,
            type: 'POST',
            data: {
                action: 'get_widget_usage_details',
                widget_name: widgetName,
                nonce: WidgetUsageTracker.nonce,
            },
            success: function (response) {
                if (response.success) {
                    var list = $('#usage-details-list');
                    list.empty();

                    $('#usage-header').text('The ' + widgetName + ' widget was found ' + response.data.length + ' times in the following content:');

                    $.each(response.data, function (index, page) {
                        list.append('<li><a href="' + page.url + '" target="_blank">' + page.title + '</a></li>');
                    });

                    $('#usage-details-modal').show();
                }
            }
        });
    });

    $('.close').on('click', function () {
        $('#usage-details-modal').hide();
    });

    $(window).on('click', function (e) {
        if (e.target.id === 'usage-details-modal') {
            $('#usage-details-modal').hide();
        }
    });
});
