<?php
/**
 * Creating a Custom Post Type
 *
 * @package yuancheng
 * @author arvinxiang.com
 * @since 1.0
 */

use function Tonik\Theme\App\config;

/**
 * Registers `donation` custom post type.
 *
 * @return void
 */
add_action('init', function () {
    // $labels = array(
    //     'name' => _x('打赏', 'Post Type General Name', 'text_domain'),
    //     'singular_name' => _x('打赏', 'Post Type Singular Name', 'text_domain'),
    //     'menu_name' => __('打赏', 'text_domain'),
    //     'name_admin_bar' => __('打赏', 'text_domain'),
    //     'archives' => __('Item Archives', 'text_domain'),
    //     'attributes' => __('Item Attributes', 'text_domain'),
    //     'parent_item_colon' => __('Parent 打赏:', 'text_domain'),
    //     'all_items' => __('全部打赏', 'text_domain'),
    //     'add_new_item' => __('创建新打赏', 'text_domain'),
    //     'add_new' => __('创建打赏', 'text_domain'),
    //     'new_item' => __('New Item', 'text_domain'),
    //     'edit_item' => __('Edit 打赏', 'text_domain'),
    //     'update_item' => __('Update 打赏', 'text_domain'),
    //     'view_item' => __('View 打赏', 'text_domain'),
    //     'view_items' => __('View Donations', 'text_domain'),
    //     'search_items' => __('搜索打赏', 'text_domain'),
    //     'not_found' => __('No donation found', 'text_domain'),
    //     'not_found_in_trash' => __('No donation found in Trash', 'text_domain'),
    //     'featured_image' => __('Featured Image', 'text_domain'),
    //     'set_featured_image' => __('Set featured image', 'text_domain'),
    //     'remove_featured_image' => __('Remove featured image', 'text_domain'),
    //     'use_featured_image' => __('Use as featured image', 'text_domain'),
    //     'insert_into_item' => __('Insert into item', 'text_domain'),
    //     'uploaded_to_this_item' => __('Uploaded to this item', 'text_domain'),
    //     'items_list' => __('Donations list', 'text_domain'),
    //     'items_list_navigation' => __('Donations list navigation', 'text_domain'),
    //     'filter_items_list' => __('Filter donation list', 'text_domain'),
    // );
    // $args = array(
    //     'label' => __('打赏', 'text_domain'),
    //     'description' => __('打赏 information pages.', 'text_domain'),
    //     'labels' => $labels,
    //     'supports' => array('author', 'title', 'custom-fields'),
    //     // 'taxonomies'          => array( 'category', 'post_tag' ),
    //     'hierarchical' => false,
    //     'public' => true,
    //     'show_ui' => true,
    //     'show_in_menu' => true,
    //     'show_in_rest' => true,
    //     'menu_position' => 5,
    //     'show_in_admin_bar' => true,
    //     'show_in_nav_menus' => true,
    //     'can_export' => true,
    //     'has_archive' => true,
    //     'exclude_from_search' => false,
    //     'publicly_queryable' => true,
    //     'capability_type' => 'post',
    //     'rewrite' => array('slug' => 'donation', 'with_front' => false),
    //     //'rewrite'             => array( 'slug' => '/', 'with_front' => false )// hide slug in URL
    // );

    register_post_type('donation', [
        'description' => __('Collection of donations.', config('textdomain')),
        'public' => true,
        'supports' => ['author', 'custom-fields'],
        'show_in_rest' => true, // 将自动生成一组类似posts的接口
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
    // flush_rewrite_rules();
});

/**
 * 后台文章列表显示打赏信息
 * @author arvinxiang.com
 * @since 1.0
 */
add_filter('manage_orders_posts_columns', function ($columns) {
    // unset( $columns['author'] );
    unset($columns['categories']);
    unset($columns['tags']);
    unset($columns['comments']);
    unset($columns['date']);
    unset($columns['views']);

    $columns['title'] = __('订单号');
    $columns['productName'] = __('商品名称');
    $columns['author'] = __('打赏人');
    $columns['status'] = __('状态');
    $columns['to'] = __('被打赏人');
    $columns['amount'] = __('打赏金额');
    $columns['time'] = __('打赏时间');
    // return $columns;

    // 调整位置
    foreach ($columns as $key => $value) {
        if ($key == 'date') {
            $sort_columns['title'] = __('订单号');
            $sort_columns['productName'] = __('商品名称');
            $sort_columns['status'] = __('状态');
            $sort_columns['to'] = __('被打赏人');
            $sort_columns['author'] = __('打赏人');
            $sort_columns['amount'] = __('打赏金额');
            $sort_columns['time'] = __('打赏时间');
        }
        $sort_columns[$key] = $value;
    }
    return $sort_columns;
});

add_action('manage_orders_posts_custom_column', function ($column_name, $id) {
    if ($column_name == 'productName') {
        $productName = get_post_meta($id, "productName", true);
        echo $productName;
    }

    if ($column_name == 'to') {
        $to = get_post_meta($id, "to", true);
        echo $to;
    }

    if ($column_name == 'status') {
        if (get_post_status($id) == 'publish') {
            echo '<span style="color:#179B16;font-weight:bold;">已打款</span>';
        } else if (get_post_status($id) == 'draft') {
            echo '未支付';
        } else if (get_post_status($id) == 'pending') {
            echo '<span style="color:#fc703e;font-weight:bold;">待打款</span>';
        } else if (get_post_status($id) == 'private') {
            echo '<span style="font-weight:bold;">未收到款项，已关闭</span>';
        }
    }

    if ($column_name == 'amount') {
        $amount = get_post_meta($id, "amount", true);
        echo $amount;
    }

    if ($column_name == 'time') {
        // $time = get_date_from_gmt(get_post_time( 'Y-m-d H:i:s', $id ));
        $time = get_post_time('Y-m-d H:i:s', false, $id, true);
        echo $time;
    }
}, 10, 2);

// /**
//  * Custom Post Type: add to query
//  */
// function postsFilter( $query ) {
//     if (  $query->is_category() && $query->is_main_query() ) {
//         $query->set('post_type', array('post', 'donation'));
//     }
//     return $query;
// }
// add_filter( 'pre_get_posts', 'postsFilter' );

/**
 * 创建打赏记录
 * @param int $from_user_id  打赏人ID
 * @param int $to_user_id  被打赏人ID
 * @param int $amount 打赏金额
 * @param string $remark 打赏留言，可选
 * @return int $Id
 */
function createDonation($from_user_id, $to_user_id, $amount, $remark, $orderId)
{
    $in_data = array(
        'post_author' => $from_user_id,
        'post_status' => 'publish', // 支付成功后，打赏记录应当是publish
        'post_type' => 'donation', // custom-post-type
    );
    // https://developer.wordpress.org/reference/functions/wp_insert_post/
    // If the $postarr parameter has ‘ID’ set to a value, then post will be updated.
    $in_id = wp_insert_post($in_data, true);

    // 订单提交错误
    if (is_wp_error($in_id)) {
        $errmsg = $in_id->get_error_message();
        return new WP_Error(1, $errmsg);
    }

    // 被打赏人
    if (isset($to_user_id)) {
        update_post_meta($in_id, 'to', $to_user_id);
    }

    // 打赏金额
    if (isset($amount)) {
        update_post_meta($in_id, 'amount', $amount);
    }

    // 备注
    if (isset($remark)) {
        update_post_meta($in_id, 'remark', $remark);
    }

    // 关联订单
    if (isset($orderId)) {
        update_post_meta($in_id, 'orderId', $orderId);
    }

    return $in_id;
}
