<?php
/**
 * File: affiliates-stories.php
 * Description: Handles story post type and submission form for the Face of Purerawz plugin.
 */

function purerawz_register_story_post_type() {
    // Check if the post type already exists
    if (!post_type_exists('purerawz_stories')) {
        $args = array(
            'public' => true,
            'label' => 'Purerawz Stories',
            'supports' => array('title', 'editor', 'author'),
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-format-status',
            'rewrite' => array('slug' => 'purerawz-stories'),
        );
        register_post_type('purerawz_stories', $args);
    }
}
add_action('init', 'purerawz_register_story_post_type');

function purerawz_story_submission_form_shortcode() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url() . '">log in</a> to submit a story.</p>';
    }
    // Process form submission
    if (isset($_POST['submit_story'])) {
        $story_title = sanitize_text_field($_POST['story_title']);
        $story_content = sanitize_textarea_field($_POST['story_content']);
        $social_link = !empty($_POST['social_link']) ? sanitize_url($_POST['social_link']) : '';

        // Insert the story as a draft
        $post_id = wp_insert_post(array(
            'post_title' => $story_title,
            'post_content' => $story_content,
            'post_type' => 'purerawz_stories',
            'post_status' => 'draft', // Saved as draft until approved
            'post_author' => get_current_user_id(),
        ));

        // Save social media link as post meta if provided
        if ($post_id && $social_link) {
            update_post_meta($post_id, 'social_media_link', $social_link);
        }

        // Return success message
        return '<p>Thank you! Your story has been submitted and is awaiting approval.</p>';
    }

    // Display the submission form
    ob_start();
    ?>
    <form method="post" action="">
        <div>
            <label for="story_title">Story Title:</label><br>
            <input type="text" id="story_title" name="story_title" required style="width: 100%; max-width: 400px;">
        </div>
        <div>
            <label for="story_content">Your Story:</label><br>
            <textarea id="story_content" name="story_content" required style="width: 100%; max-width: 400px; height: 150px;"></textarea>
        </div>
        <div>
            <label for="social_link">Social Media Link (optional):</label><br>
            <input type="url" id="social_link" name="social_link" placeholder="https://example.com" style="width: 100%; max-width: 400px;">
        </div>
        <div>
            <input type="submit" name="submit_story" value="Submit Story" style="margin-top: 10px;">
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('purerawz_story_form', 'purerawz_story_submission_form_shortcode');