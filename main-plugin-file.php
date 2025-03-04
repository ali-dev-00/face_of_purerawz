<?php
/**
 * Plugin Name: Face of Purerawz
 * Plugin URI:  https://www.linkedin.com/in/mirza-ali-dev/
 * Description: A custom plugin for Purerawz to manage affiliate registrations, updates, and deletions, integrating with AffiliateWP.
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
    purerawz_register_story_post_type();
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

// Include plugin files
require_once plugin_dir_path(__FILE__) . 'create-and-sync-db-tables.php';
require_once plugin_dir_path(__FILE__) . 'affiliates-stories.php';