<?php
/*
 * SMS 全局 config
 *
 * 文档：https://github.com/overtrue/easy-sms
 */

return [
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
        /*
         * PHP error log gateway
         *
         * http://php.net/manual/en/function.error-log.php
         */
        'error-log' => [
            'file' => '/tmp/easy-sms.log',
        ],
        /*
         * Yun Pian SMS service.
         *
         * https://www.yunpian.com/
         */
        'yunpian' => [
            'api_key' => getenv('YUNPIAN_API_KEY'),
        ],
        'aliyun' => [
            'access_key_id' => getenv('ALIYUN_SMS_API_KEY'),
            'access_key_secret' => getenv('ALIYUN_SMS_API_SECRET'),
            'sign_name' => getenv('ALIYUN_SMS_SIGN_NAME'),
        ],
    ],
];
