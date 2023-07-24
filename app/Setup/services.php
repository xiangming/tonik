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

use Tonik\Gin\Foundation\Theme;
use WP_Query;
// use Overtrue\EasySms\EasySms;
use App\Validators\Validator;
// use App\Sms\CaptchaMessage;
use App\Sms\SmsService;

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

    /**
     * 发送短信
     * 
     * 当未携带jwt时，判定为未注册，需要自动注册phone_temp新用户
     * 当携带jwt时，判定为绑定手机号，向jwt用户添加phone_temp字段
     *
     * @param   [type]  $phoneNumber  手机号，必填
     * 
     * https://github.com/qingwuit/qwshop/blob/98d00761dad7c1151c79175d6349b302cf4d63af/app/Qingwuit/Services/SmsService.php
     * 
     * TODO: 支持第二参数：模板ID
     *
     * @return  发送的结果（失败或者成功），成功则data里面携带code
     */
    register_rest_route( $namespace, 'send', array(
        'methods'  => 'POST',
        'callback' => function ($request) {
            $phoneNumber = $request->get_param('phoneNumber');
            // $phoneCountryCode = $request->get_param('phoneCountryCode');
            // $scene = $request->get_param('scene');

            // 数据格式校验
            Validator::required($phoneNumber,'手机号');
            Validator::validatePhone($phoneNumber);
            
            // die('phoneNumber');

            // 执行发送
            $code = SmsService::send($phoneNumber);
            if ($code) {
                return resOK('发送成功', ['code' => $code]);
            }

            return resError('发送失败');
        },
        'args' => array(
            'phoneNumber' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
            // 'phoneCountryCode' => array(
            //     'validate_callback' => function($param, $request, $key) {
            //         return is_numeric( $param );
            //     }
            // ),
            // 'scene' => array(
            //     'validate_callback' => function ($param) {
            //         return in_array($param, $sceneList);
            //     }
            // ),
        ),
        'permission_callback' => function() {
            return true;
        }
    ));

    // /**
    //  * 该接口必须携带JWT
    //  * 
    //  * 检查短信验证码是否正确，支持邮箱验证码和短信验证码
    //  * 
    //  * 从请求获取JWT并解析出uid
    //  */
    // register_rest_route($namespace, 'validate', array(
    //     'methods' => 'POST',
    //     'callback' => function ($request) {
    //         $token = getJwtTokenFromRequest($request);
    //         $uid = getUserIdFromJwtToken($token);

    //         // 需要登录态的接口JWT插件已经验证过token，这里不需要验证uid和token
            
    //         // 表单数据格式校验
    //         $code = $request->get_param('code');
    //         Validator::required($code, '验证码');

    //         // 检查验证码是否有效
    //         if (validateCode($uid, $code)) {
    //             // 验证通过后，将缓存的手机号转正
    //             updatePhoneFromPhoneTemp($uid);
    //         }

    //         return res_ok('验证成功');
    //     },
    //     'args' => array(
    //         'code' => array(
    //             'validate_callback' => function($param, $request, $key) {
    //                 return is_numeric( $param );
    //             }
    //         ),
    //     ),
    //     'permission_callback' => function() {
    //         return wp_get_current_user();
    //     }
    // ));
}
add_action('rest_api_init', 'Tonik\Theme\App\Setup\bind_sms_service');
