<?php

namespace App\Projects\Fans\Api;

use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\theme;

/**
 * 打赏相关 REST API 端点
 * 
 * 包含：
 * - 创建打赏订单并发起支付
 */
class DonationApi
{
    const NAMESPACE = '/wp/v2';

    /**
     * 注册所有打赏相关端点
     */
    public static function register()
    {
        self::registerDonationEndpoint();
    }

    /**
     * 创建打赏订单并发起支付
     * POST /wp/v2/payment/donation
     */
    private static function registerDonationEndpoint()
    {
        register_rest_route(self::NAMESPACE, '/payment/donation', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $parameters = $request->get_json_params();

                $from = $parameters['from'] ?? null; // 打赏人，可选
                $to = $parameters['to']; // 被打赏人，必填
                $amount = $parameters['amount']; // 打赏金额，必填
                $remark = $parameters['remark'] ?? null; // 打赏留言，可选
                $method = $parameters['method']; // 支付通道，必填
                $device = $parameters['device']; // 支付设备类型，必填

                // 处理打赏人
                $from_user_id = null;
                if ($from) {
                    $from_user_id = theme('user')->exists($from);
                    // 填写的打赏记录绑定账号不存在，则提示用户注册
                    if (!$from_user_id) {
                        return resError('打赏记录绑定账号不存在，请先注册');
                    }
                }

                // 处理被打赏人
                $to_user_id = theme('user')->exists($to);
                if (!$to_user_id) {
                    return resError('被打赏人不存在');
                }

                // 1. 创建order
                $order = theme('order')->createOrder([
                    'type' => 'donation',
                    'amount' => $amount,
                    'method' => $method,
                    'title' => '打赏-' . $to,
                    'remark' => $remark,
                    'from_user_id' => $from_user_id,
                    'to_user_id' => $to_user_id,
                ]);

                if (!$order['status']) {
                    return resError($order['msg']);
                }

                // 2. 调取第三方支付
                $rs = theme('payment')->pay($method, $device, $order['data']);

                // 3. 输出结果
                return $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
            },
            'args' => array(
                'from' => theme('args')->account(false), // 可选
                'to' => theme('args')->account(true),
                'amount' => theme('args')->amount(true),
                'remark' => theme('args')->remark(false), // 可选
                'method' => theme('args')->method(true),
                'device' => theme('args')->device(true),
            ),
            'permission_callback' => '__return_true',
        ));
    }
}
