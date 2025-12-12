<?php

namespace App\Projects\Fans\Filters;

use function Tonik\Theme\App\theme;

/**
 * 文章相关过滤器
 * 
 * 包含：
 * - REST 响应修改（添加作者信息、权限控制）
 * - REST 查询修改（日期过滤）
 * - 访问统计
 */
class PostFilter
{
    /**
     * 注册所有文章相关过滤器
     */
    public static function register()
    {
        self::registerRestPrepareFilters();
        self::registerRestQueryFilters();
        self::registerStatsFilters();
    }

    /**
     * REST 响应修改过滤器
     */
    private static function registerRestPrepareFilters()
    {
        // 修改API中的post对象
        add_filter('rest_prepare_post', function ($response, $post, $request) {
            $_data = $response->data;
            $author_uid = $post->post_author;

            // 将author_name和author_slug显示到post接口，方便前端展示
            $author = get_userdata($author_uid);
            $_data['author_name'] = $author->display_name;
            $_data['author_slug'] = $author->user_nicename;

            // 当该post有权限要求且当前用户没有权限时，则根据当前用户贡献度来过滤正文和摘要
            $permission = $_data['permission'] ?? 0;
            if ($permission > 0 && !current_user_can('edit_post', $_data['id'])) {
                $current_user_id = wp_get_current_user()->ID;
                $current_user_contribution = theme('stat')->getUserContribution($current_user_id, $author_uid);

                if ($current_user_contribution < $permission) {
                    $_data['content'] = false;
                }
            }

            $response->data = $_data;
            return $response;
        }, 10, 3);
    }

    /**
     * REST 查询修改过滤器
     */
    private static function registerRestQueryFilters()
    {
        /**
         * 使用nice_after来绕过REST不支持strtotime日期格式的问题
         * 比如：/wp/v2/posts?nice_after=-30 days
         */
        add_filter('rest_post_query', function ($args, $request) {
            if ($before = $request->get_param('nice_before')) {
                $args['date_query']['before'] = $before;
            }

            if ($after = $request->get_param('nice_after')) {
                $args['date_query']['after'] = $after;
            }

            return $args;
        }, 99, 2);
    }

    /**
     * 访问统计过滤器
     */
    private static function registerStatsFilters()
    {
        // REST-API触发这个钩子，统计文章访问次数
        add_filter('rest_pre_echo_response', function ($response, $handler, $request) {
            // 仅/wp/v2/posts?slug=xxx下触发
            if ($request->get_route() === '/wp/v2/posts' && $request->has_param('slug')) {
                $post_id = current($response)['id'] ?? null;
                if ($post_id) {
                    theme('stat')->setPostViews($post_id);
                }
            }

            return $response;
        }, 10, 3);
    }
}
