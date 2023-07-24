<?php
/**
 * WordPress核心相关功能配置和修改
 * @author arvinxiang.com
 * @since 1.0
 */


// //去除头部冗余代码
// remove_action( 'wp_head', 'feed_links_extra', 3 );
// remove_action( 'wp_head', 'rsd_link' );
// remove_action( 'wp_head', 'wlwmanifest_link' );
// remove_action( 'wp_head', 'index_rel_link' );
// remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
// remove_action( 'wp_head', 'wp_generator' );

// //去除emoji相关
// remove_action('wp_head', 'print_emoji_detection_script', 7);
// remove_action('admin_print_scripts', 'print_emoji_detection_script');
// remove_action('wp_print_styles', 'print_emoji_styles');
// remove_action('admin_print_styles', 'print_emoji_styles');
// remove_filter('the_content_feed', 'wp_staticize_emoji');
// remove_filter('comment_text_rss', 'wp_staticize_emoji');
// remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

// // 隐藏 admin Bar
// add_filter( 'show_admin_bar', function(){ return false; } );

// // 禁用可视化编辑器
// add_filter( 'user_can_richedit', function(){ return false; } );

// // 启用特色图片功能
// add_theme_support('post-thumbnails');

// 文章形式
// add_theme_support( 'post-formats', array( 'aside','gallery','link','image','quote','status','video','audio','chat' ) );

// // 阻止修改密码后，系统自动发送通知邮件
// // https://developer.wordpress.org/reference/functions/__return_false/
// add_filter( 'send_password_change_email', '__return_false' );


// //禁用古腾堡编辑器
// add_filter('use_block_editor_for_post', '__return_false');
// //屏蔽古腾堡的样式加载
// remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );


// // 更改用户主页URL
// if ( ! function_exists( 'rewrite_author_url' ) ) {
//     function rewrite_author_url() {
//         global $wp_rewrite;
//         $wp_rewrite->author_base = 'u'; // 更改前缀为 /u/
//     }
//     add_action('init', 'rewrite_author_url');
// }



// /**
//  * remove open sans from googlefonts
//  * @since 1.0
//  */
// if ( ! function_exists( 'remove_open_sans' ) ) {
//     function remove_open_sans() {
//         wp_deregister_style( 'open-sans' );
//         wp_register_style( 'open-sans', false );
//     }
//     add_action( 'init','remove_open_sans' );
// }



// // 启动主题时清理固定链接缓存
// function rewrite_flush_rules() {
//     global $pagenow, $wp_rewrite;
//     if ( 'themes.php' == $pagenow && isset( $_GET['activated'] ) ){
//         $wp_rewrite->flush_rules();
//     }
// }
// add_action( 'load-themes.php', 'rewrite_flush_rules' );



// /**
//  * 自定义 WordPress 后台底部的版权信息
//  */
// add_filter('admin_footer_text', 'left_admin_footer_text');
// function left_admin_footer_text($text) {
//     // 左边信息
//     $text = '<span id="footer-thankyou">感谢使用 <a href="'.THEME_URI.'" target="_blank">'.THEME_NAME.'</a> 进行创作</span>';
//     return $text;
// }
// add_filter('update_footer', 'right_admin_footer_text', 11);
// function right_admin_footer_text($text) {
//     // 右边信息
//     $text = "当前版本 ".THEME_VERSION;
//     return $text;
// }



// /**
//  * 返回当前用户版本
//  * beta：测试版，勾选了参与测试的用户使用
//  * pro：专业版，收费用户使用
//  */
// if ( !function_exists('getVersion')) {
//     function getVersion() {
//         if (isDevMode()) {
//             return 'dev';
//         }
//         if (isTestMode()) {
//             return 'test';
//         }
//         $uid = get_current_user_id();
//         if(get_user_meta( $uid, 'beta', true ) == '1')
//             return 'beta';
//     }
// }



// /**
//  * 根据URL判断是否开发模式
//  * 开发模式：可看到尚未上线的功能
//  * @since 1.0
//  */
// if ( ! function_exists( 'isDevMode' ) ) {
//     function isDevMode() {
//         if ( isset($_GET['dev']) || isset($_POST['dev']) ) {
//             return true;
//         } else {
//             return false;
//         }
//     }
// }



// /**
//  * 根据URL判断是否测试模式
//  * 测试模式：部分数据会变动，用于测试
//  * @since 1.0
//  */
// if ( ! function_exists( 'isTestMode' ) ) {
//     function isTestMode() {
//         if ( isset($_GET['test']) || isset($_POST['test']) ) {
//             return true;
//         } else {
//             return false;
//         }
//     }
// }



/**
 * 是否admin用户
 * @since 1.0
 */
if ( ! function_exists( 'isAdmin' ) ) {
    function isAdmin() {
        return current_user_can( 'manage_options' ) ? true : false;
    }
}



// /**
//  * 更新 post_author
//  * 用法 updatePostAuthor($pid, $uid);
//  */
// if ( !function_exists('updatePostAuthor') ) {
//     function updatePostAuthor($pid, $uid) {
//         // https://developer.wordpress.org/reference/functions/wp_update_post/
//         return wp_update_post( array(
//             'ID'          => $pid,
//             // 'post_type'   => 'orders',
//             'post_author' => $uid,
//         ), true );
//     }
// }



// /**
//  * 更新 post_status
//  * 用法 updatePostStatus($pid, 'publish');
//  */
// if ( !function_exists('updatePostStatus') ) {
//     function updatePostStatus($pid, $status) {
//         // https://developer.wordpress.org/reference/functions/wp_update_post/
//         // The date does not have to be set for drafts. You can set the date and it will not be overridden.
//         return wp_update_post( array(
//             'ID'          => $pid,
//             // 'post_type'   => 'orders',
//             'post_status' => $status,
//         ), true );
//     }
// }



// /**
//  * 通过post meta查找post
//  * @param string $meta_key
//  * @param string $meta_value
//  * @param mixed $post_status publish, draft ...
//  * @since 1.0
//  */
// if ( ! function_exists( 'getPostByMeta' ) ) {
//     function getPostByMeta($meta_key, $meta_value, $post_status='any', $post_type='any') {
//         $args = array(
//             'post_status'  => $post_status,
//             'post_type'  => $post_type,
//             'fields'     => 'ids',
//             'meta_query' => array(
//                 array(
//                     'key'   => $meta_key,
//                     'value' => $meta_value,
//                 )
//             )
//         );
//         return get_posts($args) ? get_posts($args)[0] : NULL;
//     }
// }



// /**
//  * 通过post meta查找order
//  * @param string $meta_key
//  * @param string $meta_value
//  * @param mixed $post_status publish, draft ...
//  * @since 1.0
//  */
// if ( ! function_exists( 'getOrderByMeta' ) ) {
//     function getOrderByMeta($orderName, $totalFee, $productId) {
//         $args = array(
//             'post_status'  => 'any',
//             'post_type'  => 'orders',
//             'fields'     => 'ids',
//             'meta_query' => array(
//                 array(
//                     'key'     => 'orderName',
//                     'value'   => $orderName,
//                     'compare' => '='
//                 ),
//                 array(
//                     'key'     => 'totalFee',
//                     'value'   => $totalFee,
//                     'compare' => '='
//                 ),
//                 array(
//                     'key'     => 'productId',
//                     'value'   => $productId,
//                     'compare' => '='
//                 )
//             )
//         );
//         return get_posts($args) ? get_posts($args)[0] : NULL;
//     }
// }



// /**
//  * 通过user meta查找user
//  * @param string $meta_key
//  * @param string $meta_value
//  * @usage $user_data = getUserByMeta('phone',$phone);
//  * @since 1.0
//  */
// if ( ! function_exists( 'getUserByMeta' ) ) {
//     function getUserByMeta($meta_key,$meta_value) {
//         $args = array(
//             'meta_key'     => $meta_key,
//             'meta_value'   => $meta_value,
//             'meta_compare' => '=',
//         );

//         $users = get_users( $args );

//         if ( empty( $users ) ) {
//             return NULL;
//         }

//         return $users[0];
//     }
// }



// /**
//  * 通过手机号获取用户
//  * @param $phone 要查询的手机号
//  */
// function getUserByPhone($phone) {
//     return getUserByMeta('phone',$phone);
// }



// /**
//  * base64中文URL
//  */
// if ( !function_exists( 'getBase64' ) ) {
//     function getBase64( $msg ) {
//         return urlencode( base64_encode( $msg ) );
//     }
// }



// /**
//  * 获取邮箱验证链接
//  * @since 1.0
//  */
// if ( !function_exists( 'getEmailActivateUrl' ) ) {
//     function getEmailActivateUrl( $uid, $email, $token ) {
//         // if(empty($uid)) $uid = get_current_user_id();
//         // if(empty($email)) $email = get_userdata($uid)->user_email;
//         // return site_url('/activate?uid='.$uid.'&token='.$token);
//         return site_url('/activate?u='.$uid.'&t='.$token.'&e='.base64_encode($email));
//     }
// }



// /**
//  * 将置顶文章加入全部主循环
//  */
// if ( ! function_exists( 'push_sticky_posts_to_top' ) ) {
//     function push_sticky_posts_to_top($posts) {
//         $stickies = array();
//         foreach($posts as $i => $post) {
//             if(is_sticky($post->ID)) {
//                 $stickies[] = $post;
//                 unset($posts[$i]);
//             }
//         }
//         return array_merge($stickies, $posts);
//     }
//     add_filter('the_posts', 'push_sticky_posts_to_top');
// }



// /**
//  * 返回字符串长度（中文长度为1）
//  * 服务器没有开启相关扩展，此函数将被使用
//  */
// if ( ! function_exists( 'mb_strlen' ) ) {
//     function mb_strlen($str,$charset='utf-8') {
//         $n = 0; $p = 0; $c = '';
//         $len = strlen($str);
//         if($charset == 'utf-8') {
//             for($i = 0; $i < $len; $i++) {
//                 $c = ord($str[$i]);
//                 if($c > 252) {
//                     $p = 5;
//                 } elseif($c > 248) {
//                     $p = 4;
//                 } elseif($c > 240) {
//                     $p = 3;
//                 } elseif($c > 224) {
//                     $p = 2;
//                 } elseif($c > 192) {
//                     $p = 1;
//                 } else {
//                     $p = 0;
//                 }
//                 $i+=$p;$n++;
//             }
//         } else {
//             for($i = 0; $i < $len; $i++) {
//                 $c = ord($str[$i]);
//                 if($c > 127) {
//                     $p = 1;
//                 } else {
//                     $p = 0;
//             }
//                 $i+=$p;$n++;
//             }
//         }
//         return $n;
//     }
// }



// /**
//  * 根据出生年月计算年龄
//  * 要求PHP 5.3+
//  * getYear('2010-10-22')
//  */
// if ( ! function_exists( 'getYear' ) ) {
//     function getYear($date) {
//         $year = date_diff(date_create($date), date_create('now'))->y;
//         $month = date_diff(date_create($date), date_create('now'))->m;
//         return $year + round($month/12);
//     }
// }



// /**
//  * 返回一个唯一的token
//  */
// if (!function_exists('genToken')) {
//     function genToken() {
//         $token = md5(uniqid(rand(), true));
//         //$token = $token.'-'.time();//附加token生成时间
//         return $token;
//     }
// }



// /**
//  * 获取用户token，不存在则生成并保存一个
//  */
// if (!function_exists('getToken')) {
//     function getToken($uid) {
//         // get the token from DB
//         $token = get_user_meta( $uid, 'token', true );

//         // generate a new token if token not exist
//         if (!isMD5($token)) {
//             $token = genToken();
//             update_user_meta( $uid, 'token', $token );
//         }

//         return $token;
//     }
// }



// /**
//  * 获取用户email
//  */
// if (!function_exists('getUserEmail')) {
//     function getUserEmail($uid) {
//         $user = get_userdata($uid);
//         $email = $user->user_email;

//         return $email;
//     }
// }



// /**
//  * 从HTTP Header里面获取 app-id
//  */
// if (!function_exists('getAppId')) {
//     function getAppId() {
//         return @$_SERVER['HTTP_APP_ID'];
//     }
// }



// /**
//  * 从HTTP Header里面获取 app-key
//  */
// if (!function_exists('getAppKey')) {
//     function getAppKey() {
//         return @$_SERVER['HTTP_APP_KEY'];
//     }
// }



// /**
//  * 从HTTP Header里面获取uid
//  * 对应前端的 header 是 app-uid
//  */
// if (!function_exists('getAppUid')) {
//     function getAppUid() {
//         return @$_SERVER['HTTP_APP_UID'];
//     }
// }



// /**
//  * 从HTTP Header里面获取uid
//  * 对应前端的 header 是 app-token
//  */
// if (!function_exists('getAppToken')) {
//     function getAppToken() {
//         return @$_SERVER['HTTP_APP_TOKEN'];
//     }
// }



// /**
//  * 获取：Json Web Token
//  * 依赖插件：JWT Authentication for WP-API
//  */
// if (!function_exists('getJsonWebToken')) {
//     function getJsonWebToken() {
//         $token = md5(uniqid(rand(), true));
//         //$token = $token.'-'.time();//附加token生成时间
//         return $token;
//     }
// }



// // 获取当前页URL
// if ( ! function_exists( 'get_current_page_url' ) ) {
//     function get_current_page_url(){
//         $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true:false;
//         $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
//         $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
//         $port  = $_SERVER['SERVER_PORT'];
//         // $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
//         $port = ($port=='80') ? '' : ':'.$port;
//         $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
//         return $protocol . '://' . $host . $port . $_SERVER['REQUEST_URI'];
//     }
// }



// // 跳转到登录页面
// if ( ! function_exists( 'wp_login_url_override' ) ) {
//     function wp_login_url_override($redirect = ''){
//         $login_url = site_url('/signin');

//         if ( !empty($redirect) )
//             $login_url = add_query_arg('redirect', urlencode($redirect), $login_url);

//         return apply_filters( 'login_url', $login_url, $redirect );
//     }
// }



// /**
//  * 检查基本资料是否填写完整
//  * 使用方法：if ( function_exists('checkProfile') ) checkProfile();
//  */
// if (!function_exists('checkProfile')) {
//     function checkProfile(){
//         // /welcome不检查，避免进入死循环
//         if (is_page('welcome')) return;

//         $uid = get_current_user_id();
//         $gender = get_user_meta( $uid, 'gender', true );
//         $job = get_user_meta( $uid, 'job', true );
//         $city = get_user_meta( $uid, 'city', true );

//         if (!($gender && $job && $city)) {
//             wp_redirect( site_url('/welcome') );
//             exit;
//         }
//     }
// }



// /**
//  * 检查公司资料是否填写完整
//  * 使用方法：if ( function_exists('checkCompanyProfile') ) checkCompanyProfile();
//  */
// if (!function_exists('checkCompanyProfile')) {
//     function checkCompanyProfile() {
//         // 管理员不检查
//         if (isAdmin()) return;

//         // /company不检查，避免进入死循环
//         if (is_page('company')) return;

//         $uid = get_current_user_id();

//         $name = get_user_meta( $uid, 'name', true );
//         $job = get_user_meta( $uid, 'job', true );
//         $company = get_user_meta( $uid, 'company', true );
//         $company_email = get_user_meta( $uid, 'company_email', true );

//         if (!($name && $job && $company && $company_email)) {
//             wp_redirect( site_url('/company') );
//             exit;
//         }
//     }
// }



// /**
//  * 检查用户登录状态，未登录则跳转到登录页面
//  * 使用方法：if ( function_exists('checkLogin') ) checkLogin();
//  * @since 1.0
//  */
// if ( ! function_exists( 'checkLogin' ) ) {
//     function checkLogin() {
//         if ( !is_user_logged_in() ) {
//             wp_redirect( wp_login_url_override( get_current_page_url() ) );
//             exit;
//         } else {
//             // checkProfile();

//             // contributor需要完善公司信息
//             if (isCompany()) checkCompanyProfile();
//         }
//     }
// }



// /**
//  * 管理员身份下，显示页面查询次数、加载时间和内存占用
//  */
// if ( !function_exists( 'debug' ) ) {
//     function debug( $visible = false ) {
//         $stat = sprintf(  '%d queries in %.3f seconds, using %.2fMB memory',
//             get_num_queries(),
//             timer_stop( 0, 3 ),
//             memory_get_peak_usage() / 1024 / 1024
//         );
//         echo $visible ? $stat : "<!-- {$stat} -->" ;
//     }
//     if( current_user_can( 'administrator' ) ) add_action( 'wp_footer', 'debug', 20 );
// }



// /**
//  * 语言包支持
//  */
// if ( !function_exists('load_languages')){
//     function load_languages(){
//         load_theme_textdomain(THEME_NAME, get_template_directory() . '/languages');
//     }
//     add_action('after_setup_theme', 'load_languages');
// }



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
            $my_sites = array( 'http://localhost:8000', 'https://m.yuancheng.work', );
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



/**
 * 从JWT Token里面解析出user id
 * https://developer.wordpress.org/reference/functions/get_user_id_from_string/
 * @param   [type]  $token  [$token description]
 *
 * @return  [type]          [return description]
 */
if ( !function_exists( 'getUserIdFromJwtToken' ) ) {
    function getUserIdFromJwtToken($token) {
        $array = explode(".",$token);
        $user = json_decode(base64_decode($array[1]))->data->user;
        if ( $user )
            return $user->id;
        return 0;
    }
}
