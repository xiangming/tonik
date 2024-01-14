<?php

namespace Tonik\Theme\App\Setup;

/*
|-----------------------------------------------------------
| Theme Custom Services
|-----------------------------------------------------------
|
| This file is for registering your third-parity services
| or custom logic within theme container, so they can
| be easily used for a theme template files later.
|
 */

use App\Services\ArgsService;
use App\Services\DonationService;
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
 * Binds services
 *
 * 通过theme方法快速获取：theme('books');
 *
 * @return void
 */
function bind_services()
{
    /**
     * Binds service for retrieving posts of specific post type.
     *
     * @param \Tonik\Gin\Foundation\Theme $theme  Instance of the service container
     * @param array $parameters  Parameters passed on service resolving
     *
     * @return \WP_Post[]
     */
    theme()->bind('books', function (Theme $theme, $parameters) {
        return new WP_Query([
            'post_type' => 'book',
        ]);
    });

    theme()->bind('tool', function (Theme $theme, $parameters) {
        return new ToolService();
    });

    theme()->bind('queue', function (Theme $theme, $parameters) {
        return new QueueService();
    });

    theme()->bind('sms', function (Theme $theme, $parameters) {
        return new SmsService();
    });

    theme()->bind('mail', function (Theme $theme, $parameters) {
        return new MailService();
    });

    theme()->bind('log', function (Theme $theme, $parameters) {
        return new LogService();
    });

    theme()->bind('args', function (Theme $theme, $parameters) {
        return new ArgsService();
    });

    theme()->bind('order', function (Theme $theme, $parameters) {
        return new OrderService();
    });

    theme()->bind('payment', function (Theme $theme, $parameters) {
        return new PaymentService();
    });

    theme()->bind('donation', function (Theme $theme, $parameters) {
        return new DonationService();
    });

    theme()->bind('user', function (Theme $theme, $parameters) {
        return new UserService();
    });
}
add_action('init', 'Tonik\Theme\App\Setup\bind_services');

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
