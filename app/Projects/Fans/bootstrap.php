<?php

/**
 * Fans Project Bootstrap
 * 
 * Fans 项目启动文件
 * 
 * 包含：
 * - 打赏系统（Donation）
 * - 订单系统（Order）
 * - 统计系统（Stat）
 * - 用户扩展（User）
 */

namespace Tonik\Theme\App\Projects\Fans;

use App\Services\DonationService;
use App\Services\OrderService;
use App\Services\StatService;
use App\Services\UserService;
use function Tonik\Theme\App\theme;
use Tonik\Gin\Foundation\Theme;

// 获取项目目录路径
$project_dir = __DIR__;

// ============================================
// 1. 自动加载服务类
// ============================================
$service_files = [
    'Services/DonationService.php',
    'Services/OrderService.php',
    'Services/StatService.php',
    'Services/UserService.php',
];

foreach ($service_files as $file) {
    $path = $project_dir . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// ============================================
// 2. 注册服务到容器
// ============================================
add_action('init', function () {
    // 打赏服务
    theme()->bind('donation', function (Theme $theme, $parameters) {
        return new DonationService();
    });
    
    // 订单服务
    theme()->bind('order', function (Theme $theme, $parameters) {
        return new OrderService();
    });
    
    // 统计服务
    theme()->bind('stat', function (Theme $theme, $parameters) {
        return new StatService();
    });
    
    // 用户服务（Fans 项目扩展版本）
    theme()->bind('user', function (Theme $theme, $parameters) {
        return new UserService();
    });
}, 5);

// ============================================
// 3. 加载自定义文章类型
// ============================================
if (file_exists($project_dir . '/Structure/posttypes.php')) {
    require_once $project_dir . '/Structure/posttypes.php';
}

// ============================================
// 4. 加载 REST API 字段注册
// ============================================
add_action('rest_api_init', function () use ($project_dir) {
    $rest_field_files = [
        'Setup/donation-meta.php',
        'Setup/orders-meta.php',
        'Setup/user-meta.php',
        'Setup/post-meta.php',
    ];
    
    foreach ($rest_field_files as $file) {
        $path = $project_dir . '/' . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

// ============================================
// 5. 项目初始化完成日志
// ============================================
add_action('init', function () {
    if (function_exists('theme') && theme('log')) {
        theme('log')->debug('Fans project loaded successfully');
    }
}, 999);
