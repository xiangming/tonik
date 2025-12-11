<?php

namespace App\Projects\Fans\Handlers;

use function Tonik\Theme\App\theme;

/**
 * Fans 项目支付成功处理器
 * 
 * 处理打赏订单支付成功后的业务逻辑：
 * 1. 生成打赏记录
 * 2. 加入打款队列
 */
class FansPaymentHandler
{
    /**
     * 处理打赏订单支付成功
     * 
     * @param array $orderPay 订单信息
     * @param string $paymentName 支付方式
     */
    public static function handleDonationPaymentSuccess($orderPay, $paymentName)
    {
        theme('log')->log('FansPaymentHandler handleDonationPaymentSuccess start', $orderPay, $paymentName);

        try {
            // 1. 生成打赏记录
            $rs = theme('donation')->createDonation($orderPay['from_user_id'], $orderPay['to_user_id'], $orderPay['id']);

            // 生成失败，记录错误
            if (!$rs['status']) {
                theme('log')->error('创建打赏记录失败', 'FansPaymentHandler', $rs['msg']);
                return;
            }

            // 2. 打赏记录创建后，加入打款队列
            theme('queue')->add_async('transfer', [$rs['data']]);

            theme('log')->log('FansPaymentHandler handleDonationPaymentSuccess success');
        } catch (\Exception $e) {
            theme('log')->error($e->getMessage(), 'FansPaymentHandler handleDonationPaymentSuccess');
        }
    }
}
