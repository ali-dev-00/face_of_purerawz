<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the [affiliate_leaderboard] shortcode
 */
function face_of_purerawz_affiliate_leaderboard_shortcode() {
    global $wpdb;
    $winner_table = $wpdb->prefix . 'face_of_purerawz_winner_data';
    
    // Check if there's a winner
    $has_winner = $wpdb->get_var("SELECT COUNT(*) FROM $winner_table WHERE is_winner = 1") > 0;
    
    ob_start();
    ?>
    <div id="affiliate-leaderboard" class="affiliate-leaderboard">
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

    <style>
        .leaderboard-table .winner {
            background-color: #2ecc71;
            color: white;
        }
        .leaderboard-table .rank-1 { background-color: #ffd700; } /* Gold */
        .leaderboard-table .rank-2 { background-color: #c0c0c0; } /* Silver */
        .leaderboard-table .rank-3 { background-color: #cd7f32; } /* Bronze */
    </style>

    <script>
        function fetchLeaderboard() {
            fetch('<?php echo admin_url("admin-ajax.php?action=fetch_affiliate_leaderboard"); ?>')
                .then(response => response.text())
                .then(data => {
                    console.log("leaderboard data", data);
                    document.getElementById("leaderboard-body").innerHTML = data;
                })
                .catch(error => console.error("Error fetching leaderboard:", error));
        }

        // Fetch leaderboard initially
        fetchLeaderboard();
        
        <?php if (!$has_winner) : ?>
        // Only set interval if there's no winner
        setInterval(fetchLeaderboard, 10000);
        <?php endif; ?>
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
    $winner_table = $wpdb->prefix . 'face_of_purerawz_winner_data';

    // Define the start date (April 1, 2025)
    $start_date = '2025-04-01';

    $query = $wpdb->prepare("
        SELECT 
            a.affiliate_id, 
            a.user_id, 
            u.display_name, 
            COUNT(r.referral_id) AS referral_count, 
            COALESCE(SUM(r.amount), 0) AS total_sales,
            w.is_winner
        FROM $affiliates_table a
        INNER JOIN $stories_table s ON a.user_id = s.user_id AND s.status = 'approved'
        LEFT JOIN $referrals_table r ON a.affiliate_id = r.affiliate_id AND r.status = 'paid' AND r.date >= %s
        LEFT JOIN $users_table u ON a.user_id = u.ID
        LEFT JOIN $winner_table w ON a.affiliate_id = w.affiliate_id
        WHERE a.status = 'active'
        GROUP BY a.affiliate_id
        ORDER BY 
            CASE WHEN w.is_winner = 1 THEN 0 ELSE 1 END,  -- Winners first
            total_sales DESC, 
            referral_count DESC
        LIMIT 30
    ", $start_date);

    $leaderboard_data = $wpdb->get_results($query);
    
    if ($leaderboard_data) {
        $rank = 1;
        foreach ($leaderboard_data as $affiliate) {
            $username = !empty($affiliate->display_name) ? esc_html($affiliate->display_name) : 'Anonymous';
            $total_sales = number_format($affiliate->total_sales, 2);
            
            // Determine row styling
            $row_class = '';
            if ($affiliate->is_winner == 1) {
                $row_class = 'winner';
            } elseif ($rank == 1) {
                $row_class = 'rank-1';
            } elseif ($rank == 2) {
                $row_class = 'rank-2';
            } elseif ($rank == 3) {
                $row_class = 'rank-3';
            }

            echo "
                <tr class='".esc_attr($row_class)."'>
                    <td>".esc_html($rank)."</td>
                    <td>".esc_html($username)."</td>
                    <td>".esc_html($affiliate->referral_count)."</td>
                    <td>$".esc_html($total_sales)."</td>
                </tr>
            ";
            $rank++;
        }
    } else {
        echo '<tr><td colspan="4">No approved performers with sales yet since April 1, 2025.</td></tr>';
    }

    wp_die();
}
add_action('wp_ajax_fetch_affiliate_leaderboard', 'fetch_affiliate_leaderboard');
add_action('wp_ajax_nopriv_fetch_affiliate_leaderboard', 'fetch_affiliate_leaderboard');