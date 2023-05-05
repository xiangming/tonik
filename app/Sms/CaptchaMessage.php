<?php

namespace App\Sms;

use Overtrue\EasySms\Message;
use Overtrue\EasySms\Contracts\GatewayInterface;

class CaptchaMessage extends Message
{
    protected $captcha;
    protected $captchaConfig;
    // protected $strategy = OrderStrategy::class;           // 定义本短信的网关使用策略，覆盖全局配置中的 `default.strategy`
    // protected $gateways = ['alidayu', 'yunpian', 'juhe']; // 定义本短信的适用平台，覆盖全局配置中的 `default.gateways`

    public function __construct($captcha, $captchaConfig = null)
    {
        $this->captcha = $captcha;
        $this->captchaConfig = $captchaConfig;
    }

    // 定义直接使用内容发送平台的内容
    public function getContent(GatewayInterface $gateway = null)
    {
        return sprintf('您的验证码为:', $this->captcha);
    }

    // 定义使用模板发送方式平台所需要的模板 ID
    public function getTemplate(GatewayInterface $gateway = null)
    {
        if (isset($this->captchaConfig['template_id'])) {
            return $this->captchaConfig['template_id'];
        } else {
            // 您的验证码${code}，该验证码15分钟内有效，请勿泄漏于他人！
            return 'SMS_152511386';
        }
    }

    // 模板参数
    public function getData(GatewayInterface $gateway = null)
    {
        // return [
        //     $this->captchaConfig['template_variable'] => $this->captcha
        // ];
        return [
            'code' => $this->captcha
        ];
    }
}