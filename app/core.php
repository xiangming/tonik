<?php
use App\Validators\Validator;

/**
 * 添加 CORS 跨域 header
 * @author arvinxiang.com
 * @since 1.0
 */
add_action('rest_api_init', function () {
    /* unhook default function */
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

    /* then add your own filter */
    add_filter('rest_pre_serve_request', function ($value) {
        $origin = get_http_origin();

        if ($origin) {
            $my_sites = array('http://localhost:3000', 'http://localhost:3300', 'https://apis.chuchuang.work');
            if (in_array($origin, $my_sites)) {
                $origin = esc_url_raw($origin);
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Headers: X-Requested-With, X-YC-Appid, X-YC-Appkey, content-type, Authorization');
                header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
                header('Access-Control-Allow-Credentials: true');
                header('Vary: Origin', false);
            }
        } elseif (!headers_sent() && 'GET' === $_SERVER['REQUEST_METHOD'] && !is_user_logged_in()) {
            header('Vary: Origin', false);
        }

        return $value;
    });
}, 15);

/**
 * Core REST API
 *
 * 向 user 增加自定义字段
 *
 * 注意：get和set方法获取id的方式不同：$user['id'] 和 $user->ID
 *
 * 注意：Changing or removing data from core REST API endpoint responses can break plugins or WordPress core behavior, and should be avoided wherever possible.
 *
 * https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/
 * 
 * https://developer.wordpress.org/reference/functions/sanitize_textarea_field/
 */
add_action('rest_api_init', function () {
    // 新增字段: creating, 正在创造什么？
    register_rest_field('user', 'creating', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_user_meta($object->ID, $field, $value);
        },
        'show_in_rest' => true,
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    // Valid if it is string
                    return is_string($value);
                },
                // 'validate_callback' => function ($value) {
                //     // Valid if it contains exactly 10 English letters.
                //     return (bool) preg_match('/\A[a-z]{10}\Z/', $value);
                // },
            ),
        ),
    ));
    // 新增字段: avatar_url, 头像地址
    register_rest_field('user', 'avatar_url', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_user_meta($object->ID, $field, $value);
        },
        'show_in_rest' => true,
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    // https://developer.wordpress.org/reference/functions/sanitize_url/
                    return sanitize_url($value, array('http', 'https'));
                },
                'validate_callback' => function ($value) {
                    // Valid if it is valid url
                    return (bool) Validator::isURL($value);
                },
            ),
        ),
    ));
    // 新增字段: background_image_url, 封面图地址
    register_rest_field('user', 'background_image_url', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_user_meta($object->ID, $field, $value);
        },
        'show_in_rest' => true,
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_url($value, array('http', 'https'));
                },
                'validate_callback' => function ($value) {
                    // Valid if it is valid url
                    return (bool) Validator::isURL($value);
                },
            ),
        ),
    ));
    // 新增字段: real, 真实姓名
    register_rest_field('user', 'real', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_user_meta($object->ID, $field, $value);
        },
        // Show in the WP REST API response. Default: false.
        'show_in_rest' => true,
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    // Valid if it is string
                    return is_string($value);
                },
            ),
        ),
    ));
    // 新增字段: phone, 手机号码（国内11位校验）
    register_rest_field('user', 'phone', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_user_meta($object->ID, $field, $value);
        },
        // Show in the WP REST API response. Default: false.
        'show_in_rest' => true,
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    // Valid if it is a valid phone number
                    return (bool) Validator::isPhone($value);
                },
            ),
        ),
    ));
    // 新增字段: zfb_id, 支付宝账号
    register_rest_field('user', 'zfb_id', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_user_meta($object->ID, $field, $value);
        },
        // Show in the WP REST API response. Default: false.
        'show_in_rest' => true,
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    // Valid if it is string
                    return is_string($value);
                },
            ),
        ),
    ));
    // 新增字段: wx_id, 微信账号
    register_rest_field('user', 'wx_id', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_user_meta($object->ID, $field, $value);
        },
        // Show in the WP REST API response. Default: false.
        'show_in_rest' => true,
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    // Valid if it is string
                    return is_string($value);
                },
            ),
        ),
    ));
});
