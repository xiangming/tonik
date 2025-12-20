<?php

namespace Tonik\Theme\App\Setup;

/*
|-----------------------------------------------------------
| Theme Custom Actions
|-----------------------------------------------------------
|
| 此文件用于注册通用的 WordPress Actions
| 
| 项目特定的 Actions 在 app/Projects/{ProjectName}/Setup/actions.php 中注册
|
 */

/**
 * 示例：主题激活时执行的操作
 */
function theme_activation()
{
    // 通用的主题激活逻辑
}
add_action('after_switch_theme', 'Tonik\Theme\App\Setup\theme_activation');

/**
 * 处理余额充值支付成功
 */
add_action('payment_success_recharge', function ($orderPay, $paymentName) {
    theme('log')->log('Recharge payment success', $orderPay, $paymentName);
    
    try {
        // TODO: 实现余额充值逻辑
        // theme('balance')->addRecord($orderPay['from_user_id'], $orderPay['amount'], 'recharge', $orderPay['id']);
        
        theme('log')->log('Recharge processed successfully');
    } catch (\Exception $e) {
        theme('log')->error($e->getMessage(), 'payment_success_recharge');
    }
}, 10, 2);

/**
 * 处理会员订阅支付成功
 * 
 * 支持功能：
 * - 新开通会员
 * - 延长已有会员期限（续费）
 * - 多级别会员管理
 */
add_action('payment_success_membership', function ($orderPay, $paymentName) {
    theme('log')->log('Membership payment success', $orderPay, $paymentName);
    
    try {
        $user_id = $orderPay['from_user_id'];
        $order_id = $orderPay['id'];
        
        // 获取会员计划信息
        $plan_id = get_post_meta($order_id, 'plan_id', true);
        $duration_months = get_post_meta($order_id, 'duration_months', true) ?: 1;
        $level = get_post_meta($order_id, 'level', true) ?: 'basic';
        
        // 计算新的到期时间（如果已是会员，则延长）
        $current_expire = get_user_meta($user_id, 'membership_expire', true);
        $base_time = ($current_expire && strtotime($current_expire) > time()) 
            ? strtotime($current_expire) 
            : time();
        
        $new_expire = date('Y-m-d H:i:s', strtotime("+{$duration_months} months", $base_time));
        
        // 更新会员信息
        update_user_meta($user_id, 'membership_expire', $new_expire);
        update_user_meta($user_id, 'membership_level', $level);
        update_user_meta($user_id, 'is_member', true);
        
        theme('log')->log('Membership activated successfully', [
            'user_id' => $user_id,
            'level' => $level,
            'expire' => $new_expire,
            'order_id' => $order_id
        ]);
    } catch (\Exception $e) {
        theme('log')->error($e->getMessage(), 'payment_success_membership');
    }
}, 10, 2);

/*
|-----------------------------------------------------------
| Payment REST API Endpoints
|-----------------------------------------------------------
|
| 通用的支付相关 REST API 端点
| 适用于所有项目的支付查询和回调功能
|
 */

use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\theme;

add_action('rest_api_init', function () {
    /**
     * 创建订单并发起支付（通用接口）
     * POST /wp/v2/payment/create
     * 
     * 适用于所有项目的支付场景
     * 各项目通过 order_type 区分业务类型
     */
    register_rest_route('/wp/v2', '/payment/create', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            // 提取设备类型（只用于支付通道，不存订单）
            $device = $parameters['device'] ?? null;
            unset($parameters['device']);

            // 验证必填字段
            if (empty($parameters['type']) || empty($parameters['amount']) || empty($parameters['method']) || empty($device)) {
                return resError('缺少必填字段：type, amount, method, device');
            }

            // 1. 创建订单（直接传递所有参数）
            $order = theme('order')->createOrder($parameters);

            if (!$order['status']) {
                return resError($order['msg']);
            }

            // 2. 调取第三方支付
            $rs = theme('payment')->pay($parameters['method'], $device, $order['data']);

            // 3. 返回支付结果
            return $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
        },
        'args' => array(
            'type' => array(
                'required' => true,
                'type' => 'string',
                'description' => '订单类型（如：product, service, donation, membership, recharge）',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'amount' => theme('args')->amount(true),
            'method' => theme('args')->method(true),
            'device' => theme('args')->device(true),
            'title' => array(
                'required' => false,
                'type' => 'string',
                'description' => '订单标题（可选，默认自动生成）',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'remark' => array(
                'required' => false,
                'type' => 'string',
                'description' => '订单备注/留言（可选），如买家留言、打赏留言等',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            // 其他业务字段根据订单类型自由传递
            // donation: from_user_id, to_user_id
            // membership: plan_id, duration_months, level
            // product: product_id, quantity, sku_id
            // service: service_id, appointment_time, requirements
        ),
        'permission_callback' => '__return_true',
    ));

    /**
     * 查询订单的支付结果（返回支付通道原始对象）
     * POST /wp/v2/payment/find
     */
    register_rest_route('/wp/v2', '/payment/find', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $method = $parameters['method']; // 支付通道
            $out_trade_no = $parameters['out_trade_no']; // 第三方单号

            $rs = theme('payment')->find($method, $out_trade_no);

            return $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
        },
        'args' => array(
            'method' => theme('args')->method(true),
            'out_trade_no' => theme('args')->out_trade_no(true),
        ),
        'permission_callback' => '__return_true',
    ));

    /**
     * 请求后端检查支付结果并触发支付成功后的相关操作
     * POST /wp/v2/payment/query
     */
    register_rest_route('/wp/v2', '/payment/query', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $method = $parameters['method']; // 支付通道
            $out_trade_no = $parameters['out_trade_no']; // 第三方单号

            $rs = theme('payment')->query($method, $out_trade_no);

            if (!$rs['data']) {
                return resOK(false, '订单未支付');
            }

            // 支付成功，执行paySuccess操作（无论成功与否都往下继续执行）
            try {
                // 1. 通过out_trade_no拿到orderInfo
                $order_rs = theme('order')->getOrderByNo($out_trade_no);

                // 2. 触发支付成功后的操作paySuccess
                $paySuccessData = theme('payment')->paySuccess($method, $order_rs['data']);

                if (!$paySuccessData['status']) {
                    throw new \Exception($paySuccessData['msg']);
                }
            } catch (\Exception $e) {
                theme('log')->error($e->getMessage(), '/payment/query');
            }

            return resOK(true, '订单支付成功');
        },
        'args' => array(
            'method' => theme('args')->method(true),
            'out_trade_no' => theme('args')->out_trade_no(true),
        ),
        'permission_callback' => '__return_true',
    ));

    /**
     * 支付宝通知回调
     * GET /wp/v2/payment/alipay/notify
     */
    register_rest_route('/wp/v2', '/payment/alipay/notify', array(
        'methods' => 'GET',
        'callback' => function ($request) {
            $rs = theme('payment')->notify('alipay');

            return $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
        },
        'permission_callback' => '__return_true',
    ));

    /**
     * 微信支付通知回调
     * POST /wp/v2/payment/wechat/notify
     */
    register_rest_route('/wp/v2', '/payment/wechat/notify', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $rs = theme('payment')->notify('wechat');

            return $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
        },
        'permission_callback' => '__return_true',
    ));
});
