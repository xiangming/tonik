<?php

namespace App\Services;

use function Tonik\Theme\App\theme;

class DonationService extends BaseService
{
    /**
     * 创建打赏记录
     * @param int $from_user_id  打赏人ID
     * @param int $to_user_id  被打赏人ID
     * @param string $orderId 关联订单
     *
     * @return object donation_info
     */
    public function createDonation($from_user_id, $to_user_id, $orderId)
    {
        theme('log')->log('createDonation start');

        // 生成支付通道订单号
        $out_trade_no = theme('tool')->generateTradeNo();

        $in_data = array(
            'post_author' => $from_user_id,
            'post_title' => $out_trade_no,
            'post_status' => 'draft',
            'post_type' => 'donation', // custom-post-type
        );
        // https://developer.wordpress.org/reference/functions/wp_insert_post/
        // If the $postarr parameter has ‘ID’ set to a value, then post will be updated.
        $in_id = wp_insert_post($in_data, true);

        // 订单提交错误
        if (is_wp_error($in_id)) {
            $errmsg = $in_id->get_error_message();

            theme('log')->error('createDonation', $errmsg);

            return $this->formatError($errmsg);
        }

        // 被打赏人
        if (isset($to_user_id)) {
            update_post_meta($in_id, 'to', $to_user_id);
        }

        // 关联订单
        if (isset($orderId)) {
            update_post_meta($in_id, 'orderId', $orderId);
        }

        // 打赏金额
        $amount = get_post_meta($orderId, 'amount', true);

        // 备注
        $remark = get_post_meta($orderId, 'remark', true);

        $result = [
            'id' => $in_id,
            'orderId' => $orderId,
            'out_trade_no' => $out_trade_no,
            'amount' => $amount,
            'remark' => $remark,
            'identity' => get_user_meta($to_user_id, 'alipay', true), // 用于打款，创作者收款账号
            'name' => get_user_meta($to_user_id, 'name', true), // 用于打款，创作者真实姓名
        ];

        theme('log')->log('createDonation success', $result);

        return $this->format($result);
    }

    public function getDonationById($id)
    {
        // 支付单号
        $out_trade_no = get_the_title($id);

        // 关联订单号
        $orderId = get_post_meta($id, 'orderId', true);

        // 打赏金额
        $amount = (int) get_post_meta($orderId, 'amount', true);

        // 备注
        $remark = get_post_meta($orderId, 'remark', true);

        $result = [
            'id' => $id,
            'out_trade_no' => $out_trade_no,
            'orderId' => $orderId,
            'amount' => $amount,
            'remark' => $remark,
        ];

        theme('log')->log('getDonationById success', $result);

        return $this->format($result);
    }
}
