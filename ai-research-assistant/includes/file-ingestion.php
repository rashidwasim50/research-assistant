<?php
/*
Plugin Name: Research Assistant - File Manipulator
Description: Handles all of the web scraping and file manipulation for the Research Assistant.
Version: 1.2
Author: Chris
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the PineconeService class
require_once __DIR__ . '/pinecone-service.php';

class BasicSiteScraper {
    private $pinecone_api_key;
    private $assistant_name;

    private $pinecone_service;

    public function __construct( $pinecone_api_key, $assistant_name ) {
        $this->pinecone_api_key = $pinecone_api_key;
        $this->assistant_name = $assistant_name;

        $this->pinecone_service = new PineconeService(
            $this->pinecone_api_key,
            $this->assistant_name
        );

        // AJAX handlers
        add_action('wp_ajax_upload_new_pages', [$this, 'handle_ajax_upload_new_pages']);
        add_action('wp_ajax_delete_files', [$this, 'handle_ajax_delete_files']);
        add_action('wp_ajax_update_files', [$this, 'handle_ajax_update_files']);

        // Remove the "Thank you for creating with WordPress" footer text
        add_filter('admin_footer_text', [$this, 'custom_admin_footer_text']);
    }

    public function custom_admin_footer_text() {
        return '';
    }

    private function create_temporary_file($content) {
        $plugin_dir = plugin_dir_path(__FILE__);
        $temp_dir = $plugin_dir . 'temp/';

        // Create the temporary directory if it doesn't exist
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }

        $temp_filename = uniqid() . '.txt';
        $temp_file_path = $temp_dir . $temp_filename;
        file_put_contents($temp_file_path, $content);

        return $temp_file_path;
    }

    public function render_chatbot_settings_page()
    {
        echo '<div class="wrap"><h1>Chatbot Settings</h1><p>For chatbot settings</p></div>';
    }

    public function display_admin_page() {
        echo '<div class="wrap"><h1>Research Assistant - Knowledge Files</h1>';

        echo '<style>
            .scrollable-table-body {
                display: block;
                max-height: 200px; /* Adjust the height as needed */
                overflow-y: scroll;
            }
            .scrollable-table-body tr {
                display: table;
                width: 100%;
                table-layout: fixed;
            }
            .wp-list-table thead, .wp-list-table tbody tr {
                display: table;
                width: 100%;
                table-layout: fixed;
            }
        </style>';

        try {
            // Retrieve all post types
            $post_types = $this->get_all_post_types();
            $this->display_post_type_selector($post_types);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $selected_post_types = isset($_POST['post_types']) ? $_POST['post_types'] : [];
                $site_pages = $this->get_site_pages($selected_post_types);

                $pinecone_files = $this->pinecone_service->getPineconeFiles();

                $files_only_in_site_pages = $this->getFilesOnlyInSitePages($site_pages, $pinecone_files);
                $this->displayTable($files_only_in_site_pages, "Files Only in Site Pages");
                $button_disabled_upload = empty($files_only_in_site_pages) ? 'disabled' : '';
                echo '<button id="upload-new-pages" ' . $button_disabled_upload . '>Upload New Pages</button>';

                $files_only_in_pinecone = $this->getFilesOnlyInPineconeFiles($site_pages, $pinecone_files);
                $this->displayTableDelete($files_only_in_pinecone, "Files Only in Pinecone");
                $button_disabled_delete = empty($files_only_in_pinecone) ? 'disabled' : '';
                echo '<button id="delete-files" ' . $button_disabled_delete . '>Delete Files</button>';

                $files_with_newer_timestamps = $this->getFilesWithNewerWebsiteTimestamp($site_pages, $pinecone_files);
                $files_with_newer_website_timestamp = $files_with_newer_timestamps['newer_website_files'];
                $files_with_older_pinecone_timestamp = $files_with_newer_timestamps['older_pinecone_files'];
                $this->displayCompTable($files_with_newer_website_timestamp, $files_with_older_pinecone_timestamp, "Files with Newer Website Timestamp");
                $button_disabled_update = empty($files_with_newer_website_timestamp) ? 'disabled' : '';
                echo '<button id="update-files" ' . $button_disabled_update . '>Update Files</button>';

                // Add the JavaScript to handle checkbox selection
                echo '<script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function() {
                        const checkboxesUpload = document.querySelectorAll(".file-checkbox-upload");
                        const uploadButton = document.getElementById("upload-new-pages");

                        function updateButtonStateUpload() {
                            const anyChecked = Array.from(checkboxesUpload).some(checkbox => checkbox.checked);
                            uploadButton.disabled = !anyChecked;
                        }

                        checkboxesUpload.forEach(checkbox => {
                            checkbox.addEventListener("change", updateButtonStateUpload);
                        });

                        // Initial check
                        updateButtonStateUpload();

                        const checkboxesDelete = document.querySelectorAll(".file-checkbox-delete");
                        const deleteButton = document.getElementById("delete-files");

                        function updateButtonStateDelete() {
                            const anyChecked = Array.from(checkboxesDelete).some(checkbox => checkbox.checked);
                            deleteButton.disabled = !anyChecked;
                        }

                        checkboxesDelete.forEach(checkbox => {
                            checkbox.addEventListener("change", updateButtonStateDelete);
                        });

                        // Initial check
                        updateButtonStateDelete();

                        const checkboxesUpdate = document.querySelectorAll(".file-checkbox-update");
                        const updateButton = document.getElementById("update-files");

                        function updateButtonStateUpdate() {
                            const anyChecked = Array.from(checkboxesUpdate).some(checkbox => checkbox.checked);
                            updateButton.disabled = !anyChecked;
                        }

                        checkboxesUpdate.forEach(checkbox => {
                            checkbox.addEventListener("change", updateButtonStateUpdate);
                        });

                        // Initial check
                        updateButtonStateUpdate();
                    });
                </script>';
            }

        } catch (Exception $e) {
            echo '<p style="color:red;">Error: ' . $e->getMessage() . '</p>';
        }

        echo '</div>';

        // Add the AJAX script
        echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                const ajaxurl = "' . admin_url('admin-ajax.php') . '";

                document.getElementById("upload-new-pages").addEventListener("click", function() {
                    this.disabled = true;
                    fetch(ajaxurl + "?action=upload_new_pages", {
                        method: "POST",
                        body: new FormData(document.getElementById("files-only-in-site-pages-form"))
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            alert("Files uploaded successfully!");
                        } else {
                            alert("Error uploading files: " + data.data);
                        }
                        this.disabled = false;
                    }).catch(error => {
                        alert("Error uploading files: " + error);
                        this.disabled = false;
                    });
                });

                document.getElementById("delete-files").addEventListener("click", function() {
                    this.disabled = true;
                    fetch(ajaxurl + "?action=delete_files", {
                        method: "POST",
                        body: new FormData(document.getElementById("files-only-in-pinecone-form"))
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            alert("Files deleted successfully!");
                        } else {
                            alert("Error deleting files: " + data.data);
                        }
                        this.disabled = false;
                    }).catch(error => {
                        alert("Error deleting files: " + error);
                        this.disabled = false;
                    });
                });

                document.getElementById("update-files").addEventListener("click", function() {
                    this.disabled = true;
                    fetch(ajaxurl + "?action=update_files", {
                        method: "POST",
                        body: new FormData(document.getElementById("files-with-newer-timestamps-form"))
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            alert("Files updated successfully!");
                        } else {
                            alert("Error updating files: " + data.data);
                        }
                        this.disabled = false;
                    }).catch(error => {
                        alert("Error updating files: " + error);
                        this.disabled = false;
                    });
                });
            });
        </script>';
    }

    private function upload_single_file($content, $metadata) {
        $temp_file_path = $this->create_temporary_file($content);

        try {
            $this->pinecone_service->uploadFileToPinecone($temp_file_path, $metadata);
        } catch (Exception $e) {
            error_log("Error uploading page to Pinecone: " . $e->getMessage());
        }

        unlink($temp_file_path);
    }

    public function handle_ajax_upload_new_pages() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized user');
        }

        $files = json_decode(file_get_contents('php://input'), true)['files'];

        try {
            foreach ($files as $page) {
                $metadata = [
                    'lastmod' => $page['last_modified'],
                    'source' => $page['url'],
                ];

                $this->upload_single_file($page['clean_content'], $metadata);
            }
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handle_ajax_delete_files() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized user');
        }

        $files = json_decode(file_get_contents('php://input'), true)['files'];

        try {
            foreach ($files as $file) {
                $file_id = $file['id'];
                $this->pinecone_service->deleteFile($file_id);
            }
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handle_ajax_update_files() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized user');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $newer_files = $data['newer_files'];
        $older_files = $data['older_files'];

        try {
            // Delete older files
            foreach ($older_files as $file) {
                $file_id = $file['id'];
                $this->pinecone_service->deleteFile($file_id);
            }

            // Upload newer files
            foreach ($newer_files as $page) {
                $metadata = [
                    'lastmod' => $page['last_modified'],
                    'source' => $page['url'],
                ];

                $this->upload_single_file($page['clean_content'], $metadata);
            }
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }


    public function get_all_post_types() {
        // Get all registered post types
        $post_types = get_post_types([], 'objects');
        return $post_types;
    }

    public function display_post_type_selector($post_types) {
        // Get selected post types from the request
        $selected_post_types = isset($_POST['post_types']) ? $_POST['post_types'] : [];

        echo '<h2>Select Post Types</h2>';
        echo '<form method="post">';
        echo '<div style="display: flex; flex-wrap: wrap;">';
        foreach ($post_types as $post_type) {
            $checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
            echo '<div style="flex: 1 0 25%; box-sizing: border-box; padding: 5px;">';
            echo '<label>';
            echo '<input type="checkbox" name="post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . '>';
            echo esc_html($post_type->label);
            echo '</label>';
            echo '</div>';
        }
        echo '</div>';
        echo '<input type="submit" value="Filter">';
        echo '</form>';
    }

    public function get_site_pages($selected_post_types = []) {
        // Default to all post types if none are selected
        if (empty($selected_post_types)) {
            $selected_post_types = get_post_types([], 'names');
        }
        echo 'Post types: ' . implode(', ', $selected_post_types) . '<br>';
        $pages = [];

        $args = [
            'post_type'      => $selected_post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pages[] = [
                    'url'           => get_permalink(),
                    'last_modified' => get_the_modified_date('Y-m-d H:i:s'),
                    'content'       => get_the_content(),
                    'clean_content' => wp_strip_all_tags(strip_shortcodes(apply_filters("the_content", get_the_content()))),
                ];
            }
        }

        wp_reset_postdata();
        return $pages;
    }

    private function display_site_pages($site_pages) {
        echo '<h2>Site Pages</h2>';
        echo '<table class="wp-list-table widefat">';
        echo '<thead><tr><th>URL</th><th>Last Modified</th></tr></thead>';
        echo '<tbody>';
        foreach ($site_pages as $page) {
            echo '<tr><td>' . $page['url'] . '</td><td>' . $page['last_modified'] . '</td></tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    private function display_pinecone_files($pinecone_files) {
        echo '<h2>Pinecone Files</h2>';
        echo '<table class="wp-list-table widefat">';
        echo '<thead><tr><th>URL</th><th>Last Modified</th></tr></thead>';
        echo '<tbody>';
        foreach ($pinecone_files['files'] as $file) {
            echo '<tr>';
            echo '<td>' . $file['metadata']['source'] . '</td>';
            echo '<td>' . $file['metadata']['lastmod'] . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    private function getFilesOnlyInSitePages($site_pages, $pinecone_files) {
        $pinecone_file_sources = array_column($pinecone_files['files'], 'metadata', 'source');

        $files_only_in_site_pages = [];

        foreach ($site_pages as $page) {
            $page_url = $page['url'];
            $found = false;

            foreach ($pinecone_files['files'] as $pinecone_file) {
                if (isset($pinecone_file['metadata']['source']) && $pinecone_file['metadata']['source'] === $page_url) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $files_only_in_site_pages[] = $page;
            }
        }

        return $files_only_in_site_pages;
    }

    private function getFilesOnlyInPineconeFiles($site_pages, $pinecone_files) {
        $site_page_urls = array_column($site_pages, 'url');
        $files_only_in_pinecone = [];

        foreach ($pinecone_files['files'] as $file) {
            if (!in_array($file['metadata']['source'], $site_page_urls)) {
                $files_only_in_pinecone[] = $file;
            }
        }

        return $files_only_in_pinecone; 
    }

    private function displayTable($data, $title) {
        echo '<h2>' . $title . '</h2>';
        echo '<form id="files-only-in-site-pages-form">';
        echo '<table class="wp-list-table widefat fixed">';
        echo '<thead><tr><th style="width: 5%;">Select</th><th style="width: 45%;">Source</th><th style="width: 50%;">Last Modified</th></tr></thead>';
        echo '<tbody class="scrollable-table-body">';
        foreach ($data as $item) {
            echo '<tr style="height: 40px;">';
            echo '<td style="padding: 10px;"><input type="checkbox" class="file-checkbox-upload" name="selected_items[]" value="' . (isset($item['name']) ? $item['metadata']['source'] : $item['url']) . '"></td>';
            if (isset($item['name'])) {
                echo '<td style="padding: 10px;">' . $item['metadata']['source'] . '</td>';
                echo '<td style="padding: 10px;">' . $item['metadata']['lastmod'] . '</td>';
            } else {
                echo '<td style="padding: 10px;">' . $item['url'] . '</td>';
                echo '<td style="padding: 10px;">' . $item['last_modified'] . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</form>';
    }

    private function displayTableDelete($data, $title) {
        echo '<h2>' . $title . '</h2>';
        echo '<form id="files-only-in-pinecone-form">';
        echo '<table class="wp-list-table widefat fixed">';
        echo '<thead><tr><th style="width: 5%;">Select</th><th style="width: 45%;">Source</th><th style="width: 50%;">Last Modified</th></tr></thead>';
        echo '<tbody class="scrollable-table-body">';
        foreach ($data as $item) {
            echo '<tr style="height: 40px;">';
            echo '<td style="padding: 10px;"><input type="checkbox" class="file-checkbox-delete" name="selected_items[]" value="' . (isset($item['name']) ? $item['metadata']['source'] : $item['url']) . '"></td>';
            if (isset($item['name'])) {
                echo '<td style="padding: 10px;">' . $item['metadata']['source'] . '</td>';
                echo '<td style="padding: 10px;">' . $item['metadata']['lastmod'] . '</td>';
            } else {
                echo '<td style="padding: 10px;">' . $item['url'] . '</td>';
                echo '<td style="padding: 10px;">' . $item['last_modified'] . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</form>';
    }

    private function displayCompTable($website_files, $pinecone_files, $title) {
        echo '<h2>' . $title . '</h2>';
        echo '<form id="files-with-newer-timestamps-form">';
        echo '<table class="wp-list-table widefat fixed">';
        echo '<thead><tr><th style="width: 5%;">Select</th><th style="width: 35%;">Source</th><th style="width: 30%;">Last Modified - Website</th><th style="width: 30%;">Last Modified - Pinecone</th></tr></thead>';
        echo '<tbody class="scrollable-table-body">';
        foreach ($website_files as $index => $website_file) {
            $pinecone_file = $pinecone_files[$index];
            echo '<tr style="height: 40px;">';
            echo '<td style="padding: 10px;"><input type="checkbox" class="file-checkbox-update" name="selected_items[]" value="' . $website_file['url'] . '"></td>';
            echo '<td style="padding: 10px;">' . $website_file['url'] . '</td>';
            echo '<td style="padding: 10px;">' . $website_file['last_modified'] . '</td>';
            echo '<td style="padding: 10px;">' . $pinecone_file['metadata']['lastmod'] . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</form>';
    }

    private function getFilesWithNewerWebsiteTimestamp($site_pages, $pinecone_files) {
        $newer_website_files = [];
        $older_pinecone_files = [];

        foreach ($site_pages as $page) {
            foreach ($pinecone_files['files'] as $file) {
                if (isset($file['metadata']['source']) && $file['metadata']['source'] === $page['url']) {
                    if (strtotime($page['last_modified']) > strtotime($file['metadata']['lastmod'])) {
                        $newer_website_files[] = $page;
                        $older_pinecone_files[] = $file;
                    }
                }
            }
        }

        return [
            'newer_website_files' => $newer_website_files,
            'older_pinecone_files' => $older_pinecone_files
        ];
    }

}