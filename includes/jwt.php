<?php
/**
 * 通过 jwt_auth_expire 这个filter，将token有效期设置为一年
 */
function fqht_jwt_auth_expire( $issuedAt ) {
  // return $issuedAt + (DAY_IN_SECONDS * 365);
  return time() + (DAY_IN_SECONDS * 365);
}
add_filter( 'jwt_auth_expire', 'fqht_jwt_auth_expire' );

/**
 * rewrite 'wp-json' REST API prefix with 'api'
 */
add_filter( 'rest_url_prefix', function() {
    return 'api';
});
