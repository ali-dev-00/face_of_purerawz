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
            'status'             => 'Status',
            'created_at'         => 'Created At',
            'approved_at'        => 'Approved At',
            'actions'            => 'Actions',
        ];
    }

    public function get_sortable_columns() {
        return [
            'id'          => ['id', false],
            'name'        => ['name', false],
            'email'       => ['email', false],
            'created_at'  => ['created_at', true], // Default sort column
            'approved_at' => ['approved_at', false],
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($sortable))) ? $_REQUEST['orderby'] : 'created_at';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], ['asc', 'desc'])) ? $_REQUEST['order'] : 'desc';

        $where = '';
        if ($search) {
            $where = $wpdb->prepare(
                "WHERE (name LIKE %s OR email LIKE %s OR social_media_handle LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");

        $query = "SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT $per_page OFFSET $offset";
        $this->items = $wpdb->get_results($query);

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
                        $status_text = 'Reject';
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
            case 'created_at':
            case 'approved_at':
                return esc_html($item->$column_name ?: 'N/A');
            case 'actions':
                $actions = '';
                if ($item->status === 'pending') {
                    $actions .= '<form method="post" style="display:inline; margin-right:5px;">';
                    $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                    $actions .= '<input type="hidden" name="new_status" value="approved">';
                    $actions .= '<input type="submit" name="update_status" value="Accept" class="button button-link button-small">';
                    $actions .= '</form> | ';
                    $actions .= '<form method="post" style="display:inline; margin-right:5px;">';
                    $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                    $actions .= '<input type="hidden" name="new_status" value="rejected">';
                    $actions .= '<input type="submit" name="update_status" value="Reject" class="button button-link button-small">';
                    $actions .= '</form>';
                } elseif ($item->status === 'approved') {
                    $actions .= '<form method="post" style="display:inline; margin-right:5px;">';
                    $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                    $actions .= '<input type="hidden" name="new_status" value="rejected">';
                    $actions .= '<input type="submit" name="update_status" value="Reject" class="button button-link button-small">';
                    $actions .= '</form>';
                } elseif ($item->status === 'rejected') {
                    $actions .= '<form method="post" style="display:inline; margin-right:5px;">';
                    $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
                    $actions .= '<input type="hidden" name="new_status" value="approved">';
                    $actions .= '<input type="submit" name="update_status" value="Approve" class="button button-link button-small">';
                    $actions .= '</form>';
                }
                $actions .= '<form method="post" style="display:inline; margin-left:5px;">';
                $actions .= '<input type="hidden" name="story_id" value="' . esc_attr($item->id) . '">';
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

    // Handle status update
    if (isset($_POST['update_status']) && isset($_POST['story_id']) && isset($_POST['new_status'])) {
        $story_id = intval($_POST['story_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        $approved_at = ($new_status === 'approved') ? current_time('mysql') : null;
        $has_posted = ($new_status === 'approved') ? 1 : 0;

        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $new_status,
                'approved_at' => $approved_at,
                'has_posted' => $has_posted,
            ),
            array('id' => $story_id),
            array('%s', '%s', '%d'),
            array('%d')
        );

        if ($result !== false) {
            add_settings_error('face_of_purerawz_messages', 'face_of_purerawz_success', 'Story status updated successfully.', 'updated');
        } else {
            add_settings_error('face_of_purerawz_messages', 'face_of_purerawz_error', 'Failed to update story status.', 'error');
        }
    }

    // Handle story deletion
    if (isset($_POST['delete_story']) && isset($_POST['story_id'])) {
        $story_id = intval($_POST['story_id']);
        $result = $wpdb->delete($table_name, array('id' => $story_id), array('%d'));

        if ($result !== false) {
            add_settings_error('face_of_purerawz_messages', 'face_of_purerawz_success', 'Story deleted successfully.', 'updated');
        } else {
            add_settings_error('face_of_purerawz_messages', 'face_of_purerawz_error', 'Failed to delete story.', 'error');
        }
    }

    $table = new Stories_Request_Table();
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Stories Request', 'face-of-purerawz'); ?></h1>
        <?php settings_errors('face_of_purerawz_messages'); ?>
        <form method="get">
            <input type="hidden" name="page" value="face-of-purerawz-stories" />
            <?php $table->search_box('Search Stories', 'search_id'); ?>
        </form>
        <?php $table->display(); ?>
    </div>
    <?php
}