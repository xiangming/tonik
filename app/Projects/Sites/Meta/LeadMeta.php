<?php

/**
 * Lead Meta Fields
 * 
 * 为 Lead 自定义文章类型注册 REST API 字段
 */

namespace App\Projects\Sites\Meta;

class LeadMeta
{
    /**
     * 注册所有 Lead REST 字段
     */
    public static function register()
    {
        self::registerProductIdField();
        self::registerSourceField();
        self::registerIpAddressField();
    }

    /**
     * 产品ID字段
     */
    private static function registerProductIdField()
    {
        register_rest_field('lead', 'product_id', [
            'get_callback' => function ($object) {
                return get_post_meta($object['id'], 'product_id', true);
            },
            'update_callback' => function ($value, $object) {
                if (current_user_can('edit_post', $object->ID)) {
                    update_post_meta($object->ID, 'product_id', sanitize_text_field($value));
                }
            },
            'schema' => [
                'type' => 'string',
                'description' => '关联的产品ID',
                'context' => ['view', 'edit'],
            ],
        ]);
    }

    /**
     * 来源字段
     */
    private static function registerSourceField()
    {
        register_rest_field('lead', 'source', [
            'get_callback' => function ($object) {
                return get_post_meta($object['id'], 'source', true);
            },
            'update_callback' => function ($value, $object) {
                if (current_user_can('edit_post', $object->ID)) {
                    update_post_meta($object->ID, 'source', sanitize_text_field($value));
                }
            },
            'schema' => [
                'type' => 'string',
                'description' => '线索来源',
                'context' => ['view', 'edit'],
            ],
        ]);
    }

    /**
     * IP地址字段（仅管理员可见）
     */
    private static function registerIpAddressField()
    {
        register_rest_field('lead', 'ip_address', [
            'get_callback' => function ($object) {
                if (current_user_can('manage_options')) {
                    return get_post_meta($object['id'], 'ip_address', true);
                }
                return null;
            },
            'schema' => [
                'type' => 'string',
                'description' => '提交者IP地址',
                'context' => ['edit'],
            ],
        ]);
    }
}
