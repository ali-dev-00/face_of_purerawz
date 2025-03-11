<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Affiliate_Stories_Table extends WP_List_Table {
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
            'affiliate_name'     => 'Affiliate Name',
            'sales'              => 'Sales (Earnings)',
            'payouts'            => 'Payouts',
            'referral_link'      => 'Referral Link',
            'likes_dislikes'     => 'Likes/Dislikes',
        ];
    }

    public function get_sortable_columns() {
        return [
            'id'          => ['id', false],
            'name'        => ['name', false],
            'email'       => ['email', false],
            'sales'       => ['sales', true],  // Default sort column
            'payouts'     => ['payouts', false],
        ];
    }

    public function prepare_items() {
        global $wpdb;

        $stories_table = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';
        $votes_table = $wpdb->prefix . 'face_of_purerawz_story_votes';
        $affiliates_table = $wpdb->prefix . 'affiliate_wp_affiliates';
        $users_table = $wpdb->prefix . 'users';
        $referrals_table = $wpdb->prefix . 'affiliate_wp_referrals';
        $payouts_table = $wpdb->prefix . 'affiliate_wp_payouts';

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Set default sorting to sales (descending)
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($sortable))) ? $_REQUEST['orderby'] : 'sales';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], ['asc', 'desc'])) ? $_REQUEST['order'] : 'desc';

        $where = "s.status = 'approved' AND a.status = 'active'";
        if ($search) {
            $where .= $wpdb->prepare(
                " AND (s.name LIKE %s OR s.email LIKE %s OR s.social_media_handle LIKE %s OR u.display_name LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $total_items = $wpdb->get_var(
            "SELECT COUNT(*) 
             FROM $stories_table s
             INNER JOIN $affiliates_table a ON s.user_id = a.user_id
             INNER JOIN $users_table u ON a.user_id = u.ID
             WHERE $where"
        );

        $query = "SELECT s.*, 
                         a.status AS affiliate_status,
                         a.affiliate_id,
                         u.display_name AS affiliate_name,
                         COALESCE(likes_count.votes, 0) AS likes,
                         COALESCE(dislikes_count.votes, 0) AS dislikes,
                         COALESCE(sales_count.amount, 0) AS sales,
                         COALESCE(payouts_count.amount, 0) AS payouts
                  FROM $stories_table s
                  INNER JOIN $affiliates_table a ON s.user_id = a.user_id
                  INNER JOIN $users_table u ON a.user_id = u.ID
                  LEFT JOIN (
                      SELECT story_id, COUNT(*) AS votes
                      FROM $votes_table
                      WHERE vote_type = 'like'
                      GROUP BY story_id
                  ) likes_count ON s.id = likes_count.story_id
                  LEFT JOIN (
                      SELECT story_id, COUNT(*) AS votes
                      FROM $votes_table
                      WHERE vote_type = 'dislike'
                      GROUP BY story_id
                  ) dislikes_count ON s.id = dislikes_count.story_id
                  LEFT JOIN (
                      SELECT affiliate_id, SUM(amount) AS amount
                      FROM $referrals_table
                      WHERE status = 'paid'
                      GROUP BY affiliate_id
                  ) sales_count ON a.affiliate_id = sales_count.affiliate_id
                  LEFT JOIN (
                      SELECT affiliate_id, SUM(amount) AS amount
                      FROM $payouts_table
                      WHERE status = 'complete'
                      GROUP BY affiliate_id
                  ) payouts_count ON a.affiliate_id = payouts_count.affiliate_id
                  WHERE $where
                  ORDER BY $orderby $order
                  LIMIT $per_page OFFSET $offset";

        $items = $wpdb->get_results($query);

        foreach ($items as $item) {
            $item->sales = $item->sales ? '$' . number_format($item->sales, 2) : '$0.00';
            $item->payouts = $item->payouts ? '$' . number_format($item->payouts, 2) : '$0.00';

            if (function_exists('affwp_get_affiliate_referral_url') && $item->affiliate_id) {
                $item->referral_link = '<a href="' . esc_url(affwp_get_affiliate_referral_url(['affiliate_id' => $item->affiliate_id])) . '" target="_blank">' . esc_html(affwp_get_affiliate_referral_url(['affiliate_id' => $item->affiliate_id])) . '</a>';
            } else {
                $item->referral_link = '-';
            }

            $item->likes_dislikes = esc_html($item->likes . '/' . $item->dislikes);
        }

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);

        $this->items = $items;
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'name':
            case 'email':
            case 'affiliate_name':
                return esc_html($item->$column_name);
            case 'sales':
            case 'payouts':
                return esc_html($item->$column_name);
            case 'referral_link':
                return $item->referral_link;
            case 'likes_dislikes':
                return $item->likes_dislikes;
            default:
                return print_r($item, true);
        }
    }
}

function render_affiliate_stories_page() {
    $table = new Affiliate_Stories_Table();
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1>Approved Stories for Active Affiliates</h1>
        <form method="get">
            <input type="hidden" name="page" value="face-of-purerawz-affiliate-stories" />
            <?php $table->search_box('Search Stories', 'search_id'); ?>
        </form>
        <?php $table->display(); ?>
    </div>
    <?php
}