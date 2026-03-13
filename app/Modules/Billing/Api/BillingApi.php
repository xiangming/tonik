<?php

namespace App\Modules\Billing\Api;

use function Tonik\Theme\App\theme;

/**
 * Billing REST API 端点
 *
 * - GET  /wp/v2/credits              获取用户积分信息
 * - POST /wp/v2/credits/consume      消耗用户积分
 * - GET  /wp/v2/billing/stats        获取计费统计（公开）
 */
class BillingApi
{
    /**
     * 注册所有REST API端点
     */
    public static function register()
    {
        /**
         * 获取用户积分信息
         * 
         * GET /wp-json/wp/v2/credits
         * Authorization: Bearer {jwt_token}
         */
        register_rest_route('wp/v2', '/credits', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handleGetCredits'],
            'permission_callback' => [self::class, 'checkAuth'],
        ]);

        /**
         * 消耗用户积分
         * 
         * POST /wp-json/wp/v2/credits/consume
         * Body: { "cost": 3 }
         * Authorization: Bearer {jwt_token}
         */
        register_rest_route('wp/v2', '/credits/consume', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handleConsumeCredits'],
            'permission_callback' => [self::class, 'checkAuth'],
            'args' => [
                'cost' => [
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function($param) {
                        return is_int($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        /**
         * 获取计费统计信息（公开接口）
         * 
         * GET /wp-json/wp/v2/billing/stats
         */
        register_rest_route('wp/v2', '/billing/stats', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handleGetStats'],
            'permission_callback' => '__return_true',
        ]);

        /**
         * 测试端点：充值积分（仅用于测试，生产环境应禁用或删除）
         * 
         * POST /wp-json/wp/v2/credits/recharge
         * Body: { "credits": 100 }
         */
        register_rest_route('wp/v2', '/credits/recharge', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handleRecharge'],
            'permission_callback' => [self::class, 'checkAuth'],
            'args' => [
                'credits' => [
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function($param) {
                        return is_int($param) && $param > 0;
                    },
                ],
            ],
        ]);
    }

    /**
     * 权限检查：要求用户已登录
     */
    public static function checkAuth()
    {
        return is_user_logged_in();
    }

    /**
     * 处理获取积分请求
     */
    public static function handleGetCredits($request)
    {
        $user_id = get_current_user_id();
        $billing = theme('billing');
        
        $result = $billing->getCredits($user_id);
        
        return rest_ensure_response($result);
    }

    /**
     * 处理消耗积分请求
     */
    public static function handleConsumeCredits($request)
    {
        $user_id = get_current_user_id();
        $cost = $request->get_param('cost');
        
        $billing = theme('billing');
        $result = $billing->consumeCredits($user_id, $cost);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }

    /**
     * 处理获取统计信息请求
     */
    public static function handleGetStats($request)
    {
        $billing = theme('billing');
        $result = $billing->getStats();
        
        return rest_ensure_response($result);
    }

    /**
     * 处理充值请求（测试端点，仅 WP_DEBUG 模式下可用）
     */
    public static function handleRecharge($request)
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return new \WP_Error('forbidden', '此端点仅在调试模式下可用', ['status' => 403]);
        }

        $user_id = get_current_user_id();
        $credits = $request->get_param('credits');
        
        $prefix = get_option('billing_meta_prefix', 'app');
        
        $current = (int) get_user_meta($user_id, "{$prefix}_credits", true);
        $new_credits = $current + $credits;
        update_user_meta($user_id, "{$prefix}_credits", $new_credits);
        
        return rest_ensure_response([
            'success' => true,
            'message' => "成功充值 {$credits} 积分",
            'currentCredits' => $new_credits
        ]);
    }
}
