<?php

/**
 * Sites Project Bootstrap
 * 
 * Sites 项目启动文件
 * 
 * 包含：
 * - Lead 系统（线索管理）
 * - Site 系统（站点管理）
 * - Analytics 集成（使用通用 AnalyticsService）
 */

namespace Tonik\Theme\App\Projects\Sites;

use App\Projects\Sites\Meta\LeadMeta;
use App\Projects\Sites\Meta\SiteMeta;

// 获取项目目录路径
$project_dir = __DIR__;

// ============================================
// 1. 自动加载 Meta 类
// ============================================
$meta_files = [
    'Meta/LeadMeta.php',
    'Meta/SiteMeta.php',
];

foreach ($meta_files as $file) {
    $path = $project_dir . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// ============================================
// 2. 加载自定义文章类型
// ============================================
$posttypes_file = $project_dir . '/Structure/posttypes.php';
if (file_exists($posttypes_file)) {
    require_once $posttypes_file;
}

// ============================================
// 3. 注册 REST API 字段
// ============================================
add_action('rest_api_init', function () {
    // 注册 REST 字段
    LeadMeta::register();
    SiteMeta::register();
    
    // 注意：CRUD 端点由 WordPress 自动生成（show_in_rest => true）
    // 访问：/wp/v2/sites 和 /wp/v2/leads
});

// ============================================
// 4. 项目初始化完成日志
// ============================================
add_action('init', function () {
    if (function_exists('theme') && theme('log')) {
        theme('log')->debug('Sites project loaded successfully', [
            'cpts' => ['lead', 'site'],
            'analytics' => 'using global AnalyticsService',
        ]);
    }
}, 999);
