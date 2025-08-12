<?php
/*
Plugin Name: Context Aware Chatbot (Ollama LLM)
Plugin URI:  
Description: A context-aware chatbot plugin for WordPress using local Ollama LLM with detection, setup guidance, and model check.
Version:     1.3
Author:      Md Mahbubur Rahman
Author URI:  https://m-a-h-b-u-b.github.io 
License:     MIT 
Text Domain: context-chatbot
*/

if (!defined('ABSPATH')) exit;

function cca_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'cca_start_session');

function cca_enqueue_assets() {
    wp_enqueue_style('cca-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('cca-script', plugin_dir_url(__FILE__) . 'chatbot.js', array('jquery'), null, true);

    wp_localize_script('cca-script', 'cca_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('cca_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'cca_enqueue_assets');

function cca_chatbot_shortcode() {
    ob_start(); ?>
    <div id="cca-chatbot">
        <div id="cca-chat-window"></div>
        <input type="text" id="cca-user-input" placeholder="Type your message..." autocomplete="off" />
        <button id="cca-send-btn">Send</button>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('context_chatbot', 'cca_chatbot_shortcode');

function cca_handle_message() {
    check_ajax_referer('cca_nonce', 'nonce');

    $message = sanitize_text_field($_POST['message']);

    if (!isset($_SESSION['cca_context'])) {
        $_SESSION['cca_context'] = [];
    }

    $_SESSION['cca_context'][] = ['user' => $message];

    $response = cca_generate_response($message, $_SESSION['cca_context']);

    $_SESSION['cca_context'][] = ['bot' => $response];

    wp_send_json_success(['response' => $response]);
}
add_action('wp_ajax_cca_send_message', 'cca_handle_message');
add_action('wp_ajax_nopriv_cca_send_message', 'cca_handle_message');

function cca_generate_response($message, $context) {
    $history = "";
    foreach ($context as $entry) {
        if (isset($entry['user'])) {
            $history .= "User: " . $entry['user'] . "\n";
        } elseif (isset($entry['bot'])) {
            $history .= "Bot: " . $entry['bot'] . "\n";
        }
    }
    $prompt = $history . "User: " . $message . "\nBot:";

    $api_url = "http://localhost:11434/api/generate";
    $post_data = [
        "model" => "llama3",
        "prompt" => $prompt,
        "stream" => false
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return "Error: " . curl_error($ch);
    }
    curl_close($ch);

    $result = json_decode($response, true);
    if (isset($result['response'])) {
        return trim($result['response']);
    } else {
        return "Sorry, I couldn't process your request.";
    }
}

// Admin notice if Ollama or llama3 model is missing
function cca_check_ollama_installed() {
    if (!current_user_can('manage_options')) return;

    $ollama_path = trim(shell_exec("which ollama"));
    if (empty($ollama_path)) {
        $install_commands = "curl -fsSL https://ollama.com/install.sh | sh\nollama pull llama3";
        echo '<div class="notice notice-error"><p><strong>Ollama is not installed on this server.</strong></p>';
        echo '<p>The Context Aware Chatbot requires Ollama to run locally. Please install it using the commands below on your server terminal:</p>';
        echo '<textarea readonly style="width:100%;height:70px;">' . esc_textarea($install_commands) . '</textarea>';
        echo '<p><em>Note: You need shell access (SSH) to run these commands. This will not work on most shared hosting environments.</em></p>';
        echo '</div>';
    } else {
        // Check if llama3 model exists
        $models = shell_exec("ollama list 2>/dev/null");
        if (strpos($models, "llama3") === false) {
            echo '<div class="notice notice-warning"><p><strong>The llama3 model is not installed.</strong></p>';
            echo '<p>Please install it by running:</p>';
            echo '<textarea readonly style="width:100%;height:40px;">ollama pull llama3</textarea>';
            echo '</div>';
        }
    }
}
add_action('admin_notices', 'cca_check_ollama_installed');
