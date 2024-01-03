<?php

namespace App\Services;

use function Tonik\Theme\App\wpLog;
use Yansongda\Pay\Pay;

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
        $this->config['alipay']['default']['app_id'] = getenv('ALIPAY_APP_ID');
        $this->config['alipay']['default']['app_secret_cert'] = getenv('ALIPAY_APP_SECRET_CERT');
        $this->config['alipay']['default']['app_public_cert_path'] = getenv('ALIPAY_APP_PUBLIC_CERT_PATH');
        $this->config['alipay']['default']['alipay_public_cert_path'] = getenv('ALIPAY_PUBLIC_CERT_PATH');
        $this->config['alipay']['default']['alipay_root_cert_path'] = getenv('ALIPAY_ROOT_CERT_PATH');
        $this->config['alipay']['default']['notify_url'] = getenv('ALIPAY_NOTIFY_URL');

        $this->config['wechat']['default']['mch_id'] = getenv('WECHAT_MCH_ID');
        $this->config['wechat']['default']['mch_secret_key_v2'] = getenv('WECHAT_MCH_SECRET_KEY_V2');
        $this->config['wechat']['default']['mch_secret_key'] = getenv('WECHAT_MCH_SECRET_KEY');
        $this->config['wechat']['default']['mch_secret_cert'] = getenv('WECHAT_MCH_SECRET_CERT');
        $this->config['wechat']['default']['mch_public_cert_path'] = getenv('WECHAT_MCH_SECRET_CERT_PATH');
        $this->config['wechat']['default']['notify_url'] = getenv('WECHAT_NOTIFY_URL');
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
    public function pay($paymentName = 'alipay', $device = 'scan', $orderPay = [], $recharge = false, $config = 'default')
    {
        if (empty($orderPay)) {
            return $this->formatError('订单信息错误');
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

            // return $this->format(['code_url' => $result->code_url, 'order_id' => $orderPay['id'], 'out_trade_no' => $orderPay['out_trade_no']]);
            return $this->format($result);
        } catch (\Exception $e) {
            wpLog('[' . $paymentName . ']:' . $e->getMessage());
            return $this->formatError('调取支付失败');
        }
    }

    // 支付成功后处理订单
    public function paySuccess($paymentName = 'alipay', $orderPay = null, $result = null)
    {
        if (empty($orderPay)) {
            return $this->formatError('pay success params $orderPay empty .');
        }

        // 订单已经支付
        if ($orderPay['status'] == 'publish') {
            return $this->formatError(__('tip.order.payed'));
        }

        // 如果是余额支付
        if ($paymentName == 'balance') {
            // TODO: 根据total来更新余额
            // TODO: 生成余额记录
        }

        // 判断是否支付成功，未成功则提前退出
        if ($paymentName == 'wechat') {
            if ($result->event_type != 'TRANSACTION.SUCCESS' && $result->resource['ciphertext']['trade_state'] != 'SUCCESS') {
                wpLog($result);
                throw new \Exception('wechat pay error - ' . $result->resource['ciphertext']['out_trade_no']);
            }
            $trade_no = $result->resource['ciphertext']['transaction_id'];
        }
        if ($paymentName == 'alipay') {
            if ($result->trade_status != 'TRADE_SUCCESS') {
                wpLog($result);
                throw new \Exception('alipay pay error - ' . $result->out_trade_no);
            }
            $trade_no = $result->trade_no;
        }

        // 订单信息更新
        // TODO: 把$trade_no保存到订单？
        update_post_status($orderPay['id'], 'publish'); // 已支付（付款时间自动更新）
        update_post_meta($orderPay['id'], 'method', $paymentName); // 最终成功的支付方式

        // 如果是充值，生成余额变动记录
        if ($orderPay['type'] == 'recharge') {
            // $this->getService('MoneyLog')->edit([
            //     'name'  =>  __('tip.payment.onlineRecharge'),
            //     'user_id'  =>  $orderPay['from_user_id'],
            //     'money'  =>  $orderPay['amount'],
            // ]);
            return $this->format();
        }

        // 如果不是充值
        // 增加销量 - 其他支付回调的时候也要处理一遍
        // TODO:

        // 分销处理

        // 余额支付需要返回信息 第三方支付需要返回指定信息给回调服务器
        return $paymentName == 'balance' ? $this->format() : Pay::$paymentName($this->config)->success();
    }

    // 查询订单支付结果
    public function find($paymentName = 'alipay', $out_trade_no = null)
    {
        if (empty($out_trade_no)) {
            return $this->formatError('out_trade_no empty');
        }

        // 订单数据配置
        $this->orderData = [
            'out_trade_no' => $out_trade_no,
        ];

        try {
            // $this->setConfig($paymentName, $device, $config);
            $result = Pay::$paymentName($this->config)->find($this->orderData);
            // if (in_array($device, ['app', 'wap', 'web'])) {
            //     return $this->format($result->getBody()->getContents());
            // }

            return $this->format($result);
            // {
            //     "mch_id": "1512637611",
            //     "appid": "wx63b95a5ba3d10f69",
            //     "device_info": [],
            //     "total_fee": "3000",
            //     "out_trade_no": "0020220920083631008427000",
            //     "trade_state": "NOTPAY",
            //     "trade_state_desc": "订单未支付"
            // }
            // SUCCESS--支付成功
            // REFUND--转入退款
            // NOTPAY--未支付
            // CLOSED--已关闭
            // REVOKED--已撤销(刷卡支付)
            // USERPAYING--用户支付中
            // PAYERROR--支付失败(其他原因，如银行返回失败)
            // ACCEPT--已接收，等待扣款
        } catch (\Exception $e) {
            wpLog('[' . $paymentName . ']:' . $e->getMessage());
            return $this->formatError('查询支付结果失败');
        }
    }

    // 转账到支付宝账户
    public function transfer($paymentName = 'alipay', $out_trade_no = null, $amount = null, $identity = null, $name = null)
    {
        if (empty($out_trade_no)) {
            return $this->formatError('$out_trade_no empty');
        }

        if (empty($amount)) {
            return $this->formatError('$amount empty');
        }

        if (empty($identity)) {
            return $this->formatError('$identity empty');
        }

        if (empty($name)) {
            return $this->formatError('$name empty');
        }

        // 订单数据配置
        $this->orderData = [
            'out_biz_no' => $out_trade_no,
            'trans_amount' => $amount,
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'biz_scene' => 'DIRECT_TRANSFER',
            'payee_info' => [
                'identity' => $identity,
                'identity_type' => 'ALIPAY_LOGON_ID',
                'name' => $name,
            ],
        ];

        try {
            // $this->setConfig($paymentName, $device, $config);
            $result = Pay::$paymentName($this->config)->transfer($this->orderData);

            return $this->format($result);
            // {
            //     "mch_id": "1512637611",
            //     "appid": "wx63b95a5ba3d10f69",
            //     "device_info": [],
            //     "total_fee": "3000",
            //     "out_trade_no": "0020220920083631008427000",
            //     "trade_state": "NOTPAY",
            //     "trade_state_desc": "订单未支付"
            // }
        } catch (\Exception $e) {
            wpLog('[' . $paymentName . ']:' . $e->getMessage());
            return $this->formatError('转账失败');
        }
    }

    // 修改配置
    public function setConfig($paymentName, $device = 'web', $config = 'default')
    {
        // TODO: 给证书加上绝对链接
    }

    /**
     * 第三方支付回调
     *
     * 为保证订单确实支付成功，或者其他人恶意请求notify_url。建议使用者，在接到支付宝和微信异步通知的时候进行一次主动查询。
     *
     * @return  确认回调
     */
    public function notify($paymentName = 'alipay', $device = 'scan', $config = 'default')
    {
        // $this->setConfig($paymentName, $device, $config);
        $result = Pay::$paymentName($this->config)->callback(null, ['_config' => $config]);

        try {
            if ($paymentName == 'wechat') {
                $out_trade_no = $result->resource['ciphertext']['out_trade_no'];
            }

            if ($paymentName == 'alipay') {
                $out_trade_no = $result->out_trade_no;
            }

            if (empty($out_trade_no)) {
                throw new \Exception('not found out_trade_no');
            }

            // TODO: 使用队列处理第三方回调
            // return Pay::$paymentName($this->config)->success();
            // return $pay->success()->send(); // laravel 框架中请直接 `return $pay->success()`

            // 1. 通过no拿到orderInfo
            $orderInfo = theme('order')->getOrderByNo($out_trade_no);

            // 2. 触发支付成功后的操作paySuccess
            $paySuccessData = theme('payment')->paySuccess($paymentName, $orderInfo, $result);

            // 3. 返回paySuccess结果
            if (!is_array($paySuccessData)) {
                return $paySuccessData;
            }

            if (!$paySuccessData['status']) {
                throw new \Exception($paySuccessData['msg']);
            }

            return $paySuccessData;
        } catch (\Exception $e) {
            wpLog('[' . $paymentName . ']:' . $e->getMessage());
            return $this->formatError($e->getMessage());
        }
    }
}
