<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle New Affiliate Registration via AffiliateWP Hook
 */
function face_of_purerawz_register_new_affiliate($affiliate_id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliates';

    if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
        error_log('Hook fired for face_of_purerawz_register_new_affiliate with affiliate_id: ' . $affiliate_id . ' and data: ' . print_r($data, true));
    }

    $user = isset($data['user_id']) && $data['user_id'] ? get_userdata($data['user_id']) : null;
    $account_email = $user ? $user->user_email : ($data['email'] ?? '');

    $data_array = array(
        'affiliate_id' => $affiliate_id,
        'reg_id' => $data['reg_id'] ?? null,
        'user_id' => $data['user_id'] ?? 0,
        'rate' => floatval($data['rate'] ?? 0.00),
        'rate_type' => $data['rate_type'] ?? 'percentage',
        'flat_rate_basis' => floatval($data['flat_rate_basis'] ?? null),
        'payment_email' => $data['payment_email'] ?? $account_email,
        'status' => $data['status'] ?? 'pending', // Default to 'pending' for new requests
        'earnings' => floatval($data['earnings'] ?? 0.00),
        'unpaid_earnings' => floatval($data['unpaid_earnings'] ?? 0.00),
        'referrals' => intval($data['referrals'] ?? 0),
        'visits' => intval($data['visits'] ?? 0),
        'date_registered' => $data['date_registered'] ?? current_time('mysql'),
    );

    $format = array('%d', '%d', '%d', '%f', '%s', '%f', '%s', '%s', '%f', '%f', '%d', '%d', '%s');

    // Validate and log empty or null values
    foreach ($data_array as $key => $value) {
        if (is_null($value) || (is_string($value) && empty(trim($value))) || (is_numeric($value) && $value === 0)) {
            if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
                error_log("Warning: Empty or null value for field '$key' in affiliate_id: $affiliate_id");
            }
        }
    }

    // Prevent duplicate insertions
    if (!$wpdb->get_var($wpdb->prepare("SELECT affiliate_id FROM $table_name WHERE affiliate_id = %d", $affiliate_id))) {
        $result = $wpdb->insert($table_name, $data_array, $format);

        if ($result === false) {
            if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
                error_log('Failed to insert affiliate_id ' . $affiliate_id . ': ' . $wpdb->last_error);
            }
        } else {
            if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
                error_log('Successfully inserted affiliate_id ' . $affiliate_id . ' into custom table.');
            }
        }
    } else {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Affiliate_id ' . $affiliate_id . ' already exists in custom table, skipping insertion.');
        }
    }
}
add_action('affwp_insert_affiliate', 'face_of_purerawz_register_new_affiliate', 10, 2);

/**
 * Handle Affiliate Status Changes via AffiliateWP Hook
 */
function face_of_purerawz_update_affiliate_status($affiliate_id, $new_status, $old_status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliates';

    if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
        error_log('Hook fired for face_of_purerawz_update_affiliate_status with affiliate_id: ' . $affiliate_id . ', new_status: ' . $new_status . ', old_status: ' . $old_status);
    }

    $data_array = array(
        'status' => $new_status,
    );

    $format = array('%s');

    $result = $wpdb->update(
        $table_name,
        $data_array,
        array('affiliate_id' => $affiliate_id),
        $format,
        array('%d')
    );

    if ($result === false) {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Failed to update status for affiliate_id ' . $affiliate_id . ': ' . $wpdb->last_error);
        }
    } else {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Successfully updated status to ' . $new_status . ' for affiliate_id ' . $affiliate_id . ' in custom table.');
        }
    }
}
add_action('affwp_set_affiliate_status', 'face_of_purerawz_update_affiliate_status', 10, 3);

/**
 * Handle Affiliate Update via AffiliateWP Hook (Excluding Status)
 */
function face_of_purerawz_update_affiliate($affiliate_id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliates';

    if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
        error_log('Hook fired for face_of_purerawz_update_affiliate with affiliate_id: ' . $affiliate_id . ' and data: ' . print_r($data, true));
    }

    $user = isset($data['user_id']) && $data['user_id'] ? get_userdata($data['user_id']) : null;
    $account_email = $user ? $user->user_email : ($data['email'] ?? '');

    $data_array = array(
        'affiliate_id' => $affiliate_id,
        'reg_id' => $data['reg_id'] ?? null,
        'user_id' => $data['user_id'] ?? 0,
        'rate' => floatval($data['rate'] ?? 0.00),
        'rate_type' => $data['rate_type'] ?? 'percentage',
        'flat_rate_basis' => floatval($data['flat_rate_basis'] ?? null),
        'payment_email' => $data['payment_email'] ?? $account_email,
        'earnings' => floatval($data['earnings'] ?? 0.00),
        'unpaid_earnings' => floatval($data['unpaid_earnings'] ?? 0.00),
        'referrals' => intval($data['referrals'] ?? 0),
        'visits' => intval($data['visits'] ?? 0),
    );

    $format = array('%d', '%d', '%d', '%f', '%s', '%f', '%s', '%f', '%f', '%d', '%d');

    // Validate and log empty or null values
    foreach ($data_array as $key => $value) {
        if (is_null($value) || (is_string($value) && empty(trim($value))) || (is_numeric($value) && $value === 0)) {
            if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
                error_log("Warning: Empty or null value for field '$key' in affiliate_id: $affiliate_id");
            }
        }
    }

    $result = $wpdb->update(
        $table_name,
        $data_array,
        array('affiliate_id' => $affiliate_id),
        $format,
        array('%d')
    );

    if ($result === false) {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Failed to update affiliate_id ' . $affiliate_id . ': ' . $wpdb->last_error);
        }
    } else {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Successfully updated affiliate_id ' . $affiliate_id . ' in custom table.');
        }
    }
}
add_action('affwp_update_affiliate', 'face_of_purerawz_update_affiliate', 10, 2);

/**
 * 
 * Handle Affiliate Deletion via AffiliateWP Hook
 * 
 */
function face_of_purerawz_delete_affiliate($affiliate_id) {
    global $wpdb;
    $affiliates_table = $wpdb->prefix . 'face_of_purerawz_affiliates';
    $referral_links_table = $wpdb->prefix . 'face_of_purerawz_referral_links';

    if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
        error_log('Hook fired for face_of_purerawz_delete_affiliate with affiliate_id: ' . $affiliate_id);
    }

    // Delete from face_of_purerawz_affiliates table
    $result_affiliates = $wpdb->delete(
        $affiliates_table,
        array('affiliate_id' => $affiliate_id),
        array('%d')
    );

    if ($result_affiliates === false) {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Failed to delete affiliate_id ' . $affiliate_id . ' from face_of_purerawz_affiliates table: ' . $wpdb->last_error);
        }
    } else {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Successfully deleted affiliate_id ' . $affiliate_id . ' from face_of_purerawz_affiliates table.');
        }
    }

    // Delete from face_of_purerawz_referral_links table
    $result_referral_links = $wpdb->delete(
        $referral_links_table,
        array('affiliate_id' => $affiliate_id),
        array('%d')
    );

    if ($result_referral_links === false) {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Failed to delete affiliate_id ' . $affiliate_id . ' from face_of_purerawz_referral_links table: ' . $wpdb->last_error);
        }
    } else {
        if (WP_DEBUG && defined('WP_DEBUG_LOG')) {
            error_log('Successfully deleted affiliate_id ' . $affiliate_id . ' from face_of_purerawz_referral_links table.');
        }
    }
}
add_action('affwp_delete_affiliate', 'face_of_purerawz_delete_affiliate', 10, 2);