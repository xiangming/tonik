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

use App\Projects\Fans\Services\ArgsService;
use App\Projects\Fans\Services\DonationService;
use App\Projects\Fans\Services\OrderService;
use App\Projects\Fans\Services\PaymentService;
use App\Projects\Fans\Services\StatService;
use App\Projects\Fans\Services\UserService;
use function Tonik\Theme\App\theme;
use Tonik\Gin\Foundation\Theme;

// 获取项目目录路径
$project_dir = __DIR__;
$theme_dir = get_template_directory();

// ============================================
// 0. 先加载基础依赖（Fans Services 依赖它们）
// ============================================
$dependencies = [
    '/app/Traits/ResourceTrait.php',
    '/app/Traits/TimeTrait.php',
    '/app/Services/BaseService.php',
    '/app/Validators/Validator.php',
];

foreach ($dependencies as $dep) {
    $path = $theme_dir . $dep;
    if (file_exists($path)) {
        require_once $path;
    }
}

// ============================================
// 1. 自动加载服务类
// ============================================
$service_files = [
    'Services/ArgsService.php',
    'Services/DonationService.php',
    'Services/OrderService.php',
    'Services/PaymentService.php',
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
    // 参数服务
    theme()->bind('args', function (Theme $theme, $parameters) {
        return new ArgsService();
    });
    
    // 打赏服务
    theme()->bind('donation', function (Theme $theme, $parameters) {
        return new DonationService();
    });
    
    // 订单服务
    theme()->bind('order', function (Theme $theme, $parameters) {
        return new OrderService();
    });
    
    // 支付服务
    theme()->bind('payment', function (Theme $theme, $parameters) {
        return new PaymentService();
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
// 3. 加载自定义文章类型（在 bootstrap 阶段就加载，以便 init hook 生效）
// ============================================
$posttypes_file = $project_dir . '/Structure/posttypes.php';
if (file_exists($posttypes_file)) {
    require_once $posttypes_file;
}

// ============================================
// 4. 加载项目特定的 Actions 和 Filters
// ============================================
if (file_exists($project_dir . '/Setup/actions.php')) {
    require_once $project_dir . '/Setup/actions.php';
}

if (file_exists($project_dir . '/Setup/filters.php')) {
    require_once $project_dir . '/Setup/filters.php';
}

// ============================================
// 5. 加载 REST API 字段注册
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
// 6. 项目初始化完成日志
// ============================================
add_action('init', function () {
    if (function_exists('theme') && theme('log')) {
        theme('log')->debug('Fans project loaded successfully');
    }
}, 999);
