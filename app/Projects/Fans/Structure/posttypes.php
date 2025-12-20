<?php

namespace Tonik\Theme\App\Projects\Fans\Structure;

use function Tonik\Theme\App\config;

/**
 * Fans 项目的自定义文章类型
 * 
 * 包含：donation（打赏专用 CPT）
 */
function register_post_types()
{
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
