<?php

/**
 * Launch Project - Modify Default Post Type for Products
 * 
 * Use default post type as products, register custom meta fields
 */

namespace Tonik\Theme\App\Projects\Launch\Structure;

/**
 * Modify default post type labels for products
 */
function modify_post_type_for_products() {
    global $wp_post_types;
    
    $labels = [
        'name'                  => '产品',
        'singular_name'         => '产品',
        'menu_name'             => '产品',
        'name_admin_bar'        => '产品',
        'add_new'               => '添加新产品',
        'add_new_item'          => '添加新产品',
        'new_item'              => '新产品',
        'edit_item'             => '编辑产品',
        'view_item'             => '查看产品',
        'all_items'             => '所有产品',
        'search_items'          => '搜索产品',
        'not_found'             => '未找到产品',
        'not_found_in_trash'    => '回收站中未找到产品',
    ];

    $wp_post_types['post']->labels = (object) $labels;
    $wp_post_types['post']->label = '产品';
    $wp_post_types['post']->menu_icon = 'dashicons-laptop';
    $wp_post_types['post']->rewrite = ['slug' => 'product'];
    $wp_post_types['post']->rest_base = 'products';
}

/**
 * Register product meta fields for REST API
 */
function register_product_meta_fields() {
    // Product tagline
    register_post_meta('post', 'tagline', [
        'type'              => 'string',
        'description'       => '产品标语（一句话介绍）',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Product website URL
    register_post_meta('post', 'website_url', [
        'type'              => 'string',
        'description'       => '产品官网 URL',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    // Product logo URL
    register_post_meta('post', 'logo_url', [
        'type'              => 'string',
        'description'       => '产品 Logo URL',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    // Product views count
    register_post_meta('post', 'product_views', [
        'type'              => 'integer',
        'description'       => '产品浏览量',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);

    // Product clicks count
    register_post_meta('post', 'product_clicks', [
        'type'              => 'integer',
        'description'       => '产品点击量（访问官网次数）',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);

    // Product images array
    register_post_meta('post', 'images', [
        'type'              => 'array',
        'description'       => '产品图片',
        'single'            => true,
        'show_in_rest'      => [
            'schema' => [
                'type'  => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ],
        ],
        'default'           => [],
        'sanitize_callback' => function($value) {
            if (!is_array($value)) {
                return [];
            }
            // Filter out empty strings and validate URLs
            $filtered = array_filter($value, function($url) {
                return !empty($url) && is_string($url) && filter_var($url, FILTER_VALIDATE_URL);
            });
            return array_values(array_map('esc_url_raw', $filtered));
        },
    ]);
}

// Register hooks
add_action('init', __NAMESPACE__ . '\\modify_post_type_for_products');
add_action('init', __NAMESPACE__ . '\\register_product_meta_fields');
