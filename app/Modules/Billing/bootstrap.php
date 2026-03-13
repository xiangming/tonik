<?php

/**
 * Billing Module Bootstrap
 * 
 * 计费系统模块 — 跨项目可插拔功能
 * 
 * 功能：
 * - theme('billing') 服务绑定
 * - REST API 端点 (wp/v2/credits, wp/v2/billing/stats)
 * - 用户注册时自动赠送初始积分
 * 
 * 在 Project bootstrap 中按需引入：
 * ```php
 * $modules = ['Billing'];
 * ```
 */

namespace App\Modules\Billing;

use App\Modules\Billing\Services\BillingService;
use App\Modules\Billing\Api\BillingApi;
use function Tonik\Theme\App\theme;
use Tonik\Gin\Foundation\Theme;

$module_dir = __DIR__;
$theme_dir  = get_template_directory();

// 加载依赖文件（幂等加载）
$dependencies = [
    '/app/Services/BaseService.php',
];

foreach ($dependencies as $dep) {
    $path = $theme_dir . $dep;
    if (file_exists($path)) {
        require_once $path;
    }
}

// 加载模块类文件
require_once $module_dir . '/Services/BillingService.php';
require_once $module_dir . '/Api/BillingApi.php';

// 绑定 billing 服务到主题容器
add_action('after_setup_theme', function () {
    theme()->bind('billing', function (Theme $theme, $parameters) {
        return new BillingService();
    });
}, 5);

// 注册 REST API 端点
add_action('rest_api_init', function () {
    BillingApi::register();
});

// 用户注册 Hook：赠送初始积分
add_action('user_register', function ($user_id) {
    $prefix = get_option('billing_meta_prefix', 'app');
    $initial_credits = (int) get_option('billing_initial_credits', 10);
    
    if ($initial_credits > 0) {
        update_user_meta($user_id, "{$prefix}_credits", $initial_credits);
    }
});

// 支付成功 Hook：订阅购买/续费后充值积分
add_action('payment_success_membership', function ($order_id, $payment_data) {
    $user_id = $payment_data['from_user_id'] ?? null;
    $tier = $payment_data['tier'] ?? null;

    if (!$user_id || !$tier) {
        return;
    }

    // 所有付费套餐共用同一积分额度，时长不同
    $duration_months = match($tier) {
        'monthly' => 1,
        'yearly'  => 12,
        'ltd'     => 1200,
        default   => 0,
    };

    if ($duration_months > 0) {
        theme('billing')->handleSubscriptionPurchase($user_id, $tier, $duration_months);
    }
}, 10, 2);
