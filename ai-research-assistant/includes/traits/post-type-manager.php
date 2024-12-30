<?php

trait PostTypeManager {
    /**
     * Get all registered post types
     * 
     * @return array Array of post type objects
     */
    public function get_all_post_types(): array {
        return get_post_types([], 'objects');
    }

    /**
     * Display the post type selector form
     * 
     * @param array $post_types Array of post type objects
     * @return void
     */
    public function display_post_type_selector(array $post_types): void {
        // Retrieve saved selected post types
        $saved_post_types = get_option('ai_research_selected_post_types', []);
        if (!is_array($saved_post_types)) {
            $saved_post_types = [];
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $selected_post_types = isset($_POST['post_types']) ? $_POST['post_types'] : [];
            update_option('ai_research_selected_post_types', $selected_post_types);
        } else {
            $selected_post_types = $saved_post_types;
        }

        $this->render_post_type_form($post_types, $selected_post_types);
    }

    /**
     * Render the post type selection form
     * 
     * @param array $post_types Array of post type objects
     * @param array $selected_post_types Array of selected post type names
     * @return void
     */
    private function render_post_type_form(array $post_types, array $selected_post_types): void {
        echo '<h2>Select Post Types</h2>';
        echo '<form method="post" class="post-type-selector">';
        echo '<div class="post-type-grid">';
        
        foreach ($post_types as $post_type) {
            $checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
            echo sprintf(
                '<div class="post-type-item"><label><input type="checkbox" name="post_types[]" value="%s" %s>%s</label></div>',
                esc_attr($post_type->name),
                $checked,
                esc_html($post_type->label)
            );
        }
        
        echo '</div>';
        echo '<input type="submit" class="button button-primary" value="Update">';
        echo '</form>';
    }
}