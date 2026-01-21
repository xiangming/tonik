<?php

/**
 * Site Project - Custom Post Types
 * 
 * 注册 Site 项目的自定义文章类型
 * - Lead (线索)
 * - Site (站点)
 */

namespace App\Projects\Site\Structure;

/**
 * 注册 Lead 自定义文章类型
 */
function register_lead_post_type()
{
    register_post_type('lead', [
        'label' => 'Leads',
        'labels' => [
            'name' => 'Leads',
            'singular_name' => 'Lead',
            'add_new' => '添加新 Lead',
            'add_new_item' => '添加新 Lead',
            'edit_item' => '编辑 Lead',
            'new_item' => '新 Lead',
            'view_item' => '查看 Lead',
            'search_items' => '搜索 Leads',
            'not_found' => '未找到 Lead',
            'not_found_in_trash' => '回收站中未找到 Lead',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-email',
        'menu_position' => 25,
        'supports' => ['title', 'custom-fields'],
        'show_in_rest' => true,
        'rest_base' => 'leads',
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'has_archive' => false,
        'rewrite' => false,
    ]);

    // 注册 Lead Meta 字段
    register_post_meta('lead', 'product_id', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('lead', 'source', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('lead', 'ip_address', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('lead', 'user_agent', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('lead', 'referer', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
}
add_action('init', 'App\Projects\Site\Structure\register_lead_post_type');

/**
 * 注册 Site 自定义文章类型
 */
function register_site_post_type()
{
    register_post_type('site', [
        'labels' => [
            'name' => '站点',
            'singular_name' => '站点',
            'add_new' => '添加站点',
            'add_new_item' => '添加新站点',
            'edit_item' => '编辑站点',
            'new_item' => '新站点',
            'view_item' => '查看站点',
            'search_items' => '搜索站点',
            'not_found' => '未找到站点',
            'not_found_in_trash' => '回收站中未找到站点',
        ],
        'public' => true,
        'show_in_rest' => true,
        'rest_base' => 'sites',
        'supports' => ['title', 'editor', 'custom-fields'],
        'has_archive' => false,
        'menu_icon' => 'dashicons-admin-site',
        'menu_position' => 26,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'rewrite' => [
            'slug' => 'site',
            'with_front' => false,
        ],
    ]);

    // 注册 Site Meta 字段
    register_post_meta('site', 'site_data', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => function($value) {
            // 验证 JSON 格式
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $value : '';
            }
            return wp_json_encode($value);
        },
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('site', 'site_settings', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => function($value) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $value : '';
            }
            return wp_json_encode($value);
        },
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('site', 'user_input', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => function($value) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $value : '';
            }
            return wp_json_encode($value);
        },
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('site', 'local_key', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);

    // Analytics Meta 字段（使用通用 AnalyticsService - 混合存储策略）
    
    // 总浏览量和点击量
    register_post_meta('site', 'site_views', [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('site', 'site_clicks', [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);
    
    // 今日数据
    register_post_meta('site', 'site_views_today', [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    register_post_meta('site', 'site_clicks_today', [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    // 本周数据
    register_post_meta('site', 'site_views_week', [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    register_post_meta('site', 'site_clicks_week', [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    // 本月数据
    register_post_meta('site', 'site_views_month', [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    register_post_meta('site', 'site_clicks_month', [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    // 每日详细数据（JSON）
    register_post_meta('site', 'site_views_daily', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    register_post_meta('site', 'site_clicks_daily', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    // 最后浏览时间
    register_post_meta('site', 'site_last_viewed', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    // 辅助字段（用于日期检查）
    register_post_meta('site', 'site_views_today_date', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('site', 'site_clicks_today_date', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('site', 'site_views_week_start', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('site', 'site_clicks_week_start', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('site', 'site_views_month_start', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('site', 'site_clicks_month_start', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
}
add_action('init', 'App\Projects\Site\Structure\register_site_post_type');

/**
 * 自定义 Lead 后台列表列
 */
function lead_custom_columns($columns)
{
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = '邮箱';
    $new_columns['product_id'] = '产品 ID';
    $new_columns['source'] = '来源';
    $new_columns['date'] = '提交时间';
    
    return $new_columns;
}
add_filter('manage_lead_posts_columns', 'App\Projects\Site\Structure\lead_custom_columns');

/**
 * 填充 Lead 自定义列内容
 */
function lead_custom_column_content($column, $post_id)
{
    switch ($column) {
        case 'product_id':
            echo esc_html(get_post_meta($post_id, 'product_id', true));
            break;
        case 'source':
            $source = get_post_meta($post_id, 'source', true);
            if ($source) {
                echo '<span style="display:inline-block;padding:2px 8px;background:#f0f0f0;border-radius:4px;font-size:12px;">' 
                    . esc_html($source) . '</span>';
            }
            break;
    }
}
add_action('manage_lead_posts_custom_column', 'App\Projects\Site\Structure\lead_custom_column_content', 10, 2);

/**
 * 使 Lead 自定义列可排序
 */
function lead_sortable_columns($columns)
{
    $columns['product_id'] = 'product_id';
    $columns['source'] = 'source';
    
    return $columns;
}
add_filter('manage_edit-lead_sortable_columns', 'App\Projects\Site\Structure\lead_sortable_columns');

/**
 * 自定义 Site 后台列表列
 */
function site_custom_columns($columns)
{
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = '站点名称';
    $new_columns['site_views'] = '浏览量';
    $new_columns['site_clicks'] = '点击量';
    $new_columns['date'] = '创建时间';
    
    return $new_columns;
}
add_filter('manage_site_posts_columns', 'App\Projects\Site\Structure\site_custom_columns');

/**
 * 填充 Site 自定义列内容
 */
function site_custom_column_content($column, $post_id)
{
    switch ($column) {
        case 'site_views':
            $views = get_post_meta($post_id, 'site_views', true);
            echo '<strong>' . number_format((int)$views) . '</strong>';
            break;
        case 'site_clicks':
            $clicks = get_post_meta($post_id, 'site_clicks', true);
            echo '<strong>' . number_format((int)$clicks) . '</strong>';
            break;
    }
}
add_action('manage_site_posts_custom_column', 'App\Projects\Site\Structure\site_custom_column_content', 10, 2);

/**
 * 使 Site 自定义列可排序
 */
function site_sortable_columns($columns)
{
    $columns['site_views'] = 'site_views';
    $columns['site_clicks'] = 'site_clicks';
    
    return $columns;
}
add_filter('manage_edit-site_sortable_columns', 'App\Projects\Site\Structure\site_sortable_columns');

/**
 * 处理自定义列排序
 */
function site_orderby_meta($query)
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('site_views' === $orderby) {
        $query->set('meta_key', 'site_views');
        $query->set('orderby', 'meta_value_num');
    }

    if ('site_clicks' === $orderby) {
        $query->set('meta_key', 'site_clicks');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'App\Projects\Site\Structure\site_orderby_meta');
