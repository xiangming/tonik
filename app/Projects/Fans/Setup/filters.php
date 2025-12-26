<?php

namespace Tonik\Theme\App\Setup;

use function Tonik\Theme\App\theme;

/*
|-----------------------------------------------------------
| Theme Filters
|-----------------------------------------------------------
|
| Fans 项目的 Filters 和 Hooks
|
 */

/**
 * 配置项目专属支付参数
 */
add_filter('payment_service_config', function ($config) {
    // 设置 Fans 项目的支付宝 return_url
    if (!empty($_ENV['FANS_ALIPAY_RETURN_URL'])) {
        $config['alipay']['default']['return_url'] = $_ENV['FANS_ALIPAY_RETURN_URL'];
    }
    
    return $config;
});
