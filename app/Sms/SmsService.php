<?php

namespace App\Sms;

use App\Sms\CaptchaMessage;
use function Tonik\Theme\App\config;
use function Tonik\Theme\App\wpLog;
use Overtrue\EasySms\EasySms;

class SmsService
{
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
    public static function send($phoneNumber)
    {
        try {
            $code = mt_rand(1000, 9999);

            $easySms = new EasySms(config('sms'));
            $message = new CaptchaMessage($code);

            $easySms->send($phoneNumber, $message);

            // $easySms->send($phoneNumber, [
            //     'content'  => "您的验证码为: {$code}",
            //     'template' => 'SMS_152511386',
            //     'data' => [
            //         'code' => $code
            //     ],
            // ]);

            return $code;
        } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $e) {
            $error_msg = $e->getException('aliyun')->getMessage();
            wpLog('[SMS send to' . $phoneNumber . ']:' . $error_msg);
            // resError($error_msg);
            // exit();

            return false;
        }
    }
}
