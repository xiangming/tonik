<?php

namespace App\Projects\Fans\Api;

use App\Validators\Validator;
use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\theme;

/**
 * 用户相关 REST API 端点
 * 
 * 包含：
 * - 验证码发送
 * - 用户注册
 * - 密码找回/修改
 * - 手机/邮箱绑定
 * - 用户存在性检查
 * - 账号删除
 */
class UserApi
{
    const NAMESPACE = '/wp/v2';

    /**
     * 注册所有用户相关端点
     */
    public static function register()
    {
        self::registerCodeEndpoint();
        self::registerRegisterEndpoint();
        self::registerForgotPasswordEndpoint();
        self::registerChangePasswordEndpoint();
        self::registerBindPhoneEndpoint();
        self::registerBindEmailEndpoint();
        self::registerExistsEndpoint();
        self::registerDeleteEndpoint();
        self::registerTestQueueEndpoint();
    }

    /**
     * 发送验证码
     * POST /wp/v2/users/code
     */
    private static function registerCodeEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/code', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $account = $request->get_param('account');

                // 参数格式验证
                if (!is_email($account) && !Validator::isPhone($account)) {
                    return resError('请输入正确的邮箱或手机号');
                }

                // 发送验证码
                $send_code = theme('captcha')->send($account);

                if (!$send_code['status']) {
                    return resError($send_code['msg']);
                }

                return resOK('验证码发送成功');
            },
            'args' => array(
                'account' => theme('args')->account(true),
            ),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * 用户注册
     * POST /wp/v2/users/register
     */
    private static function registerRegisterEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/register', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $account = $request->get_param('account');
                $code = $request->get_param('code');
                $password = $request->get_param('password');

                // 验证码校验
                $check_code = theme('captcha')->check($account, $code);
                if (!$check_code['status']) {
                    return resError($check_code['msg']);
                }

                // 创建用户
                $create_user = theme('user')->createUser($account, $password);
                if (!$create_user['status']) {
                    return resError($create_user['msg']);
                }

                return resOK('注册成功', $create_user['data']);
            },
            'args' => array(
                'account' => theme('args')->account(true),
                'code' => theme('args')->code(true),
                'password' => theme('args')->password(true),
            ),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * 忘记密码
     * POST /wp/v2/users/forgot
     */
    private static function registerForgotPasswordEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/forgot', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $account = $request->get_param('account');
                $code = $request->get_param('code');
                $password = $request->get_param('password');

                // 验证码校验
                $check_code = theme('captcha')->check($account, $code);
                if (!$check_code['status']) {
                    return resError($check_code['msg']);
                }

                // 检查用户是否存在
                $uid = theme('user')->exists($account);
                if (!$uid) {
                    return resError('该账号不存在');
                }

                // 修改密码
                theme('user')->updatePassword($uid, $password);

                return resOK('密码修改成功');
            },
            'args' => array(
                'account' => theme('args')->account(true),
                'code' => theme('args')->code(true),
                'password' => theme('args')->password(true),
            ),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * 修改密码（已登录）
     * POST /wp/v2/users/password
     */
    private static function registerChangePasswordEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/password', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $password = $request->get_param('password');
                $uid = wp_get_current_user()->ID;

                if (!$uid) {
                    return resError('请先登录');
                }

                theme('user')->updatePassword($uid, $password);

                return resOK('密码修改成功');
            },
            'args' => array(
                'password' => theme('args')->password(true),
            ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));
    }

    /**
     * 绑定手机号
     * POST /wp/v2/users/bind/phone
     */
    private static function registerBindPhoneEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/bind/phone', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $phone = $request->get_param('phone');
                $code = $request->get_param('code');
                $uid = wp_get_current_user()->ID;

                if (!$uid) {
                    return resError('请先登录');
                }

                // 验证码校验
                $check_code = theme('captcha')->check($phone, $code);
                if (!$check_code['status']) {
                    return resError($check_code['msg']);
                }

                // 更新手机号
                theme('user')->updatePhone($uid, $phone);

                return resOK('手机号绑定成功');
            },
            'args' => array(
                'phone' => theme('args')->phone(true),
                'code' => theme('args')->code(true),
            ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));
    }

    /**
     * 绑定邮箱
     * POST /wp/v2/users/bind/email
     */
    private static function registerBindEmailEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/bind/email', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $email = $request->get_param('email');
                $code = $request->get_param('code');
                $uid = wp_get_current_user()->ID;

                if (!$uid) {
                    return resError('请先登录');
                }

                // 验证码校验
                $check_code = theme('captcha')->check($email, $code);
                if (!$check_code['status']) {
                    return resError($check_code['msg']);
                }

                // 更新邮箱
                theme('user')->updateEmail($uid, $email);

                return resOK('邮箱绑定成功');
            },
            'args' => array(
                'email' => theme('args')->email(true),
                'code' => theme('args')->code(true),
            ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));
    }

    /**
     * 检查账号是否存在
     * POST /wp/v2/users/exists
     */
    private static function registerExistsEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/exists', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $account = $request->get_param('account');
                $exists = theme('user')->exists($account);

                return resOK('', ['exists' => (bool) $exists]);
            },
            'args' => array(
                'account' => theme('args')->account(true),
            ),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * 删除账号
     * POST /wp/v2/users/delete
     */
    private static function registerDeleteEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/delete', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $account = $request->get_param('account');
                $uid = wp_get_current_user()->ID;

                if (!$uid) {
                    return resError('请先登录');
                }

                // 检查账号是否匹配
                $check_account = theme('user')->exists($account);
                if ($check_account != $uid) {
                    return resError('账号不匹配');
                }

                // 删除用户（软删除：修改角色为 deleted）
                wp_update_user([
                    'ID' => $uid,
                    'role' => 'deleted',
                ]);

                return resOK('账号已删除');
            },
            'args' => array(
                'account' => theme('args')->account(true),
            ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));
    }

    /**
     * 测试队列（开发用）
     * GET /wp/v2/users/test_queue
     */
    private static function registerTestQueueEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/users/test_queue', array(
            'methods' => 'GET',
            'callback' => function ($request) {
                theme('queue')->add_async('test', ['test' => 'data']);

                return resOK('队列任务已添加');
            },
            'permission_callback' => '__return_true',
        ));
    }
}
