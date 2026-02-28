<?php

namespace App\Modules\Analytics\Api;

use function Tonik\Theme\App\theme;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\resError;

/**
 * Analytics REST API 端点
 *
 * - POST /analytics/v1/track              追踪浏览/点击
 * - GET  /analytics/v1/{type}/{id}/trends 趋势数据
 * - GET  /analytics/v1/{type}/top         热门内容
 */

class AnalyticsApi
{
    public static function register()
    {
        /**
         * 追踪端点 - 记录浏览或点击
         * 
         * POST /wp-json/analytics/v1/track
         * Body: { "post_type": "site", "post_id": 123, "action": "view" }
         */
        register_rest_route('analytics/v1', '/track', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handleTrack'],
            'permission_callback' => '__return_true',
            'args' => [
                'post_type' => ['required' => true, 'type' => 'string'],
                'post_id'   => ['required' => true, 'type' => 'integer'],
                'action'    => ['required' => true, 'type' => 'string', 'enum' => ['view', 'click']],
            ],
        ]);

        /**
         * 趋势数据端点
         * 
         * GET /wp-json/analytics/v1/{post_type}/{id}/trends?days=30
         */
        register_rest_route('analytics/v1', '/(?P<post_type>[a-z_]+)/(?P<id>\d+)/trends', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handleTrends'],
            'permission_callback' => '__return_true',
            'args' => [
                'days' => ['type' => 'integer', 'default' => 30, 'minimum' => 1, 'maximum' => 90],
            ],
        ]);

        /**
         * 热门内容端点
         * 
         * GET /wp-json/analytics/v1/{post_type}/top?period=week&limit=10
         */
        register_rest_route('analytics/v1', '/(?P<post_type>[a-z_]+)/top', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handleTop'],
            'permission_callback' => '__return_true',
            'args' => [
                'period' => ['type' => 'string', 'default' => 'total', 'enum' => ['total', 'week', 'month']],
                'limit'  => ['type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 100],
            ],
        ]);
    }

    public static function handleTrack($request)
    {
        $post_type = $request->get_param('post_type');
        $post_id   = $request->get_param('post_id');
        $action    = $request->get_param('action');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== $post_type) {
            return resError('文章不存在', null, 'post_not_found');
        }

        $analytics = theme('analytics');

        if ($action === 'view') {
            $analytics->trackView($post_type, $post_id);
        } else {
            $analytics->trackClick($post_type, $post_id);
        }

        return resOK(['post_id' => $post_id, 'action' => $action], '追踪成功');
    }

    public static function handleTrends($request)
    {
        $post_type = $request['post_type'];
        $post_id   = $request['id'];
        $days      = $request->get_param('days');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== $post_type) {
            return resError('文章不存在', null, 'post_not_found');
        }

        $analytics = theme('analytics');

        return resOK([
            'post_id'   => $post_id,
            'post_type' => $post_type,
            'trends'    => [
                'views'  => $analytics->getTrend($post_type, $post_id, $days, 'views'),
                'clicks' => $analytics->getTrend($post_type, $post_id, $days, 'clicks'),
            ],
            'stats' => [
                'today' => [
                    'views'  => (int) get_post_meta($post_id, "{$post_type}_views_today", true),
                    'clicks' => (int) get_post_meta($post_id, "{$post_type}_clicks_today", true),
                ],
                'week' => [
                    'views'  => (int) get_post_meta($post_id, "{$post_type}_views_week", true),
                    'clicks' => (int) get_post_meta($post_id, "{$post_type}_clicks_week", true),
                ],
                'month' => [
                    'views'  => (int) get_post_meta($post_id, "{$post_type}_views_month", true),
                    'clicks' => (int) get_post_meta($post_id, "{$post_type}_clicks_month", true),
                ],
                'total' => [
                    'views'  => (int) get_post_meta($post_id, "{$post_type}_views", true),
                    'clicks' => (int) get_post_meta($post_id, "{$post_type}_clicks", true),
                ],
                'last_viewed' => get_post_meta($post_id, "{$post_type}_last_viewed", true),
            ],
        ]);
    }

    public static function handleTop($request)
    {
        $post_type = $request['post_type'];
        $period    = $request->get_param('period');
        $limit     = $request->get_param('limit');

        if (!post_type_exists($post_type)) {
            return resError('无效的文章类型', null, 'invalid_post_type');
        }

        $top_content = theme('analytics')->getTopContent($post_type, $period, $limit);

        return resOK([
            'post_type' => $post_type,
            'period'    => $period,
            'limit'     => $limit,
            'items'     => $top_content,
        ]);
    }
}
