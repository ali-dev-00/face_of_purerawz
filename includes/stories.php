<?php

/**
 * Display and process the story submission form
 */
function purerawz_story_submission_form_shortcode() {
    // Step 1: Non-logged-in user sees login prompt
    if (!is_user_logged_in()) {
        return '<p>Please <a href="/my-account">log in</a> to submit a story.</p>';
    }

    global $wpdb;
    $affiliates_table = $wpdb->prefix . 'affiliate_wp_affiliates';
    $stories_table = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';
    $user_id = get_current_user_id();
 
    // Step 2: Check if the user is an affiliate (approved or not)
    $affiliate_status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM $affiliates_table WHERE user_id = %d",
        $user_id
    ));
   

    if (!$affiliate_status) {
        // User is not an affiliate at all
        return '<p class="error">Only approved affiliates can submit a story. <a href="' . esc_url(home_url('/affiliate-area')) . '">Apply for an affiliate account</a> to get started.</p>';
    } elseif ($affiliate_status !== 'approved') {
        // User is an affiliate but not approved
        return '<p class="error">Only approved affiliates can submit a story. Your affiliate account is not yet approved. Please wait for approval or contact support.</p>';
    }

    // Step 3: Check if the user has already uploaded a story
    $existing_story = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $stories_table WHERE user_id = %d",
        $user_id
    ));

    if ($existing_story > 0) {
        return '<p class="notice">Your story is already uploaded.</p>';
    }

    // Step 4: Approved affiliate with no prior story can submit
    if (isset($_POST['submit_story'])) {
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

        // Retrieve affiliate_id from affiliate_wp_affiliates table
        $affiliate_id = $wpdb->get_var($wpdb->prepare(
            "SELECT affiliate_id FROM {$wpdb->prefix}affiliate_wp_affiliates WHERE user_id = %d",
            $user_id
        ));

        if (empty($affiliate_id)) {
            return '<p class="error">Affiliate ID not found. Please contact support.</p>';
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
            $stories_table,
            array(
                'user_id' => $user_id,
                'affiliate_id' => $affiliate_id,
                'name' => $name,
                'email' => $email,
                'social_media_handle' => $social_media_handle,
                'file_upload' => $file_upload,
                'status' => $status,
                'created_at' => $created_at,
                'approved_at' => null,
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            return '<p>Thank you! Your story has been submitted and is awaiting approval.</p>';
        } else {
            return '<p class="error">There was an error submitting your story. Please try again.</p>';
        }
    }

    // Display the form for approved affiliates with no prior story
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
 * Display and process the story cards by shortcode
 */
function purerawz_approved_stories_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';
    $votes_table = $wpdb->prefix . 'face_of_purerawz_story_votes';
    $affiliates_table = $wpdb->prefix . 'face_of_purerawz_affiliates';

    ob_start();
    ?>
    <div class="purerawz-story-cards" id="story-cards">
        <p>Loading stories...</p>
    </div>

    <script>
        function fetchStories() {
            fetch('<?php echo admin_url("admin-ajax.php?action=fetch_approved_stories"); ?>', {
                credentials: 'same-origin'
            })
                .then(response => response.text())
                .then(data => {
                    console.log("Stories data:", data);
                    document.getElementById("story-cards").innerHTML = data;
                })
                .catch(error => console.error("Error fetching stories:", error));
        }

        // Function to cast a vote for a story
        function castStoryVote(storyId, voteType) {
            const userIp = '<?php echo esc_js($_SERVER['REMOTE_ADDR']); ?>'; // Get user IP
            fetch('<?php echo admin_url("admin-ajax.php?action=cast_story_vote"); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `story_id=${storyId}&vote_type=${voteType}&user_ip=${encodeURIComponent(userIp)}&nonce=<?php echo wp_create_nonce('story_vote_nonce'); ?>`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchStories(); // Refresh stories on successful vote
                        console.log('Vote cast successfully at ' + new Date().toLocaleTimeString());
                    } else {
                        console.error('Vote failed:', data.data.message);
                    }
                })
                .catch(error => console.error("Error casting vote:", error));
        }

        fetchStories();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('purerawz_approved_stories', 'purerawz_approved_stories_shortcode');

/**
 * AJAX handler to fetch approved stories from approved affiliates
 */
function fetch_approved_stories() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'face_of_purerawz_affiliate_stories';
    $votes_table = $wpdb->prefix . 'face_of_purerawz_story_votes';
    $affiliates_table = $wpdb->prefix . 'face_of_purerawz_affiliates';

    // Fetch approved stories from approved affiliates, ordered by approved_at (latest first)
    $stories = $wpdb->get_results(
        "SELECT s.*, 
                (SELECT COUNT(*) FROM $votes_table v WHERE v.story_id = s.id AND v.vote_type = 'like') AS like_count,
                (SELECT COUNT(*) FROM $votes_table v WHERE v.story_id = s.id AND v.vote_type = 'dislike') AS dislike_count
         FROM $table_name s
         INNER JOIN $affiliates_table a ON s.affiliate_id = a.affiliate_id
         WHERE s.status = 'approved' AND a.status = 'approved'
         ORDER BY s.approved_at DESC"
    );

    if (!$stories) {
        echo '<p>No approved stories from approved affiliates found.</p>';
        wp_die();
    }

    foreach ($stories as $story) {
        $like_count = intval($story->like_count);
        $dislike_count = intval($story->dislike_count);

        // Check if user has voted
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $has_liked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $votes_table WHERE story_id = %d AND user_ip = %s AND vote_type = 'like'",
            $story->id, $user_ip
        )) > 0;
        $has_disliked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $votes_table WHERE story_id = %d AND user_ip = %s AND vote_type = 'dislike'",
            $story->id, $user_ip
        )) > 0;

        // Determine if buttons should be disabled
        $like_disabled = $has_liked ? 'disabled' : '';
        $dislike_disabled = $has_disliked ? 'disabled' : '';

        ?>
        <div class="purerawz-story-card">
            <h3><?php echo esc_html($story->name); ?></h3>
            <p><strong>Email:</strong> <?php echo esc_html($story->email); ?></p>
            <?php if ($story->social_media_handle): ?>
                <p><strong>Social Media:</strong> <?php echo esc_html($story->social_media_handle); ?></p>
            <?php endif; ?>
            <?php if ($story->file_upload): ?>
                <p><strong>Content:</strong> <a href="<?php echo esc_url($story->file_upload); ?>" target="_blank">View File</a></p>
            <?php endif; ?>
            <p><small>Approved on: <?php echo esc_html($story->approved_at); ?></small></p>
            <p>
                <strong>Likes:</strong> <?php echo esc_html($like_count); ?> | 
                <strong>Dislikes:</strong> <?php echo esc_html($dislike_count); ?>
            </p>
            <div class="vote-buttons">
                <button class="vote-button like-btn <?php echo $has_liked ? 'voted' : ''; ?>" 
                        onclick="castStoryVote(<?php echo esc_js($story->id); ?>, 'like')"
                        <?php echo $like_disabled; ?>>
                    👍
                </button>
                <button class="vote-button dislike-btn <?php echo $has_disliked ? 'voted' : ''; ?>" 
                        onclick="castStoryVote(<?php echo esc_js($story->id); ?>, 'dislike')"
                        <?php echo $dislike_disabled; ?>>
                    👎
                </button>
            </div>
        </div>
        <?php
    }

    wp_die();
}
add_action('wp_ajax_fetch_approved_stories', 'fetch_approved_stories');
add_action('wp_ajax_nopriv_fetch_approved_stories', 'fetch_approved_stories');

/**
 * AJAX handler to cast a vote for a story
 */
function cast_story_vote() {
    check_ajax_referer('story_vote_nonce', 'nonce');

    global $wpdb;
    $votes_table = $wpdb->prefix . 'face_of_purerawz_story_votes';

    $story_id = intval($_POST['story_id']);
    $vote_type = sanitize_text_field($_POST['vote_type']);
    $user_ip = sanitize_text_field($_POST['user_ip']);

    if (!in_array($vote_type, ['like', 'dislike'])) {
        wp_send_json_error(array('message' => 'Invalid vote type'));
        wp_die();
    }

    // Check if user has already voted for this story
    $existing_vote = $wpdb->get_row($wpdb->prepare(
        "SELECT vote_type FROM $votes_table WHERE story_id = %d AND user_ip = %s",
        $story_id, $user_ip
    ));

    if ($existing_vote) {
        // If user changes vote, update it; otherwise, prevent duplicate
        if ($existing_vote->vote_type === $vote_type) {
            wp_send_json_error(array('message' => 'You have already voted this way'));
            wp_die();
        } else {
            $wpdb->update(
                $votes_table,
                array('vote_type' => $vote_type),
                array('story_id' => $story_id, 'user_ip' => $user_ip),
                array('%s'),
                array('%d', '%s')
            );
        }
    } else {
        $wpdb->insert(
            $votes_table,
            array(
                'story_id' => $story_id,
                'vote_type' => $vote_type,
                'user_ip' => $user_ip,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }

    wp_send_json_success();
    wp_die();
}
add_action('wp_ajax_cast_story_vote', 'cast_story_vote');
add_action('wp_ajax_nopriv_cast_story_vote', 'cast_story_vote');
