<?php

namespace Tonik\Theme\App\Structure;

/*
|-----------------------------------------------------------
| Theme Custom Post Types
|-----------------------------------------------------------
|
| This file is for registering your theme post types.
| Custom post types allow users to easily create
| and manage various types of content.
|
 */

use function Tonik\Theme\App\config;

/**
 * Registers `book` custom post type.
 * 
 * https://developer.wordpress.org/reference/functions/register_post_type/
 * 
 * https://developer.wordpress.org/resource/dashicons/
 *
 * @return void
 */
function register_post_types()
{
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

    register_post_type('orders', [
        'description' => __('Collection of orders.', config('textdomain')),
        'public' => true,
        'supports' => ['title', 'author', 'custom-fields'],
        'show_in_rest' => true, // 将自动生成一组类似posts的接口
        'menu_icon' => 'dashicons-text',
        'labels' => [
            'name' => _x('Orders', 'post type general name', config('textdomain')),
            'singular_name' => _x('Order', 'post type singular name', config('textdomain')),
            'menu_name' => _x('Orders', 'admin menu', config('textdomain')),
            'name_admin_bar' => _x('Order', 'add new on admin bar', config('textdomain')),
            'add_new' => _x('Add New', 'donation', config('textdomain')),
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
}
add_action('init', 'Tonik\Theme\App\Structure\register_post_types');
