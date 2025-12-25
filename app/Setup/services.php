<?php

namespace Tonik\Theme\App\Setup;

/*
|-----------------------------------------------------------
| Theme Custom Services
|-----------------------------------------------------------
|
| 此文件用于注册通用的基础服务
| 
| 项目特定的服务（如 Fans 项目）在 app/Projects/{ProjectName}/bootstrap.php 中注册
|
 */

use App\Services\AnalyticsService;
use App\Services\ArgsService;
use App\Services\LogService;
use App\Services\MailService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\QueueService;
use App\Services\SmsService;
use App\Services\ToolService;
use App\Services\UserService;
use function Tonik\Theme\App\theme;
use Tonik\Gin\Foundation\Theme;
use WP_Query;

/**
 * 注册通用基础服务
 *
 * 通过 theme() 方法快速获取：theme('log'), theme('mail') 等
 *
 * 注意：使用 after_setup_theme hook（优先级5）确保在 rest_api_init 之前注册
 *
 * @return void
 */
function bind_services()
{
    /**
     * 示例：绑定特定文章类型查询服务
     */
    theme()->bind('books', function (Theme $theme, $parameters) {
        return new WP_Query([
            'post_type' => 'book',
        ]);
    });

    // 工具服务
    theme()->bind('tool', function (Theme $theme, $parameters) {
        return new ToolService();
    });

    // 参数验证服务
    theme()->bind('args', function (Theme $theme, $parameters) {
        return new ArgsService();
    });

    // 分析服务（通用）
    theme()->bind('analytics', function (Theme $theme, $parameters) {
        return new AnalyticsService();
    });

    // 支付服务
    theme()->bind('payment', function (Theme $theme, $parameters) {
        return new PaymentService();
    });

    // 订单服务
    theme()->bind('order', function (Theme $theme, $parameters) {
        return new OrderService();
    });

    // 用户服务（基础版本，项目可以覆盖）
    theme()->bind('user', function (Theme $theme, $parameters) {
        return new UserService();
    });

    // 队列服务
    theme()->bind('queue', function (Theme $theme, $parameters) {
        return new QueueService();
    });

    // 短信服务
    theme()->bind('sms', function (Theme $theme, $parameters) {
        return new SmsService();
    });

    // 邮件服务
    theme()->bind('mail', function (Theme $theme, $parameters) {
        return new MailService();
    });

    // 日志服务
    theme()->bind('log', function (Theme $theme, $parameters) {
        return new LogService();
    });
}
add_action('after_setup_theme', 'Tonik\Theme\App\Setup\bind_services', 5);

/**
 * 发送短信接口
 *
 * 基于easy-sms：https://github.com/overtrue/easy-sms
 *
 * 技术文档：https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
 *
 * 使用方法：POST api/v1/sms/send
 *
 * 提示：'is_numeric' 数字和数字字符串则返回 TRUE，否则返回 FALSE。
 */
function bind_sms_service()
{
    $namespace = 'sms/v1';

    $sceneList = array(
        'SCENE_LOGIN', // 用于用户登录
        'SCENE_REGISTER', // 用于用户注册
        'SCENE_RESET_PASSWORD', // 用于重置密码
        'SCENE_BIND_PHONE', // 用于绑定手机号
        'SCENE_UNBIND_PHONE', // 用于解绑手机号
        'SCENE_COMPLETE_PHONE', // 用于在注册/登录时补全手机号信息
        'SCENE_IDENTITY_VERIFICATION', // 用于进行用户实名认证
        'SCENE_DELETE_ACCOUNT', // 用于注销账号
    );
}
add_action('rest_api_init', 'Tonik\Theme\App\Setup\bind_sms_service');
