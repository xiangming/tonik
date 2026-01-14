<?php

/**
 * Analytics API Endpoints
 * 
 * 通用分析端点，支持所有项目使用
 * 
 * 端点说明：
 * - POST /analytics/v1/track - 追踪浏览/点击
 * - GET  /analytics/v1/{post_type}/{id}/trends - 获取趋势数据
 * - GET  /analytics/v1/{post_type}/top - 获取热门内容
 */

namespace App\Http;

use function Tonik\Theme\App\theme;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\resError;

/**
 * 注册 Analytics API 端点
 */
add_action('rest_api_init', function () {
    
    /**
     * 追踪端点 - 记录浏览或点击
     * 
     * POST /wp-json/analytics/v1/track
     * 
     * Body:
     * {
     *   "post_type": "site",     // 文章类型：site, lead, fan 等
     *   "post_id": 123,          // 文章ID
     *   "action": "view"         // 动作：view 或 click
     * }
     */
    register_rest_route('analytics/v1', '/track', [
        'methods' => 'POST',
        'callback' => function($request) {
            $post_type = $request->get_param('post_type');
            $post_id = $request->get_param('post_id');
            $action = $request->get_param('action');
            
            // 参数验证
            if (!$post_type || !$post_id || !in_array($action, ['view', 'click'])) {
                return resError('参数错误：需要 post_type, post_id 和 action (view|click)', null, 'invalid_params');
            }
            
            // 验证文章是否存在
            $post = get_post($post_id);
            if (!$post || $post->post_type !== $post_type) {
                return resError('文章不存在', null, 'post_not_found');
            }
            
            // 调用 AnalyticsService
            $analytics = theme('analytics');
            
            if ($action === 'view') {
                $analytics->trackView($post_type, $post_id);
            } else {
                $analytics->trackClick($post_type, $post_id);
            }
            
            return resOK([
                'post_id' => $post_id,
                'action' => $action,
            ], '追踪成功');
        },
        'permission_callback' => '__return_true', // 公开端点
        'args' => [
            'post_type' => [
                'required' => true,
                'type' => 'string',
                'description' => '文章类型',
            ],
            'post_id' => [
                'required' => true,
                'type' => 'integer',
                'description' => '文章ID',
            ],
            'action' => [
                'required' => true,
                'type' => 'string',
                'enum' => ['view', 'click'],
                'description' => '追踪动作',
            ],
        ],
    ]);
    
    /**
     * 趋势数据端点
     * 
     * GET /wp-json/analytics/v1/{post_type}/{id}/trends?days=30
     * 
     * 示例：
     * - /analytics/v1/site/123/trends
     * - /analytics/v1/fan/456/trends?days=7
     */
    register_rest_route('analytics/v1', '/(?P<post_type>[a-z_]+)/(?P<id>\d+)/trends', [
        'methods' => 'GET',
        'callback' => function($request) {
            $post_type = $request['post_type'];
            $post_id = $request['id'];
            $days = $request->get_param('days') ?? 30;
            
            // 验证文章是否存在
            $post = get_post($post_id);
            if (!$post || $post->post_type !== $post_type) {
                return resError('文章不存在', null, 'post_not_found');
            }
            
            $analytics = theme('analytics');
            
            // 获取趋势数据
            $views_trend = $analytics->getTrend($post_type, $post_id, $days, 'views');
            $clicks_trend = $analytics->getTrend($post_type, $post_id, $days, 'clicks');
            
            // 获取聚合统计
            $prefix = $post_type;
            $stats = [
                'today' => [
                    'views' => (int)get_post_meta($post_id, "{$prefix}_views_today", true),
                    'clicks' => (int)get_post_meta($post_id, "{$prefix}_clicks_today", true),
                ],
                'week' => [
                    'views' => (int)get_post_meta($post_id, "{$prefix}_views_week", true),
                    'clicks' => (int)get_post_meta($post_id, "{$prefix}_clicks_week", true),
                ],
                'month' => [
                    'views' => (int)get_post_meta($post_id, "{$prefix}_views_month", true),
                    'clicks' => (int)get_post_meta($post_id, "{$prefix}_clicks_month", true),
                ],
                'total' => [
                    'views' => (int)get_post_meta($post_id, "{$prefix}_views", true),
                    'clicks' => (int)get_post_meta($post_id, "{$prefix}_clicks", true),
                ],
                'last_viewed' => get_post_meta($post_id, "{$prefix}_last_viewed", true),
            ];
            
            return resOK([
                'post_id' => $post_id,
                'post_type' => $post_type,
                'trends' => [
                    'views' => $views_trend,
                    'clicks' => $clicks_trend,
                ],
                'stats' => $stats,
            ]);
        },
        'permission_callback' => '__return_true',
        'args' => [
            'days' => [
                'type' => 'integer',
                'default' => 30,
                'minimum' => 1,
                'maximum' => 90,
                'description' => '获取最近N天的趋势数据',
            ],
        ],
    ]);
    
    /**
     * 热门内容端点
     * 
     * GET /wp-json/analytics/v1/{post_type}/top?period=week&limit=10
     * 
     * 示例：
     * - /analytics/v1/site/top
     * - /analytics/v1/fan/top?period=month&limit=20
     */
    register_rest_route('analytics/v1', '/(?P<post_type>[a-z_]+)/top', [
        'methods' => 'GET',
        'callback' => function($request) {
            $post_type = $request['post_type'];
            $period = $request->get_param('period') ?? 'total';
            $limit = $request->get_param('limit') ?? 10;
            
            // 验证 post_type 是否存在
            if (!post_type_exists($post_type)) {
                return resError('无效的文章类型', null, 'invalid_post_type');
            }
            
            $analytics = theme('analytics');
            $top_content = $analytics->getTopContent($post_type, $period, $limit);
            
            return resOK([
                'post_type' => $post_type,
                'period' => $period,
                'limit' => $limit,
                'items' => $top_content,
            ]);
        },
        'permission_callback' => '__return_true',
        'args' => [
            'period' => [
                'type' => 'string',
                'default' => 'total',
                'enum' => ['total', 'week', 'month'],
                'description' => '统计周期',
            ],
            'limit' => [
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'description' => '返回数量',
            ],
        ],
    ]);
});

