<?php

interface TableRendererInterface {
    public function renderTable(array $data, string $title, string $formId, string $checkboxClass): string;
    public function renderComparisonTable(array $websiteFiles, array $pineconeFiles, string $title): string;
    public function renderDeleteTable(array $data, string $title, string $formId, string $checkboxClass): string;
}