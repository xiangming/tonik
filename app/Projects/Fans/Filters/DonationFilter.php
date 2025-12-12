<?php

namespace App\Projects\Fans\Filters;

use function Tonik\Theme\App\theme;

/**
 * 打赏相关过滤器
 * 
 * 包含：
 * - 后台列表定制
 * - REST 响应修改
 * - REST 查询修改
 * - 用户访问统计
 */
class DonationFilter
{
    /**
     * 注册所有打赏相关过滤器
     */
    public static function register()
    {
        self::registerAdminFilters();
        self::registerRestPrepareFilters();
        self::registerRestQueryFilters();
        self::registerStatsFilters();
    }

    /**
     * 后台管理过滤器
     */
    private static function registerAdminFilters()
    {
        // 定制管理后台打赏列表列
        add_filter('manage_donation_posts_columns', function ($columns) {
            unset($columns['categories']);
            unset($columns['tags']);
            unset($columns['comments']);
            unset($columns['date']);
            unset($columns['views']);

            $columns['title'] = __('订单号');
            $columns['productName'] = __('商品名称');
            $columns['author'] = __('打赏人');
            $columns['status'] = __('状态');
            $columns['to'] = __('被打赏人');
            $columns['amount'] = __('打赏金额');
            $columns['time'] = __('打赏时间');

            // 调整位置
            $sort_columns = [];
            foreach ($columns as $key => $value) {
                if ($key == 'date') {
                    $sort_columns['title'] = __('订单号');
                    $sort_columns['productName'] = __('商品名称');
                    $sort_columns['status'] = __('状态');
                    $sort_columns['to'] = __('被打赏人');
                    $sort_columns['author'] = __('打赏人');
                    $sort_columns['amount'] = __('打赏金额');
                    $sort_columns['time'] = __('打赏时间');
                }
                $sort_columns[$key] = $value;
            }
            return $sort_columns;
        });
    }

    /**
     * REST 响应修改过滤器
     */
    private static function registerRestPrepareFilters()
    {
        // 修改API中的donation对象
        add_filter('rest_prepare_donation', function ($response, $post, $request) {
            $_data = $response->data;
            $author_uid = $post->post_author;

            // 将author_name和author_slug显示到接口，方便前端展示
            $author = get_userdata($author_uid);
            $_data['author_name'] = $author->display_name;
            $_data['author_slug'] = $author->user_nicename;

            // 权限控制（如果需要）
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
        // 支持按被打赏人查询：/wp/v2/donation?to=1
        add_filter('rest_donation_query', function ($args, $request) {
            if ($to = $request->get_param('to')) {
                $args['meta_key'] = 'to';
                $args['meta_value'] = $to;
            }
            return $args;
        }, 99, 2);

        // 支持日期格式查询：/wp/v2/donation?nice_after=-30 days
        add_filter('rest_donation_query', function ($args, $request) {
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
     * 用户访问统计过滤器
     */
    private static function registerStatsFilters()
    {
        // REST-API触发这个钩子，统计用户主页访问次数
        add_filter('rest_pre_echo_response', function ($response, $handler, $request) {
            // 仅/wp/v2/users?slug=xxx下触发
            if ($request->get_route() === '/wp/v2/users' && $request->has_param('slug')) {
                $slugs = $request->get_param('slug');
                $user = get_user_by('slug', $slugs[0]);

                if ($user) {
                    theme('stat')->setUserViews($user->ID);
                }
            }

            return $response;
        }, 10, 3);
    }
}
