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
     */
    private static function registerRefreshEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/stat/refresh', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $user_slug = $request->get_param('user_slug');

                // 通过 user_slug 获取用户信息
                $user = get_user_by('slug', $user_slug);

                if (!$user) {
                    return resError('用户不存在');
                }

                // 刷新统计
                theme('stat')->refresh($user->ID);

                return resOK('统计数据已刷新');
            },
            'args' => array(
                'user_slug' => theme('args')->user_slug(true),
            ),
            'permission_callback' => '__return_true',
        ));
    }
}
