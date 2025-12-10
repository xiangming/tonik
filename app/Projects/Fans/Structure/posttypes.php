<?php

namespace Tonik\Theme\App\Projects\Fans\Structure;

use function Tonik\Theme\App\config;

/**
 * Fans 项目的自定义文章类型
 * 
 * 包含：orders（订单）、donation（打赏）
 */
function register_post_types()
{
    // Orders - 订单
    register_post_type('orders', [
        'description' => __('Collection of orders.', config('textdomain')),
        'public' => true,
        'supports' => ['title', 'author', 'custom-fields'],
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-text',
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

    // Donation - 打赏
    register_post_type('donation', [
        'description' => __('Collection of donations.', config('textdomain')),
        'public' => true,
        'supports' => ['title', 'author', 'custom-fields'],
        'show_in_rest' => true,
        'rest_base' => 'donations',
        'menu_icon' => 'dashicons-coffee',
        'labels' => [
            'name' => _x('Donations', 'post type general name', config('textdomain')),
            'singular_name' => _x('Donation', 'post type singular name', config('textdomain')),
            'menu_name' => _x('Donations', 'admin menu', config('textdomain')),
            'name_admin_bar' => _x('Donation', 'add new on admin bar', config('textdomain')),
            'add_new' => _x('Add New', 'donation', config('textdomain')),
            'add_new_item' => __('Add New Donation', config('textdomain')),
            'new_item' => __('New Donation', config('textdomain')),
            'edit_item' => __('Edit Donation', config('textdomain')),
            'view_item' => __('View Donation', config('textdomain')),
            'all_items' => __('All Donations', config('textdomain')),
            'search_items' => __('Search Donations', config('textdomain')),
            'parent_item_colon' => __('Parent Donations:', config('textdomain')),
            'not_found' => __('No donations found.', config('textdomain')),
            'not_found_in_trash' => __('No donations found in Trash.', config('textdomain')),
        ],
    ]);
}
add_action('init', __NAMESPACE__ . '\\register_post_types');
