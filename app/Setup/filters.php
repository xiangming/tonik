<?php

namespace Tonik\Theme\App\Setup;

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
 * Shortens posts excerpts to 60 words.
 *
 * @return integer
 */
function modify_excerpt_length()
{
    return 60;
}
add_filter('excerpt_length', 'Tonik\Theme\App\Setup\modify_excerpt_length');

/**
 * 通过 jwt_auth_expire 这个filter，将token有效期设置为一年
 */
add_filter('jwt_auth_expire', function ($issuedAt) {
    // return $issuedAt + (DAY_IN_SECONDS * 365);
    return time() + (DAY_IN_SECONDS * 365);
});

/**
 * rewrite 'wp-json' REST API prefix with 'api'
 */
add_filter('rest_url_prefix', function () {
    return 'api';
});

/**
 * 修改 /token 接口返回值，增加avatar和roles字段
 */
add_filter('jwt_auth_token_before_dispatch', function ($data, $user) {
    $avatar = get_avatar_url($user->ID);
    $data['user_roles'] = $user->caps;
    $data['user_avatar'] = $avatar;
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
 * 修改post返回值
 */
function _modify_rest_prepare($response, $post, $request) {
    $_data = $response->data;
    $uid = $post->post_author;

    // // My custom fields that I want to include in the WP API v2 responce
    // $fields = ['job_title', 'job_city', 'job_highlight'];
    // foreach ( $fields as $field ) {
    //   $_data[$field] = get_post_meta( $pid, $field, true );
    // }

    // 获取用户数据
    $user = get_userdata($uid);
    $_data['author_name'] = $user->display_name;
    $_data['author_slug'] = $user->user_nicename;

    // $user = wp_get_current_user();
    // $bookmarks = (array) get_user_meta($user->ID, 'bookmarks', true);
    // $_data['bookmarked'] = in_array($pid, $bookmarks);

    $response->data = $_data;
    return $response;
}
add_filter('rest_prepare_post', 'Tonik\Theme\App\Setup\_modify_rest_prepare', 10, 3);
add_filter('rest_prepare_donation', 'Tonik\Theme\App\Setup\_modify_rest_prepare', 10, 3);

/**
 * /wp/v2/donation?to=1
 * 
 * from: https://wordpress.stackexchange.com/questions/332310/how-to-search-by-metadata-using-rest-api
 */
add_filter( 'rest_donation_query', function( $args, $request ){
    if ( $to = $request->get_param( 'to' ) ) {
        $args['meta_key'] = 'to';
        $args['meta_value'] = $to;
    }
    return $args;
}, 99, 2 );