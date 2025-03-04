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

    
    face_of_purerawz_create_affiliates_table(); // Create custom table for storing affiliates 
    face_of_purerawz_create_stories_table();  // Custom table for handling stories
   // Sync existing AffiliateWP affiliates to custom table
     face_of_purerawz_sync_existing_affiliates();
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
require_once plugin_dir_path(__FILE__) . 'includes/create-db-tables.php';
require_once plugin_dir_path(__FILE__) . 'includes/sync_registered_affiliates.php';
require_once plugin_dir_path(__FILE__) . 'includes/affiliates-stories.php';