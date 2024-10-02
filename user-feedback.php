<?php
/*
Plugin Name: Simple User Feedback
Description: A simple plugin to collect user feedback on posts.
Version: 1.0
Author: Mahnoor
*/


function suf_create_feedback_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_feedback';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        user_name varchar(100) NOT NULL,
        user_email varchar(100),
        feedback_text text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'suf_create_feedback_table');

function suf_feedback_form($atts) {
    ob_start(); 
    ?>
    <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" class="suf-feedback-form">
        <div class="form-group">
            <label for="user_name">Your Name:</label>
            <input type="text" id="user_name" name="user_name" placeholder="Your Name" required>
        </div>
        <div class="form-group">
            <label for="user_email">Your Email (optional):</label>
            <input type="email" id="user_email" name="user_email" placeholder="Your Email">
        </div>
        <div class="form-group">
            <label for="feedback_text">Your Feedback:</label>
            <textarea id="feedback_text" name="feedback_text" placeholder="Your Feedback" required></textarea>
        </div>
        <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>">
        <input type="submit" name="suf_submit_feedback" value="Submit Feedback" class="submit-button">
    </form>
    <?php
    if (isset($_GET['feedback_status'])) {
        if ($_GET['feedback_status'] == 'success') {
            echo '<p class="feedback-success">Thank you for your feedback!</p>';
        } elseif ($_GET['feedback_status'] == 'error') {
            echo '<p class="feedback-error">There was an error submitting your feedback. Please try again.</p>';
        }
    }
    return ob_get_clean();
}

add_shortcode('suf_feedback_form', 'suf_feedback_form');



function suf_feedback_form_styles() {
    echo '<style>
        .suf-feedback-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .submit-button {
            background-color: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .submit-button:hover {
            background-color: #005177;
        }
        .feedback-success {
            color: green;
            margin-top: 10px;
        }
        .feedback-error {
            color: red;
            margin-top: 10px;
        }
    </style>';
}
add_action('wp_head', 'suf_feedback_form_styles');


function suf_handle_feedback_submission() {
    if (isset($_POST['suf_submit_feedback'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_feedback';

        $post_id = intval($_POST['post_id']);
        $user_name = sanitize_text_field($_POST['user_name']);
        $user_email = sanitize_email($_POST['user_email']);
        $feedback_text = sanitize_textarea_field($_POST['feedback_text']);

        $result = $wpdb->insert($table_name, [
            'post_id' => $post_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'feedback_text' => $feedback_text,
        ]);

        if ($result) {
            wp_redirect(add_query_arg('feedback_status', 'success', $_SERVER['REQUEST_URI']));
            exit;
        } else {
            wp_redirect(add_query_arg('feedback_status', 'error', $_SERVER['REQUEST_URI']));
            exit;
        }
    }
}
add_action('init', 'suf_handle_feedback_submission');

function suf_admin_menu() {
    add_menu_page('User Feedback', 'User Feedback', 'manage_options', 'user-feedback', 'suf_display_feedback');
}
add_action('admin_menu', 'suf_admin_menu');

function suf_display_feedback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_feedback';
    $feedbacks = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap">';
    echo '<h1>User Feedback</h1>';
    
    if ($feedbacks) {
        echo '<table class="suf-feedback-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Name</th>';
        echo '<th>Email</th>';
        echo '<th>Feedback</th>';
        echo '<th>Date</th>';
        echo '<th>Time</th>'; // New column for time
        echo '<th>Action</th>'; // Action column
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($feedbacks as $feedback) {
            $created_at = new DateTime($feedback->created_at);
            echo '<tr>';
            echo '<td>' . esc_html($feedback->user_name) . '</td>';
            echo '<td>' . esc_html($feedback->user_email) . '</td>';
            echo '<td>' . esc_html($feedback->feedback_text) . '</td>';
            echo '<td>' . esc_html($created_at->format('Y-m-d')) . '</td>'; // Display date
            echo '<td>' . esc_html($created_at->format('H:i:s')) . '</td>'; // Display time
            echo '<td><a href="' . esc_url(add_query_arg(['action' => 'delete', 'id' => $feedback->id], $_SERVER['REQUEST_URI'])) . '" onclick="return confirm(\'Are you sure you want to delete this feedback?\');">Delete</a></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No feedback available.</p>';
    }
    
    echo '</div>';
}


function suf_feedback_table_styles() {
    echo '<style>
        .suf-feedback-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .suf-feedback-table th, .suf-feedback-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .suf-feedback-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        .suf-feedback-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .suf-feedback-table tr:hover {
            background-color: #f1f1f1;
        }
    </style>';
}
add_action('admin_head', 'suf_feedback_table_styles');



function suf_handle_feedback_deletion() {
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_feedback';
        $feedback_id = intval($_GET['id']);

        // Delete the feedback
        $wpdb->delete($table_name, ['id' => $feedback_id]);

        // Redirect back to the feedback page
        wp_redirect(remove_query_arg(['action', 'id'], $_SERVER['REQUEST_URI']));
        exit;
    }
}
add_action('admin_init', 'suf_handle_feedback_deletion');
