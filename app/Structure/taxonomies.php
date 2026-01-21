<?php

namespace Tonik\Theme\App\Structure;

/*
|-----------------------------------------------------------
| Theme Custom Taxonomies
|-----------------------------------------------------------
|
| This file is for registering your theme custom taxonomies.
| Taxonomies help to classify posts and custom post types.
|
*/

use function Tonik\Theme\App\config;

/**
 * Registers `book_genre` custom taxonomy.
 * 已禁用 - 如需使用请取消注释
 *
 * @return void
 */
/*
function register_book_genre_taxonomy()
{
    register_taxonomy('book_genre', 'book', [
        'rewrite' => [
            'slug' => 'books/genre',
            'with_front' => true,
            'hierarchical' => true,
        ],
        'hierarchical' => true,
        'public' => true,
        'labels' => [
            'name' => _x('Genres', 'taxonomy general name', config('textdomain')),
            'singular_name' => _x('Genre', 'taxonomy singular name', config('textdomain')),
            'search_items' => __('Search Genres', config('textdomain')),
            'all_items' => __('All Genres', config('textdomain')),
            'parent_item' => __('Parent Genre', config('textdomain')),
            'parent_item_colon' => __('Parent Genre:', config('textdomain')),
            'edit_item' => __('Edit Genre', config('textdomain')),
            'update_item' => __('Update Genre', config('textdomain')),
            'add_new_item' => __('Add New Genre', config('textdomain')),
            'new_item_name' => __('New Genre Name', config('textdomain')),
            'menu_name' => __('Genre', config('textdomain')),
        ],
    ]);
}
add_action('init', 'Tonik\Theme\App\Structure\register_book_genre_taxonomy');
*/

/**
 * 注册 order_type taxonomy 用于订单类型管理
 * 
 * 使用 taxonomy 而非 post_meta 的优势：
 * 1. 性能优化：有专门的索引表，查询速度快
 * 2. 数据规范：只能使用预定义的术语，避免拼写错误
 * 3. 自动统计：自动聚合每种类型的订单数量
 * 4. REST API 支持：自动生成 API 端点
 *
 * @return void
 */
function register_order_type_taxonomy()
{
    register_taxonomy('order_type', 'orders', [
        'hierarchical' => false,  // 像标签（非层级结构）
        'public' => false,        // 不在前台显示
        'show_ui' => true,        // 显示管理界面
        'show_in_rest' => true,   // REST API 可用
        'show_admin_column' => true,  // 在订单列表显示列
        'labels' => [
            'name' => '订单类型',
            'singular_name' => '订单类型',
            'all_items' => '所有类型',
            'edit_item' => '编辑类型',
            'menu_name' => '订单类型',
        ],
    ]);
    
    // 初始化订单类型（只在未创建时执行）
    $order_types = [
        'donation' => '打赏',
        'membership' => '会员订阅',
        'product' => '商品购买',
        'service' => '服务预订',
        'recharge' => '余额充值',
    ];
    
    foreach ($order_types as $slug => $name) {
        if (!term_exists($slug, 'order_type')) {
            wp_insert_term($name, 'order_type', ['slug' => $slug]);
        }
    }
}
add_action('init', 'Tonik\Theme\App\Structure\register_order_type_taxonomy');
