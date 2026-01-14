<?php

namespace App\Projects\Fans\Api;

use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\theme;

/**
 * 统计相关 REST API 端点
 * 
 * 包含：
 * - 刷新统计数据
 */
class StatApi
{
    const NAMESPACE = '/wp/v2';

    /**
     * 注册所有统计相关端点
     */
    public static function register()
    {
        self::registerRefreshEndpoint();
    }

    /**
     * 刷新统计数据
     * POST /wp/v2/stat/refresh
     * 
     * 仅允许登录用户刷新自己的统计数据
     */
    private static function registerRefreshEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/stat/refresh', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $current_user_id = get_current_user_id();

                if (!$current_user_id) {
                    return resError('请先登录');
                }

                // 刷新当前登录用户的统计数据
                $result = theme('stat')->refresh($current_user_id);

                return resOK($result, '统计数据已刷新');
            },
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));
    }
}
