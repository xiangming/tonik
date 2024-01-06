<?php

namespace App\Services;

use function Tonik\Theme\App\theme;

class DonationService extends BaseService
{
    /**
     * 创建打赏记录
     * @param int $from_user_id  打赏人ID
     * @param int $to_user_id  被打赏人ID
     * @param int $amount 打赏金额
     * @param string $remark 打赏留言，可选
     * @param string $orderId 关联订单，可选
     *
     * @return object donation_info
     */
    public function create($from_user_id, $to_user_id, $amount, $remark, $orderId)
    {
        // 生成支付通道订单号
        $out_trade_no = date('YmdHis') . '00' . mt_rand(10000, 99999);

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

            theme('log')->log($errmsg, 'donation create error');

            return $this->formatError($errmsg);
        }

        // 被打赏人
        if (isset($to_user_id)) {
            update_post_meta($in_id, 'to', $to_user_id);
        }

        // 打赏金额
        if (isset($amount)) {
            update_post_meta($in_id, 'amount', $amount);
        }

        // 备注
        if (isset($remark)) {
            update_post_meta($in_id, 'remark', $remark);
        }

        // 关联订单
        if (isset($orderId)) {
            update_post_meta($in_id, 'orderId', $orderId);
        }

        $donation_info = [
            'id' => $in_id,
            'out_trade_no' => $out_trade_no,
            'orderId' => $orderId,
            'amount' => $amount,
            'identity' => get_user_meta($to_user_id, 'alipay_id', true), // FIXME: 使用创作者入驻的字段
            'name' => get_user_meta($to_user_id, 'real_name', true), // FIXME: 使用创作者入驻的字段
        ];

        theme('log')->log($donation_info, 'donation create success');

        return $this->format($donation_info);
    }
}
