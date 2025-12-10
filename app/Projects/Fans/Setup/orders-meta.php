<?php

namespace Tonik\Theme\App\Setup;

/**
 * 订单字段注册 - 统一使用 register_rest_field 确保接口一致性
 * 
 * 所有字段都注册到顶层，便于前端对接
 */
function register_orders_fields()
{
    // 服务名称 - 从 post meta 获取
    register_rest_field('orders', 'name', array(
        'get_callback' => function ($object) {
            return sanitize_text_field(get_post_meta($object['id'], 'name', true));
        },
        'schema' => array(
            'type' => 'string',
            'description' => '服务名称',
            'readonly' => true,
        ),
    ));

    // 订单金额 - 从 post meta 获取
    register_rest_field('orders', 'amount', array(
        'get_callback' => function ($object) {
            return (int) get_post_meta($object['id'], 'amount', true);
        },
        'schema' => array(
            'type' => 'integer',
            'description' => '订单金额',
            'readonly' => true,
        ),
    ));

    // 支付方式 - 从 post meta 获取
    register_rest_field('orders', 'method', array(
        'get_callback' => function ($object) {
            return sanitize_text_field(get_post_meta($object['id'], 'method', true));
        },
        'schema' => array(
            'type' => 'string',
            'description' => '支付方式',
            'readonly' => true,
        ),
    ));
}

// 初始化
add_action('rest_api_init', 'Tonik\Theme\App\Setup\register_orders_fields');
