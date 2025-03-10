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
        <h2>AFFILIATE LEADERBOARD</h2>
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Affiliate</th>
                    <th>Referrals</th>
                    <th>Total Sales</th>
                </tr>
            </thead>
            <tbody id="leaderboard-body">
                <tr><td colspan="4">Loading leaderboard...</td></tr>
            </tbody>
        </table>
    </div>

    <script>
        function fetchLeaderboard() {
            fetch('<?php echo admin_url("admin-ajax.php?action=fetch_affiliate_leaderboard"); ?>')
                .then(response => response.text())
                .then(data => {
                    console.log("leaderboard data",data);
                    document.getElementById("leaderboard-body").innerHTML = data;
                })
                .catch(error => console.error("Error fetching leaderboard:", error));
        }

        // Fetch leaderboard initially and every 15 seconds
        fetchLeaderboard();
        setInterval(fetchLeaderboard, 15000);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('affiliate_leaderboard', 'face_of_purerawz_affiliate_leaderboard_shortcode');

/**
 * AJAX handler to fetch leaderboard data
 */
function fetch_affiliate_leaderboard() {
    global $wpdb;

    $affiliates_table = $wpdb->prefix . 'affiliate_wp_affiliates';
    $referrals_table = $wpdb->prefix . 'affiliate_wp_referrals';
    $users_table = $wpdb->prefix . 'users';
    $stories_table = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';

    $query = "
        SELECT a.affiliate_id, a.user_id, u.display_name, 
               COUNT(r.referral_id) AS referral_count, 
               COALESCE(SUM(r.amount), 0) AS total_sales
        FROM $affiliates_table a
        LEFT JOIN $referrals_table r ON a.affiliate_id = r.affiliate_id AND r.status = 'paid'
        LEFT JOIN $users_table u ON a.user_id = u.ID
        LEFT JOIN $stories_table s ON a.user_id = s.user_id AND s.has_posted = 1 AND s.status = 'approved'
        WHERE a.status = 'active' AND s.id IS NOT NULL
        GROUP BY a.affiliate_id
        ORDER BY total_sales DESC, referral_count DESC
        LIMIT 30
    ";

    $leaderboard_data = $wpdb->get_results($query);
    
    if ($leaderboard_data) {
        $rank = 1;
        foreach ($leaderboard_data as $affiliate) {
            $username = !empty($affiliate->display_name) ? esc_html($affiliate->display_name) : 'Anonymous';
            $highlight_class = ($rank <= 3) ? 'top-performer' : '';
            $total_sales = number_format($affiliate->total_sales, 2);
            echo "
                <tr class='".esc_attr($highlight_class)."'>
                    <td>".esc_html($rank)."</td>
                    <td>".esc_html($username)."</td>
                    <td>".esc_html($affiliate->referral_count)."</td>
                    <td>$".esc_html($total_sales)."</td>
                </tr>
            ";
            $rank++;
        }
    } else {
        echo '<tr><td colspan="4">No approved performers with sales yet.</td></tr>';
    }

    wp_die();
}
add_action('wp_ajax_fetch_affiliate_leaderboard', 'fetch_affiliate_leaderboard');
add_action('wp_ajax_nopriv_fetch_affiliate_leaderboard', 'fetch_affiliate_leaderboard');

/**
 * Add CSS for styling
 */
function face_of_purerawz_leaderboard_styles() {
    ?>
    <style>
        .affiliate-leaderboard {
            text-align: center;
        }
        .affiliate-leaderboard h2 {
            color: #dc3545;
            font-size: 2.5em;
            font-family: 'Arial', sans-serif;
            margin-bottom: 20px;
        }
        .leaderboard-table {
            width: 80%;
            margin: 0 auto;
            border-collapse: collapse;
        }
        .leaderboard-table th, .leaderboard-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        .leaderboard-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .top-performer:nth-child(1) {
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
