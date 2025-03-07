<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the [affiliate_leaderboard] shortcode
 */
function face_of_purerawz_affiliate_leaderboard_shortcode() {
    ob_start();
    ?>
    <div id="affiliate-leaderboard" class="affiliate-leaderboard">
        <h2>Top Affiliate Performers</h2>
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Affiliate</th>
                    <th>Referrals</th>
                    <th>Total Sales</th>
                </tr>
            </thead>
            <tbody id="leaderboard-data">
                <?php
                global $wpdb;
                $affiliates_table = $wpdb->prefix . 'face_of_purerawz_affiliates';
                $referrals_table = $wpdb->prefix . 'affiliate_wp_referrals';

                // Custom query to get top 30 approved affiliates with referral sales
                $query = $wpdb->prepare(
                    "SELECT a.affiliate_id, a.user_id, a.payment_email, COALESCE(a.custom_username, u.display_name, a.payment_email) AS username, 
                            COUNT(r.referral_id) AS referral_count, SUM(r.amount) AS total_sales
                     FROM $affiliates_table a
                     LEFT JOIN $referrals_table r ON a.affiliate_id = r.affiliate_id AND r.status = 'paid'
                     LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                     WHERE a.status = 'active' AND a.has_posted = 1
                     GROUP BY a.affiliate_id, a.user_id, a.payment_email, u.display_name
                     ORDER BY total_sales DESC, referral_count DESC
                     LIMIT 30"
                );

                $leaderboard_data = $wpdb->get_results($query);

                if ($leaderboard_data) {
                    $rank = 1;
                    foreach ($leaderboard_data as $affiliate) {
                        $username = $affiliate->total_sales > 0 ? $affiliate->username : 'Anonymous';
                        $highlight_class = ($rank <= 3) ? 'top-performer' : '';
                        $total_sales = number_format($affiliate->total_sales, 2);
                        ?>
                        <tr class="<?php echo esc_attr($highlight_class); ?>">
                            <td><?php echo esc_html($rank); ?></td>
                            <td><?php echo esc_html($username); ?></td>
                            <td><?php echo esc_html($affiliate->referral_count); ?></td>
                            <td>$<?php echo esc_html($total_sales); ?></td>
                        </tr>
                        <?php
                        $rank++;
                    }
                } else {
                    echo '<tr><td colspan="4">No approved performers yet.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function refreshLeaderboard() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'refresh_affiliate_leaderboard'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#leaderboard-data').html(response.data.html);
                        }
                    },
                    error: function() {
                        console.log('Error refreshing leaderboard');
                    }
                });
            }

            // Auto-refresh every 15 seconds (15000 milliseconds)
            setInterval(refreshLeaderboard, 15000);

            // Initial refresh
            refreshLeaderboard();
        });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('affiliate_leaderboard', 'face_of_purerawz_affiliate_leaderboard_shortcode');

/**
 * AJAX handler to refresh leaderboard data
 */
function face_of_purerawz_refresh_affiliate_leaderboard() {
    check_ajax_referer('leaderboard_nonce', 'nonce');

    ob_start();
    global $wpdb;
    $affiliates_table = $wpdb->prefix . 'face_of_purerawz_affiliates';
    $referrals_table = $wpdb->prefix . 'affiliate_wp_referrals';

    $query = $wpdb->prepare(
        "SELECT a.affiliate_id, a.user_id, a.payment_email, COALESCE(a.custom_username, u.display_name, a.payment_email) AS username, 
                COUNT(r.referral_id) AS referral_count, SUM(r.amount) AS total_sales
         FROM $affiliates_table a
         LEFT JOIN $referrals_table r ON a.affiliate_id = r.affiliate_id AND r.status = 'paid'
         LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
         WHERE a.status = 'active' AND a.has_posted = 1
         GROUP BY a.affiliate_id, a.user_id, a.payment_email, u.display_name
         ORDER BY total_sales DESC, referral_count DESC
         LIMIT 30"
    );

    $leaderboard_data = $wpdb->get_results($query);

    if ($leaderboard_data) {
        $rank = 1;
        foreach ($leaderboard_data as $affiliate) {
            $username = $affiliate->total_sales > 0 ? $affiliate->username : 'Anonymous';
            $highlight_class = ($rank <= 3) ? 'top-performer' : '';
            $total_sales = number_format($affiliate->total_sales, 2);
            ?>
            <tr class="<?php echo esc_attr($highlight_class); ?>">
                <td><?php echo esc_html($rank); ?></td>
                <td><?php echo esc_html($username); ?></td>
                <td><?php echo esc_html($affiliate->referral_count); ?></td>
                <td>$<?php echo esc_html($total_sales); ?></td>
            </tr>
            <?php
            $rank++;
        }
    } else {
        echo '<tr><td colspan="4">No approved performers yet.</td></tr>';
    }

    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
    wp_die();
}
add_action('wp_ajax_refresh_affiliate_leaderboard', 'face_of_purerawz_refresh_affiliate_leaderboard');
add_action('wp_ajax_nopriv_refresh_affiliate_leaderboard', 'face_of_purerawz_refresh_affiliate_leaderboard');

// Enqueue necessary scripts
function face_of_purerawz_enqueue_leaderboard_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'ajaxurl', admin_url('admin-ajax.php'));
    wp_nonce_field('leaderboard_nonce', '_leaderboard_nonce');
}
add_action('wp_enqueue_scripts', 'face_of_purerawz_enqueue_leaderboard_scripts');

// Add CSS for styling
function face_of_purerawz_leaderboard_styles() {
    ?>
    <style>
        .affiliate-leaderboard table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .affiliate-leaderboard th, .affiliate-leaderboard td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .affiliate-leaderboard th {
            background-color: #f2f2f2;
        }
        .top-performer {
            background-color: #ffd700;
            font-weight: bold;
        }
        .top-performer:nth-child(2) {
            background-color: #c0c0c0;
        }
        .top-performer:nth-child(3) {
            background-color: #cd7f32;
        }
    </style>
    <?php
}
add_action('wp_head', 'face_of_purerawz_leaderboard_styles');