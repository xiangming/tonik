<?php

namespace Tonik\Theme\App\Setup;

/*
|-----------------------------------------------------------
| Theme Actions
|-----------------------------------------------------------
|
| This file purpose is to include your custom
| actions hooks, which process a various
| logic at specific parts of WordPress.
|
 */

/**
 * Example action handler.
 *
 * @return integer
 */
function example_action()
{
    //
}
add_filter('excerpt_length', 'Tonik\Theme\App\Setup\example_action');

/**
 * 打款 action handler.
 *
 * @return integer
 */
function transfer($donation)
{
    $paymentService = theme('payment');

    // 向支付通道查询一次订单状态
    $out_trade_no = get_the_title($donation['orderId']);
    $paymentName = get_post_meta($donation['orderId'], 'method');
    $rs = $paymentService->find($paymentName, $out_trade_no);

    // TODO: 支持余额支付

    // 查询失败，提前退出
    if (!$rs['status']) {
        // update_post_status($donation['id'], 'pending');
        return;
    }

    // 支付未成功则设置为draft并提前退出
    if ($rs['data']['trade_state'] !== 'SUCCESS') {
        // update_post_status($donation['id'], 'draft');
        return;
    }

    // 支付成功

    // 执行打款（当前只支持alipay）
    $rs = $paymentService->transfer('alipay', '2023122712345', '0.1', '282818269@qq.com', '向明'); // FIXME: 改为动态值
    // $rs = $paymentService->transfer('alipay', '2023122712345', $donation['amount'], $donation['identity'], $donation['name']);

    // 打款失败
    if (!$rs['status']) {
        update_post_status($donation['id'], 'pending');
        return;
    }

    // 打款成功
    update_post_status($donation['id'], 'publish');
}
add_action('transfer', 'Tonik\Theme\App\Setup\transfer');

/**
 * Fire a callback only when post or custom-post-type transitioned to 'publish'.
 *
 * 'transition_post_status' is a generic action that is called every time a post changes status.
 *
 * 'transition_post_status' is executed before 'save_post', so you can not get_post_meta when create new post.
 *
 * https://developer.wordpress.org/reference/hooks/transition_post_status/
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function statusHook($new_status, $old_status, $post)
{
    // 从非publish变更为publish时执行
    if ('publish' == $new_status && 'publish' != $old_status && isset($post->post_type)) {
        $id = $post->ID;

        switch ($post->post_type) {

            case 'orders':
                $out_trade_no = $post->post_title;
                $order = theme('order')->getOrderByNo($out_trade_no);
                // $type = get_post_meta($id, 'type', true);
                // $amount = get_post_meta($id, 'amount', true);
                // $remark = get_post_meta($id, 'remark', true);
                // $type = get_post_meta($id, 'type', true);

                switch ($type) {
                    // donation: 生成打赏记录
                    case 'donation':

                        // FIXME: 支付成功要100%完成打款，否则要生成打款记录，用于用户手动打款
                        $donation = createDonation($order['from_user_id'], $order['to_user_id'], $order['amount'], $order['remark'], $order['id']);

                        // 加入打款队列
                        $queue = theme('queue');
                        $queue->add_async('transfer', [$donation]);

                        // // 获取关联uid
                        // $uid = get_post_field('post_author', $id);

                        // // 获取剩余额度
                        // $credit = (int) get_user_meta($uid, 'credit', true);

                        // // 充值额度
                        // $credit += (int) substr($productId, 2);

                        // // 更新额度
                        // $credit = update_user_meta($uid, 'credit', $credit);

                        break;

                    // 普通订单支付成功
                    default:
                        // 获取职位ID
                        $pid = get_post_meta($id, 'productId', true);

                        // 更新职位状态
                        update_post_status($pid, 'publish');

                        // 更新职位数据
                        update_post_meta($pid, 'job_highlight', 1);
                        // update_post_meta($pid, 'job_push_email', 1);

                        // 获取置顶列表
                        $stickies = get_option('sticky_posts');
                        // 置顶此职位
                        if (!is_array($stickies)) {
                            $stickies = array($pid);
                        }

                        if (!in_array($pid, $stickies)) {
                            $stickies[] = $pid;
                        }

                        update_option('sticky_posts', $stickies);

                        // 更新职位发布时间为当前时间（先存草稿后支付，时间需要修正）
                        $result = wp_update_post(
                            array(
                                'ID' => $pid,
                                'post_date' => current_time('mysql'),
                            )
                        );

                        // 更新错误
                        if (is_wp_error($result)) {
                            $errmsg = $result->get_error_message();

                            wpLog('statusHook错误：无法更新职位发布时间');

                            // print_r(json_encode(array('status'=>'fail', 'message'=>$errmsg)));
                            // exit();
                        }

                        // // 赠送额度：三送一
                        // $uid = get_post_field('post_author',$pid);
                        // if (shouldGiveCredit($uid)) giveCredit($uid);

                        break;
                }

                break;

            // 职位publish后触发：邮件通知author
            case 'post':
                $pid = $id;

                // 获取author id
                $uid = get_post_field('post_author', $pid);

                // 通知author
                sendPublishNotifyEmail($uid, $pid);

                break;

            default:

                break;
        }
    }
}
add_action('transition_post_status', 'Tonik\Theme\App\Setup\statusHook');
