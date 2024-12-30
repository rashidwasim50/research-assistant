<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the BasicSiteScraper class
require_once __DIR__ . '/classes/BasicSiteScraper.php';

// Access the global BasicSiteScraper instance
global $basic_site_scraper;

function verify_ajax_nonce() {    
    if (!isset($_POST['nonce'])) {
        error_log('Nonce not set in request');
        wp_send_json_error('Nonce not set');
        exit;
    }

    if (!wp_verify_nonce($_POST['nonce'], 'file_manager_nonce')) {
        error_log('Nonce verification failed');
        wp_send_json_error('Invalid nonce');
        exit;
    }

    error_log('Nonce verification passed');
}

function handle_ajax_upload_new_pages() {
    verify_ajax_nonce();
    global $basic_site_scraper;

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    if (!isset($_POST['selected_items']) || !is_array($_POST['selected_items'])) {
        wp_send_json_error('No files provided');
    }

    $selected_urls = $_POST['selected_items'];
    $files = [];

    foreach ($selected_urls as $url) {
        $post_id = url_to_postid($url);
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                $files[] = [
                    'url' => get_permalink($post),
                    'last_modified' => get_the_modified_date('Y-m-d H:i:s', $post),
                    'title' => get_the_title($post),
                    'clean_content' => wp_strip_all_tags(strip_shortcodes(apply_filters('the_content', $post->post_content))),
                ];
            }
        }
    }

    if (empty($files)) {
        wp_send_json_error('No valid files found for the selected items.');
    }

    try {
        foreach ($files as $page) {
            $metadata = [
                'lastmod' => $page['last_modified'],
                'source'  => $page['url'],
                'title'   => $page['title'],
            ];

            $basic_site_scraper->file_manager->upload_single_file($page['clean_content'], $metadata);
        }
        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

function handle_ajax_delete_files() {
    verify_ajax_nonce();
    global $basic_site_scraper;

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    if (!isset($_POST['selected_items']) || !is_array($_POST['selected_items'])) {
        wp_send_json_error('No files provided');
    }

    $selected_ids = $_POST['selected_items'];

    try {
        foreach ($selected_ids as $file_id) {
            $basic_site_scraper->pinecone_service->deleteFile($file_id);
        }
        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

function handle_ajax_delete_both_files() {
    verify_ajax_nonce();
    global $basic_site_scraper;

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    if (!isset($_POST['selected_items']) || !is_array($_POST['selected_items'])) {
        wp_send_json_error('No files provided');
    }

    $selected_ids = $_POST['selected_items'];

    try {
        foreach ($selected_ids as $file_id) {
            $basic_site_scraper->pinecone_service->deleteFile($file_id);
        }
        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

function handle_ajax_update_files() {
    verify_ajax_nonce();
    global $basic_site_scraper;

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    if (!isset($_POST['selected_items'])) {
        wp_send_json_error('No files selected');
    }

    $selected_items = $_POST['selected_items'];

    try {
        foreach ($selected_items as $item) {
            $data = json_decode(stripslashes($item), true);
            
            if (!$data || !isset($data['website']) || !isset($data['pinecone'])) {
                continue;
            }

            // Delete old file using stored Pinecone ID
            $basic_site_scraper->pinecone_service->deleteFile($data['pinecone']['id']);

            // Upload new file using stored website data
            $metadata = [
                'lastmod' => $data['website']['last_modified'],
                'source'  => $data['website']['url'],
                'title'   => $data['website']['title']
            ];
            
            $basic_site_scraper->file_manager->upload_single_file($data['website']['content'], $metadata);
        }

        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}


function handle_ajax_get_file_tables() {
    verify_ajax_nonce();
    global $basic_site_scraper;

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
        return;
    }

    try {
        $site_pages = $basic_site_scraper->file_manager->get_site_pages();
        $pinecone_files = $basic_site_scraper->pinecone_service->getPineconeFiles();

        // Get files only in site pages
        $files_only_in_site_pages = $basic_site_scraper->getFilesOnlyInSitePages($site_pages, $pinecone_files);
        
        // Get files only in Pinecone
        $files_only_in_pinecone = $basic_site_scraper->getFilesOnlyInPineconeFiles($site_pages, $pinecone_files);
        
        // Get files with newer timestamps
        $files_with_newer_timestamps = $basic_site_scraper->getFilesWithNewerWebsiteTimestamp($site_pages, $pinecone_files);

        // Render tables
        $response = array(
            'files_only_in_site_pages' => $basic_site_scraper->table_renderer->renderTable(
                $files_only_in_site_pages,
                "Files Only in Site Pages",
                "files-only-in-site-pages-form",
                "file-checkbox-upload"
            ),
            'files_only_in_pinecone' => $basic_site_scraper->table_renderer->renderDeleteTable(
                $files_only_in_pinecone,
                "Files Only in Pinecone",
                "files-only-in-pinecone-form",
                "file-checkbox-delete"
            ),
            'files_with_newer_timestamps' => $basic_site_scraper->table_renderer->renderComparisonTable(
                $files_with_newer_timestamps['newer_website_files'],
                $files_with_newer_timestamps['older_pinecone_files'],
                "Files with Newer Website Timestamp"
            )
        );

        wp_send_json_success($response);
    } catch (Exception $e) {
        error_log('Error in get_file_tables: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}

// Register AJAX handlers
add_action('wp_ajax_upload_new_pages', 'handle_ajax_upload_new_pages');
add_action('wp_ajax_delete_files', 'handle_ajax_delete_files');
add_action('wp_ajax_delete_both_files', 'handle_ajax_delete_both_files');
add_action('wp_ajax_update_files', 'handle_ajax_update_files');
add_action('wp_ajax_get_file_tables', 'handle_ajax_get_file_tables');

