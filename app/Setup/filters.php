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
    
    // 添加会员信息
    $data['membership_level'] = get_user_meta($user->ID, 'membership_level', true) ?: 'free';
    $data['membership_expire'] = get_user_meta($user->ID, 'membership_expire', true);
    
    return $data;
}, 10, 3);
