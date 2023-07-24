<?php
// if(! defined('THEME_NAME') ) define( 'THEME_NAME' , wp_get_theme()->get('Name') );
// if(! defined('THEME_URI') ) define( 'THEME_URI' , wp_get_theme()->get('ThemeURI') );
// if(! defined('THEME_AUTHOR') ) define( 'THEME_AUTHOR' , wp_get_theme()->get('Author') );
// if(! defined('THEME_VERSION') ) define( 'THEME_VERSION' , wp_get_theme()->get('Version') );
// if(! defined('THEME_DESC') ) define( 'THEME_DESC' , wp_get_theme()->get('Description') );

// // 微信公众号：远程工作
// if(! defined('APPID') ) define( 'APPID' , 'wx63b95a5ba3d10f69' );
// if(! defined('APPSECRET') ) define( 'APPSECRET' , '3e6b5c5f21e6b2522cfcd8ee69e54ceb' );

// // 微信公众测试账号
// if(! defined('APPID') ) define( 'APPID' , 'wx6aafed32b5cd8922' );
// if(! defined('APPSECRET') ) define( 'APPSECRET' , 'd60cabd4914fe32d03b0c9a5b94710c2' );

// // 微信小程序：远程.work
// if(! defined('MAPPID') ) define( 'MAPPID' , 'wx2ca59c42c3137c3e' );
// if(! defined('MAPPSECRET') ) define( 'MAPPSECRET' , '1074b1379fcfe2b6724071b49957c0a0' );

// // 反馈系统：兔小巢
// if(! defined('FEEDBACK_URL') ) define( 'FEEDBACK_URL' , 'https://support.qq.com/product/385742' );


/**
 * 载入功能模块
 * @since 1.0
 */

// // 以下接口是扩展WP自带接口（通过增加字段）
// include 'rest/applied.php'; // 投递
// include 'rest/bookmarks.php'; // 收藏
// include 'rest/post.php'; // 职位

// // 以下是新建接口，不依赖WP自带接口
// include 'rest/job.php'; // api/v1/jobs/
// include 'rest/resume.php'; // api/v1/resume/

// // REST API（用于小程序，后来没有使用）
// // TODO: merge 到 rest
// include 'api.php';

// WordPress核心修改和函数
include 'core.php';

// 帮助函数
include 'helpers.php';

// 自定义JWT插件功能
include 'jwt.php';

// // WordPress hooks
// include 'hooks.php';

// // 数据格式验证函数
// include 'validation.php';

// // 微信相关
// include 'wx/weixin.php';

// // HTTP请求
// include 'http.php';

// // 移除url里面的category并修复链接
// include 'no-category.php';

// // search all taxonomies(tag & category & taxonomy) & 高亮搜索关键词
// include 'search-enhance.php';

// // 载入SEO模块
// include 'seo.php';

// // 载入会员中心
// include 'ucenter.php';

// // 载入浏览数统计 & 后台文章列表显示浏览数views
// include 'views.php';

// // 邮件
// include 'mail.php';

// // 阿里大鱼SMS
// include 'sms.php';

// // 支付
// include 'pay.php';

// // 职位
// include 'job.php';

// // 职位投递数（前台+后台）
// include 'applied.php';

// // 自定义类型-订单
// include 'post-type-orders.php';

// // // 自定义类型-博客
// // include 'post-type-news.php';
