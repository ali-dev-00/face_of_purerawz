<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create the custom affiliates table for Face of Purerawz
 */
function face_of_purerawz_create_affiliates_table() {
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

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
/**
 * Create the custom affiliates table for Face of Purerawz
 */
function face_of_purerawz_create_stories_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            social_media_handle varchar(255) DEFAULT NULL,
            file_upload varchar(255) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            approved_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
 