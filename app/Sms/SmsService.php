<?php

namespace App\Sms;

use App\Sms\CaptchaMessage;
use function Tonik\Theme\App\config;
use Overtrue\EasySms\EasySms;

class SmsService
{
    /**
     * 发送短信
     *
     * @param   [type]  $phoneNumber  手机号，必填
     *
     * https://github.com/qingwuit/qwshop/blob/98d00761dad7c1151c79175d6349b302cf4d63af/app/Qingwuit/Services/SmsService.php
     *
     * TODO: 支持第二参数：模板ID
     *
     * @return  发送的结果（失败或者成功）
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
            // $error_msg = $e->getException('aliyun')->getMessage();
            // $smsLog->error_msg = $error_msg;
            // $smsLog->status = 0;
            // $smsLog->save();
            // return $this->resError(__('tip.sms.sendErr'));
            return 0;
        }
    }
}
