<?php

namespace App\Services;

use Yansongda\Pay\Pay;
use function Tonik\Theme\App\wpLog;

/**
 * https://github.com/qingwuit/qwshop/blob/0800390867087a691866913eec9c6412c9a08e03/app/Qingwuit/Services/PaymentService.php
 */
class PaymentService extends BaseService
{
    protected $config = [
        'alipay' => [
            'default' => [
                // 必填-支付宝分配的 app_id
                'app_id' => '2016082000295641',
                // 必填-应用私钥 字符串或路径
                // 在 https://open.alipay.com/develop/manage 《应用详情->开发设置->接口加签方式》中设置
                'app_secret_cert' => 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCDRjOg5DnX+8L+rB8d2MbrQ30Z7JPM4hiDhawHSwQCQ7RlmQNpl6b/N6IrPLcPFC1uii179U5Il5xTZynfjkUyJjnHusqnmHskftLJDKkmGbSUFMAlOv+NlpUWMJ2A+VUopl+9FLyqcV+XgbaWizxU3LsTtt64v89iZ2iC16H6/6a3YcP+hDZUjiNGQx9cuwi9eJyykvcwhDkFPxeBxHbfwppsul+DYUyTCcl0Ltbga/mUechk5BksW6yPPwprYHQBXyM16Jc3q5HbNxh3660FyvUBFLuVWIBs6RtR2gZCa6b8rOtCkPQKhUKvzRMlgheOowXsWdk99GjxGQDK5W4XAgMBAAECggEAYPKnjlr+nRPBnnNfR5ugzH67FToyrU0M7ZT6xygPfdyijaXDb2ggXLupeGUOjIRKSSijDrjLZ7EQMkguFHvtfmvcoDTDFaL2zq0a3oALK6gwRGxOuzAnK1naINkmeOmqiqrUab+21emEv098mRGbLNEXGCgltCtz7SiRdo/pgIPZ1wHj4MH0b0K2bFG3xwr51EyaLXKYH4j6w9YAXXsTdvzcJ+eRE0Yq4uGPfkziqg8d0xXSEt90HmCGHKo4O2eh1w1IlBcHfK0F6vkeUAtrtAV01MU2bNoRU147vKFxjDOVBlY1nIZY/drsbiPMuAfSsodL0hJxGSYivbKTX4CWgQKBgQDd0MkF5AIPPdFC+fhWdNclePRw4gUkBwPTIUljMP4o+MhJNrHp0sEy0sr1mzYsOT4J20hsbw/qTnMKGdgy784bySf6/CC7lv2hHp0wyS3Es0DRJuN+aTyyONOKGvQqd8gvuQtuYJy+hkIoHygjvC3TKndX1v66f9vCr/7TS0QPywKBgQCXgVHERHP+CarSAEDG6bzI878/5yqyJVlUeVMG5OXdlwCl0GAAl4mDvfqweUawSVFE7qiSqy3Eaok8KHkYcoRlQmAefHg/C8t2PNFfNrANDdDB99f7UhqhXTdBA6DPyW02eKIaBcXjZ7jEXZzA41a/zxZydKgHvz4pUq1BdbU5ZQKBgHyqGCDgaavpQVAUL1df6X8dALzkuqDp9GNXxOgjo+ShFefX/pv8oCqRQBJTflnSfiSKAqU2skosdwlJRzIxhrQlFPxBcaAcl0VTcGL33mo7mIU0Bw2H1d4QhAuNZIbttSvlIyCQ2edWi54DDMswusyAhHxwz88/huJfiad1GLaLAoGASIweMVNuD5lleMWyPw2x3rAJRnpVUZTc37xw6340LBWgs8XCEsZ9jN4t6s9H8CZLiiyWABWEBufU6z+eLPy5NRvBlxeXJOlq9iVNRMCVMMsKybb6b1fzdI2EZdds69LSPyEozjkxdyE1sqH468xwv8xUPV5rD7qd83+pgwzwSJkCgYBrRV0OZmicfVJ7RqbWyneBG03r7ziA0WTcLdRWDnOujQ9orhrkm+EY2evhLEkkF6TOYv4QFBGSHfGJ0SwD7ghbCQC/8oBvNvuQiPWI8B+00LwyxXNrkFOxy7UfIUdUmLoLc1s/VdBHku+JEd0YmEY+p4sjmcRnlu4AlzLxkWUTTg==',
                // 必填-应用公钥证书 路径
                // 设置应用私钥后，即可下载得到以下3个证书
                'app_public_cert_path' => '/Users/yansongda/pay/cert/appCertPublicKey_2016082000295641.crt',
                // 必填-支付宝公钥证书 路径
                'alipay_public_cert_path' => '/Users/yansongda/pay/cert/alipayCertPublicKey_RSA2.crt',
                // 必填-支付宝根证书 路径
                'alipay_root_cert_path' => '/Users/yansongda/pay/cert/alipayRootCert.crt',
                'return_url' => 'https://yansongda.cn/alipay/return',
                'notify_url' => 'https://yansongda.cn/alipay/notify',
                // 选填-第三方应用授权token
                'app_auth_token' => '',
                // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                'service_provider_id' => '',
                // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                'mode' => Pay::MODE_NORMAL,
            ],
        ],
        'wechat' => [
            'default' => [
                // 必填-商户号，服务商模式下为服务商商户号
                // 可在 https://pay.weixin.qq.com/ 账户中心->商户信息 查看
                'mch_id' => '',
                // 选填-v2商户私钥
                'mch_secret_key_v2' => '',
                // 必填-v3 商户秘钥
                // 即 API v3 密钥(32字节，形如md5值)，可在 账户中心->API安全 中设置
                'mch_secret_key' => '',
                // 必填-商户私钥 字符串或路径
                // 即 API证书 PRIVATE KEY，可在 账户中心->API安全->申请API证书 里获得
                // 文件名形如：apiclient_key.pem
                'mch_secret_cert' => '',
                // 必填-商户公钥证书路径
                // 即 API证书 CERTIFICATE，可在 账户中心->API安全->申请API证书 里获得
                // 文件名形如：apiclient_cert.pem
                'mch_public_cert_path' => '',
                // 必填-微信回调url
                // 不能有参数，如?号，空格等，否则会无法正确回调
                'notify_url' => 'https://yansongda.cn/wechat/notify',
                // 选填-公众号 的 app_id
                // 可在 mp.weixin.qq.com 设置与开发->基本配置->开发者ID(AppID) 查看
                'mp_app_id' => 'wx63b95a5ba3d10f69',
                // 选填-小程序 的 app_id
                'mini_app_id' => '',
                // 选填-app 的 app_id
                'app_id' => '',
                // 选填-合单 app_id
                'combine_app_id' => '',
                // 选填-合单商户号
                'combine_mch_id' => '',
                // 选填-服务商模式下，子公众号 的 app_id
                'sub_mp_app_id' => '',
                // 选填-服务商模式下，子 app 的 app_id
                'sub_app_id' => '',
                // 选填-服务商模式下，子小程序 的 app_id
                'sub_mini_app_id' => '',
                // 选填-服务商模式下，子商户id
                'sub_mch_id' => '',
                // 选填-微信平台公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
                'wechat_public_cert_path' => [
                    '45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__ . '/Cert/wechatPublicKey.crt',
                ],
                // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
                'mode' => Pay::MODE_NORMAL,
            ],
        ],
        'logger' => [
            'enable' => true,
            'file' => './logs/pay.log',
            'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http' => [ // optional
            'timeout' => 5.0,
            'connect_timeout' => 5.0,
            // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
        ],
    ];

    public function __construct()
    {
        $this->config['wechat']['default']['mch_id'] = getenv('WECHAT_MCH_ID');
        $this->config['wechat']['default']['mch_secret_key_v2'] = getenv('WECHAT_MCH_SECRET_KEY_V2');
        $this->config['wechat']['default']['mch_secret_key'] = getenv('WECHAT_MCH_SECRET_KEY');
        $this->config['wechat']['default']['mch_secret_cert'] = getenv('WECHAT_MCH_SECRET_CERT');
        $this->config['wechat']['default']['mch_public_cert_path'] = getenv('WECHAT_MCH_SECRET_CERT_PATH');
    }

    /**
     * 调取第三方支付
     *
     * @param   [String]   $paymentName  支付类型 如：wechat
     * @param   [String]   $device       设备类型[web | app | wap | h5]
     * @param   [Array]    $orderPay     支付订单的支付数据
     * @param   [Boolean]  $recharge     是否是充值方式
     * @param   [Array]    $config       多租户配置文件
     *
     * @return  []                     [return description]
     */
    public function pay($paymentName = 'wechat', $device = 'scan', $orderPay = [], $recharge = false, $config = 'default')
    {
        if (empty($orderPay)) {
            return $this->formatError('订单信息错误');
            // resError('订单信息错误');
            // exit();
        }

        // 支付配置
        $this->payData = [];
        $this->payData['_config'] = $config;

        // 余额支付处理
        if ($paymentName == 'balance') {
            // TODO:
        }

        // 充值管理
        if ($recharge) {
            $this->payData['name'] = '在线充值';
        }

        // 微信
        if ($paymentName == 'wechat') {
            $this->payData['out_trade_no'] = $orderPay['out_trade_no'];
            $this->payData['description'] = $recharge ? $this->payData['name'] : $orderPay['name'];
            $this->payData['amount'] = [
                'total' => $orderPay['amount'] * 100,
            ];

            // 小程序和公众号需要openID
            if (in_array($device, ['mp', 'mini'])) {
                // TODO:
            }

            // 如果是wap 必填场景信息
            if (in_array($device, ['wap'])) {
                // TODO:
            }
        }

        // 支付宝
        if ($paymentName == 'alipay') {
            $this->payData['out_trade_no'] = $orderPay['out_trade_no'];
            $this->payData['subject'] = $recharge ? $this->payData['name'] : $orderPay['name'];
            $this->payData['total_amount'] = $orderPay['amount'];
        }

        try {
            // $this->setConfig($paymentName, $device, $config);
            $result = Pay::$paymentName($this->config)->$device($this->payData);
            if (in_array($device, ['app', 'wap', 'web'])) {
                return $this->format($result->getBody()->getContents());
            }

            return $this->format($result);
        } catch (\Exception $e) {
            // Log::error('[' . $paymentName . ']:' . $e->getMessage());
            wpLog('[' . $paymentName . ']:' . $e->getMessage());
            return $this->formatError('调取支付失败');
        }
    }

    // 支付成功后处理订单
    public function paySuccess($paymentName = 'balance', $orderPay = null, $result = null)
    {
        if (empty($orderPay)) return $this->formatError('pay success params $orderPay empty .');

        // 订单已经支付
        if ($orderPay->pay_status == 1) return $this->formatError(__('tip.order.payed')); 


        // 如果是充值
        if ($paymentName == 'balance') {
            // TODO:根据total来更新余额
        }

        if ($paymentName == 'wechat') {
            if ($result->event_type != 'TRANSACTION.SUCCESS' && $result->resource['ciphertext']['trade_state'] != 'SUCCESS') {
                Log::error($result);
                throw new \Exception('wechat pay error - ' . $result->resource['ciphertext']['out_trade_no']);
            }
            $trade_no = $result->resource['ciphertext']['transaction_id'];
        }
        if ($paymentName == 'alipay') {
            if ($result->trade_status != 'TRADE_SUCCESS') {
                Log::error($result);
                throw new \Exception('alipay pay error - ' . $result->out_trade_no);
            }
            $trade_no = $result->trade_no;
        }
        
        // 订单信息更新
        // TODO: 把$trade_no保存到订单、付款时间now()、已支付

        // 订单状态修改
        $this->getService('Order', true)->whereIn('id', $orderIds)->update([
            'order_status' => 2,
            'pay_time' => now(),
            'payment_name' => $paymentName,
        ]);


        // 增加销量 - 其他支付回调的时候也要处理一遍
        // TODO:

        // 余额支付需要返回信息 第三方支付需要返回指定信息给回调服务器
        return $paymentName == 'balance' ? $this->format() : Pay::$paymentName($this->config)->success();
    }

    public function scan($out_trade_no, $body, $total_fee)
    {
        $order = [
            'out_trade_no' => $out_trade_no,
            'body' => $body,
            'total_fee' => $total_fee * 100, // 单位：分 ==> 元
            // 'openid' => 'onkVf1FjWS5SBIixxxxxxx', // 转账时需要
        ];

        $result = Pay::wechat($this->config)->scan($order);
        $result->out_trade_no = $out_trade_no;
        // print_r(json_encode($result)); // debug
        // return $result->code_url; // 二维码内容
        return $result;

        // {
        //     "return_code": "SUCCESS",
        //     "return_msg": "OK",
        //     "result_code": "SUCCESS",
        //     "mch_id": "1512637611",
        //     "appid": "wx63b95a5ba3d10f69",
        //     "nonce_str": "lqwYuInyNS0Hko7l",
        //  "out_trade_no": "0020220912081430002216100",
        //     "sign": "A6F04152883E6CC6692AB6231930E2F2",
        //     "prepay_id": "wx091430087762831625d5fdb64b36020000",
        //     "trade_type": "NATIVE",
        //     "code_url": "weixin://wxpay/bizpayurl?pr=CB8VMJzzz",
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

        return $pay->success()->send(); // laravel 框架中请直接 `return $pay->success()`
    }
}
