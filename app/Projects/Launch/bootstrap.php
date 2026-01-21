<?php

/**
 * Launch Project Bootstrap
 * 
 * Product launch and showcase system
 * 
 * Features:
 * - Products custom post type
 * - Product categories and tags
 * - Analytics integration for views/clicks
 * - REST API support
 */

namespace Tonik\Theme\App\Projects\Launch;

use function Tonik\Theme\App\theme;
use Tonik\Gin\Foundation\Theme;

// Get project directory path
$project_dir = __DIR__;
$theme_dir = get_template_directory();

// ============================================
// 1. Load dependencies
// ============================================
$dependencies = [
    '/app/Traits/ResourceTrait.php',
    '/app/Traits/TimeTrait.php',
    '/app/Services/BaseService.php',
];

foreach ($dependencies as $dep) {
    $path = $theme_dir . $dep;
    if (file_exists($path)) {
        require_once $path;
    }
}

// ============================================
// 2. Auto-load service classes
// ============================================
$service_files = [
    // Add project-specific services here as needed
    // 'Services/ProductService.php',
];

foreach ($service_files as $file) {
    $path = $project_dir . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// ============================================
// 3. Register services to container
// ============================================
add_action('after_setup_theme', function () {
    // Register project-specific services here
    // Example:
    // theme()->bind('product', function (Theme $theme, $parameters) {
    //     return new ProductService();
    // });
}, 10);

// ============================================
// 4. Load custom post types and taxonomies
// ============================================
$posttypes_file = $project_dir . '/Structure/posttypes.php';
if (file_exists($posttypes_file)) {
    require_once $posttypes_file;
}

// ============================================
// 5. Load REST API extensions
// ============================================
$rest_api_file = $project_dir . '/Structure/rest-api.php';
if (file_exists($rest_api_file)) {
    require_once $rest_api_file;
}

// ============================================
// 6. Load project-specific actions and filters
// ============================================
if (file_exists($project_dir . '/Setup/actions.php')) {
    require_once $project_dir . '/Setup/actions.php';
}

if (file_exists($project_dir . '/Setup/filters.php')) {
    require_once $project_dir . '/Setup/filters.php';
}

// ============================================
// 7. Register REST API endpoints
// ============================================
add_action('rest_api_init', function () {
    // Register API endpoints here
    // Example:
    // ProductApi::register();
});

// ============================================
// 8. Project initialization complete
// ============================================
add_action('init', function () {
    if (function_exists('theme') && theme('log')) {
        theme('log')->debug('Launch project loaded successfully');
    }
}, 999);
