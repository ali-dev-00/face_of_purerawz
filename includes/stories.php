<?php
/**
 * Display and process the story submission form
 */
function purerawz_story_submission_form_shortcode() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url() . '">log in</a> to submit a story.</p>';
    }

    // Process form submission
    if (isset($_POST['submit_story'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';

        // Get and validate form data (use pre-filled values for name and email since fields are disabled)
        $user_id = get_current_user_id();
        $name = get_user_meta($user_id, 'nickname', true) ? sanitize_text_field(get_user_meta($user_id, 'nickname', true)) : '';
        $email = get_userdata($user_id)->user_email ? sanitize_email(get_userdata($user_id)->user_email) : '';
        $social_media_handle = !empty($_POST['social_handles']) ? sanitize_text_field($_POST['social_handles']) : '';
        $file_upload = '';
        $status = 'pending'; // Default status as per DB
        $created_at = current_time('mysql');

        // Validate required fields (name and email are now fetched from user data)
        if (empty($name)) {
            return '<p class="error">Name is required.</p>';
        }
        if (empty($email) || !is_email($email)) {
            return '<p class="error">A valid email is required.</p>';
        }

        // Handle file upload if provided (optional, images or videos, max 1 MB)
        if (!empty($_FILES['upload_file']['name'])) {
            $file = $_FILES['upload_file'];
            $file_name = sanitize_file_name($file['name']);
            $file_size = $file['size']; // Size in bytes
            $file_type = $file['type'];

            // Validate file size (max 1 MB = 1,048,576 bytes)
            if ($file_size > 1048576) {
                return '<p class="error">File size must not exceed 1 MB.</p>';
            }

            // Validate file type (images or videos only: .jpg, .png, .mp4, .mov)
            $allowed_types = array('image/jpeg', 'image/png', 'video/mp4', 'video/quicktime');
            if (!in_array($file_type, $allowed_types)) {
                return '<p class="error">Only JPG, PNG, MP4, or MOV files are allowed.</p>';
            }

            // Define the upload directory in the plugin folder
            $plugin_dir = plugin_dir_path(__FILE__); // Path to the plugin directory
            $upload_dir = $plugin_dir . 'assets/uploads/stories/';
            
            // Create the upload directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate a unique filename to avoid overwriting
            $unique_file_name = wp_unique_filename($upload_dir, $file_name);
            $upload_path = $upload_dir . $unique_file_name;

            // Move the uploaded file to the plugin's assets/uploads/stories/ directory
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $file_upload = plugin_dir_url(__FILE__) . 'assets/uploads/stories/' . $unique_file_name; // Store the URL
            } else {
                return '<p class="error">File upload failed. Please try again.</p>';
            }
        }

        // Insert data into the custom table
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'name' => $name,
                'email' => $email,
                'social_media_handle' => $social_media_handle,
                'file_upload' => $file_upload,
                'status' => $status,
                'created_at' => $created_at,
                'approved_at' => null,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            return '<p>Thank you! Your story has been submitted and is awaiting approval.</p>';
        } else {
            return '<p class="error">There was an error submitting your story. Please try again.</p>';
        }
    }

    // Display the submission form
    ob_start();
    ?>
    <form method="post" action="" enctype="multipart/form-data">
        <div>
            <label for="name">Name:</label><br>
            <input type="text" id="name" name="name" required disabled style="width: 100%; max-width: 400px;" value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'nickname', true)); ?>">
        </div>
        <div>
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" required disabled style="width: 100%; max-width: 400px;" value="<?php echo esc_attr(get_userdata(get_current_user_id())->user_email); ?>">
        </div>
        <div>
            <label for="social_handles">Social Media Handles (optional):</label><br>
            <input type="text" id="social_handles" name="social_handles" placeholder="e.g., @username1, @username2" style="width: 100%; max-width: 400px;">
        </div>
        <div>
            <label for="upload_file">Upload Content (optional, image or video, max 1 MB):</label><br>
            <input type="file" id="upload_file" name="upload_file" accept=".jpg,.png,.mp4,.mov" style="width: 100%; max-width: 400px;">
        </div>
        <div>
            <input type="submit" name="submit_story" value="Submit Story" style="margin-top: 10px;">
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('purerawz_story_form', 'purerawz_story_submission_form_shortcode');