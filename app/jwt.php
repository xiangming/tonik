<?php
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
 * 从JWT Token里面解析出user id
 * https://developer.wordpress.org/reference/functions/get_user_id_from_string/
 * @param   [type]  $token  [$token description]
 *
 * @return  [type]          [return description]
 */
if (!function_exists('getUserIdFromJwtToken')) {
    function getUserIdFromJwtToken($token)
    {
        $array = explode(".", $token);
        $user = json_decode(base64_decode($array[1]))->data->user;
        if ($user) {
            return $user->id;
        }

        return 0;
    }
}
