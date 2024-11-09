jQuery(document).ready(function ($) {
    // Event delegation for .view-details clicks
    $(document).on('click', '.view-details', function (e) {
        e.preventDefault();

        var widgetName = $(this).data('widget-name');

        // Show the modal before data is loaded.
        $('#usage-details-modal').show();

        // Show a loading spinner.
        $('#usage-details-list').html('<div class="loading-spinner"></div>');
        $('#usage-header').text('Loading details for "' + widgetName + '"...');

        $.ajax({
            url: WidgetUsageTracker.ajax_url,
            type: 'POST',
            data: {
                action: 'get_widget_usage_details',
                widget_name: widgetName,
                nonce: WidgetUsageTracker.nonce,
            },
            success: function (response) {
                // Clear the loading spinner.
                $('#usage-details-list').empty();

                if (response.success) {
                    var list = $('#usage-details-list');
                    var postCount = response.data.length;

                    // Update the header based on the number of posts.
                    if (postCount > 0) {
                        $('#usage-header').text('The "' + widgetName + '" widget is used in ' + postCount + ' post' + (postCount > 1 ? 's' : '') + ':');
                    } else {
                        $('#usage-header').text('No usages found for the "' + widgetName + '" widget.');
                    }

                    // Append each unique post to the list.
                    $.each(response.data, function (index, page) {
                        list.append('<li><a href="' + page.url + '" target="_blank">' + page.title + '</a></li>');
                    });
                } else {
                    // Handle the case where no data is returned.
                    $('#usage-header').text('No usages found for the "' + widgetName + '" widget.');
                }
            },
            error: function () {
                alert('An error occurred while fetching the widget usage details.');
                $('#usage-details-modal').hide(); // Hide the modal on error.
            }
        });
    });

    // Close modal when the close button is clicked
    $(document).on('click', '.close', function () {
        $('#usage-details-modal').hide();
    });

    // Close modal when clicking outside the modal content
    $(window).on('click', function (e) {
        if ($(e.target).is('#usage-details-modal')) {
            $('#usage-details-modal').hide();
        }
    });
});
