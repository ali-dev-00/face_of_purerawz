<?php
/**
 * Display and process the story submission form
 */
function purerawz_story_submission_form_shortcode() {

    if (!is_user_logged_in()) {
        return '<p>Please <a href="#">log in</a> to submit a story.</p>';
    }


    if (isset($_POST['submit_story'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';

       
        $user_id = get_current_user_id();
        $name = get_user_meta($user_id, 'nickname', true) ? sanitize_text_field(get_user_meta($user_id, 'nickname', true)) : '';
        $email = get_userdata($user_id)->user_email ? sanitize_email(get_userdata($user_id)->user_email) : '';
        $social_media_handle = !empty($_POST['social_handles']) ? sanitize_text_field($_POST['social_handles']) : '';
        $file_upload = '';
        $status = 'pending'; // Default status as per DB
        $created_at = current_time('mysql');

       
        if (empty($name)) {
            return '<p class="error">Name is required.</p>';
        }
        if (empty($email) || !is_email($email)) {
            return '<p class="error">A valid email is required.</p>';
        }

       
        if (!empty($_FILES['upload_file']['name'])) {
            $file = $_FILES['upload_file'];
            $file_name = sanitize_file_name($file['name']);
            $file_size = $file['size']; // Size in bytes
            $file_type = $file['type'];

            if ($file_size > 1048576) {
                return '<p class="error">File size must not exceed 1 MB.</p>';
            }

           
            $allowed_types = array('image/jpeg', 'image/png', 'video/mp4', 'video/quicktime');
            if (!in_array($file_type, $allowed_types)) {
                return '<p class="error">Only JPG, PNG, MP4, or MOV files are allowed.</p>';
            }

            $plugin_dir = plugin_dir_path(__FILE__);
            $upload_dir = $plugin_dir . 'assets/uploads/stories/';
            

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $unique_file_name = wp_unique_filename($upload_dir, $file_name);
            $upload_path = $upload_dir . $unique_file_name;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $file_upload = plugin_dir_url(__FILE__) . 'assets/uploads/stories/' . $unique_file_name; 
            } else {
                return '<p class="error">File upload failed. Please try again.</p>';
            }
        }

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



/**
 * 
 * Display and process the story cards by shortcode
 * 
*/


function purerawz_approved_stories_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';
    $votes_table = $wpdb->prefix . 'purerawz_story_votes';

    // Fetch approved stories
    $stories = $wpdb->get_results(
        "SELECT s.*, 
                (SELECT COUNT(*) FROM $votes_table v WHERE v.story_id = s.id AND v.vote_type = 'like') AS likes,
                (SELECT COUNT(*) FROM $votes_table v WHERE v.story_id = s.id AND v.vote_type = 'dislike') AS dislikes
        FROM $table_name s
        WHERE s.status = 'approved'
        ORDER BY s.approved_at DESC"
    );

    if (!$stories) {
        return '<p>No approved stories found.</p>';
    }

    ob_start();
    ?>
    <div class="purerawz-story-cards">
        <?php foreach ($stories as $story): ?>
            <div class="purerawz-story-card" data-story-id="<?php echo esc_attr($story->id); ?>">
                <h3><?php echo esc_html($story->name); ?></h3>
                <p><strong>Email:</strong> <?php echo esc_html($story->email); ?></p>
                <?php if ($story->social_media_handle): ?>
                    <p><strong>Social Media:</strong> <?php echo esc_html($story->social_media_handle); ?></p>
                <?php endif; ?>
                <?php if ($story->file_upload): ?>
                    <p><strong>Content:</strong> <a href="<?php echo esc_url($story->file_upload); ?>" target="_blank">View File</a></p>
                <?php endif; ?>
                <p><small>Approved on: <?php echo esc_html($story->approved_at); ?></small></p>

                <!-- Voting Section -->
                <div class="vote-section">
                    <button class="vote-btn like-btn" data-vote="like">
                        👍 <span class="like-count"><?php echo esc_html($story->likes); ?></span>
                    </button>
                    <button class="vote-btn dislike-btn" data-vote="dislike">
                        👎 <span class="dislike-count"><?php echo esc_html($story->dislikes); ?></span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <style>
        .vote-btn {
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.2em;
            margin: 5px;
        }
        .voted {
            color: red;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('purerawz_approved_stories', 'purerawz_approved_stories_shortcode');
