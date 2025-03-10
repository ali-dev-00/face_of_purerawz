<?php

/**
 * Plugin Name: Face of Purerawz
 * Plugin URI:  https://www.linkedin.com/in/mirza-ali-dev/
 * Description: A custom plugin for Purerawz to manage affiliate registrations, updates, deletions, and story submissions, integrating with AffiliateWP.
 * Version: 1.0.0
 * Author: Ali Haider
 * Author URI: https://www.linkedin.com/in/mirza-ali-dev/
 * License: GPL2
 * Text Domain: face-of-purerawz
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FACE_OF_PURERAWZ_VERSION', '1.0.0');
define('FACE_OF_PURERAWZ_DIR', plugin_dir_path(__FILE__));
define('FACE_OF_PURERAWZ_URL', plugin_dir_url(__FILE__));

/**
 * Plugin Activation Hook
 */
function face_of_purerawz_activate()
{
    face_of_purerawz_create_affiliates_table(); // create affiliates table
    face_of_purerawz_create_stories_table(); // create stories table
    face_of_purerawz_create_referral_links_table(); // create referral links table
    //face_of_purerawz_sync_existing_affiliates(); // sync affiliates in custom table
    //face_of_purerawz_store_existing_affiliates(); // populate referral links

    // Set installation timestamp
    if (!get_option('face_of_purerawz_installed')) {
        add_option('face_of_purerawz_installed', time());
    }
}
register_activation_hook(__FILE__, 'face_of_purerawz_activate');

/**
 * Plugin Deactivation Hook
 */


function face_of_purerawz_deactivate()
{
    // Cleanup tasks (retain data if necessary)
    delete_option('face_of_purerawz_installed');
}
register_deactivation_hook(__FILE__, 'face_of_purerawz_deactivate');


// Include frontend assets 
function enqueue_plugin_assets()
{
    wp_enqueue_style(
        'purerawz-frontend-style',
        FACE_OF_PURERAWZ_URL . 'assets/css/frontend.css',
        array(),
        FACE_OF_PURERAWZ_VERSION
    );
     wp_enqueue_script(
        'face-of-purerawz-frontend-script',
        FACE_OF_PURERAWZ_URL . 'assets/js/frontend.js',
        array('jquery'), // Ensure jQuery is loaded
        FACE_OF_PURERAWZ_VERSION,
        true // Load in footer
    );

}
add_action('wp_enqueue_scripts', 'enqueue_plugin_assets');



// Include required files
require_once FACE_OF_PURERAWZ_DIR . '/includes/database.php';      // Database table creation
require_once FACE_OF_PURERAWZ_DIR . '/includes/sync.php';          // Affiliate sync with AffiliateWP
require_once FACE_OF_PURERAWZ_DIR . '/includes/stories.php';       // Story submission form and handling
require_once FACE_OF_PURERAWZ_DIR . '/includes/affiliate-link-hooks.php'; // Story submission form and handling
require_once FACE_OF_PURERAWZ_DIR . '/includes/leaderboard.php'; // Leaderboard functionality
require_once FACE_OF_PURERAWZ_DIR . '/admin/settings.php';      // Wp admin plugin functionality 

// pass this in get request to sync referral links /?sync-affiliate-links
// function face_of_purerawz_handle_affiliate_sync() {
//     if (isset($_GET['sync-affiliate-links'])) {
//         face_of_purerawz_store_existing_affiliates();
//         echo "Affiliate referral links synced successfully!";
//         exit; // Stop further execution to prevent unwanted output
//     }
// }
// add_action('init', 'face_of_purerawz_handle_affiliate_sync');



function face_of_purerawz_store_existing_affiliates() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_referral_links';
    $affiliates_table = $wpdb->prefix . 'affiliate_wp_affiliates';

    // Get all existing affiliates
    $affiliates = $wpdb->get_results("SELECT affiliate_id FROM $affiliates_table");
   
    if ($affiliates) {
        foreach ($affiliates as $affiliate) {
            $affiliate_id = $affiliate->affiliate_id;

            if (function_exists('affwp_get_affiliate_referral_url')) {
                $referral_url = affwp_get_affiliate_referral_url(array('affiliate_id' => $affiliate_id));
                echo "<pre>";
                print_r($referral_url);
                echo "</pre>";
                die;
                // Check if the affiliate already exists in the custom table
                $existing_link = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE affiliate_id = %d", $affiliate_id));

                if (!$existing_link) {
                    // Insert the referral link if not already stored
                    $wpdb->insert(
                        $table_name,
                        array(
                            'affiliate_id'   => $affiliate_id,
                            'referral_link'  => $referral_url,
                            'created_at'     => current_time('mysql'),
                        ),
                        array('%d', '%s', '%s')
                    );
                }
            }else {
                var_dump("function not exist");
                return;
            }
        }
    }
}

/**
 * Sync Existing Affiliates from AffiliateWP
 */
function face_of_purerawz_sync_existing_affiliates() {
    global $wpdb;
    $affiliates_table = $wpdb->prefix . 'affiliate_wp_affiliates';
    $new_table = $wpdb->prefix . 'face_of_purerawz_affiliates';

    $existing_affiliates = $wpdb->get_results("SELECT * FROM $affiliates_table");
    
    foreach ($existing_affiliates as $affiliate) {
        $user = $affiliate->user_id ? get_userdata($affiliate->user_id) : null;
        $account_email = $user ? $user->user_email : ($affiliate->email ?? '');

        $data = array(
            'affiliate_id' => $affiliate->affiliate_id,
            'reg_id' => $affiliate->reg_id,
            'user_id' => $affiliate->user_id,
            'rate' => $affiliate->rate,
            'rate_type' => $affiliate->rate_type,
            'flat_rate_basis' => $affiliate->flat_rate_basis,
            'payment_email' => $affiliate->payment_email,
            'status' => $affiliate->status,
            'earnings' => $affiliate->earnings,
            'unpaid_earnings' => $affiliate->unpaid_earnings,
            'referrals' => $affiliate->referrals,
            'visits' => $affiliate->visits,
            'date_registered' => $affiliate->date_registered,
        );

        $format = array('%d', '%d', '%d', '%f', '%s', '%f', '%s', '%s', '%f', '%f', '%d', '%d', '%s');

        if (!$wpdb->get_var($wpdb->prepare("SELECT affiliate_id FROM $new_table WHERE affiliate_id = %d", $affiliate->affiliate_id))) {
            $wpdb->insert($new_table, $data, $format);
        }
    }
}

