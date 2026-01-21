<?php
/**
 * Launch 项目 - REST API 扩展
 * 
 * 文件位置: app/Projects/Launch/Structure/rest-api.php
 * 
 * 说明：
 * 1. 在 REST API 响应中添加完整的分类和标签信息
 * 2. 提供 _details 字段，包含 name, slug, description
 */

namespace App\Projects\Launch\Structure;

/**
 * 添加产品分类和标签的详细信息到 REST API 响应
 */
function add_product_terms_to_rest_response($response, $post, $request) {
    if ($post->post_type !== 'products') {
        return $response;
    }

    $data = $response->get_data();

    // 获取产品分类详细信息
    $categories = wp_get_post_terms($post->ID, 'product_category');
    $category_details = [];
    
    if (!is_wp_error($categories) && !empty($categories)) {
        foreach ($categories as $category) {
            $category_details[] = [
                'id'          => $category->term_id,
                'name'        => $category->name,
                'slug'        => $category->slug,
                'description' => $category->description,
                'count'       => $category->count,
            ];
        }
    }

    // 获取产品标签详细信息
    $tags = wp_get_post_terms($post->ID, 'product_tag');
    $tag_details = [];
    
    if (!is_wp_error($tags) && !empty($tags)) {
        foreach ($tags as $tag) {
            $tag_details[] = [
                'id'          => $tag->term_id,
                'name'        => $tag->name,
                'slug'        => $tag->slug,
                'description' => $tag->description,
                'count'       => $tag->count,
            ];
        }
    }

    // 添加到响应数据
    $data['product_categories_details'] = $category_details;
    $data['product_tags_details'] = $tag_details;

    $response->set_data($data);
    return $response;
}

// 注册 Hook
add_filter('rest_prepare_products', __NAMESPACE__ . '\\add_product_terms_to_rest_response', 10, 3);
