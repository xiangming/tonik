<?php
/**
 * Creating a Custom Post Type
 *
 * @package yuancheng
 * @author arvinxiang.com
 * @since 1.0
 */

/**
 * Custom Post Type: Register
 */
function order_post_type()
{

    $labels = array(
        'name' => _x('订单', 'Post Type General Name', 'text_domain'),
        'singular_name' => _x('订单', 'Post Type Singular Name', 'text_domain'),
        'menu_name' => __('订单', 'text_domain'),
        'name_admin_bar' => __('订单', 'text_domain'),
        'archives' => __('Item Archives', 'text_domain'),
        'attributes' => __('Item Attributes', 'text_domain'),
        'parent_item_colon' => __('Parent 订单:', 'text_domain'),
        'all_items' => __('全部订单', 'text_domain'),
        'add_new_item' => __('创建新订单', 'text_domain'),
        'add_new' => __('创建订单', 'text_domain'),
        'new_item' => __('New Item', 'text_domain'),
        'edit_item' => __('Edit 订单', 'text_domain'),
        'update_item' => __('Update 订单', 'text_domain'),
        'view_item' => __('View 订单', 'text_domain'),
        'view_items' => __('View Orders', 'text_domain'),
        'search_items' => __('Search order', 'text_domain'),
        'not_found' => __('No order found', 'text_domain'),
        'not_found_in_trash' => __('No order found in Trash', 'text_domain'),
        'featured_image' => __('Featured Image', 'text_domain'),
        'set_featured_image' => __('Set featured image', 'text_domain'),
        'remove_featured_image' => __('Remove featured image', 'text_domain'),
        'use_featured_image' => __('Use as featured image', 'text_domain'),
        'insert_into_item' => __('Insert into item', 'text_domain'),
        'uploaded_to_this_item' => __('Uploaded to this item', 'text_domain'),
        'items_list' => __('Orders list', 'text_domain'),
        'items_list_navigation' => __('Orders list navigation', 'text_domain'),
        'filter_items_list' => __('Filter orders list', 'text_domain'),
    );
    $args = array(
        'label' => __('订单', 'text_domain'),
        'description' => __('订单 information pages.', 'text_domain'),
        'labels' => $labels,
        'supports' => array('author', 'title', 'custom-fields'),
        // 'taxonomies'          => array( 'category', 'post_tag' ),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => true,
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type' => 'post',
        'rewrite' => array('slug' => 'o', 'with_front' => false),
        //'rewrite'             => array( 'slug' => '/', 'with_front' => false )// hide slug in URL
    );
    register_post_type('orders', $args); // order会导致无法排序，只能改名
    flush_rewrite_rules();
}
add_action('init', 'order_post_type', 0);

/**
 * 后台文章列表显示订单信息
 * @author arvinxiang.com
 * @since 1.0
 */
if (!function_exists('custom_manage_posts_column') && !function_exists('manage_posts_custom_column_show')) {
    function custom_manage_posts_column($columns)
    {
        // unset( $columns['author'] );
        unset($columns['categories']);
        unset($columns['tags']);
        unset($columns['comments']);
        unset($columns['date']);
        unset($columns['views']);

        $columns['title'] = __('交易号');
        $columns['orderName'] = __('产品名称');
        $columns['author'] = __('创建者');
        $columns['status'] = __('状态');
        $columns['totalFee'] = __('订单金额');
        $columns['time'] = __('下单时间');
        // return $columns;

        // 调整位置
        foreach ($columns as $key => $value) {
            if ($key == 'date') {
                $sort_columns['title'] = __('交易号');
                $sort_columns['orderName'] = __('产品名称');
                $sort_columns['author'] = __('创建者');
                $sort_columns['status'] = __('状态');
                $sort_columns['totalFee'] = __('订单金额');
                $sort_columns['time'] = __('下单时间');
            }
            $sort_columns[$key] = $value;
        }
        return $sort_columns;
    }
    add_filter('manage_orders_posts_columns', 'custom_manage_posts_column');

    function manage_posts_custom_column_show($column_name, $id)
    {
        if ($column_name == 'orderName') {
            $orderName = get_post_meta($id, "orderName", true);
            echo $orderName;
        } else if ($column_name == 'status') {
            if (get_post_status($id) == 'publish') {
                echo '<span style="color:#179B16;font-weight:bold;">已支付</span>';
            } else if (get_post_status($id) == 'draft') {
                echo '未支付';
            } else if (get_post_status($id) == 'private') {
                echo '<span style="color:#fc703e;font-weight:bold;">超时关闭</span>';
            }
        } else if ($column_name == 'totalFee') {
            $totalFee = get_post_meta($id, "totalFee", true);
            echo $totalFee;
        } else if ($column_name == 'time') {
            // $time = get_date_from_gmt(get_post_time( 'Y-m-d H:i:s', $id ));
            $time = get_post_time('Y-m-d H:i:s', false, $id, true);
            echo $time;
        } else {
            return;
        }
    }
    add_action('manage_orders_posts_custom_column', 'manage_posts_custom_column_show', 10, 2);
}

// /**
//  * Custom Post Type: add to query
//  */
// function postsFilter( $query ) {
//     if (  $query->is_category() && $query->is_main_query() ) {
//         $query->set('post_type', array('post', 'order'));
//     }
//     return $query;
// }
// add_filter( 'pre_get_posts', 'postsFilter' );
