<?php

namespace Tonik\Theme\App\Setup;

use function Tonik\Theme\App\theme;

/*
|-----------------------------------------------------------
| Theme Filters
|-----------------------------------------------------------
|
| This file purpose is to include your theme various
| filters hooks, which changes output or behaviour
| of different parts of WordPress functions.
|
 */

/**
 * Hides sidebar on index template on specific views.
 *
 * @see apply_filters('theme/index/sidebar/visibility')
 * @see apply_filters('theme/single/sidebar/visibility')
 */
function show_index_sidebar($status)
{
    if (is_404() || is_page()) {
        return false;
    }

    return $status;
}
add_filter('theme/index/sidebar/visibility', 'Tonik\Theme\App\Setup\show_index_sidebar');
add_filter('theme/single/sidebar/visibility', 'Tonik\Theme\App\Setup\show_index_sidebar');

/**
 * Shortens posts excerpts to 200 words.
 *
 * @return integer
 */
function modify_excerpt_length()
{
    return 200;
}
add_filter('excerpt_length', 'Tonik\Theme\App\Setup\modify_excerpt_length');

/**
 * Filter the excerpt "read more" string.
 *
 * @param string $more "Read more" excerpt string.
 * @return string (Maybe) modified "read more" excerpt string.
 */
function modify_excerpt_more($more)
{
    return '...';
}
add_filter('excerpt_more', 'Tonik\Theme\App\Setup\modify_excerpt_more');

/**
 * 去掉content和excerpt里面的<p></p>
 */
remove_filter('the_content', 'wpautop');
remove_filter('the_excerpt', 'wpautop');

/**
 * rewrite 'wp-json' REST API prefix with 'api'
 */
add_filter('rest_url_prefix', function () {
    return 'api';
});

/**
 * 通过 jwt_auth_expire 这个filter，将token有效期设置为一年
 */
add_filter('jwt_auth_expire', function ($issuedAt) {
    // return $issuedAt + (DAY_IN_SECONDS * 365);
    return time() + (DAY_IN_SECONDS * 365);
});

/**
 * 修改 /token 接口的返回值
 */
add_filter('jwt_auth_token_before_dispatch', function ($data, $user) {
    $data['user_id'] = $user->ID; // 用于前端from字段
    // $data['username'] = $user->user_login;
    // $avatar = get_avatar_url($user->ID);
    // $data['user_avatar'] = $avatar;
    $data['user_roles'] = $user->caps;
    return $data;
}, 10, 3);

/**
 * 定制管理后台文章列表列
 *
 * https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/
 */
add_filter('manage_donation_posts_columns', function ($columns) {
    // unset( $columns['author'] );
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
    // return $columns;

    // 调整位置
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

/**
 * 修改API中的post对象
 */
function _modify_rest_prepare($response, $post, $request)
{
    $_data = $response->data;
    $author_uid = $post->post_author;

    // // My custom fields that I want to include in the WP API v2 responce
    // $fields = ['job_title', 'job_city', 'job_highlight'];
    // foreach ( $fields as $field ) {
    //   $_data[$field] = get_post_meta( $pid, $field, true );
    // }

    // 将author_name和author_slug显示到post接口，方便前端展示
    $author = get_userdata($author_uid);
    $_data['author_name'] = $author->display_name;
    $_data['author_slug'] = $author->user_nicename;

    // 当该post有权限要求且当前用户没有权限时，则根据当前用户贡献度来过滤正文和摘要
    if ($_data['permission'] > 0 && !current_user_can('edit_post', $_data['id'])) {
        $current_user_id = wp_get_current_user()->ID;
        $current_user_contribution = theme('stat')->getUserContribution($current_user_id, $author_uid);

        if ($current_user_contribution < $_data['permission']) {
            $_data['content'] = false;
            // $_data['excerpt'] = false;
        }
    }

    $response->data = $_data;
    return $response;
}
add_filter('rest_prepare_post', 'Tonik\Theme\App\Setup\_modify_rest_prepare', 10, 3);
add_filter('rest_prepare_donation', 'Tonik\Theme\App\Setup\_modify_rest_prepare', 10, 3);

// 修改API中的comment对象
add_filter('rest_prepare_comment', function ($response, $post, $request) {
    $_data = $response->data;

    // 获取用户数据
    $user = get_userdata($_data['author']);
    // $_data['author_name'] = $user->display_name;
    $_data['author_slug'] = $user->user_nicename;

    // 获取parent用户数据
    $parent = get_comment($_data['parent']);
    if ($parent) {
        $_data['parent_name'] = $parent->comment_author;
    }

    // "comment_ID": "5",
    // "comment_post_ID": "11",
    // "comment_author": "Arvin",
    // "comment_author_email": "282818269@qq.com",
    // "comment_author_url": "https://afdian.net/a/evanyou?tab=home",
    // "comment_author_IP": "::1",
    // "comment_date": "2024-02-05 22:22:52",
    // "comment_date_gmt": "2024-02-05 14:22:52",
    // "comment_content": "写的真好",
    // "comment_karma": "0",
    // "comment_approved": "1",
    // "comment_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36",
    // "comment_type": "comment",
    // "comment_parent": "0",
    // "user_id": "1"

    $response->data = $_data;
    return $response;
}, 10, 3);

/**
 * /wp/v2/donation?to=1
 *
 * from: https://wordpress.stackexchange.com/questions/332310/how-to-search-by-metadata-using-rest-api
 */
add_filter('rest_donation_query', function ($args, $request) {
    if ($to = $request->get_param('to')) {
        $args['meta_key'] = 'to';
        $args['meta_value'] = $to;
    }
    return $args;
}, 99, 2);

/**
 * 使用nice_after来绕过REST不支持strtotime日期格式的问题
 *
 * 比如：/wp/v2/orders?nice_after=-30 days
 *
 * https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
 */
function _modify_rest_query($args, $request)
{
    if ($before = $request->get_param('nice_before')) {
        $args['date_query']['before'] = $before;
    }

    if ($after = $request->get_param('nice_after')) {
        $args['date_query']['after'] = $after;
    }

    return $args;
}
add_filter('rest_post_query', 'Tonik\Theme\App\Setup\_modify_rest_query', 99, 2);
add_filter('rest_orders_query', 'Tonik\Theme\App\Setup\_modify_rest_query', 99, 2);
add_filter('rest_donation_query', 'Tonik\Theme\App\Setup\_modify_rest_query', 99, 2);

/**
 * WP默认不返回没有发表过内容的用户数据，比如：http://localhost/wp/api/wp/v2/users/3217
 *
 * 具体细节：https://wordpress.stackexchange.com/questions/331042/unable-to-get-the-info-of-the-user-which-doesnt-have-created-any-post-via-rest
 *
 * 我们这里通过filter来放开这个限制，但是需要注意数据安全
 */
// add_filter('rest_request_before_callbacks', function ($response, $handler, $request) {
//     // // 其他路由，放行
//     // if (\WP_REST_Server::READABLE !== $request->get_method()) {
//     //     return $response;
//     // }

//     // // 其他路由，放行
//     // if (!preg_match('~/wp/v2/users/\d+~', $request->get_route())) {
//     //     return $response;
//     // }

//     add_filter('get_usernumposts', function ($count) {
//         return $count > 0 ? $count : 1;
//     });

//     return $response;
// }, 10, 3);

// REST-API触发这个钩子
add_filter('rest_pre_echo_response', function ($response, $handler, $request) {
    // 仅/wp/v2/users?slug=xxx下触发，统计主页访问次数
    if ($request->get_route() === '/wp/v2/users' && $request->has_param('slug')) {
        $slugs = $request->get_param('slug');

        //* Get the ID
        $user = get_user_by('slug', $slugs[0]);

        if ($user) {
            theme('stat')->setUserViews($user->ID);
        }
    }

    // 仅/wp/v2/posts?slug=xxx下触发，统计文章访问次数
    if ($request->get_route() === '/wp/v2/posts' && $request->has_param('slug')) {
        $slugs = $request->get_param('slug');

        //* Get the ID
        $post_id = current($response)['id'];

        if ($post_id) {
            theme('stat')->setPostViews($post_id);
        }
    }

    // Return the response
    return $response;
}, 10, 3);
