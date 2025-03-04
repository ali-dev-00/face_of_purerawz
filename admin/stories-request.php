<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display and manage the Stories Request admin page
 *
 * @return void
 */
function face_of_purerawz_stories_request() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';

    add_action('admin_enqueue_scripts', 'face_of_purerawz_enqueue_scripts');
    add_action('wp_ajax_face_of_purerawz_update_story_status', 'face_of_purerawz_update_story_status_callback');

    // Fetch all stories from the table, ordered by creation date (descending)
    $stories = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    // Display admin page
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Stories Request', 'face-of-purerawz'); ?></h1>

        <?php settings_errors('face_of_purerawz_messages'); ?>

        <table class="wp-list-table widefat fixed striped face-of-purerawz-stories-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'face-of-purerawz'); ?></th>
                    <th><?php esc_html_e('Name', 'face-of-purerawz'); ?></th>
                    <th><?php esc_html_e('Email', 'face-of-purerawz'); ?></th>
                    <th><?php esc_html_e('Social Media Handle', 'face-of-purerawz'); ?></th>
                    <th><?php esc_html_e('File Upload', 'face-of-purerawz'); ?></th>
                    <th><?php esc_html_e('Status', 'face-of-purerawz'); ?></th>
                    <th><?php esc_html_e('Created At', 'face-of-purerawz'); ?></th>
                    <th><?php esc_html_e('Approved At', 'face-of-purerawz'); ?></th>
                    <th><?php esc_html_e('Actions', 'face-of-purerawz'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stories): ?>
                    <?php foreach ($stories as $story): ?>
                        <tr>
                            <td><?php echo esc_html($story->id); ?></td>
                            <td><?php echo esc_html($story->name); ?></td>
                            <td><?php echo esc_html($story->email); ?></td>
                            <td><?php echo esc_html($story->social_media_handle ?: 'N/A'); ?></td>
                            <td>
                                <?php if ($story->file_upload): ?>
                                    <a href="<?php echo esc_url($story->file_upload); ?>" target="_blank">View File</a>
                                <?php else: ?>
                                    <?php esc_html_e('No file uploaded', 'face-of-purerawz'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                switch ($story->status) {
                                    case 'approved':
                                        $status_class = 'approved';
                                        $status_text = 'Approved';
                                        break;
                                    case 'rejected':
                                        $status_class = 'rejected';
                                        $status_text = 'Reject';
                                        break;
                                    case 'pending':
                                        $status_class = 'pending';
                                        $status_text = 'Processing';
                                        break;
                                    default:
                                        $status_class = 'pending';
                                        $status_text = 'Processing';
                                        break;
                                }
                                ?>
                                <span class="face-of-purerawz-status-badge <?php echo esc_attr($status_class); ?>" data-story-id="<?php echo esc_attr($story->id); ?>">
                                    <?php echo esc_html($status_text); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($story->created_at); ?></td>
                            <td><?php echo esc_html($story->approved_at ?: 'N/A'); ?></td>
                            <td>
                                <select class="face-of-purerawz-status-dropdown" data-story-id="<?php echo esc_attr($story->id); ?>">
                                    <option value="pending" <?php selected($story->status, 'pending'); ?>>Pending</option>
                                    <option value="approved" <?php selected($story->status, 'approved'); ?>>Approve</option>
                                    <option value="rejected" <?php selected($story->status, 'rejected'); ?>>Reject</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9"><?php esc_html_e('No stories submitted yet.', 'face-of-purerawz'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Enqueue scripts and styles for the Stories Request page
 *
 * @return void
 */
function face_of_purerawz_enqueue_scripts() {
    $screen = get_current_screen();
    if ($screen->base === 'toplevel_page_face-of-purerawz' || $screen->base === 'face-of-purerawz_page_face-of-purerawz-stories') {
        wp_enqueue_style(
            'face-of-purerawz-admin-style',
            FACE_OF_PURERAWZ_URL . 'assets/css/admin.css',
            array(),
            FACE_OF_PURERAWZ_VERSION
        );

        wp_enqueue_script(
            'face-of-purerawz-admin-script',
            FACE_OF_PURERAWZ_URL . 'assets/js/admin.js',
            array('jquery'),
            FACE_OF_PURERAWZ_VERSION,
            true
        );

        // Localize script to pass AJAX URL and nonce
        wp_localize_script(
            'face-of-purerawz-admin-script', // Fixed typo in variable name
            'face_of_purerawz_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('face_of_purerawz_update_status')
            )
        );
    }
}

/**
 * AJAX callback to update story status
 *
 * @return void
 */
function face_of_purerawz_update_story_status_callback() {
    check_ajax_referer('face_of_purerawz_update_status', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';

    $story_id = intval($_POST['story_id']);
    $new_status = sanitize_text_field($_POST['new_status']);

    $approved_at = ($new_status === 'approved') ? current_time('mysql') : null;

    $result = $wpdb->update(
        $table_name,
        array(
            'status' => $new_status,
            'approved_at' => $approved_at,
        ),
        array('id' => $story_id),
        array('%s', '%s'),
        array('%d')
    );

    if ($result !== false) {
        wp_send_json_success(array(
            'message' => 'Story status updated successfully.',
            'status' => $new_status,
            'approved_at' => $approved_at ?: 'N/A'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to update story status. Please try again.'));
    }

    wp_die();
}