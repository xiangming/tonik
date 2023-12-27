<?php

namespace App\Services;

class OrderService extends BaseService
{
    // 创建订单前处理订单
    public function createOrderBefore()
    {
        // TODO: 格式化商品数据等
    }

    /**
     * 创建订单
     *
     * @param   [string]  $type    购买的服务类型：打赏（donation）、购买（buy）、充值（recharge）等
     * @param   [string]  $amount  金额
     * @param   [string]  $name    购买的服务名称
     * @param   [string]  $remark  备注留言
     *
     * @return  [type]           [return description]
     */
    public function createOrder($type, $amount, $name, $remark, $related)
    {
        // TODO: 格式化商品数据等

        // TODO: 优惠券的处理

        // TODO: 地址验证

        // 生成订单号
        // $out_trade_no = date('YmdHis') . '-' . $productId . '-' . $from_user_id . '-' . $to_user_id . '-' . rand(1000, 9999); // 注意总长度不能超过32位
        $out_trade_no = date('YmdHis') . '00' . mt_rand(10000, 99999);

        // 创建订单
        $in_data = array(
            // 'post_author'    => $uid,
            'post_title' => $out_trade_no,
            'post_status' => 'draft',
            'post_type' => 'orders',
        );
        // https://developer.wordpress.org/reference/functions/wp_insert_post/
        // If the $postarr parameter has ‘ID’ set to a value, then post will be updated.
        $in_id = wp_insert_post($in_data, true);

        // 订单创建错误
        if (is_wp_error($in_id)) {
            $errmsg = $in_id->get_error_message();
            // return new WP_Error(1, $errmsg);
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

        // TODO: 执行成功则删除购物车

        $order_pay_info = [
            'id' => $in_id,
            'out_trade_no' => $out_trade_no,
            'amount' => $amount,
            'name' => $name,
            'type' => $type,
            'remark' => $remark,
            'related' => $related,
        ];

        return $this->format($order_pay_info);
    }

    // 创建订单后处理
    public function createOrderAfter()
    {

    }

    // 库存消减增加 is_type 0 减少  1增加
    public function orderStock($goods_id, $sku_id, $num, $is_type = 0)
    {

    }

    // 销量消减增加 is_type 0 减少  1增加
    public function orderSale($goods_id, $num, $is_type = 0)
    {

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
        $order_id = request()->order_id;
        $payment_name = request()->payment_name ?? '';
        $device = request()->device ?? 'web';

        // 获取用户信息
        $userInfo = $this->getUser('users');
        if (!$userInfo['status']) {
            return $this->formatError($userInfo['msg']);
        }
        $userId = $userInfo['data']['id'];

        // 检查支付方式是否传过来
        if (empty($payment_name)) {
            return $this->formatError(__('tip.order.paymentErr'));
        }

        // 如果是余额支付
        $balance = 0;
        if ($payment_name == 'balance') {
            if (!Hash::check(request()->pay_password ?? '', $userInfo['data']['pay_password'])) {
                return $this->formatError(__('tip.pwdErr'));
            }
            $balance = $userInfo['data']['money'];
        }

        // 判断订单号是否为空
        if (empty($order_id)) {
            return $this->formatError(__('tip.order.error') . ' - pay');
        }
        $order_arr = explode(',', $order_id); // 转化为数组
        $order_str = implode('', $order_arr); // 转化为字符串生成支付订单号

        // 判断是否订单是该用户的并且订单是否有支付成功过
        $order_model = $this->getService('Order', true);
        // 判断是否存在 指定订单
        if (!$order_model->whereIn('id', $order_arr)->where('user_id', $userId)->exists()) {
            return $this->formatError(__('tip.order.error') . ' - pay2');
        }
        // 判断是否已经支付过了
        $order_list = $order_model->whereIn('id', $order_arr)->where('user_id', $userId)->where('order_status', 1)->get();
        if ($order_list->isEmpty()) {
            return $this->formatError(__('tip.order.payed'));
        }

        $make_rand = date('YmdHis') . substr($userId, 0, 1) . mt_rand(10000, 99999); // 生成订单号
        $rs = $this->createPayOrder([
            'pay_no' => $make_rand,
            'user_id' => $userId,
            'payment_name' => $payment_name,
            'order_list' => $order_list,
            'device' => $device,
            'balance' => $balance,
        ]);

        // 创建支付订单失败
        if (!$rs['status']) {
            return $this->formatError($rs['msg']);
        }

        // 获取支付信息,调取第三方支付
        $payment_model = new PaymentService();
        $rs = $payment_model->pay($payment_name, $device, $rs['data']);
        return $rs['status'] ? $this->format($rs['data']) : $this->formatError($rs['msg']);
    }

    // 创建支付订单
    // @param bool $recharge_pay 是否是充值 还是订单
    // @param string $pay_no 支付订单号
    protected function createPayOrder($opt = [])
    {
        // 创建支付订单
        $params = [
            'recharge_pay' => false,
            'user_id' => 0,
            'pay_no' => '',
            'payment_name' => '',
            'order_list' => [],
            'device' => 'pc',
            'balance' => 0, // 用户余额
        ];

        if (empty($params['user_id'])) {
            $params['user_id'] = $this->getUserId('users');
        }

        $params = array_merge($params, $opt);
        $create_data = [];
        if ($params['recharge_pay']) {
            $pay_no = date('YmdHis') . mt_rand(10000, 99999);
            $create_data = [
                'belong_id' => $params['user_id'],
                'pay_no' => $pay_no,
                'payment_name' => $params['payment_name'],
                'is_recharge' => 1,
                'device' => $params['device'],
                'total' => abs(request()->total ?? 1), // 充值金额
            ];
        } else {
            $order_ids = [];
            $total_price = 0;
            $order_balance = 0;
            foreach ($params['order_list'] as $v) {
                $order_ids[] = $v['id'];
                $total_price = bcadd($total_price, $v['total_price'], 2);
                // $order_balance += $v['order_balance'];
            }
            // 余额支付时判断是否余额足够
            if ($params['payment_name'] == 'balance') {
                if ($total_price > $params['balance']) {
                    return $this->formatError(__('tip.order.moneyNotEnough'));
                }
                $order_balance = $total_price;
            }
            $create_data = [
                'belong_id' => $params['user_id'],
                'name' => mb_substr($params['order_list'][0]['order_name'], 0, 60),
                'pay_no' => $params['pay_no'],
                'order_ids' => implode(',', $order_ids),
                'payment_name' => $params['payment_name'],
                'device' => $params['device'],
                'total' => $total_price, // 订单总金额
                'balance' => $order_balance, // 余额支付金额
            ];
        }

        try {
            // 三秒钟不能重复支付订单创建 设计订单支付号 当前时间到秒的十位+用户ID+订单ID号
            $op = $this->getService('OrderPay', true)->where('belong_id', $params['user_id'])->orderBy('id', 'desc')->first();
            if ($op) {
                if (($op->created_at->timestamp + 3) > time()) {
                    return $this->formatError(__('tip.order.moneyPay'));
                }
            }
            $order_pay_info = $this->getService('OrderPay', true)->create($create_data);
            return $this->format($order_pay_info);
        } catch (\Exception $e) {
            return $this->formatError(__('tip.order.payErr') . $e->getMessage());
        }
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
        try {

            // 用户不允许随意操作状态，只能修改 取消订单和确定订单

            // 商家可操作

            switch ($order_status) {
                case 0: // 取消订单

                // 只有待支付的订单能取消

                // 库存修改

                // 如果有优惠券则修改优惠券

                case 1: // 等待支付

                    break;
                case 2: // 等待发货 用户已经支付完成

                    break;
                case 3: // 发货完成 等待 确认收货

                    // 如果已经是该数据就不再更新订单状态

                    break;
                case 4: // 确认收货 等待评论

                    // 只有待收货的订单能 确认

                    break;
                case 5: // 5售后 商家处理

                    break;
                case 6: // 6订单完成

                    break;
            }

        } catch (\Exception $e) {
            return $this->formatError($e->getMessage());
        }
    }

    // 地址验证
    public function checkAddress()
    {

    }

    // 计算运费
    // @param mixed $freight_id 运费模版
    // @param mixed $total_weight 总重量
    // @param mixed $store_id 店铺ID
    // @param mixed $area_id 省份ID
    protected function sumFreight($freight_id, $total_weight, $store_id, $area_id)
    {

    }

    // 根据订单ID获取商品数据并格式化
    public function createOrderFormat($params)
    {

    }

    // base64 代码验证
    public function base64Check()
    {
        $base64 = request()->params ?? '';

        // 如果为空
        if (empty($base64)) {
            return $this->formatError(__('tip.order.error'));
        }

        // 判断是否能解析
        try {
            $params = json_decode(base64_decode($base64), true);
        } catch (\Exception $e) {
            return $this->formatError(__('tip.order.error') . '2');
        }
        return $this->format($params);
    }

    // // 获取订单
    public function getOrders($type = "donation")
    {

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
        $args = array(
            'title' => $no,
            'post_status' => 'any',
            'post_type' => 'orders',
            // 'fields' => 'ids',
        );
        $orders = get_posts($args);

        // FIXME: check the orders count
        if ($orders) {
            $order = $orders[0];
            $orderId = $order['id'];
            return array_merge($order, [
                'from_user_id' => $order['author'],
                'to_user_id' => get_post_meta($orderId, 'related'),
                'name' => get_post_meta($orderId, 'name'),
                'amount' => get_post_meta($orderId, 'amount'),
                'type' => get_post_meta($orderId, 'type'),
                'remark' => get_post_meta($orderId, 'remark'),
            ]);
        }

        return null;
    }

    // 获取关联项目信息
    public function getOrderRelatedInfo($order_info)
    {
        $result = 'N/A';
        switch ($order_info['type']) {
            case 'recharge':
                $result = '';
                break;
            case 'donation':
                if ($order_info['related']) {
                    $related_info = explode('-', $order_info['related']); // 转化为数组
                    if ($related_info[0] == 'user') {
                        $result = $related_info[1];
                    }
                }
                break;
            default:
                break;
        }
        return $result;
    }

    // 获取支付类型
    public function getOrderPayMentCn($payment_name)
    {
        $cn = __('tip.waitPay');
        switch ($payment_name) {
            case 'wechat':
                $cn = __('tip.paymentWechat');
                break;
            case 'ali':
                $cn = __('tip.paymentAli');
                break;
            case 'balance':
                $cn = __('tip.paymentMoney');
                break;
        }
        return $cn;
    }
}
