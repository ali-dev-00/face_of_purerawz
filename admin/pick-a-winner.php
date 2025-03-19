<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Winner_Picker_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'affiliate',
            'plural'   => 'affiliates',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'              => '<input type="checkbox" />',
            'affiliate_id'    => 'Affiliate ID',
            'name'            => 'Name',
            'email'           => 'Email',
            'earnings'        => 'Earnings',
            'referrals'       => 'Referrals',
            'likes'           => 'Likes',
            'is_winner'       => 'Winner Status',
        ];
    }

    public function get_sortable_columns() {
        return [
            'affiliate_id' => ['affiliate_id', false],
            'name'         => ['name', false],
            'email'        => ['email', false],
            'earnings'     => ['earnings', true],
            'referrals'    => ['referrals', false],
            'likes'        => ['likes', false],
        ];
    }

    public function get_bulk_actions() {
        return [
            'pick_winner' => 'Pick as Winner',
        ];
    }

    public function process_bulk_action() {
        if ('pick_winner' !== $this->current_action()) {
            return;
        }

        global $wpdb;
        $winner_table = $wpdb->prefix . 'face_of_purerawz_winner_data';
        
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to perform this action.');
        }

        if (isset($_POST['affiliate']) && is_array($_POST['affiliate'])) {
            check_admin_referer('bulk-' . $this->_args['plural']);
            
            $affiliate_ids = array_map('intval', $_POST['affiliate']);
            
            if (!empty($affiliate_ids)) {
                $wpdb->query("UPDATE $winner_table SET is_winner = 0 WHERE is_winner = 1");
                
                $placeholders = array_fill(0, count($affiliate_ids), '%d');
                $query = $wpdb->prepare(
                    "UPDATE $winner_table SET is_winner = 1 WHERE affiliate_id IN (" . implode(',', $placeholders) . ")",
                    $affiliate_ids
                );
                $wpdb->query($query);
                
                wp_safe_redirect(add_query_arg(['picked' => count($affiliate_ids)], admin_url('admin.php?page=winner-picker')));
                exit;
            }
        }
    }

    public function prepare_items() {
        global $wpdb;
        $winner_table = $wpdb->prefix . 'face_of_purerawz_winner_data';

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        
        $per_page = 30;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'earnings';
        $order = isset($_REQUEST['order']) && in_array(strtolower($_REQUEST['order']), ['asc', 'desc']) ? $_REQUEST['order'] : 'desc';

        $where = "status = 'active' AND story_status = 'approved'";
        if ($search) {
            $where .= $wpdb->prepare(
                " AND (name LIKE %s OR email LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $winner_table WHERE $where");

        $query = $wpdb->prepare(
            "SELECT * FROM $winner_table WHERE $where ORDER BY %s %s LIMIT %d OFFSET %d",
            $orderby,
            $order,
            $per_page,
            $offset
        );

        $this->items = $wpdb->get_results($query);
        
        foreach ($this->items as $item) {
            $item->earnings = '$' . number_format(floatval($item->earnings), 2);
        }

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="affiliate[]" value="%s" />',
            $item->affiliate_id
        );
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'affiliate_id':
            case 'name':
            case 'email':
            case 'earnings':
            case 'referrals':
            case 'likes':
                return esc_html($item->$column_name);
            case 'is_winner':
                return $item->is_winner ? '<span class="face-of-purerawz-status-badge winner">Winner</span>' : '-';
            default:
                return print_r($item, true);
        }
    }
}

/**
 * Update winner data function
 */
function face_of_purerawz_update_winner_data() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to perform this action.');
    }

    if (!isset($_POST['update_winner_data_nonce']) || !wp_verify_nonce($_POST['update_winner_data_nonce'], 'update_winner_data')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $winner_table = $wpdb->prefix . 'face_of_purerawz_winner_data';
    $source_table = $wpdb->prefix . 'some_affiliate_table'; // Verify this table exists

    $wpdb->query("TRUNCATE TABLE $winner_table");

    $top_affiliates = $wpdb->get_results($wpdb->prepare("
        SELECT affiliate_id, name, email, earnings, referrals, likes
        FROM $source_table
        WHERE status = 'active' AND story_status = 'approved'
        ORDER BY earnings DESC
        LIMIT %d",
        30
    ));

    if ($top_affiliates) {
        foreach ($top_affiliates as $affiliate) {
            $wpdb->insert(
                $winner_table,
                [
                    'affiliate_id' => $affiliate->affiliate_id,
                    'user_id'      => 0, // Add logic to get correct user_id if needed
                    'name'         => $affiliate->name,
                    'email'        => $affiliate->email,
                    'status'       => 'active',
                    'story_status' => 'approved',
                    'referrals'    => $affiliate->referrals,
                    'earnings'     => $affiliate->earnings,
                    'likes'        => $affiliate->likes,
                    'is_winner'    => 0,
                    'last_updated' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%d', '%s']
            );
        }
    }

    wp_safe_redirect(add_query_arg(['updated' => 'true'], admin_url('admin.php?page=winner-picker')));
    exit;
}

/**
 * Render the winner picker page
 */
function render_winner_picker_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Handle winner data update
    if (isset($_POST['action']) && $_POST['action'] === 'update_winner_data') {
        face_of_purerawz_update_winner_data();
    }

    $table = new Winner_Picker_Table();
    $table->process_bulk_action();
    $table->prepare_items();

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Pick a Winner', 'face-of-purerawz'); ?></h1>
        
        <?php if (isset($_GET['picked'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php printf(esc_html(_n('%d affiliate picked as winner.', '%d affiliates picked as winners.', intval($_GET['picked']))), intval($_GET['picked'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Winner data updated successfully with top 30 candidates.', 'face-of-purerawz'); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" class="winner-form">
            <input type="hidden" name="action" value="update_winner_data">
            <?php wp_nonce_field('update_winner_data', 'update_winner_data_nonce'); ?>
            <p><button type="submit" class="button"><?php esc_html_e('Update Winner Data (Top 30)', 'face-of-purerawz'); ?></button></p>
        </form>

        <form method="post" id="winner-table-form">
            <?php $table->display(); ?>
        </form>
    </div>
    <?php
}

// Add admin menu
function face_of_purerawz_admin_menu() {
    add_menu_page(
        'Winner Picker',
        'Winner Picker',
        'manage_options',
        'winner-picker',
        'render_winner_picker_page',
        'dashicons-awards',
        30
    );
}
add_action('admin_menu', 'face_of_purerawz_admin_menu');