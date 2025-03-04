    console.log("plugin js file loaded")
    jQuery(document).ready(function($) {
        // Handle status dropdown changes with AJAX
        $('.face-of-purerawz-status-dropdown').on('change', function() {
            var $select = $(this);
            var storyId = $select.data('story-id');
            var newStatus = $select.val();

            // Send AJAX request to update story status
            $.ajax({
                url: face_of_purerawz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'face_of_purerawz_update_story_status',
                    nonce: face_of_purerawz_ajax.nonce,
                    story_id: storyId,
                    new_status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Update the status badge dynamically
                        var statusClass = '';
                        var statusText = '';
                        switch (newStatus) {
                            case 'approved':
                                statusClass = 'approved';
                                statusText = 'Approved';
                                break;
                            case 'rejected':
                                statusClass = 'rejected';
                                statusText = 'Reject';
                                break;
                            case 'pending':
                                statusClass = 'pending';
                                statusText = 'Processing';
                                break;
                        }
                        $select.closest('tr').find('.face-of-purerawz-status-badge')
                            .removeClass('approved rejected pending')
                            .addClass(statusClass)
                            .text(statusText);

                        // Update Approved At if applicable
                        var approvedAt = response.data.approved_at;
                        $select.closest('tr').find('td:nth-child(8)').text(approvedAt);

                        // Optionally, show a success message (e.g., using WordPress admin notices)
                        alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while updating the status. Please try again.');
                }
            });
        });
    });