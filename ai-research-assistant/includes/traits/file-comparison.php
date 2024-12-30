<?php

trait FileComparison {
    /**
     * Get files that exist only in site pages and not in Pinecone
     * 
     * @param array $site_pages Array of WordPress posts
     * @param array $pinecone_files Array of Pinecone files
     * @return array Array of files only in site pages
     */
    public function getFilesOnlyInSitePages(array $site_pages, array $pinecone_files): array {
        $pinecone_sources = array_column($pinecone_files['files'], 'metadata');
        $pinecone_urls = array_column($pinecone_sources, 'source');

        $files_only_in_site_pages = [];

        foreach ($site_pages as $post) {
            $page_url = get_permalink($post);
            
            if (!in_array($page_url, $pinecone_urls)) {
                $files_only_in_site_pages[] = [
                    'url' => $page_url,
                    'title' => get_the_title($post),
                    'last_modified' => get_the_modified_date('Y-m-d H:i:s', $post),
                    'content' => $post->post_content,
                    'clean_content' => wp_strip_all_tags(strip_shortcodes(apply_filters('the_content', $post->post_content))),
                ];
            }
        }

        return $files_only_in_site_pages;
    }

    /**
     * Get files that exist only in Pinecone and not in site pages
     * 
     * @param array $site_pages Array of WordPress posts
     * @param array $pinecone_files Array of Pinecone files
     * @return array Array of files only in Pinecone
     */
    public function getFilesOnlyInPineconeFiles(array $site_pages, array $pinecone_files): array {
        $site_urls = array_map(function($post) {
            return get_permalink($post);
        }, $site_pages);

        return array_filter($pinecone_files['files'], function($file) use ($site_urls) {
            return !in_array($file['metadata']['source'], $site_urls);
        });
    }

    /**
     * Get files that have newer timestamps on the website than in Pinecone
     * 
     * @param array $site_pages Array of WordPress posts
     * @param array $pinecone_files Array of Pinecone files
     * @return array Array containing newer website files and their Pinecone counterparts
     */
    public function getFilesWithNewerWebsiteTimestamp(array $site_pages, array $pinecone_files): array {
        $newer_website_files = [];
        $older_pinecone_files = [];
        
        $pinecone_lookup = [];
        foreach ($pinecone_files['files'] as $file) {
            if (isset($file['metadata']['source'])) {
                $pinecone_lookup[$file['metadata']['source']] = $file;
            }
        }

        foreach ($site_pages as $post) {
            $page_url = get_permalink($post);
            $page_last_modified = get_the_modified_date('Y-m-d H:i:s', $post);
            
            if (isset($pinecone_lookup[$page_url])) {
                $file = $pinecone_lookup[$page_url];
                if (strtotime($page_last_modified) > strtotime($file['metadata']['lastmod'])) {
                    $newer_website_files[] = [
                        'url' => $page_url,
                        'title' => get_the_title($post),
                        'last_modified' => $page_last_modified,
                        'content' => $post->post_content,
                        'clean_content' => wp_strip_all_tags(strip_shortcodes(apply_filters('the_content', $post->post_content))),
                    ];
                    $older_pinecone_files[] = $file;
                }
            }
        }

        return [
            'newer_website_files' => $newer_website_files,
            'older_pinecone_files' => $older_pinecone_files
        ];
    }

    /**
     * Get files that exist in both website and Pinecone with matching timestamps
     * 
     * @param array $site_pages Array of WordPress posts
     * @param array $pinecone_files Array of Pinecone files
     * @return array Array of matching files
     */
    public function getFilesInBoth(array $site_pages, array $pinecone_files): array {
        $matching_files = [];
        
        $pinecone_lookup = [];
        foreach ($pinecone_files['files'] as $file) {
            if (isset($file['metadata']['source'])) {
                $pinecone_lookup[$file['metadata']['source']] = $file;
            }
        }

        foreach ($site_pages as $post) {
            $page_url = get_permalink($post);
            $page_last_modified = get_the_modified_date('Y-m-d H:i:s', $post);
            
            if (isset($pinecone_lookup[$page_url])) {
                $file = $pinecone_lookup[$page_url];
                if (strtotime($page_last_modified) === strtotime($file['metadata']['lastmod'])) {
                    $matching_files[] = $file;
                }
            }
        }

        return $matching_files;
    }
}