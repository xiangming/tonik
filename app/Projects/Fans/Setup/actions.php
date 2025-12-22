<?php

namespace Tonik\Theme\App\Setup;

use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\theme;

define('WP_V2_NAMESPACE', '/wp/v2'); // WP REST API 内置命名空间

/*
|-----------------------------------------------------------
| Theme Actions
|-----------------------------------------------------------
|
| Fans 项目的 Actions 和 Hooks
|
| 注意：REST API 端点已拆分到 app/Projects/Fans/Api/ 目录
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
 * 添加 CORS 跨域 header
 *
 * https://stackoverflow.com/questions/63282687/wordpress-rest-pre-serve-request-produces-php-header-warnings
 */
add_action('rest_api_init', function () {
    /* unhook default function */
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

    /* then add your own filter */
    add_filter('rest_pre_serve_request', function ($value) {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

        if ($origin) {
            $my_sites = array('http://localhost:1300', 'http://localhost:1400', 'https://dashang.me', 'https://zayue.com');
            if (in_array($origin, $my_sites)) {
                $origin = esc_url_raw($origin);
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Headers: X-Requested-With, content-type, Authorization');
                header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
                header('Access-Control-Allow-Credentials: true');
                header('Vary: Origin', false);
            }
        } elseif (!headers_sent() && 'GET' === $_SERVER['REQUEST_METHOD'] && !is_user_logged_in()) {
            header('Vary: Origin', false);
        }

        return $value;
    }, 10, 1);
}, 10);

/**
 * 定制管理后台文章列表列内容
 *
 * https://developer.wordpress.org/reference/hooks/manage_post-post_type_posts_custom_column/
 */
add_action('manage_donation_posts_custom_column', function ($column_name, $id) {
    if ($column_name == 'productName') {
        $productName = get_post_meta($id, "productName", true);
        echo $productName;
    }

    if ($column_name == 'to') {
        $to = get_post_meta($id, "to", true);
        echo $to;
    }

    if ($column_name == 'status') {
        if (get_post_status($id) == 'publish') {
            echo '<span style="color:#179B16;font-weight:bold;">已打款</span>';
        } else if (get_post_status($id) == 'draft') {
            echo '未支付';
        } else if (get_post_status($id) == 'pending') {
            echo '<span style="color:#fc703e;font-weight:bold;">待打款</span>';
        } else if (get_post_status($id) == 'private') {
            echo '<span style="font-weight:bold;">未收到款项，已关闭</span>';
        }
    }

    if ($column_name == 'amount') {
        $amount = get_post_meta($id, "amount", true);
        if ($amount) {
            echo '¥' . number_format($amount, 2);
        } else {
            echo '—';
        }
    }

    if ($column_name == 'time') {
        $time = get_post_time('Y-m-d H:i:s', false, $id, true);
        echo $time;
    }
}, 10, 2);

/**
 * 打款 action handler，供队列调用
 *
 * @return integer
 */
function transfer($donation)
{
    $paymentService = theme('payment');

    // 向支付通道查询一次订单状态
    $out_trade_no = get_the_title($donation['orderId']);
    $paymentName = get_post_meta($donation['orderId'], 'method', true); // 如果是在wp-admin设置的，可能没有method
    $rs = $paymentService->query($paymentName, $out_trade_no);

    // TODO: 支持余额支付

    // 查询失败，提前退出
    if (!$rs['status']) {
        theme('log')->log($rs['msg'], 'transfer check error');
        return;
    }

    theme('log')->log($rs['data'], 'transfer check success');

    // 支付未成功，则提前退出
    if (!$rs['data']) {
        theme('log')->debug($rs, '相关订单支付未成功，提前退出');
        return;
    }

    // 支付成功

    theme('log')->log($donation, 'donation transfer start');

    // 计算扣除平台手续费后的待结算金额
    $donation['amount'] = theme('tool')->settledAmount($donation['amount'], $_ENV['FEE_RATE']);

    // 执行打款（当前只支持alipay）
    $rs = $paymentService->transfer('alipay', $donation['out_trade_no'], $donation['amount'], $donation['identity'], $donation['name']);
    // $rs = $paymentService->transfer('alipay', $donation['out_trade_no'], '0.1', 'arvinxiang@qq.com', '向明'); // 测试使用

    // 打款失败
    if (!$rs['status']) {
        theme('log')->log($rs['msg'], 'donation transfer error');
        theme('tool')->updatePostStatus($donation['id'], 'pending'); // 支付已成功，设置为pending，用户可手动提现
        return;
    }

    // 打款成功
    theme('tool')->updatePostStatus($donation['id'], 'publish');

    theme('log')->log($rs['msg'], 'donation transfer success');
}
add_action('transfer', 'Tonik\Theme\App\Setup\transfer');

// /**
//  * Fire a callback only when post or custom-post-type transitioned to 'publish'.
//  *
//  * 'transition_post_status' is a generic action that is called every time a post changes status.
//  *
//  * 'transition_post_status' is executed before 'save_post', so you can not get_post_meta when create new post.
//  *
//  * https://developer.wordpress.org/reference/hooks/transition_post_status/
//  *
//  * @param string  $new_status New post status.
//  * @param string  $old_status Old post status.
//  * @param WP_Post $post       Post object.
//  */
// add_action('transition_post_status', function ($new_status, $old_status, $post) {
//     // 从非publish变更为publish时执行
//     if ('publish' == $new_status && 'publish' != $old_status && isset($post->post_type)) {
//         // $id = $post->ID;

//         theme('log')->log($post, 'order');

//         switch ($post->post_type) {

//             case 'orders':
//                 $out_trade_no = $post->post_title;
//                 $rs = theme('order')->getOrderByNo($out_trade_no);

//                 if (!$rs['status']) {
//                     return $this->formatError($rs['msg']);
//                 }

//                 $order = $rs['data'];

//                 theme('log')->log($order, 'order');

//                 switch ($order['type']) {

//                     case 'donation':

//                         theme('log')->log('donation start');

//                         // 支付成功后要生成打赏记录
//                         $rs = theme('donation')->createDonation($order['from_user_id'], $order['to_user_id'], $order['amount'], $order['remark'], $order['id']);

//                         if (!$rs['status']) {
//                             theme('log')->log($rs, 'donation create end');
//                             return;
//                         }

//                         theme('log')->log('donation end');

//                         // 打赏记录创建后，加入打款队列
//                         try {
//                             theme('queue')->add_async('transfer', [$rs['data']]);
//                         } catch (\Throwable $th) {
//                             theme('log')->error($th, 'donation transfer error catch');
//                             return;
//                         }

//                         break;

//                     default:
//                         // TODO: Implement

//                         break;
//                 }

//                 break;

//             // 职位publish后触发：邮件通知author
//             case 'post':
//                 $pid = $id;

//                 // 获取author id
//                 $uid = get_post_field('post_author', $pid);

//                 // 通知author
//                 sendPublishNotifyEmail($uid, $pid);

//                 break;

//             default:

//                 break;
//         }
//     }
// }, 10, 3);

