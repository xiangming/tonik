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

use function Tonik\Theme\App\theme;
use function Tonik\Theme\App\config;
use function Tonik\Theme\App\format;
use function Tonik\Theme\App\formatError;
use Tonik\Gin\Foundation\Theme;
use WP_Query;
use Overtrue\EasySms\EasySms;
use App\Validators\PhoneValidator;

/**
 * Service handler for retrieving posts of specific post type.
 *
 * @return void
 */
function bind_books_service()
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
}
add_action('init', 'Tonik\Theme\App\Setup\bind_books_service');

/**
 * 发送短信接口
 * 
 * 基于easy-sms：https://github.com/overtrue/easy-sms
 * 
 * 技术文档：https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
 * 
 * 使用方法：POST api/v1/sendSms
 * 
 * 提示：'is_numeric' 数字和数字字符串则返回 TRUE，否则返回 FALSE。
 */
$sceneList = array(
    'SCENE_LOGIN', // 用于用户登录
    'SCENE_REGISTER', // 用于用户注册
    'SCENE_RESET_PASSWORD', // 用于重置密码
    'SCENE_BIND_PHONE', // 用于绑定手机号
    'SCENE_UNBIND_PHONE', // 用于解绑手机号
    'SCENE_BIND_MFA', // 用于绑定 MFA
    'SCENE_VERIFY_MFA', // 用于验证 MFA
    'SCENE_UNBIND_MFA', // 用于解绑 MFA
    'SCENE_COMPLETE_PHONE', // 用于在注册/登录时补全手机号信息
    'SCENE_IDENTITY_VERIFICATION', // 用于进行用户实名认证
    'SCENE_DELETE_ACCOUNT', // 用于注销账号
);
function registerSmsService()
{
    register_rest_route( 'sms/v1', 'sendSms', array(
        'methods'  => 'POST',
        'callback' => function ($request) {
            // var_dump(config('sms'));
            // die();

            // $error = null;
            try {
                $phoneNumber = $request->get_param('phoneNumber');
                $phoneCountryCode = $request->get_param('phoneCountryCode');
                $scene = $request->get_param('scene');
                
                // var_dump(PhoneValidator::validate($phoneNumber));
                // die('phoneNumber');

                if (!PhoneValidator::validate($phoneNumber)) {
                    return formatError('手机号码格式错误');
                }
                
                $code = mt_rand(1000, 9999);

                $easySms = new EasySms(config('sms'));

                $easySms->send($phoneNumber, [
                    'content'  => "您的验证码为: {$code}",
                    'template' => 'SMS_152511386',
                    'data' => [
                        'code' => $code
                    ],
                ]);
            } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $e) {
                // $error_msg = $e->getException('aliyun')->getMessage();
                // $smsLog->error_msg = $error_msg;
                // $smsLog->status = 0;
                // $smsLog->save();
                // return $this->formatError(__('tip.sms.sendErr'));
                return formatError('发送失败');
            }
        },
        'args' => array(
            'phoneNumber' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
            'phoneCountryCode' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
            'scene' => array(
                'validate_callback' => function ($param) {
                    return in_array($param, $sceneList);
                }
            ),
        ),
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
}
add_action('rest_api_init', 'Tonik\Theme\App\Setup\registerSmsService');