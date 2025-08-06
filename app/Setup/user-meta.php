<?php

namespace Tonik\Theme\App\Setup;

use function Tonik\Theme\App\theme;

/**
 * 用户字段注册 - 统一使用 register_rest_field 确保接口一致性
 * 
 * 所有字段都注册到顶层，便于前端对接
 */
function register_user_fields()
{
    // 公开字段 - 所有人可读
    $public_fields = [
        'creating' => array(
            'description' => '正在创造什么',
            'editable' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'avatar' => array(
            'description' => '头像地址',
            'editable' => false, // 只读，通过单独上传接口更新
            'sanitize_callback' => 'esc_url_raw',
        ),
        'background' => array(
            'description' => '封面图地址',
            'editable' => false, // 只读，通过单独上传接口更新
            'sanitize_callback' => 'esc_url_raw',
        ),
        'gender' => array(
            'description' => '性别',
            'editable' => true,
            'sanitize_callback' => function ($value) {
                return in_array($value, ['male', 'female', 'other']) ? $value : '';
            },
        ),
        'birthday' => array(
            'description' => '生日',
            'editable' => true,
            'sanitize_callback' => function ($value) {
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
            },
        ),
        'city' => array(
            'description' => '居住地',
            'editable' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'hideGuide' => array(
            'description' => '是否隐藏新手引导',
            'editable' => true,
            'sanitize_callback' => function ($value) {
                return (bool) $value;
            },
        ),
    ];

    foreach ($public_fields as $field_name => $config) {
        register_rest_field('user', $field_name, array(
            'get_callback' => function ($object) use ($field_name) {
                $value = get_user_meta($object['id'], $field_name, true);
                return $field_name === 'hideGuide' ? (bool) $value : $value;
            },
            'update_callback' => $config['editable'] ? function ($value, $object) use ($field_name, $config) {
                $current_user_id = get_current_user_id();
                // 只允许用户更新自己的字段
                if ($current_user_id != $object->ID) {
                    return new \WP_Error(
                        'rest_cannot_edit',
                        '您没有权限编辑这个用户的信息',
                        array('status' => 403)
                    );
                }
                
                $value = call_user_func($config['sanitize_callback'], $value);
                return update_user_meta($object->ID, $field_name, $value);
            } : null,
            'schema' => array(
                'type' => $field_name === 'hideGuide' ? 'boolean' : 'string',
                'description' => $config['description'],
                'readonly' => !$config['editable'],
            ),
        ));
    }

    // 私有字段 - 仅用户自己可见和编辑（敏感信息）
    $private_fields = [
        'realname' => '真实姓名',
        'alipay' => '支付宝账号',
        'wechat' => '微信号',
        'qq' => 'QQ号',
    ];
    
    foreach ($private_fields as $field_name => $description) {
        register_rest_field('user', $field_name, array(
            'get_callback' => function ($object) use ($field_name) {
                $current_user_id = get_current_user_id();
                // 严格检查：只有用户自己可以看到私人信息
                if ($current_user_id != $object['id']) {
                    return null; // 不返回任何信息，字段不出现在响应中
                }
                
                $value = get_user_meta($object['id'], $field_name, true);
                return $value;
            },
            'update_callback' => function ($value, $object) use ($field_name) {
                $current_user_id = get_current_user_id();
                // 严格检查：只有用户自己可以更新私人信息
                if ($current_user_id != $object->ID) {
                    return new \WP_Error(
                        'rest_cannot_edit',
                        '您没有权限编辑这个用户的私有信息',
                        array('status' => 403)
                    );
                }
                
                // 验证和清理数据
                switch ($field_name) {
                    case 'alipay':
                        if (is_email($value)) {
                            $value = sanitize_email($value);
                        } elseif (!preg_match('/^1[3-9]\d{9}$/', $value)) {
                            $value = '';
                        }
                        break;
                    case 'qq':
                        $value = preg_match('/^\d{5,11}$/', $value) ? $value : '';
                        break;
                    default:
                        $value = sanitize_text_field($value);
                }
                
                return update_user_meta($object->ID, $field_name, $value);
            },
            'schema' => array(
                'type' => 'string',
                'description' => $description,
            ),
        ));
    }

    // 注册时间（从 WP_User 对象获取）
    register_rest_field('user', 'registered', array(
        'get_callback' => function ($object) {
            $user = get_user_by('id', $object['id']);
            return $user ? $user->user_registered : '';
        },
        'schema' => array(
            'type' => 'string',
            'format' => 'date-time',
            'readonly' => true,
        ),
    ));

    // 公开统计字段 - 所有人可读的统计数据
    $public_stats = [
        'supporters' => '总打赏人数',
        'views' => '主页访问次数',
        'posts' => '已发布的动态数量',
    ];

    foreach ($public_stats as $field_name => $description) {
        register_rest_field('user', $field_name, array(
            'get_callback' => function ($object) use ($field_name) {
                switch ($field_name) {
                    case 'supporters':
                        return theme('stat')->getTotalSupporters($object['id']);
                    case 'views':
                        return theme('stat')->getUserViews($object['id']);
                    case 'posts':
                        return (int) count_user_posts($object['id'], 'post', true);
                    default:
                        return 0;
                }
            },
            'schema' => array(
                'type' => 'integer',
                'description' => $description,
                'readonly' => true,
            ),
        ));
    }

    // 私有统计字段 - 仅用户自己可见的敏感统计数据
    $private_stats = [
        'income' => '总收入',
    ];

    foreach ($private_stats as $field_name => $description) {
        register_rest_field('user', $field_name, array(
            'get_callback' => function ($object) use ($field_name) {
                $current_user_id = get_current_user_id();
                // 严格检查：只有用户自己可以看到收入信息
                if ($current_user_id != $object['id']) {
                    return null; // 不返回任何信息，字段不出现在响应中
                }
                
                return theme('stat')->getTotalIncome($object['id']);
            },
            'schema' => array(
                'type' => 'integer',
                'description' => $description,
                'readonly' => true,
            ),
        ));
    }

    // 状态字段 - 布尔值计算字段
    $computed_status = [
        'hasPayment' => '收款信息是否完整',
        'hasSupporters' => '是否有被打赏的记录',
    ];

    foreach ($computed_status as $field_name => $description) {
        register_rest_field('user', $field_name, array(
            'get_callback' => function ($object) use ($field_name) {
                switch ($field_name) {
                    case 'hasPayment':
                        return theme('user')->hasPayment($object['id']);
                    case 'hasSupporters':
                        return theme('user')->hasSupporters($object['id']);
                    default:
                        return false;
                }
            },
            'schema' => array(
                'type' => 'boolean',
                'description' => $description,
                'readonly' => true,
            ),
        ));
    }

    // 社交关系字段
    register_rest_field('user', 'following', array(
        'get_callback' => function ($object) {
            return theme('user')->getFollowing();
        },
        'update_callback' => function ($value, $object, $field, $request) {
            if ($request->has_param('unFollow')) {
                return theme('user')->unFollow($value);
            }
            return theme('user')->follow($value);
        },
        'schema' => array(
            'type' => 'integer',
            'description' => '关注列表操作',
        ),
    ));

    register_rest_field('user', 'followed', array(
        'get_callback' => function ($object) {
            return theme('user')->isFollowed($object['id']);
        },
        'schema' => array(
            'type' => 'boolean',
            'description' => '当前用户是否关注目标用户',
            'readonly' => true,
        ),
    ));
}

// 初始化
add_action('rest_api_init', 'Tonik\Theme\App\Setup\register_user_fields');
