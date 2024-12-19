<?php
/*
Plugin Name: AI Research Assistant
Description: AI Research Assistant transforms your website into an interactive, AI-powered knowledge hub, allowing visitors to ask questions and receive instant, accurate answers based on your website's content. By indexing all your site’s content, this plugin builds a responsive chat interface where users can engage with your information seamlessly, boosting engagement and reducing time spent searching for answers.
Version: 1.1
Author: AI Research Assistant
*/
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Add a rewrite rule for the virtual "assistant" page
function ai_research_add_rewrite_rules() {
    add_rewrite_rule('^assistant/?$', 'index.php?assistant_page=1', 'top');
}
add_action('init', 'ai_research_add_rewrite_rules');

require_once plugin_dir_path(__FILE__) . 'includes/pinecone-service.php';
require_once plugin_dir_path(__FILE__) . 'includes/file-ingestion.php';

function ai_research_admin_assets($hook) {
    // Only enqueue on the chat interface settings page
    if ($hook != 'toplevel_page_ai-research-assistant') {
        return;
    }
    wp_enqueue_media(); 
    wp_enqueue_script('ai-media-js', plugin_dir_url(__FILE__) . 'js/media-uploader.js', array('jquery'), null, true);
    wp_enqueue_style('ai-media-css', plugin_dir_url(__FILE__) . 'css/media-style.css');
}
add_action( 'admin_enqueue_scripts', 'ai_research_admin_assets' );

// Enqueue scripts and styles
function ai_research_assets() {
    wp_enqueue_style( 'ai-research-css', plugin_dir_url( __FILE__ ) . 'css/ai-research.css' );
    wp_enqueue_script( 'ai-research-js', plugin_dir_url( __FILE__ ) . 'js/ai-research.js', array('jquery'), null, true );
    wp_localize_script( 'ai-research-js', 'chatInterfaceAjax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'ai_research_nonce' ),
        'imagesUrl'  => plugins_url('images', __FILE__)
    ) );
}
add_action( 'wp_enqueue_scripts', 'ai_research_assets' );

// Register the custom query variable
function ai_research_query_vars($vars) {
    $vars[] = 'assistant_page';
    return $vars;
}
add_filter('query_vars', 'ai_research_query_vars');

// Load the custom template for the "assistant" page
function ai_research_load_assistant_template($template) {
    if (get_query_var('assistant_page') == 1) {
        $custom_template = plugin_dir_path(__FILE__) . 'templates/assistant-template.php';

        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('template_include', 'ai_research_load_assistant_template');

// Flush rewrite rules on activation and deactivation
function ai_research_flush_rewrite_rules() {
    ai_research_add_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ai_research_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');


function ai_research_settings() {
    add_option( 'ai_research_assistant_text');
    add_option( 'ai_research_assistant_heading');
    add_option( 'ai_research_assistant_footer_text');
    add_option( 'ai_research_assistant_placeholder_text' );

    register_setting( 'ai-research-assistant-options-group', 'ai_research_assistant_logo' );
    register_setting( 'ai-research-assistant-options-group', 'ai_research_assistant_text' );
    register_setting( 'ai-research-assistant-options-group', 'ai_research_assistant_heading' );
    register_setting( 'ai-research-assistant-options-group', 'ai_research_assistant_footer_text' );
    register_setting( 'ai-research-assistant-options-group', 'ai_research_assistant_placeholder_text' );
}
add_action( 'admin_init', 'ai_research_settings' );

function ai_research_register_settings() {
    register_setting( 'ai-research-assistant-options-group', 'ai_research_assistant_name' );
    register_setting( 'ai-research-assistant-options-group', 'ai_research_api_key', 'ai_research_api_key_sanitize' );
    register_setting( 'ai-research-assistant-options-group', 'ai_research_assistant_contact_us_link', 'esc_url_raw' );
}
add_action( 'admin_init', 'ai_research_register_settings' );

function ai_research_api_key_sanitize( $input ) {
    if ( ! empty( $input ) ) {
        return $input;
    } else {
        // Return existing API key if input is empty
        return get_option( 'ai_research_api_key' );
    }
}

function ai_research_menu() {
    add_menu_page(
        'AI Research Assistant',
        'AI Research Assistant',
        'manage_options',
        'ai-research-assistant',
        'ai_research_chat_settings_page',
        plugins_url( 'ai-research-assistant/images/juris.svg' ),
        10
    );

    add_submenu_page(
        'ai-research-assistant',
        'Chat Settings',
        'Chat Settings',
        'manage_options',
        'ai-research-assistant-chat-settings',
        'ai_research_chat_settings_page'
    );

    add_submenu_page(
        'ai-research-assistant',
        'File Settings',
        'File Settings',
        'manage_options',
        'ai-research-assistant-file-settings',
        'ai_research_file_settings_page'
    );

    // Remove the duplicate top-level menu item
    remove_submenu_page('ai-research-assistant', 'ai-research-assistant');
}
add_action( 'admin_menu', 'ai_research_menu' );

function ai_research_chat_settings_page() {
    $api_key = get_option('ai_research_api_key');
    $contact_us_link = get_option('ai_research_assistant_contact_us_link');
    ?>
    <div class="wrap ai-assist-sett">
        <h2>Chat Settings</h2>
        <div class="ai-assist-form">
            <form method="post" action="options.php">
                <?php
                settings_fields( 'ai-research-assistant-options-group' );
                do_settings_sections( 'ai-research-assistant-options-group' );
                ?>
                <table class="form-table">
                    <!-- Assistant Name Field -->
                    <tr valign="top">
                        <th scope="row">Assistant Name:</th>
                        <td>
                            <input type="text" name="ai_research_assistant_name" value="<?php echo esc_attr( get_option('ai_research_assistant_name') ); ?>" />
                        </td>
                    </tr>

                    <!-- API Key Field -->
                    <tr valign="top">
                        <th scope="row">API Key:</th>
                        <td>
                            <input type="password" name="ai_research_api_key" value="" placeholder="Enter API Key" />
                            <?php if ( ! empty( $api_key ) ) : ?>
                                <p class="description">Leave blank to keep the current API key.</p>
                            <?php else : ?>
                                <p class="description" style="color: red;">API Key Required.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Logo:</th>
                        <td>
                            <input type="button" class="button" id="upload_logo_button" value="Upload Logo">
                            <input type="hidden" name="ai_research_assistant_logo" id="ai_research_assistant_logo" value="<?php echo esc_attr( get_option( 'ai_research_assistant_logo' ) ); ?>">
                            <div id="logo_preview" style="margin-top: 10px;">
                                <?php if ( get_option( 'ai_research_assistant_logo' ) ) : ?>
                                    <img src="<?php echo esc_url( get_option( 'ai_research_assistant_logo' ) ); ?>" style="max-width: 200px;">
                                    <span class="remove-media" style="cursor: pointer; color: red;">&times;</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Heading:</th>
                        <td>
                            <textarea name="ai_research_assistant_heading" rows="3" cols="50"><?php echo esc_textarea( get_option( 'ai_research_assistant_heading' ) ); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top"> 
                        <th scope="row">Content:</th>
                        <td>
                            <textarea name="ai_research_assistant_text" rows="6" cols="50"><?php echo esc_textarea( get_option( 'ai_research_assistant_text' ) ); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Footer Text:</th>
                        <td>
                            <textarea name="ai_research_assistant_footer_text" rows="3" cols="50"><?php echo esc_textarea( get_option( 'ai_research_assistant_footer_text' ) ); ?></textarea>
                        </td>
                    </tr>
                    <!-- New Contact Us Link Field -->
                    <tr valign="top">
                        <th scope="row">Contact Us Link:</th>
                        <td>
                            <input type="url" name="ai_research_assistant_contact_us_link" value="<?php echo esc_attr( $contact_us_link ); ?>" placeholder="https://example.com/contact" />
                            <p class="description">URL for the Contact Us button.</p>
                        </td>
                    </tr>
                    <!-- New Placeholder Text Field -->
                    <tr valign="top">
                        <th scope="row">Chat Input Placeholder Text:</th>
                        <td>
                            <input type="text" name="ai_research_assistant_placeholder_text" 
                                value="<?php echo esc_attr( get_option( 'ai_research_assistant_placeholder_text', '' ) ); ?>" 
                                placeholder="How can we help you?" />
                        </td>
                    </tr>
                </table>
                <p><b>Note:</b> The Assistant page has been created automatically. You can view it by clicking <a href="<?php echo site_url('assistant'); ?>" target="_blank">Here.</a></p>
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
    <?php
}

function ai_research_file_settings_page() {
    $api_key = get_option( 'ai_research_api_key' );
    $assistant_name = get_option( 'ai_research_assistant_name' );

    $basic_site_scraper = new BasicSiteScraper( $api_key, $assistant_name );
    $basic_site_scraper->display_admin_page();
}

function send_chat_message() {
    $api_key = get_option('ai_research_api_key');
    $assistant_name = get_option('ai_research_assistant_name');

    $url = "https://prod-1-data.ke.pinecone.io/assistant/chat/{$assistant_name}";

    $headers = array(
        "Api-Key: {$api_key}",
        "Content-Type: application/json"
    );

    $user_message = sanitize_text_field( $_POST['message'] );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "messages": [
                {
                "role": "user",
                "content": "'.$user_message.'"
                }
            ]
        }',
        CURLOPT_HTTPHEADER => $headers
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $response_data = json_decode($response, true); 
    if (isset($response_data['message']['content'])) {
        $content = $response_data['message']['content'];
        $sentences = preg_split('/(?<=\.)\s+/', $content);
        $paragraphs = [];
        $current_paragraph = '';
        $word_count = 0;

        foreach ($sentences as $sentence) {
            $sentence_word_count = str_word_count($sentence);
            if ($word_count + $sentence_word_count > 80) {
                $paragraphs[] = $current_paragraph;
                $current_paragraph = $sentence;
                $word_count = $sentence_word_count;
            } else {
                $current_paragraph .= ($current_paragraph ? ' ' : '') . $sentence;
                $word_count += $sentence_word_count;
            }
        }
        if ($current_paragraph) {
            $paragraphs[] = $current_paragraph;
        }
        $image_url = plugins_url('images/content-logo.png', __FILE__);
        $formatted_content = '';

        foreach ($paragraphs as $paragraph) {
            $formatted_content .= '<p>' . htmlspecialchars($paragraph) . '</p>';
        }
        echo '<div class="answer-message" id="answerMessage">'
           . '<img src="' . $image_url . '" alt="Content Logo" />'
           . $formatted_content
           . '</div>';
    } else {
        echo 'No content found in the response.';
    }

    echo '<div class="copy-text-outer">
        <span class="copy-text-icon-svg" id="copyTextIcon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l140.1 0L400 115.9 400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-204.1c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-32-48 0 0 32c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l32 0 0-48-32 0z"></path></svg>
        </span>
        <span class="copy-text-icon">Copy this answer to your clipboard</span>
    </div>';
    echo '<script>
        document.getElementById("copyTextIcon").addEventListener("click", function() {
            var content = document.getElementById("answerMessage").innerText;
            var textArea = document.createElement("textarea");
            textArea.value = content;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);

            // Change the text next to the icon to "Text copied to clipboard!"
            var copyText = document.querySelector(".copy-text-icon");
            copyText.innerText = "Text copied to clipboard!";

            // Optionally, reset the text after a short delay (e.g., 2 seconds)
            setTimeout(function() {
                copyText.innerText = "Copy this answer to your clipboard";
            }, 2000); // Reset after 2 seconds
        });
    </script>';

    if (isset($response_data['citations']) && is_array($response_data['citations'])) {
        $source_urls = array();
        foreach ($response_data['citations'] as $citation) {
            if (isset($citation['references']) && is_array($citation['references'])) {
                foreach ($citation['references'] as $reference) {
                    if (isset($reference['file']['metadata']['source'])) {
                        $source = $reference['file']['metadata']['source'];
                        if ($source) {
                            $source_urls[] = $source;
                        }
                    }
                }
            }
        }
        // Remove duplicates
        $unique_sources = array_unique($source_urls);
        if (!empty($unique_sources)) {
            echo '<div class="jd-lists">';
            echo '<p class="jd-list-head">Where this answer came from:</p>';
            echo '<ul class="jd-unorder-list">';
            foreach ($unique_sources as $source) {
                echo '<li><a class="singedUrl" href="' . $source . '" target="_blank" download>' . $source . '</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    } else {
        echo 'No references found in the response.';
    }
}
add_action( 'wp_ajax_send_chat_message', 'send_chat_message' );
add_action( 'wp_ajax_nopriv_send_chat_message', 'send_chat_message' );

function allow_svg_uploads($mime_types) {
    $mime_types['svg'] = 'image/svg+xml'; // Add SVG support
    return $mime_types;
}
add_filter('upload_mimes', 'allow_svg_uploads');