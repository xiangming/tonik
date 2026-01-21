<?php

/**
 * Launch Project - REST API Extensions
 * 
 * Add detailed category and tag information to REST API responses
 */

namespace Tonik\Theme\App\Projects\Launch\Structure;

/**
 * Add product terms details to REST API response
 * 
 * Adds complete category and tag information to products REST API responses
 * including name, slug, description for easy frontend consumption
 */
function add_product_terms_to_rest_response($response, $post, $request) {
    if ($post->post_type !== 'products') {
        return $response;
    }

    $data = $response->get_data();

    // Get product category details
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

    // Get product tag details
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

    // Add to response data
    $data['product_categories_details'] = $category_details;
    $data['product_tags_details'] = $tag_details;

    $response->set_data($data);
    return $response;
}

// Register hook
add_filter('rest_prepare_products', __NAMESPACE__ . '\\add_product_terms_to_rest_response', 10, 3);
