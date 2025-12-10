<?php

namespace Tonik\Theme\App\Setup;

use function Tonik\Theme\App\theme;

/**
 * 文章字段注册 - 统一使用 register_rest_field 确保接口一致性
 * 
 * 所有字段都注册到顶层，便于前端对接
 */
function register_post_fields()
{
    // 内容权限字段 - 设置谁可以查看内容
    register_rest_field('post', 'permission', array(
        'get_callback' => function ($object) {
            return (int) get_post_meta($object['id'], 'permission', true);
        },
        'update_callback' => function ($value, $object) {
            $current_user_id = get_current_user_id();
            // 只允许作者更新自己的文章权限
            if ($current_user_id != $object->post_author) {
                return new \WP_Error(
                    'rest_cannot_edit',
                    '您没有权限编辑这篇文章的权限设置',
                    array('status' => 403)
                );
            }
            
            $value = absint($value);
            return update_post_meta($object->ID, 'permission', $value);
        },
        'schema' => array(
            'type' => 'integer',
            'description' => '查看权限等级',
            'minimum' => 0,
        ),
    ));

    // 内容锁定状态 - 当前用户是否可以查看（动态计算字段）
    register_rest_field('post', 'lock', array(
        'get_callback' => function ($object) {
            $current_user_id = get_current_user_id();
            $permission = (int) get_post_meta($object['id'], 'permission', true);
            
            // 如果没有设置权限要求，默认所有人可看
            if (!$permission) {
                return false;
            }
            
            // 作者自己总是可以看到自己的内容
            if ($current_user_id == $object['author']) {
                return false;
            }
            
            // 检查当前用户的贡献度是否满足要求
            $current_user_contribution = theme('stat')->getUserContribution($current_user_id, $object['author']);
            return $current_user_contribution < $permission;
        },
        'schema' => array(
            'type' => 'boolean',
            'description' => '内容是否被锁定（当前用户无法查看）',
            'readonly' => true,
        ),
    ));
}

// 初始化
add_action('rest_api_init', 'Tonik\Theme\App\Setup\register_post_fields');
