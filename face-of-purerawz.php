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
    // Register the custom post type
    purerawz_register_story_post_type();

    // Set an option to track installation
    if (!get_option('face_of_purerawz_installed')) {
        add_option('face_of_purerawz_installed', time());
    }

    // Create the custom affiliates table
    face_of_purerawz_create_affiliates_table();

    // Sync existing AffiliateWP affiliates during activation (uncomment if desired)
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
require_once plugin_dir_path(__FILE__) . 'includes/create-and-sync-db-tables.php';
require_once plugin_dir_path(__FILE__) . 'includes/affiliates-stories.php';