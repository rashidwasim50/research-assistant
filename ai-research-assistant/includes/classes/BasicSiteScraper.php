<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the PineconeService class
require_once __DIR__ . '/../services/pinecone-service.php';
require_once __DIR__ . '/../traits/file-comparison.php';
require_once __DIR__ . '/../traits/post-type-manager.php';
require_once __DIR__ . '/../interfaces/table-renderer-interface.php';
require_once __DIR__ . '/table-renderer.php';
require_once __DIR__ . '/file-manager.php';

class BasicSiteScraper {
    use FileComparison;
    use PostTypeManager;

    private string $pinecone_api_key;
    private string $assistant_name;
    public PineconeService $pinecone_service;
    public FileManager $file_manager;
    public TableRendererInterface $table_renderer;

    public function __construct( 
        $pinecone_api_key, 
        $assistant_name, 
        ?TableRendererInterface $tableRenderer = null
    ) 
    {
        $this->pinecone_api_key = $pinecone_api_key;
        $this->assistant_name = $assistant_name;
        $this->table_renderer = $tableRenderer ?? new TableRenderer();
        
        $this->pinecone_service = new PineconeService(
            $this->pinecone_api_key,
            $this->assistant_name
        );

        $this->file_manager = new FileManager($this->pinecone_service);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_fileingestion_styles']);
        add_filter('admin_footer_text', [$this, 'custom_admin_footer_text']);
    }

    public function custom_admin_footer_text() {
        return '';
    }

    public function render_chatbot_settings_page()
    {
        echo '<div class="wrap"><h1>Chatbot Settings</h1><p>For chatbot settings</p></div>';
    }

    public function enqueue_fileingestion_styles($hook) {
        // Only load on our plugin's admin page
        if ($hook != 'toplevel_page_ai-research-assistant' && 
            $hook != 'ai-research-assistant_page_ai-research-assistant-file-settings') {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'ai-research-admin-tables', 
            plugins_url('/css/file-ingestion-tables.css', dirname(dirname(__FILE__)))
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'ai-research-file-manager',
            plugins_url('/js/admin/file-manager.js', dirname(dirname(__FILE__))),
            array('jquery'),
            '1.0.0',
            true
        );

        wp_enqueue_script(
            'ai-research-table-sorting',
            plugins_url('/js/table-sorting.js', dirname(dirname(__FILE__))),
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize script
        wp_localize_script(
            'ai-research-file-manager',
            'fileManagerAjax',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('file_manager_nonce')
            )
        );
    }

    public function display_admin_page() {
        echo '<div class="wrap"><h1>Research Assistant - Knowledge Files</h1>';

        try {
            // Retrieve all post types
            $post_types = $this->get_all_post_types();
            $this->display_post_type_selector($post_types);

            // Get saved selected post types
            $selected_post_types = get_option('ai_research_selected_post_types', []);

            if (!empty($selected_post_types)) {

                $site_pages = $this->file_manager->get_site_pages();
                error_log('Site pages count: ' . count($site_pages));

                $pinecone_files = $this->pinecone_service->getPineconeFiles();
                error_log('Pinecone files count: ' . count($pinecone_files['files']));

                $files_only_in_site_pages = $this->getFilesOnlyInSitePages($site_pages, $pinecone_files);
                echo '<div id="files-only-in-site-pages-table">';
                echo $this->table_renderer->renderTable(
                    $files_only_in_site_pages, 
                    "Files Only in Site Pages", 
                    "files-only-in-site-pages-form", 
                    "file-checkbox-upload"
                );
                echo '</div>';
                $button_disabled_upload = empty($files_only_in_site_pages) ? 'disabled' : '';
                echo '<button id="upload-new-pages" ' . $button_disabled_upload . '>Upload New Pages</button>';

                $files_only_in_pinecone = $this->getFilesOnlyInPineconeFiles($site_pages, $pinecone_files);
                echo '<div id="files-only-in-pinecone-files-table">';
                echo $this->table_renderer->renderDeleteTable(
                    $files_only_in_pinecone, 
                    "Files Only in Pinecone",
                    "files-only-in-pinecone-form",
                    "file-checkbox-delete",
                );
                echo '</div>';
                $button_disabled_delete = empty($files_only_in_pinecone) ? 'disabled' : '';
                echo '<button id="delete-files" ' . $button_disabled_delete . '>Delete Files</button>';

                $files_with_newer_timestamps = $this->getFilesWithNewerWebsiteTimestamp($site_pages, $pinecone_files);
                echo '<div id="files-with-newer-website-timestamp-table">';
                echo $this->table_renderer->renderComparisonTable(
                    $files_with_newer_timestamps['newer_website_files'], 
                    $files_with_newer_timestamps['older_pinecone_files'], 
                    "Files with Newer Website Timestamp");
                echo '</div>';
                $button_disabled_update = empty($files_with_newer_timestamps['newer_website_files']) ? 'disabled' : '';
                echo '<button id="update-files" ' . $button_disabled_update . '>Update Files</button>';

                // Add this after the other tables in display_admin_page()
                $files_in_both = $this->getFilesInBoth($site_pages, $pinecone_files);
                echo '<div id="files-in-both-table">';
                echo $this->table_renderer->renderDeleteTable(
                    $files_in_both,
                    "Files In Both",
                    "files-in-both-form",
                    "file-checkbox-both"
                );
                echo '</div>';
                $button_disabled_both = empty($files_in_both) ? 'disabled' : '';
                echo '<button id="delete-both-files" ' . $button_disabled_both . '>Delete from Pinecone</button>';
            }

        } catch (Exception $e) {
            error_log('BasicSiteScraper Error: ' . $e->getMessage());
            echo '<p style="color:red;">Error: ' . $e->getMessage() . '</p>';
        }

        echo '</div>';

    }
}