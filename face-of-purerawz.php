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
function face_of_purerawz_activate() {
    face_of_purerawz_create_affiliates_table(); // create affiliates table
    face_of_purerawz_create_stories_table(); // create stories table
    face_of_purerawz_create_referral_links_table(); // create referral links table
    face_of_purerawz_sync_existing_affiliates(); // sync affiliates in custom table
    face_of_purerawz_populate_referral_links(); // populate referral links

    // Set installation timestamp
    if (!get_option('face_of_purerawz_installed')) {
        add_option('face_of_purerawz_installed', time());
    }
}
register_activation_hook(__FILE__, 'face_of_purerawz_activate');

/**
 * Plugin Deactivation Hook
 */


function face_of_purerawz_deactivate() {
    // Cleanup tasks (retain data if necessary)
    delete_option('face_of_purerawz_installed');
}
register_deactivation_hook(__FILE__, 'face_of_purerawz_deactivate');


// Include frontend assets 
function enqueue_plugin_assets() {
    wp_enqueue_style(
        'purerawz-frontend-style',
        FACE_OF_PURERAWZ_URL . 'assets/css/frontend.css',
        array(),
        FACE_OF_PURERAWZ_VERSION
    );
}
add_action('wp_enqueue_scripts', 'enqueue_plugin_assets');




// Include required files
require_once FACE_OF_PURERAWZ_DIR . '/includes/database.php';      // Database table creation
require_once FACE_OF_PURERAWZ_DIR . '/includes/sync.php';          // Affiliate sync with AffiliateWP
require_once FACE_OF_PURERAWZ_DIR . '/includes/stories.php';       // Story submission form and handling
require_once FACE_OF_PURERAWZ_DIR . '/includes/affiliate-link-hooks.php'; // Story submission form and handling
require_once FACE_OF_PURERAWZ_DIR . '/admin/settings.php';      // Wp admin plugin functionality 
