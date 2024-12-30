<?php

class FileManager {
    private $pinecone_service;
    private $temp_dir;

    public function __construct(PineconeService $pinecone_service) {
        $this->pinecone_service = $pinecone_service;
        $this->temp_dir = plugin_dir_path(__FILE__) . '../temp/';
        
        // Ensure temp directory exists
        if (!is_dir($this->temp_dir)) {
            mkdir($this->temp_dir, 0777, true);
        }
    }

    /**
     * Upload a single file to Pinecone
     */
    public function upload_single_file(string $content, array $metadata): void {
        $temp_file_path = $this->create_temporary_file($content);

        try {
            $this->pinecone_service->uploadFileToPinecone($temp_file_path, $metadata);
        } catch (Exception $e) {
            error_log("Error uploading page to Pinecone: " . $e->getMessage());
            throw $e;
        } finally {
            $this->cleanup_temporary_file($temp_file_path);
        }
    }

    /**
     * Create a temporary file with content
     */
    private function create_temporary_file(string $content): string {
        $temp_filename = uniqid() . '.txt';
        $temp_file_path = $this->temp_dir . $temp_filename;
        
        if (file_put_contents($temp_file_path, $content) === false) {
            throw new Exception('Failed to create temporary file');
        }

        return $temp_file_path;
    }

    /**
     * Clean up temporary file
     */
    private function cleanup_temporary_file(string $file_path): void {
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    /**
     * Get site pages based on selected post types
     */
    public function get_site_pages(): array {
        $selected_post_types = get_option('ai_research_selected_post_types', []);

        if (!is_array($selected_post_types)) {
            return [];
        }

        $args = [
            'post_type'      => $selected_post_types ?: 'any',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];

        $query = new WP_Query($args);
        return $query->posts;
    }
}