<?php

namespace Tonik\Theme\App\Structure;

/*
|-----------------------------------------------------------
| Theme Custom Post Types
|-----------------------------------------------------------
|
| 此文件用于注册通用的自定义文章类型
| 
| 项目特定的 Post Types（如 orders, donation）
| 在 app/Projects/{ProjectName}/Structure/posttypes.php 中注册
|
 */

use function Tonik\Theme\App\config;

/**
 * 注册通用自定义文章类型
 * 
 * https://developer.wordpress.org/reference/functions/register_post_type/
 * https://developer.wordpress.org/resource/dashicons/
 *
 * @return void
 */
function register_post_types()
{
    // Orders - 通用订单系统
    register_post_type('orders', [
        'description' => __('Universal order management system.', config('textdomain')),
        'public' => true,
        'supports' => ['title', 'author', 'custom-fields'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-cart',
        'labels' => [
            'name' => _x('Orders', 'post type general name', config('textdomain')),
            'singular_name' => _x('Order', 'post type singular name', config('textdomain')),
            'menu_name' => _x('Orders', 'admin menu', config('textdomain')),
            'name_admin_bar' => _x('Order', 'add new on admin bar', config('textdomain')),
            'add_new' => _x('Add New', 'order', config('textdomain')),
            'add_new_item' => __('Add New Order', config('textdomain')),
            'new_item' => __('New Order', config('textdomain')),
            'edit_item' => __('Edit Order', config('textdomain')),
            'view_item' => __('View Order', config('textdomain')),
            'all_items' => __('All Orders', config('textdomain')),
            'search_items' => __('Search Orders', config('textdomain')),
            'parent_item_colon' => __('Parent Orders:', config('textdomain')),
            'not_found' => __('No orders found.', config('textdomain')),
            'not_found_in_trash' => __('No orders found in Trash.', config('textdomain')),
        ],
    ]);

    // Book - 示例自定义文章类型（通用）
    register_post_type('book', [
        'description' => __('Collection of books.', config('textdomain')),
        'public' => true,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
        'labels' => [
            'name' => _x('Books', 'post type general name', config('textdomain')),
            'singular_name' => _x('Book', 'post type singular name', config('textdomain')),
            'menu_name' => _x('Books', 'admin menu', config('textdomain')),
            'name_admin_bar' => _x('Book', 'add new on admin bar', config('textdomain')),
            'add_new' => _x('Add New', 'book', config('textdomain')),
            'add_new_item' => __('Add New Book', config('textdomain')),
            'new_item' => __('New Book', config('textdomain')),
            'edit_item' => __('Edit Book', config('textdomain')),
            'view_item' => __('View Book', config('textdomain')),
            'all_items' => __('All Books', config('textdomain')),
            'search_items' => __('Search Books', config('textdomain')),
            'parent_item_colon' => __('Parent Books:', config('textdomain')),
            'not_found' => __('No books found.', config('textdomain')),
            'not_found_in_trash' => __('No books found in Trash.', config('textdomain')),
        ],
    ]);
}
add_action('init', 'Tonik\Theme\App\Structure\register_post_types');

/**
 * Orders 后台列表自定义列
 */
function orders_custom_columns($columns)
{
    // 重新排列列顺序
    $new_columns = [
        'cb' => $columns['cb'] ?? '',
        'title' => $columns['title'] ?? '标题',
        'taxonomy-order_type' => $columns['taxonomy-order_type'] ?? '订单类型',
        'order_amount' => '金额',
        'order_status' => '状态',
        'author' => $columns['author'] ?? '作者',
        'date' => $columns['date'] ?? '日期',
    ];
    return $new_columns;
}
add_filter('manage_orders_posts_columns', 'Tonik\Theme\App\Structure\orders_custom_columns');

/**
 * Orders 后台列表自定义列内容
 */
function orders_custom_column_content($column, $post_id)
{
    switch ($column) {
        case 'order_amount':
            $amount = get_post_meta($post_id, 'amount', true);
            if ($amount) {
                echo '¥' . number_format($amount, 2); // 金额单位已经是元
            } else {
                echo '-';
            }
            break;
        case 'order_status':
            $status = get_post_status($post_id);
            $status_labels = [
                'draft' => '待支付',
                'publish' => '已支付',
                'trash' => '已关闭',
                'pending' => '待审核',
            ];
            $label = $status_labels[$status] ?? $status ?? '-';
            
            // 添加快速筛选链接
            if ($status && $status !== '-') {
                $filter_url = add_query_arg([
                    'post_type' => 'orders',
                    'post_status' => $status,
                ], admin_url('edit.php'));
                echo '<a href="' . esc_url($filter_url) . '">' . esc_html($label) . '</a>';
            } else {
                echo esc_html($label);
            }
            break;
    }
}
add_action('manage_orders_posts_custom_column', 'Tonik\Theme\App\Structure\orders_custom_column_content', 10, 2);
