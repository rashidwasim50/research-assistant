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
require_once __DIR__ . '/services/pinecone-service.php';
require_once __DIR__ . '/classes/BasicSiteScraper.php';
require_once __DIR__ . '/ajax-handlers.php';

// Initialize BasicSiteScraper
$api_key = get_option('ai_research_api_key');
$assistant_name = get_option('ai_research_assistant_name');
$basic_site_scraper = new BasicSiteScraper($api_key, $assistant_name);

// Include the AJAX handlers
require_once __DIR__ . '/ajax-handlers.php';