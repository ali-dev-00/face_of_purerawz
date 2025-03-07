<?php
if (!defined('ABSPATH')) {
    exit;
}


function face_of_purerawz_store_referral_link($affiliate_id, $status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_referral_links';

    // Ensure the status is 'active' (approved affiliate)
    if ($status === 'active' && function_exists('affwp_get_affiliate_referral_url')) {
        $referral_url = affwp_get_affiliate_referral_url(array('affiliate_id' => $affiliate_id));

        // Check if the referral link already exists
        $existing_link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE affiliate_id = %d", $affiliate_id));

        if ($existing_link) {
            // Update existing referral link
            $wpdb->update(
                $table_name,
                array('referral_link' => $referral_url, 'created_at' => current_time('mysql')),
                array('affiliate_id' => $affiliate_id),
                array('%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new referral link
            $wpdb->insert(
                $table_name,
                array('affiliate_id' => $affiliate_id, 'referral_link' => $referral_url, 'created_at' => current_time('mysql')),
                array('%d', '%s', '%s')
            );
        }
    }
}

// Hook into affiliate approval process
add_action('affwp_set_affiliate_status', 'face_of_purerawz_store_referral_link', 10, 2);

function face_of_purerawz_delete_referral_link($affiliate_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_referral_links';

    $wpdb->delete($table_name, array('affiliate_id' => $affiliate_id), array('%d'));
}
add_action('affwp_delete_affiliate', 'face_of_purerawz_delete_referral_link', 10, 1);
