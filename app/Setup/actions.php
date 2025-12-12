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
});
