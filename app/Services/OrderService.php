<?php

namespace App\Services;

use \App\Services\BaseService;
use function Tonik\Theme\App\theme;

class OrderService extends BaseService
{
    // 创建订单前处理订单
    public function createOrderBefore()
    {
        theme('log')->log('OrderService createOrderBefore start');

        // TODO: 格式化商品数据等
    }

    /**
     * 创建内部订单（非第三方订单）
     *
     * @param   [string]  $type    服务类型：打赏（donation）、购买（buy）、充值（recharge）等
     * @param   [string]  $amount  金额
     * @param   [string]  $name    购买的服务名称
     * @param   [string]  $remark  备注/留言
     * @param   [string]  $related 关联项目：被打赏人
     * @param   [string]  $method  支付方式
     * @param   [string]  $author_id 创建者，可选
     *
     * @return  [object]    订单信息
     */
    public function createOrder($type, $amount, $name, $remark, $related, $method, $author_id)
    {
        theme('log')->log('OrderService->createOrder() start', $type, $amount, $name, $remark, $related, $method, $author_id);

        // TODO: 格式化商品数据等

        // TODO: 优惠券的处理

        // TODO: 地址验证

        // 生成支付通道订单号
        $out_trade_no = theme('tool')->generateTradeNo(); // 注意总长度不能超过32位

        // 创建订单
        $in_data = array(
            'post_title' => $out_trade_no,
            'post_status' => 'draft',
            'post_type' => 'orders', // custom-post-type
        );

        // 如果指定了author_id，则设置
        if($author_id) $in_data['post_author'] = $author_id;

        // https://developer.wordpress.org/reference/functions/wp_insert_post/
        // If the $postarr parameter has ‘ID’ set to a value, then post will be updated.
        $in_id = wp_insert_post($in_data, true);

        // 订单创建错误
        if (is_wp_error($in_id)) {
            $errmsg = $in_id->get_error_message();

            theme('log')->error('OrderService->createOrder() error', $errmsg);

            return $this->formatError($errmsg);
        }

        // 金额
        if (isset($amount)) {
            update_post_meta($in_id, 'amount', $amount);
        }

        // 服务名称
        if (isset($name)) {
            update_post_meta($in_id, 'name', $name);
        }

        // 服务类型
        if (isset($type)) {
            update_post_meta($in_id, 'type', $type);
        }

        // 备注
        if (isset($remark)) {
            update_post_meta($in_id, 'remark', $remark);
        }

        // 关联项目
        if (isset($related)) {
            update_post_meta($in_id, 'related', $related);
        }

        // 支付通道
        if (isset($method)) {
            update_post_meta($in_id, 'method', $method);
        }

        // TODO: 执行成功则删除购物车

        $result = [
            'id' => $in_id,
            'out_trade_no' => $out_trade_no,
            'amount' => $amount,
            'name' => $name,
            'type' => $type,
            'remark' => $remark,
            'related' => $related,
            'method' => $method,
        ];

        theme('log')->log('OrderService->createOrder() success', $result);

        return $this->format($result);
    }

    // 创建订单后处理
    public function createOrderAfter()
    {
        theme('log')->log('OrderService createOrderAfter start');
    }

    // 库存消减增加 is_type 0 减少  1增加
    public function orderStock($goods_id, $sku_id, $num, $is_type = 0)
    {
        theme('log')->log('OrderService orderStock start');
    }

    // 销量消减增加 is_type 0 减少  1增加
    public function orderSale($goods_id, $num, $is_type = 0)
    {
        theme('log')->log('OrderService orderSale start');
    }

    /**
     * // 支付订单 function
     *
     * @param string $order_id 如：10,12,13
     * @param string $payment_name 如：wechat_scan|balance|wechat_h5
     * @param string $pay_password 如：123456 （非必填,payment_name=balance则需要填写)
     * @param string $recharge 如：1 （非必填）
     * @return void
     */
    public function payOrder()
    {
        theme('log')->log('OrderService payOrder start');
    }

    // 创建支付订单
    // @param bool $recharge_pay 是否是充值 还是订单
    // @param string $pay_no 支付订单号
    protected function createPayOrder($opt = [])
    {
        theme('log')->log('OrderService createPayOrder start');
    }

    /**
     * 订单状态修改 function
     *
     * @param [type] $order_id 订单ID
     * @param [type] $order_status 订单状态
     * @param [type] $auth 用户操作还是管理员操作 user|admin
     * @return void
     */
    public function editOrderStatus($order_id, $order_status, $auth = "users")
    {
        theme('log')->log('OrderService editOrderStatus start');
    }

    // 地址验证
    public function checkAddress()
    {
        theme('log')->log('OrderService checkAddress start');
    }

    // 计算运费
    // @param mixed $freight_id 运费模版
    // @param mixed $total_weight 总重量
    // @param mixed $store_id 店铺ID
    // @param mixed $area_id 省份ID
    protected function sumFreight($freight_id, $total_weight, $store_id, $area_id)
    {
        theme('log')->log('OrderService sumFreight start');
    }

    // 根据订单ID获取商品数据并格式化
    public function createOrderFormat($params)
    {
        theme('log')->log('OrderService createOrderFormat start');
    }

    // 查询订单是否支付成功，$rs['data']返回布尔值
    public function check($orderId)
    {
        theme('log')->log('OrderService check start');

        if (empty($orderId)) {
            theme('log')->error('$orderId empty', 'OrderService check');

            return $this->formatError('$orderId empty.');
        }

        $status = get_post_status($orderId);

        if (!$status) {
            theme('log')->error('get_post_status error', 'OrderService check');

            return $this->formatError('order status check failed');
        }

        theme('log')->log('OrderService check success');

        return $this->format($status === 'publish');
    }

    // // 获取订单
    public function getOrders($type = "donation")
    {
        theme('log')->log('OrderService getOrders start');
    }

    /**
     * 通过out_trade_no获取订单信息
     *
     * @param   [type]  out_trade_no
     *
     * @return  [type]  订单信息对象
     */
    public function getOrderByNo($no)
    {
        theme('log')->log('OrderService getOrderByNo start');

        $args = array(
            'title' => $no,
            'post_status' => 'any',
            'post_type' => 'orders',
            // 'fields' => 'ids',
        );
        $orders = get_posts($args);

        // check the order
        if (empty($orders) || empty($orders[0])) {
            theme('log')->error('订单不存在', 'OrderService getOrderByNo');

            return $this->formatError('订单不存在');
        }

        $order = $orders[0];
        $orderId = $order->ID;
        $result = [
            'id' => $orderId,
            'status' => get_post_status($orderId),
            'from_user_id' => $order->post_author,
            'to_user_id' => get_post_meta($orderId, 'related', true),
            'name' => get_post_meta($orderId, 'name', true),
            'amount' => get_post_meta($orderId, 'amount', true),
            'type' => get_post_meta($orderId, 'type', true),
            'remark' => get_post_meta($orderId, 'remark', true),
        ];

        theme('log')->log($result, 'OrderService getOrderByNo success');

        return $this->format($result);
    }
}
