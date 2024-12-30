<?php

class TableRenderer implements TableRendererInterface {
    public function renderTable(array $data, string $title, string $formId, string $checkboxClass): string {
        ob_start();
        ?>
        <h2><?php echo esc_html($title); ?></h2>
        <form id="<?php echo esc_attr($formId); ?>">
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th class="column-checkbox" data-sortable="false">Select</th>
                        <th class="column-title" data-sortable="true">Title</th>
                        <th class="column-source" data-sortable="true">Source</th>
                        <th class="column-modified" data-sortable="true">Last Modified</th>
                    </tr>
                </thead>
                <tbody class="scrollable-table-body">
                    <?php foreach ($data as $item): ?>
                        <tr>
                            <td>
                                <input type="checkbox" 
                                       class="<?php echo esc_attr($checkboxClass); ?>" 
                                       name="selected_items[]" 
                                       value="<?php echo esc_attr($item['url']); ?>">
                            </td>
                            <td><?php echo esc_html($item['title'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['url']); ?></td>
                            <td><?php echo esc_html($item['last_modified']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <?php
        return ob_get_clean();
    }

    public function renderDeleteTable(array $data, string $title, string $formId, string $checkboxClass): string {
        ob_start();
        ?>
        <h2><?php echo esc_html($title); ?></h2>
        <form id="<?php echo esc_attr($formId); ?>">
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th class="column-checkbox" data-sortable="false">Select</th>
                        <th class="column-title" data-sortable="true">Title</th>
                        <th class="column-source" data-sortable="true">Source</th>
                        <th class="column-modified" data-sortable="true">Last Modified</th>
                    </tr>
                </thead>
                <tbody class="scrollable-table-body">
                    <?php foreach ($data as $item): ?>
                        <tr>
                            <td>
                                <input type="checkbox" 
                                       class="<?php echo esc_attr($checkboxClass); ?>" 
                                       name="selected_items[]" 
                                       value="<?php echo esc_attr($item['id']); ?>">
                            </td>
                            <td><?php echo esc_html($item['metadata']['title'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['metadata']['source'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['metadata']['lastmod'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <?php
        return ob_get_clean();
    }

    public function renderComparisonTable(array $websiteFiles, array $pineconeFiles, string $title): string {
        ob_start();
        ?>
        <h2><?php echo esc_html($title); ?></h2>
        <form id="files-with-newer-timestamps-form">
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th class="column-checkbox">Select</th>
                        <th class="column-title">Title</th>
                        <th class="column-source">Source</th>
                        <th class="column-website-modified" data-sortable="true">Website Modified</th>
                        <th class="column-pinecone-modified" data-sortable="true">Pinecone Modified</th>
                    </tr>
                </thead>
                <tbody class="scrollable-table-body">
                    <?php 
                    foreach ($websiteFiles as $index => $websiteFile): 
                        $pineconeFile = $pineconeFiles[$index] ?? [];
                        $rowData = [
                            'website' => [
                                'url' => $websiteFile['url'],
                                'title' => $websiteFile['title'],
                                'last_modified' => $websiteFile['last_modified'],
                                'content' => $websiteFile['clean_content'] ?? ''
                            ],
                            'pinecone' => [
                                'id' => $pineconeFile['id'] ?? ''
                            ]
                        ];
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" 
                                       class="file-checkbox-update" 
                                       name="selected_items[]" 
                                       value="<?php echo esc_attr(json_encode($rowData)); ?>">
                            </td>
                            <td><?php echo esc_html($websiteFile['title']); ?></td>
                            <td><?php echo esc_html($websiteFile['url']); ?></td>
                            <td><?php echo esc_html($websiteFile['last_modified']); ?></td>
                            <td><?php echo esc_html($pineconeFile['metadata']['lastmod'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <?php
        return ob_get_clean();
    }
}