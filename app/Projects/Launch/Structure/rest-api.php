<?php

/**
 * Launch Project - REST API Extensions
 * 
 * Add detailed category and tag information to REST API responses
 */

namespace Tonik\Theme\App\Projects\Launch\Structure;

function add_product_terms_to_rest_response($response, $post, $request) {
    $data = $response->get_data();

    $categories = wp_get_post_terms($post->ID, 'category');
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

    $tags = wp_get_post_terms($post->ID, 'post_tag');
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

    $data['categories_details'] = $category_details;
    $data['tags_details'] = $tag_details;

    $response->set_data($data);
    return $response;
}

add_filter('rest_prepare_post', __NAMESPACE__ . '\\add_product_terms_to_rest_response', 10, 3);
