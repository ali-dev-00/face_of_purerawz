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

    // Handle status update via POST
    if (isset($_POST['update_status']) && isset($_POST['story_id']) && isset($_POST['new_status'])) {
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
            add_settings_error(
                'face_of_purerawz_messages',
                'face_of_purerawz_success',
                'Story status updated successfully.',
                'updated'
            );
        } else {
            add_settings_error(
                'face_of_purerawz_messages',
                'face_of_purerawz_error',
                'Failed to update story status. Please try again.',
                'error'
            );
        }

        // Redirect to refresh the page and show messages
        $current_url = remove_query_arg('update_status', add_query_arg(array('paged' => $current_page, 's' => $search_query), admin_url('admin.php?page=face-of-purerawz-stories')));
        wp_redirect($current_url);
        exit;
    }

    // Pagination setup
    $per_page = 10; // Number of stories per page
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Search setup
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $search_where = '';
    if (!empty($search_query)) {
        $search_where = $wpdb->prepare(
            "WHERE (name LIKE %s OR email LIKE %s OR social_media_handle LIKE %s)",
            '%' . $wpdb->esc_like($search_query) . '%',
            '%' . $wpdb->esc_like($search_query) . '%',
            '%' . $wpdb->esc_like($search_query) . '%'
        );
    }

    // Count total stories for pagination
    $total_stories = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $search_where");
    $total_pages = ceil($total_stories / $per_page);

    // Fetch stories with pagination and search
    $stories = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name $search_where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    // Display admin page
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Stories Request', 'face-of-purerawz'); ?></h1>

        <?php settings_errors('face_of_purerawz_messages'); ?>

        <!-- Search Form -->
        <form method="GET" action="" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="face-of-purerawz-stories">
            <input type="text" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Search stories...', 'face-of-purerawz'); ?>" style="padding: 5px; width: 200px;">
            <input type="submit" value="<?php esc_attr_e('Search', 'face-of-purerawz'); ?>" class="button">
        </form>

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
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="story_id" value="<?php echo esc_attr($story->id); ?>">
                                    <select name="new_status" style="margin-right: 10px;">
                                        <option value="pending" <?php selected($story->status, 'pending'); ?>>Pending</option>
                                        <option value="approved" <?php selected($story->status, 'approved'); ?>>Approved</option>
                                        <option value="rejected" <?php selected($story->status, 'rejected'); ?>>Rejected</option>
                                    </select>
                                    <input type="submit" name="update_status" value="Save" class="button button-small">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9"><?php esc_html_e('No stories found.', 'face-of-purerawz'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_stories > $per_page): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'total' => $total_pages,
                        'current' => $current_page,
                        'prev_text' => __('« Previous'),
                        'next_text' => __('Next »'),
                        'type' => 'list'
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}