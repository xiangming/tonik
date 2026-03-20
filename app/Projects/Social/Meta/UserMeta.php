<?php

namespace App\Projects\Social\Meta;

/**
 * 社交项目用户字段注册
 *
 * 公开字段：avatar, gender, birthday, city, photos, tags
 * 私有字段：wechat, qq, phone
 */
class UserMeta
{
    public static function register()
    {
        self::registerPublicFields();
        self::registerPrivateFields();
    }

    /**
     * 公开字段 - 所有人可读，仅本人可写
     */
    private static function registerPublicFields()
    {
        // ---- avatar ----
        register_rest_field('user', 'avatar', [
            'get_callback' => function ($obj) {
                return get_user_meta($obj['id'], 'avatar', true) ?: '';
            },
            'update_callback' => function ($value, $user) {
                if (get_current_user_id() !== $user->ID) {
                    return new \WP_Error('rest_forbidden', '无权编辑', ['status' => 403]);
                }
                return update_user_meta($user->ID, 'avatar', esc_url_raw($value));
            },
            'schema' => ['type' => 'string', 'description' => '头像地址'],
        ]);

        // ---- gender ----
        register_rest_field('user', 'gender', [
            'get_callback' => function ($obj) {
                return get_user_meta($obj['id'], 'gender', true) ?: '';
            },
            'update_callback' => function ($value, $user) {
                if (get_current_user_id() !== $user->ID) {
                    return new \WP_Error('rest_forbidden', '无权编辑', ['status' => 403]);
                }
                $v = in_array($value, ['male', 'female', 'other']) ? $value : '';
                return update_user_meta($user->ID, 'gender', $v);
            },
            'schema' => ['type' => 'string', 'description' => '性别'],
        ]);

        // ---- birthday ----
        register_rest_field('user', 'birthday', [
            'get_callback' => function ($obj) {
                return get_user_meta($obj['id'], 'birthday', true) ?: '';
            },
            'update_callback' => function ($value, $user) {
                if (get_current_user_id() !== $user->ID) {
                    return new \WP_Error('rest_forbidden', '无权编辑', ['status' => 403]);
                }
                $v = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
                return update_user_meta($user->ID, 'birthday', $v);
            },
            'schema' => ['type' => 'string', 'description' => '生日（YYYY-MM-DD）'],
        ]);

        // ---- city ----
        register_rest_field('user', 'city', [
            'get_callback' => function ($obj) {
                return get_user_meta($obj['id'], 'city', true) ?: '';
            },
            'update_callback' => function ($value, $user) {
                if (get_current_user_id() !== $user->ID) {
                    return new \WP_Error('rest_forbidden', '无权编辑', ['status' => 403]);
                }
                return update_user_meta($user->ID, 'city', sanitize_text_field($value));
            },
            'schema' => ['type' => 'string', 'description' => '居住城市'],
        ]);

        // ---- 数组字段：photos ----
        register_rest_field('user', 'photos', [
            'get_callback' => function ($obj) {
                $raw = get_user_meta($obj['id'], 'photos', true);
                $decoded = $raw ? json_decode($raw, true) : [];
                return is_array($decoded) ? $decoded : [];
            },
            'update_callback' => function ($value, $user) {
                if (get_current_user_id() !== $user->ID) {
                    return new \WP_Error('rest_forbidden', '无权编辑', ['status' => 403]);
                }
                // 确保每个元素是合法 URL
                $sanitized = array_values(array_filter(
                    array_map('esc_url_raw', (array) $value)
                ));
                return update_user_meta($user->ID, 'photos', json_encode($sanitized));
            },
            'schema' => [
                'type'        => 'array',
                'description' => '相册图片 URL 列表',
                'items'       => ['type' => 'string'],
            ],
        ]);

        // ---- 数组字段：tags ----
        register_rest_field('user', 'tags', [
            'get_callback' => function ($obj) {
                $raw = get_user_meta($obj['id'], 'tags', true);
                $decoded = $raw ? json_decode($raw, true) : [];
                return is_array($decoded) ? $decoded : [];
            },
            'update_callback' => function ($value, $user) {
                if (get_current_user_id() !== $user->ID) {
                    return new \WP_Error('rest_forbidden', '无权编辑', ['status' => 403]);
                }
                $sanitized = array_values(array_map('sanitize_text_field', (array) $value));
                return update_user_meta($user->ID, 'tags', json_encode($sanitized));
            },
            'schema' => [
                'type'        => 'array',
                'description' => '个人标签列表',
                'items'       => ['type' => 'string'],
            ],
        ]);
    }

    /**
     * 私有字段 - 仅本人可见和编辑
     */
    private static function registerPrivateFields()
    {
        $private_fields = [
            'wechat' => ['description' => '微信号', 'sanitize' => 'sanitize_text_field'],
            'qq'     => [
                'description' => 'QQ号',
                'sanitize'    => function ($value) {
                    return preg_match('/^\d{5,11}$/', $value) ? $value : '';
                },
            ],
            'phone'  => [
                'description' => '手机号',
                'sanitize'    => function ($value) {
                    return preg_match('/^1[3-9]\d{9}$/', $value) ? $value : '';
                },
            ],
        ];

        foreach ($private_fields as $field_name => $config) {
            register_rest_field('user', $field_name, [
                'get_callback' => function ($obj) use ($field_name) {
                    // 非本人请求返回 null，与 Fans 项目保持一致
                    if (get_current_user_id() !== (int) $obj['id']) {
                        return null;
                    }
                    return get_user_meta($obj['id'], $field_name, true) ?: '';
                },
                'update_callback' => function ($value, $user) use ($field_name, $config) {
                    if (get_current_user_id() !== $user->ID) {
                        return new \WP_Error('rest_forbidden', '无权编辑私有信息', ['status' => 403]);
                    }
                    $sanitized = is_callable($config['sanitize'])
                        ? call_user_func($config['sanitize'], $value)
                        : call_user_func($config['sanitize'], $value);
                    return update_user_meta($user->ID, $field_name, $sanitized);
                },
                'schema' => [
                    'type'        => 'string',
                    'description' => $config['description'],
                ],
            ]);
        }
    }
}
