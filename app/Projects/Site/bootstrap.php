<?php

/**
 * Site Project Bootstrap
 * 
 * Site 项目启动文件
 * 
 * 包含：
 * - Lead 系统（线索管理）
 * - Site 系统（站点管理）
 * - Analytics 集成（使用通用 AnalyticsService）
 */

namespace Tonik\Theme\App\Projects\Site;

// 获取项目目录路径
$project_dir = __DIR__;

// ============================================
// 1. 加载自定义文章类型（包含 Meta 字段注册）
// ============================================
$posttypes_file = $project_dir . '/Structure/posttypes.php';
if (file_exists($posttypes_file)) {
    require_once $posttypes_file;
}

// ============================================
// 2. 注册 Analytics 计算字段（只读）
// ============================================
add_action('rest_api_init', function () {
    // 为 Site 添加 analytics 计算字段（汇总统计数据）
    register_rest_field('site', 'analytics', [
        'get_callback' => function ($object) {
            if (function_exists('theme') && theme('analytics')) {
                return theme('analytics')->getAnalytics('site', $object['id']);
            }
            
            // 降级方案：直接读取 meta
            return [
                'views' => (int) get_post_meta($object['id'], 'site_views', true),
                'clicks' => (int) get_post_meta($object['id'], 'site_clicks', true),
                'views_today' => (int) get_post_meta($object['id'], 'site_views_today', true),
                'views_week' => (int) get_post_meta($object['id'], 'site_views_week', true),
                'views_month' => (int) get_post_meta($object['id'], 'site_views_month', true),
                'clicks_today' => (int) get_post_meta($object['id'], 'site_clicks_today', true),
                'clicks_week' => (int) get_post_meta($object['id'], 'site_clicks_week', true),
                'clicks_month' => (int) get_post_meta($object['id'], 'site_clicks_month', true),
                'conversion_rate' => 0,
                'conversion_rate_week' => 0,
                'conversion_rate_month' => 0,
                'last_viewed' => get_post_meta($object['id'], 'site_last_viewed', true),
            ];
        },
        'schema' => [
            'type' => 'object',
            'description' => '站点统计数据（所有维度）',
            'readonly' => true,
            'properties' => [
                'views' => ['type' => 'integer', 'description' => '总浏览量'],
                'clicks' => ['type' => 'integer', 'description' => '总点击量'],
                'views_today' => ['type' => 'integer', 'description' => '今日浏览量'],
                'views_week' => ['type' => 'integer', 'description' => '本周浏览量'],
                'views_month' => ['type' => 'integer', 'description' => '本月浏览量'],
                'clicks_today' => ['type' => 'integer', 'description' => '今日点击量'],
                'clicks_week' => ['type' => 'integer', 'description' => '本周点击量'],
                'clicks_month' => ['type' => 'integer', 'description' => '本月点击量'],
                'conversion_rate' => ['type' => 'number', 'description' => '总转化率'],
                'conversion_rate_week' => ['type' => 'number', 'description' => '本周转化率'],
                'conversion_rate_month' => ['type' => 'number', 'description' => '本月转化率'],
                'last_viewed' => ['type' => 'string', 'description' => '最后浏览时间'],
            ],
        ],
    ]);
});

// ============================================
// 3. 项目初始化完成日志
// ============================================
add_action('init', function () {
    if (function_exists('theme') && theme('log')) {
        theme('log')->debug('Site project loaded successfully', [
            'cpts' => ['lead', 'site'],
            'meta_fields' => 'auto-registered via register_post_meta',
            'analytics' => 'using global AnalyticsService',
        ]);
    }
}, 999);
