<?php

namespace App\Services;

use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;

// require_once __DIR__ . '/../vendor/autoload.php';

// https://pay.yansongda.cn/docs/v2/quickUsage.html
// https://github.com/qingwuit/qwshop/blob/0800390867087a691866913eec9c6412c9a08e03/app/Qingwuit/Services/PaymentService.php

class WechatPayService
{
    protected $config = [
        // 'appid' => 'wx63b95a5ba3d10f69', // APP APPID
        'app_id' => 'wx63b95a5ba3d10f69', // 公众号 APPID
        // 'miniapp_id' => 'wxb3fxxxxxxxxxxx', // 小程序 APPID
        'mch_id' => '1512637611', // 商户号
        'key' => '8934e7d15453e97507ef794cf7b051cd', // 微信支付后台设置APIv2密钥，必须是32位
        'notify_url' => 'http://yanda.net.cn/notify.php',
        // 'cert_client' => './cert/apiclient_cert.pem', // optional，退款等情况时用到
        // 'cert_key' => './cert/apiclient_key.pem',// optional，退款等情况时用到
        'log' => [ // optional
            'file' => './logs/wechat.log',
            'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http' => [ // optional
            'timeout' => 5.0,
            'connect_timeout' => 5.0,
            // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
        ],
        // 'mode' => 'dev', // optional, dev/hk;当为 `hk` 时，为香港 gateway。
    ];

    public function scan($out_trade_no, $body, $total_fee)
    {
        $order = [
            'out_trade_no' => $out_trade_no,
            'body' => $body,
            'total_fee' => $total_fee * 100, // 元转分（微信支付单位为分）
            // 'openid' => 'onkVf1FjWS5SBIixxxxxxx', // 转账时需要
        ];

        $result = Pay::wechat($this->config)->scan($order);
        $result->out_trade_no = $out_trade_no;
        // print_r(json_encode($result)); // debug
        // return $result->code_url; // 二维码内容
        return $result;

        // {
            // 	"return_code": "SUCCESS",
            // 	"return_msg": "OK",
            // 	"result_code": "SUCCESS",
            // 	"mch_id": "1512637611",
            // 	"appid": "wx63b95a5ba3d10f69",
            // 	"nonce_str": "lqwYuInyNS0Hko7l",
            //  "out_trade_no": "0020220912081430002216100",
            // 	"sign": "A6F04152883E6CC6692AB6231930E2F2",
            // 	"prepay_id": "wx091430087762831625d5fdb64b36020000",
            // 	"trade_type": "NATIVE",
            // 	"code_url": "weixin://wxpay/bizpayurl?pr=CB8VMJzzz",
            //  "qrcode_src": "data:image/png;base64,****"
        // }
    }

    public function find($out_trade_no)
    {
        $order = [
            'out_trade_no' => $out_trade_no,
        ];
        // $order = '1514027114';

        $result = Pay::wechat($this->config)->find($order);
        // print_r(json_encode($result));
        return $result;

        // {
        //     "return_code": "SUCCESS",
        //     "return_msg": "OK",
        //     "result_code": "SUCCESS",
        //     "mch_id": "1512637611",
        //     "appid": "wx63b95a5ba3d10f69",
        //     "device_info": [],
        //     "trade_state": "NOTPAY",
        //     "total_fee": "3000",
        //     "out_trade_no": "0020220920083631008427000",
        //     "trade_state_desc": "订单未支付",
        //     "nonce_str": "V9dCjNI01HpleeFc",
        //     "sign": "AB4A3510D5562CF68A7135063EE76388"
        // }
    }

    public function notify()
    {
        $pay = Pay::wechat($this->config);

        try {
            $data = $pay->verify(); // 是的，验签就这么简单！

            Log::debug('Wechat notify', $data->all());
        } catch (\Exception $e) {
            // $e->getMessage();
        }

        return $pay->success()->send();// laravel 框架中请直接 `return $pay->success()`
    }
}
