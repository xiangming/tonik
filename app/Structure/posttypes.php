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
