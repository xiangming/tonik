<?php

/**
 * Analytics Module Bootstrap
 * 
 * 数据分析模块 — 跨项目可插拔功能
 * 
 * 提供：
 * - theme('analytics') 服务绑定
 * - REST API 端点 (analytics/v1/*)
 * 
 * 在 Project bootstrap 中按需引入：
 * ```php
 * require_once get_template_directory() . '/app/Modules/Analytics/bootstrap.php';
 * ```
 */

namespace App\Modules\Analytics;

use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Analytics\Api\AnalyticsApi;
use function Tonik\Theme\App\theme;
use Tonik\Gin\Foundation\Theme;

$module_dir = __DIR__;
$theme_dir  = get_template_directory();

// Load dependencies (idempotent via require_once)
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

// Load module classes
require_once $module_dir . '/Services/AnalyticsService.php';
require_once $module_dir . '/Api/AnalyticsApi.php';

// Bind analytics service to the theme container
add_action('after_setup_theme', function () {
    theme()->bind('analytics', function (Theme $theme, $parameters) {
        return new AnalyticsService();
    });
}, 5);

// Register REST API routes
add_action('rest_api_init', function () {
    AnalyticsApi::register();
});
