<?php
/**
 * Plugin Name: Face of Purerawz
 * Plugin URI:  https://www.linkedin.com/in/mirza-ali-dev/
 * Description: A custom plugin for Purerawz to manage affiliate registrations and integrate with AffiliateWP.
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

/**
 * Plugin Activation Hook
 */
function face_of_purerawz_activate() {
    // Set a default option or create necessary database tables.
    if (!get_option('face_of_purerawz_installed')) {
        add_option('face_of_purerawz_installed', time());
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliates';
    
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            affiliate_id mediumint(9) NOT NULL AUTO_INCREMENT,
            reg_id mediumint(9) DEFAULT NULL,
            user_id mediumint(9) NOT NULL,
            rate decimal(10,2) NOT NULL,
            rate_type varchar(20) NOT NULL,
            flat_rate_basis decimal(10,2) DEFAULT NULL,
            payment_email varchar(100) NOT NULL,
            status varchar(20) NOT NULL,
            earnings decimal(10,2) NOT NULL,
            unpaid_earnings decimal(10,2) NOT NULL,
            referrals int(11) NOT NULL,
            visits int(11) NOT NULL,
            date_registered datetime NOT NULL,
            PRIMARY KEY (affiliate_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Sync existing AffiliateWP affiliates during activation (if table was created)
    // face_of_purerawz_sync_existing_affiliates();
}
register_activation_hook(__FILE__, 'face_of_purerawz_activate');

/**
 * Plugin Deactivation Hook
 */
function face_of_purerawz_deactivate() {
    // Perform cleanup tasks but retain data if necessary.
    delete_option('face_of_purerawz_installed');
}
register_deactivation_hook(__FILE__, 'face_of_purerawz_deactivate');

/**
 * Handle New Affiliate Registration via AffiliateWP Hook
 */
function face_of_purerawz_register_new_affiliate($affiliate_id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliates';

    // Fetch user data using the user_id from AffiliateWP
    $user = get_userdata($data['user_id']); // Assuming the affiliate is linked to a WordPress user

    if ($user) {
        $username = $user->user_login;
        $account_email = $user->user_email;
    } else {
        $username = '';
        $account_email = $data['email'] ?? '';
    }

    // Map data from AffiliateWP to match the table structure
    $data_array = array(
        'affiliate_id' => $affiliate_id,
        'reg_id' => null, // You can set this to a specific value or leave as NULL if not used
        'user_id' => $data['user_id'],
        'rate' => $data['rate'], // Default to '' if not set
        'rate_type' => $data['rate_type'] ?? 'percentage',
        'flat_rate_basis' => $data['flat_rate_basis'],
        'payment_email' => $data['payment_email'] ?? $account_email,
        'status' => $data['status'] ?? 'active',
        'earnings' => $data['earnings'],
        'unpaid_earnings' => $data['unpaid_earnings'],
        'referrals' => $data['referrals'] ?? 0,
        'visits' => $data['visits'] ?? 0,
        'date_registered' => $data['date_registered'] ?? current_time('mysql')
    );

    $format = array(
        '%d', '%d', '%d', '%f', '%s', '%f', '%s', '%s', '%f', '%f', '%d', '%d', '%s'
    );

    // Check if the affiliate isn’t already in your table
    if (!$wpdb->get_var("SELECT affiliate_id FROM $table_name WHERE affiliate_id = %d", $affiliate_id)) {
        $wpdb->insert($table_name, $data_array, $format);
    }
}
add_action('affwp_insert_affiliate', 'face_of_purerawz_register_new_affiliate', 10, 2);

/**
 * Sync Existing Affiliates from AffiliateWP
 */
function face_of_purerawz_sync_existing_affiliates() {
    global $wpdb;
    $affiliates_table = $wpdb->prefix . 'affiliate_wp_affiliates';
    $new_table = $wpdb->prefix . 'face_of_purerawz_affiliates';

    $existing_affiliates = $wpdb->get_results("SELECT * FROM $affiliates_table");
    
    foreach ($existing_affiliates as $affiliate) {
        // Fetch user data using the user_id from AffiliateWP
        $user = get_userdata($affiliate->user_id); // Assuming linked to a WordPress user
        
        if ($user) {
            $username = $user->user_login;
            $account_email = $user->user_email;
        } else {
            $username = '';
            $account_email = $affiliate->email ?? '';
        }

        // Map fields from AffiliateWP to match the custom table structure
        $data = array(
            'affiliate_id' => $affiliate->affiliate_id,
            'reg_id' => $affiliate->reg_id, // Use AffiliateWP reg_id or NULL if not set
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
            'date_registered' => $affiliate->date_registered
        );

        $format = array(
            '%d', '%d', '%d', '%f', '%s', '%f', '%s', '%s', '%f', '%f', '%d', '%d', '%s'
        );

        // Check if the affiliate isn’t already in your table
        if (!$wpdb->get_var("SELECT affiliate_id FROM $new_table WHERE affiliate_id = %d", $affiliate->affiliate_id)) {
            $wpdb->insert($new_table, $data, $format);
        }
    }
}

/**
 * Handle GET Request to Sync Affiliates to Custom Database
 */
function face_of_purerawz_handle_sync_request() {
    if (isset($_GET['sync-affiliates-to-customDb']) && current_user_can('manage_options')) { // Restrict to admins
        face_of_purerawz_sync_existing_affiliates();
        wp_die('Affiliates synced to custom database successfully.', 'Sync Complete', array('response' => 200));
    }
}
add_action('init', 'face_of_purerawz_handle_sync_request');


