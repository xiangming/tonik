<?php
/**
 * 添加 CORS 跨域 header
 * @author arvinxiang.com
 * @since 1.0
 */
add_action('rest_api_init', function() {
  /* unhook default function */
  remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

  /* then add your own filter */
  add_filter('rest_pre_serve_request', function( $value ) {
      $origin = get_http_origin();

      if ( $origin ) {
          $my_sites = array( 'http://localhost:3000', 'http://localhost:3300', 'https://apis.chuchuang.work', );
          if ( in_array( $origin, $my_sites ) ) {
              $origin = esc_url_raw( $origin );
              header( 'Access-Control-Allow-Origin: ' . $origin );
              header( 'Access-Control-Allow-Headers: X-Requested-With, X-YC-Appid, X-YC-Appkey, content-type, Authorization' );
              header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
              header( 'Access-Control-Allow-Credentials: true' );
              header( 'Vary: Origin', false );
          }
      } elseif ( ! headers_sent() && 'GET' === $_SERVER['REQUEST_METHOD'] && ! is_user_logged_in() ) {
          header( 'Vary: Origin', false );
      }

      return $value;
  });
}, 15);