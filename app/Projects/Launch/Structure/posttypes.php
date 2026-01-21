<?php

/**
 * Launch Project - Custom Post Types & Meta Fields
 * 
 * Register products post type, taxonomies, and meta fields
 */

namespace Tonik\Theme\App\Projects\Launch\Structure;

/**
 * Register Products custom post type
 */
function register_products_post_type() {
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
        'parent_item_colon'     => '父级产品：',
        'not_found'             => '未找到产品',
        'not_found_in_trash'    => '回收站中未找到产品',
    ];

    $args = [
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => ['slug' => 'product'],
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-laptop',
        'show_in_rest'        => true,
        'rest_base'           => 'products',
        'supports'            => ['title', 'editor', 'author', 'custom-fields'],
    ];

    register_post_type('products', $args);
    
    // Register meta fields immediately after post type registration
    register_product_meta_fields();
}

/**
 * Register product category taxonomy
 */
function register_product_category_taxonomy() {
    $labels = [
        'name'                       => '产品分类',
        'singular_name'              => '产品分类',
        'search_items'               => '搜索分类',
        'all_items'                  => '所有分类',
        'parent_item'                => '父级分类',
        'parent_item_colon'          => '父级分类：',
        'edit_item'                  => '编辑分类',
        'update_item'                => '更新分类',
        'add_new_item'               => '添加新分类',
        'new_item_name'              => '新分类名称',
        'menu_name'                  => '产品分类',
    ];

    $args = [
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
        'show_in_rest'      => true,
        'rest_base'         => 'product_category',
        'rewrite'           => ['slug' => 'category'],
    ];

    register_taxonomy('product_category', ['products'], $args);
}

/**
 * Register product tag taxonomy
 */
function register_product_tag_taxonomy() {
    $labels = [
        'name'                       => '产品标签',
        'singular_name'              => '产品标签',
        'search_items'               => '搜索标签',
        'popular_items'              => '热门标签',
        'all_items'                  => '所有标签',
        'edit_item'                  => '编辑标签',
        'update_item'                => '更新标签',
        'add_new_item'               => '添加新标签',
        'new_item_name'              => '新标签名称',
        'separate_items_with_commas' => '用逗号分隔标签',
        'add_or_remove_items'        => '添加或移除标签',
        'choose_from_most_used'      => '从常用标签中选择',
        'menu_name'                  => '产品标签',
    ];

    $args = [
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
        'show_in_rest'      => true,
        'rest_base'         => 'product_tag',
        'rewrite'           => ['slug' => 'tag'],
    ];

    register_taxonomy('product_tag', ['products'], $args);
}

/**
 * Register product meta fields for REST API
 */
function register_product_meta_fields() {
    // Product tagline
    register_post_meta('products', 'tagline', [
        'type'              => 'string',
        'description'       => '产品标语（一句话介绍）',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Product website URL
    register_post_meta('products', 'website_url', [
        'type'              => 'string',
        'description'       => '产品官网 URL',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    // Product logo URL
    register_post_meta('products', 'logo_url', [
        'type'              => 'string',
        'description'       => '产品 Logo URL',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    // Is featured product
    register_post_meta('products', 'is_featured', [
        'type'              => 'boolean',
        'description'       => '是否为精选产品',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);

    // Product views count
    register_post_meta('products', 'product_views', [
        'type'              => 'integer',
        'description'       => '产品浏览量',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);

    // Product clicks count
    register_post_meta('products', 'product_clicks', [
        'type'              => 'integer',
        'description'       => '产品点击量（访问官网次数）',
        'single'            => true,
        'show_in_rest'      => true,
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
}

// Register hooks
add_action('init', __NAMESPACE__ . '\\register_products_post_type');
add_action('init', __NAMESPACE__ . '\\register_product_category_taxonomy');
add_action('init', __NAMESPACE__ . '\\register_product_tag_taxonomy');
