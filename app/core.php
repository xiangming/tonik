<?php
/**
 * 更新 post_status
 * 用法 update_post_status($pid, 'publish');
 */
if ( !function_exists('update_post_status') ) {
    function update_post_status($pid, $status) {
        // https://developer.wordpress.org/reference/functions/wp_update_post/
        // The date does not have to be set for drafts. You can set the date and it will not be overridden.
        return wp_update_post( array(
            'ID'          => $pid,
            // 'post_type'   => 'orders',
            'post_status' => $status,
        ), true );
    }
}