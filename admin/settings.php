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
}
add_action('admin_menu', 'face_of_purerawz_admin_menu');

/**
 * Display the main admin dashboard (placeholder, can be expanded later)
 */
function face_of_purerawz_admin_dashboard() {
    echo '<div class="wrap"><h1>' . esc_html__('Face of Purerawz Dashboard', 'face-of-purerawz') . '</h1><p>Welcome to the Face of Purerawz admin dashboard.</p></div>';
}

require_once FACE_OF_PURERAWZ_DIR . 'admin/stories-request.php';