<?php

namespace Tonik\Theme\App\Setup;

use function Tonik\Theme\App\theme;

/**
 * 打赏字段注册 - 统一使用 register_rest_field 确保接口一致性
 * 
 * 所有字段都注册到顶层，便于前端对接
 */
function register_donation_fields()
{
    // 被打赏人ID - 从 post meta 获取
    register_rest_field('donation', 'to', array(
        'get_callback' => function ($object) {
            return (int) get_post_meta($object['id'], 'to', true);
        },
        'schema' => array(
            'type' => 'integer',
            'description' => '被打赏人用户ID',
            'readonly' => true,
        ),
    ));

    // 打赏金额 - 从 donation 服务获取
    register_rest_field('donation', 'amount', array(
        'get_callback' => function ($object) {
            $donation_data = theme('donation')->getDonationById($object['id']);
            
            if (!$donation_data['status'] || empty($donation_data['data'])) {
                return 0;
            }
            
            return (float) ($donation_data['data']['amount'] ?? 0);
        },
        'schema' => array(
            'type' => 'number',
            'description' => '打赏金额',
            'readonly' => true,
        ),
    ));

    // 打赏留言 - 从 donation 服务获取
    register_rest_field('donation', 'remark', array(
        'get_callback' => function ($object) {
            $donation_data = theme('donation')->getDonationById($object['id']);
            
            if (!$donation_data['status'] || empty($donation_data['data'])) {
                return '';
            }
            
            return sanitize_text_field($donation_data['data']['remark'] ?? '');
        },
        'schema' => array(
            'type' => 'string',
            'description' => '打赏留言',
            'readonly' => true,
        ),
    ));
}

// 初始化
add_action('rest_api_init', 'Tonik\Theme\App\Setup\register_donation_fields');
