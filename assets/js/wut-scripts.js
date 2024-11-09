jQuery(document).ready(function ($) {
    // Event delegation for .view-details clicks
    $(document).on('click', '.view-details', function (e) {
        e.preventDefault();

        var widgetName = $(this).data('widget-name');

        // Show the modal before data is loaded.
        $('#usage-details-modal').show();

        // Show a loading indicator (optional)
        $('#usage-details-list').html('<li>Loading...</li>');

        $.ajax({
            url: WidgetUsageTracker.ajax_url,
            type: 'POST',
            data: {
                action: 'get_widget_usage_details',
                widget_name: widgetName,
                nonce: WidgetUsageTracker.nonce,
            },
            success: function (response) {
                // Clear the loading indicator.
                $('#usage-details-list').empty();

                console.log('AJAX Response:', response); // Debugging

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
                        console.log('Appending Post:', page.title, page.url); // Debugging
                        list.append('<li><a href="' + page.url + '" target="_blank">' + page.title + '</a></li>');
                    });

                } else {
                    // Handle the case where no data is returned.
                    $('#usage-header').text('No usages found for the "' + widgetName + '" widget.');
                    $('#usage-details-list').empty();
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
