<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Stories_Request_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'story',
            'plural'   => 'stories',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'id'                 => 'ID',
            'name'               => 'Name',
            'email'              => 'Email',
            'social_media_handle' => 'Social Media Handle',
            'file_upload'        => 'File Upload',
            'status'             => 'Story Status',
            'affiliate_status'   => 'Affiliate Status',
            'approved_at'        => 'Approved At',
            'actions'            => 'Actions',
        ];
    }

    public function get_sortable_columns() {
        return [
            'id'          => ['id', false],
            'name'        => ['name', false],
            'email'       => ['email', false],
            'approved_at' => ['approved_at', false],
            'status'      => ['status', false],
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';
        $affiliates_table = $wpdb->prefix . 'affiliate_wp_affiliates';

        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($table_exists !== $table_name) {
            add_settings_error('face_of_purerawz_messages', 'table_error', "Table $table_name does not exist.", 'error');
            $this->items = [];
            $this->set_pagination_args(['total_items' => 0, 'per_page' => 10]);
            return;
        }

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $status_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field($_REQUEST['status_filter']) : 'all';
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($sortable))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], ['asc', 'desc'])) ? $_REQUEST['order'] : 'asc';

        $where = 'WHERE 1=1';
        if ($search) {
            $where .= $wpdb->prepare(
                " AND (s.name LIKE %s OR s.email LIKE %s OR s.social_media_handle LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        if ($status_filter !== 'all') {
            $where .= $wpdb->prepare(" AND s.status = %s", $status_filter);
        }

        // Get total items
        $total_items_query = "SELECT COUNT(*) FROM $table_name s $where";
        $total_items = $wpdb->get_var($total_items_query);
        if ($total_items === null) {
            add_settings_error('face_of_purerawz_messages', 'query_error', "Error counting items: " . $wpdb->last_error, 'error');
            $total_items = 0;
        }

        // Fetch items with corrected JOIN
        $query = "
            SELECT s.*, a.status AS affiliate_status
            FROM $table_name s
            LEFT JOIN $affiliates_table a ON a.user_id = (
                SELECT ID FROM $wpdb->users u WHERE u.user_email = s.email LIMIT 1
            )
            $where
            ORDER BY s.$orderby $order
            LIMIT $per_page OFFSET $offset
        ";
        $this->items = $wpdb->get_results($query);

        if ($wpdb->last_error) {
            add_settings_error('face_of_purerawz_messages', 'query_error', "Error fetching items: " . $wpdb->last_error, 'error');
            $this->items = [];
            $total_items = 0;
        }

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'name':
            case 'email':
                return esc_html($item->$column_name);
            case 'social_media_handle':
                return esc_html($item->$column_name ?: 'N/A');
            case 'file_upload':
                return $item->$column_name 
                    ? '<a href="' . esc_url($item->$column_name) . '" target="_blank" title="Preview file in new tab">Preview File</a>' 
                    : 'No file uploaded';
            case 'status':
                $status_class = $status_text = '';
                switch ($item->status) {
                    case 'approved':
                        $status_class = 'approved';
                        $status_text = 'Approved';
                        break;
                    case 'rejected':
                        $status_class = 'rejected';
                        $status_text = 'Rejected';
                        break;
                    case 'pending':
                        $status_class = 'pending';
                        $status_text = 'Pending';
                        break;
                    default:
                        $status_class = 'pending';
                        $status_text = 'Pending';
                }
                return '<span class="face-of-purerawz-status-badge ' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
            case 'affiliate_status':
                    $affiliate_status = $item->affiliate_status ?? 'N/A';
                    $status_class = ($affiliate_status === 'active') ? 'approved' : 'rejected';
                    return '<span class="face-of-purerawz-status-badge ' . esc_attr($status_class) . '">' . esc_html(ucfirst($affiliate_status)) . '</span>';                
            case 'approved_at':
                return esc_html($item->$column_name ?: 'N/A');
            case 'actions':
                $actions = '';
                if ($item->status === 'pending') {
                    $actions .= '<form method="post" style="display:inline; margin-right:5px;">';
                    $actions .= wp_nonce_field('story_action_' . $item->id, '_wpnonce', true, false);
                    $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                    $actions .= '<input type="hidden" name="new_status" value="approved">';
                    $actions .= '<input type="submit" name="update_status" value="Accept" class="button button-link button-small">';
                    $actions .= '</form> | ';
                    $actions .= '<form method="post" style="display:inline; margin-right:5px;">';
                    $actions .= wp_nonce_field('story_action_' . $item->id, '_wpnonce', true, false);
                    $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                    $actions .= '<input type="hidden" name="new_status" value="rejected">';
                    $actions .= '<input type="submit" name="update_status" value="Reject" class="button button-link button-small">';
                    $actions .= '</form>';
                } elseif ($item->status === 'approved') {
                    $actions .= '<form method="post" style="display:inline; margin-right:5px;">';
                    $actions .= wp_nonce_field('story_action_' . $item->id, '_wpnonce', true, false);
                    $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                    $actions .= '<input type="hidden" name="new_status" value="rejected">';
                    $actions .= '<input type="submit" name="update_status" value="Reject" class="button button-link button-small">';
                    $actions .= '</form>';
                } elseif ($item->status === 'rejected') {
                    $actions .= '<form method="post" style="display:inline; margin-right:5px;">';
                    $actions .= wp_nonce_field('story_action_' . $item->id, '_wpnonce', true, false);
                    $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                    $actions .= '<input type="hidden" name="new_status" value="approved">';
                    $actions .= '<input type="submit" name="update_status" value="Approve" class="button button-link button-small">';
                    $actions .= '</form>';
                }
                $actions .= '<form method="post" style="display:inline; margin-left:5px;">';
                $actions .= wp_nonce_field('story_action_' . $item->id, '_wpnonce', true, false);
                $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                $actions .= '<input type="hidden" name="action" value="delete">';
                $actions .= '<input type="submit" name="delete_story" value="Delete" class="button button-link button-small button-danger" onclick="return confirm(\'Are you sure you want to delete this story?\');">';
                $actions .= '</form>';
                return $actions;
            default:
                return print_r($item, true);
        }
    }
}

function face_of_purerawz_stories_request() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';

    // Handle single status update or deletion
    if (isset($_POST['story_id']) && check_admin_referer('story_action_' . $_POST['story_id'])) {
        $story_id = intval($_POST['story_id']);
        if (isset($_POST['update_status']) && isset($_POST['new_status'])) {
            $new_status = sanitize_text_field($_POST['new_status']);
            $approved_at = ($new_status === 'approved') ? current_time('mysql') : null;

            // Removed 'has_posted' from the update since the column doesn't exist
            $result = $wpdb->update(
                $table_name,
                ['status' => $new_status, 'approved_at' => $approved_at],
                ['id' => $story_id],
                ['%s', '%s'],
                ['%d']
            );

            $message = $result !== false ? 'Story status updated successfully.' : 'Failed to update story status: ' . $wpdb->last_error;
            $type = $result !== false ? 'updated' : 'error';
            add_settings_error('face_of_purerawz_messages', 'face_of_purerawz_status', $message, $type);
        } elseif (isset($_POST['delete_story']) && isset($_POST['action']) && $_POST['action'] === 'delete') {
            $result = $wpdb->delete($table_name, ['id' => $story_id], ['%d']);
            $message = $result !== false ? 'Story deleted successfully.' : 'Failed to delete story: ' . $wpdb->last_error;
            $type = $result !== false ? 'updated' : 'error';
            add_settings_error('face_of_purerawz_messages', 'face_of_purerawz_delete', $message, $type);
        }
    }

    $table = new Stories_Request_Table();
    $table->prepare_items();

    // Get the current filter value
    $status_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field($_REQUEST['status_filter']) : 'all';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Stories Request', 'face-of-purerawz'); ?></h1>
        <p><?php esc_html_e('Active affiliates with approved story status will be considered as approved affiliates.', 'face-of-purerawz'); ?></p>
        <?php settings_errors('face_of_purerawz_messages'); ?>
        <form method="get">
            <input type="hidden" name="page" value="face-of-purerawz-stories" />
            <label for="status_filter">Filter by Status: </label>
            <select name="status_filter" id="status_filter" onchange="this.form.submit()">
                <option value="all" <?php selected($status_filter, 'all'); ?>>All</option>
                <option value="approved" <?php selected($status_filter, 'approved'); ?>>Approved</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejected</option>
            </select>
            <?php $table->search_box('Search Stories', 'search_id'); ?>
        </form>
        <form method="post">
            <?php $table->display(); ?>
        </form>
    </div>
    <?php
}