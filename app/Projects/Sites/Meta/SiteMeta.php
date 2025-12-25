<?php

/**
 * Site Meta Fields
 * 
 * 为 Site 自定义文章类型注册 REST API 字段
 */

namespace App\Projects\Sites\Meta;

class SiteMeta
{
    /**
     * 注册所有 Site REST 字段
     */
    public static function register()
    {
        self::registerSiteDataField();
        self::registerSiteSettingsField();
        self::registerUserInputField();
        self::registerLocalKeyField();
        self::registerAnalyticsFields();
    }

    /**
     * 站点数据字段
     */
    private static function registerSiteDataField()
    {
        register_rest_field('site', 'site_data', [
            'get_callback' => function ($object) {
                $data = get_post_meta($object['id'], 'site_data', true);
                return $data ? json_decode($data, true) : null;
            },
            'update_callback' => function ($value, $object) {
                if (current_user_can('edit_post', $object->ID)) {
                    $json = is_string($value) ? $value : wp_json_encode($value);
                    update_post_meta($object->ID, 'site_data', $json);
                }
            },
            'schema' => [
                'type' => ['object', 'null'],
                'description' => '站点数据',
                'context' => ['view', 'edit'],
            ],
        ]);
    }

    /**
     * 站点设置字段
     */
    private static function registerSiteSettingsField()
    {
        register_rest_field('site', 'site_settings', [
            'get_callback' => function ($object) {
                $data = get_post_meta($object['id'], 'site_settings', true);
                return $data ? json_decode($data, true) : null;
            },
            'update_callback' => function ($value, $object) {
                if (current_user_can('edit_post', $object->ID)) {
                    $json = is_string($value) ? $value : wp_json_encode($value);
                    update_post_meta($object->ID, 'site_settings', $json);
                }
            },
            'schema' => [
                'type' => ['object', 'null'],
                'description' => '站点配置',
                'context' => ['view', 'edit'],
            ],
        ]);
    }

    /**
     * 用户输入字段
     */
    private static function registerUserInputField()
    {
        register_rest_field('site', 'user_input', [
            'get_callback' => function ($object) {
                $data = get_post_meta($object['id'], 'user_input', true);
                return $data ? json_decode($data, true) : null;
            },
            'update_callback' => function ($value, $object) {
                if (current_user_can('edit_post', $object->ID)) {
                    $json = is_string($value) ? $value : wp_json_encode($value);
                    update_post_meta($object->ID, 'user_input', $json);
                }
            },
            'schema' => [
                'type' => ['object', 'null'],
                'description' => '用户输入数据',
                'context' => ['view', 'edit'],
            ],
        ]);
    }

    /**
     * 本地密钥字段
     */
    private static function registerLocalKeyField()
    {
        register_rest_field('site', 'local_key', [
            'get_callback' => function ($object) {
                return get_post_meta($object['id'], 'local_key', true);
            },
            'update_callback' => function ($value, $object) {
                if (current_user_can('edit_post', $object->ID)) {
                    update_post_meta($object->ID, 'local_key', sanitize_text_field($value));
                }
            },
            'schema' => [
                'type' => 'string',
                'description' => '本地密钥',
                'context' => ['view', 'edit'],
            ],
        ]);
    }

    /**
     * Analytics 统计字段（使用通用 AnalyticsService - 混合存储）
     */
    private static function registerAnalyticsFields()
    {
        // 完整的分析数据（包含所有维度）
        register_rest_field('site', 'analytics', [
            'get_callback' => function ($object) {
                if (function_exists('theme') && theme('analytics')) {
                    return theme('analytics')->getAnalytics($object['id'], 'site');
                }
                
                // 降级方案：直接读取 meta
                return [
                    'views' => (int) get_post_meta($object['id'], 'site_views', true),
                    'clicks' => (int) get_post_meta($object['id'], 'site_clicks', true),
                    'views_today' => (int) get_post_meta($object['id'], 'site_views_today', true),
                    'views_week' => (int) get_post_meta($object['id'], 'site_views_week', true),
                    'views_month' => (int) get_post_meta($object['id'], 'site_views_month', true),
                    'clicks_today' => (int) get_post_meta($object['id'], 'site_clicks_today', true),
                    'clicks_week' => (int) get_post_meta($object['id'], 'site_clicks_week', true),
                    'clicks_month' => (int) get_post_meta($object['id'], 'site_clicks_month', true),
                    'conversion_rate' => 0,
                    'conversion_rate_week' => 0,
                    'last_viewed' => get_post_meta($object['id'], 'site_last_viewed', true),
                ];
            },
            'schema' => [
                'type' => 'object',
                'description' => '站点统计数据（所有维度）',
                'readonly' => true,
                'properties' => [
                    'views' => [
                        'type' => 'integer',
                        'description' => '总浏览量',
                    ],
                    'clicks' => [
                        'type' => 'integer',
                        'description' => '总点击量',
                    ],
                    'views_today' => [
                        'type' => 'integer',
                        'description' => '今日浏览量',
                    ],
                    'views_week' => [
                        'type' => 'integer',
                        'description' => '本周浏览量',
                    ],
                    'views_month' => [
                        'type' => 'integer',
                        'description' => '本月浏览量',
                    ],
                    'clicks_today' => [
                        'type' => 'integer',
                        'description' => '今日点击量',
                    ],
                    'clicks_week' => [
                        'type' => 'integer',
                        'description' => '本周点击量',
                    ],
                    'clicks_month' => [
                        'type' => 'integer',
                        'description' => '本月点击量',
                    ],
                    'conversion_rate' => [
                        'type' => 'number',
                        'description' => '总转化率',
                    ],
                    'conversion_rate_week' => [
                        'type' => 'number',
                        'description' => '本周转化率',
                    ],
                    'last_viewed' => [
                        'type' => 'string',
                        'description' => '最后浏览时间',
                    ],
                ],
            ],
        ]);
    }
}
