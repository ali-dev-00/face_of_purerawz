<?php
/**
 * Plugin Name: Face of Purerawz
 * Plugin URI:  https://www.linkedin.com/in/mirza-ali-dev/
 * Description: A custom plugin for Purerawz.
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
 * Function to execute on plugin activation
 */
function face_of_purerawz_activate() {
    // Code to execute on activation, like setting up database tables, default options, etc.
    update_option('face_of_purerawz_installed', time());
}
register_activation_hook(__FILE__, 'face_of_purerawz_activate');

/**
 * Function to execute on plugin deactivation
 */
function face_of_purerawz_deactivate() {
    // Code to execute on deactivation, like cleanup tasks.
    delete_option('face_of_purerawz_installed');
}
register_deactivation_hook(__FILE__, 'face_of_purerawz_deactivate');
