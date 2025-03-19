<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the admin menu for Face of Purerawz
 */
function face_of_purerawz_admin_menu() {
    add_menu_page(
        'Face of Purerawz',
        'Face of Purerawz',
        'manage_options',
        'face-of-purerawz',
        'face_of_purerawz_admin_dashboard',
        'dashicons-admin-users'
    );
    
    add_submenu_page(
        'face-of-purerawz',
        'Stories Request',
        'Stories Request',
        'manage_options',
        'face-of-purerawz-stories',
        'face_of_purerawz_stories_request'
    );

    add_submenu_page(
        'face-of-purerawz',
        'Approved Affiliates',
        'Approved Affiliates',
        'manage_options',
        'face-of-purerawz-affiliate-stories',
        'render_affiliate_stories_page'
    );
    add_submenu_page(
        'face-of-purerawz',
        'Pick a Winner',
        'Pick a Winner',
        'manage_options',
        'face-of-purerawz-winner',
        'render_winner_picker_page'
    );
}
add_action('admin_menu', 'face_of_purerawz_admin_menu');

/**
 * Display the main admin dashboard (placeholder, can be expanded later)
 */
function face_of_purerawz_admin_dashboard() {
    echo '<div class="wrap"><h1>' . esc_html__('Face of Purerawz Dashboard', 'face-of-purerawz') . '</h1><p>Welcome to the Face of Purerawz admin dashboard.</p></div>';
}

// Enqueue scripts and styles for admin pages
function face_of_purerawz_enqueue_assets($hook) {
    // Load only on the plugin's admin pages
    if ($hook !== 'toplevel_page_face-of-purerawz' && 
        $hook !== 'face-of-purerawz_page_face-of-purerawz-stories' && 
        $hook !== 'face-of-purerawz_page_face-of-purerawz-affiliate-stories' && 
        $hook !== 'face-of-purerawz_page_face-of-purerawz-winner') {
        return;
    }

    // Debug: Log to confirm the function is running
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Enqueuing assets for Face of Purerawz admin pages: ' . $hook);
    }

    // Enqueue CSS
    wp_enqueue_style(
        'face-of-purerawz-admin-style',
        FACE_OF_PURERAWZ_URL . 'assets/css/admin.css',
        array(),
        FACE_OF_PURERAWZ_VERSION
    );

    // Enqueue JavaScript
    wp_enqueue_script(
        'face-of-purerawz-admin-script',
        FACE_OF_PURERAWZ_URL . 'assets/js/admin.js',
        array('jquery'), // Ensure jQuery is loaded
        FACE_OF_PURERAWZ_VERSION,
        true // Load in footer
    );
}
add_action('admin_enqueue_scripts', 'face_of_purerawz_enqueue_assets');

// Include the stories request functionality
require_once FACE_OF_PURERAWZ_DIR . 'admin/stories-table.php';
require_once FACE_OF_PURERAWZ_DIR . 'admin/affiliate-stories-table.php';
require_once FACE_OF_PURERAWZ_DIR . 'admin/pick-a-winner.php';