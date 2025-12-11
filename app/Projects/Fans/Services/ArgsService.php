<?php

namespace App\Projects\Fans\Services;

use \App\Services\BaseService;
use \App\Validators\Validator;
use function Tonik\Theme\App\theme;

class ArgsService extends BaseService
{
    // https://www.shawnhooper.ca/2017/02/15/wp-rest-secrets-found-reading-core-code/
    public function demo($required)
    {
        return [
            'required' => $required,
            'type' => "integer", // string, boolean, number
            'minimum' => 6,
            'maximum' => 20,
            "description" => "演示",
            'enum' => array(
                'en_CA',
                'en_US',
                'fr_CA',
            ),
            'validate_callback' => function ($param, $request) {
                if (!preg_match('/[a-z0-9]+/', $param)) {
                    return new WP_Error(
                        'rest_invalid_title',
                        'Please enter a valid title for the post.',
                        array('status' => 400)
                    );
                }

                return true;
            },
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function user_slug($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "用户slug。",
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    /**
     * 请求参数配置
     *
     * @param boolean $required
     *
     * @return array config
     */
    public function account($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "用户名、邮箱或者手机号。",
            'validate_callback' => function ($param, $request, $key) {
                // 巧妙设计：只允许邮箱和手机新用户或者已注册用户开放（防止使用用户名胡乱请求）
                return is_email($param) || Validator::isPhone($param) || theme('user')->exists($param);
            },
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function phone($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "手机号。",
            'validate_callback' => function ($param, $request, $key) {
                return Validator::isPhone($param);
            },
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function email($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "邮箱。",
            'validate_callback' => function ($param, $request, $key) {
                return is_email($param);
            },
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function phoneOrEmail($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "邮箱或者手机号。",
            'validate_callback' => function ($param, $request, $key) {
                return is_email($param) || Validator::isPhone($param);
            },
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function password($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            'minLength' => 6,
            'maxLength' => 20,
            "description" => "密码。",
            // 'validate_callback' => function ($param, $request, $key) {
            //     return Validator::validateLength($param, '密码', 6, 20);
            // },
            // 'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function code($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "验证码。",
            'validate_callback' => function ($param, $request, $key) {
                return is_numeric($param);
            },
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function out_trade_no($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "第三方订单号。",
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function amount($required)
    {
        return [
            'required' => $required,
            'type' => "number",
            // 'default' => 10,
            'minimum' => 1,
            'maximum' => 5000,
            "description" => "金额。",
            // 'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function remark($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "留言。",
            'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function method($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "支付通道。",
            'enum' => array(
                'alipay',
                'wechat',
                'balance',
            ),
            // 'validate_callback' => function ($param, $request, $key) {
            //     return in_array($param, ['alipay', 'wechat', 'balance']);
            // },
            // 'sanitize_callback' => 'sanitize_text_field',
        ];
    }

    public function device($required)
    {
        return [
            'required' => $required,
            'type' => "string",
            "description" => "支付设备类型。",
            'enum' => array(
                'web',
                'wap',
                'app',
                'scan',
                'transfer',
                'mini',
                'mp',
                'pos',
            ),
            // 'validate_callback' => function ($param, $request, $key) {
            //     return in_array($param, ['web', 'wap', 'app', 'scan', 'transfer', 'mini', 'mp', 'pos']);
            // },
            // 'sanitize_callback' => 'sanitize_text_field',
        ];
    }
}
