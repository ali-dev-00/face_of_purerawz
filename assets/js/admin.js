// jQuery(document).ready(function($) {
//     // Debug: Log to console to confirm script is loaded
//     console.log("Face of Purerawz admin JS file loaded");

//     // Ensure face_of_purerawz_ajax is defined
//     if (typeof face_of_purerawz_ajax === 'undefined') {
//         console.error('Face of Purerawz AJAX object not defined. Check script localization.');
//         return;
//     }

//     // Handle status dropdown changes with AJAX
//     $('.face-of-purerawz-status-dropdown').on('change', function() {
//         var $select = $(this);
//         var storyId = $select.data('story-id');
//         var newStatus = $select.val();

//         // Debug: Log the AJAX request details
//         console.log('Sending AJAX request for story ID:', storyId, 'with status:', newStatus);

//         // Send AJAX request to update story status
//         $.ajax({
//             url: face_of_purerawz_ajax.ajax_url,
//             type: 'POST',
//             data: {
//                 action: 'face_of_purerawz_update_story_status',
//                 nonce: face_of_purerawz_ajax.nonce,
//                 story_id: storyId,
//                 new_status: newStatus
//             },
//             success: function(response) {
//                 if (response.success) {
//                     // Debug: Log successful response
//                     console.log('Status update successful:', response);

//                     // Update the status badge dynamically
//                     var statusClass = '';
//                     var statusText = '';
//                     switch (newStatus) {
//                         case 'approved':
//                             statusClass = 'approved';
//                             statusText = 'Approved';
//                             break;
//                         case 'rejected':
//                             statusClass = 'rejected';
//                             statusText = 'Reject';
//                             break;
//                         case 'pending':
//                             statusClass = 'pending';
//                             statusText = 'Processing';
//                             break;
//                     }
//                     $select.closest('tr').find('.face-of-purerawz-status-badge')
//                         .removeClass('approved rejected pending')
//                         .addClass(statusClass)
//                         .text(statusText);

//                     // Update Approved At if applicable
//                     var approvedAt = response.data.approved_at;
//                     $select.closest('tr').find('td:nth-child(8)').text(approvedAt);

//                     // Show a success message
//                     console.log(response.data.message);
//                 } else {
//                     // Debug: Log error response
//                     console.error('Status update failed:', response.data.message);
//                     console.log(response.data.message);
//                 }
//             },
//             error: function(xhr, status, error) {
//                 // Debug: Log AJAX error
//                 console.error('AJAX error:', error);
                
//             }
//         });
//     });
// });