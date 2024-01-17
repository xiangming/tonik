<?php

namespace App\Services;

use function Tonik\Theme\App\theme;
use Overtrue\EasySms\EasySms;

class SmsService extends BaseService
{
    protected $config = [
        // HTTP 请求的超时时间（秒）
        'timeout' => 5.0,

        // 默认发送配置
        'default' => [
            // 网关调用策略，默认：顺序调用
            'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

            // 默认可用的发送网关
            'gateways' => [
                'yunpian', 'aliyun',
            ],
        ],

        // 可用的网关配置
        'gateways' => [
            'errorlog' => [
                'file' => './logs/easy-sms.log',
            ],
            'yunpian' => [
                'api_key' => '',
            ],
            'aliyun' => [
                'access_key_id' => '',
                'access_key_secret' => '',
                'sign_name' => '',
            ],
            //...
        ],
    ];

    public function __construct()
    {
        $this->config['gateways']['yunpian']['api_key'] = $_ENV['YUNPIAN_API_KEY'];
        $this->config['gateways']['aliyun']['access_key_id'] = $_ENV['ALIYUN_SMS_API_KEY'];
        $this->config['gateways']['aliyun']['access_key_secret'] = $_ENV['ALIYUN_SMS_API_SECRET'];
        $this->config['gateways']['aliyun']['sign_name'] = $_ENV['ALIYUN_SMS_SIGN_NAME'];
    }

    /**
     * 发送短信
     *
     * @param   [type]  $phoneNumber  手机号，必填
     *
     * https://github.com/qingwuit/qwshop/blob/98d00761dad7c1151c79175d6349b302cf4d63af/app/Qingwuit/Services/SmsService.php
     * https://github.com/gptlink/gptlink/blob/4f590e81d979833bba409efddaf68b48b6a4a001/gptserver/app/Http/Service/SmsService.php
     *
     * TODO: 支持第二参数：模板ID
     *
     * @return code on success, false on failure
     */
    public function send($phoneNumber)
    {
        theme('log')->log('SmsService send start');

        try {
            $code = mt_rand(1000, 9999);

            $easySms = new EasySms($this->config);
            $message = new CaptchaMessage($code);

            $rs = $easySms->send($phoneNumber, $message);

            // $easySms->send($phoneNumber, [
            //     'content'  => "您的验证码为: {$code}",
            //     'template' => 'SMS_152511386',
            //     'data' => [
            //         'code' => $code
            //     ],
            // ]);

            // return $code;
        } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $e) {
            $error_msg = $e->getException('aliyun')->getMessage();

            theme('log')->error($error_msg, 'SmsService send to ' . $phoneNumber);

            return $this->formatError('调取短信服务失败');
        }

        if (isset($rs) && $rs['aliyun']['status'] == 'success' && $rs['aliyun']['result']['Code'] == 'OK') {
            theme('log')->log('SmsService send success');

            return $this->format($code);
        } else {
            theme('log')->error('短信发送失败', 'SmsService send to ' . $phoneNumber);

            return $this->formatError('短信发送失败');
        }
    }
}
