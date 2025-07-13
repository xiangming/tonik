<?php

namespace Tonik\Theme\App\Setup;

require_once ABSPATH . 'wp-admin/includes/user.php';

use App\Validators\Validator;
use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\theme;

define('WP_V2_NAMESPACE', '/wp/v2'); // WP REST API 内置命名空间

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
        echo $amount;
    }

    if ($column_name == 'time') {
        // $time = get_date_from_gmt(get_post_time( 'Y-m-d H:i:s', $id ));
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

/**
 * 向/wp/v2/users/增加一些子接口
 */
add_action('rest_api_init', function () {
    /**
     * 发送短信或者邮件验证码，应用场景：手机号注册、绑定邮箱、绑定手机等
     *
     * 使用wordpress存储方案：https://developer.wordpress.org/apis/transients/
     *
     * 注意：该接口需要限制请求频率
     *
     * @return  发送是否成功
     */
    register_rest_route(WP_V2_NAMESPACE, '/users/code', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $account = $parameters['account'];

            // 频率检测，默认60秒
            Validator::validateCodeLimit($account, 60);

            // 发送验证码
            if (is_email($account)) {
                // 发送邮箱验证码
                $mailService = theme('mail');
                $rs = $mailService->send($account);
            } else if (Validator::isPhone($account)) {
                // 执行发送手机验证码
                $smsService = theme('sms');
                $rs = $smsService->send($account);
            }

            // 发送失败
            if (!$rs['status']) {
                resError($rs['msg']);
                exit();
            }

            // 保存验证码，用于下次验证
            theme('tool')->saveCacheCode($account, $rs['data']);

            resOK(true, '发送成功');
            exit();
        },
        'args' => array(
            'account' => theme('args')->account(true),
        ),
        'permission_callback' => '__return_true',
    ));

    // 注册前，判断用户是否已存在
    register_rest_route(WP_V2_NAMESPACE, '/users/register', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $account = $parameters['account'];
            $code = $parameters['code'];
            $password = $parameters['password'];

            // 校验验证码
            Validator::validateCacheCode($account, $code);

            // 验证码通过，则检查用户是否存在
            $uid = theme('user')->exists($account);
            if ($uid) {
                resError('用户已经存在，请勿重复注册');
                exit();
            }

            // 创建账号
            $rs = theme('user')->createUser($account, $password, 'author'); // author可以自己发布文章，contributor只能发草稿

            // 创建失败
            if (!$rs['status']) {
                resError($rs['msg']);
                exit();
            }

            resOK(true, '注册成功');
            exit();
        },
        'args' => array(
            'account' => theme('args')->account(true),
            'code' => theme('args')->code(true),
            'password' => theme('args')->password(true),
        ),
        'permission_callback' => '__return_true',
    ));

    // 忘记密码/重置密码
    register_rest_route(WP_V2_NAMESPACE, '/users/forgot', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $account = $parameters['account'];
            $code = $parameters['code'];
            $password = $parameters['password'];

            // 校验验证码
            Validator::validateCacheCode($account, $code);

            // 验证码通过，则检查用户是否存在
            $uid = theme('user')->exists($account);
            if (!$uid) {
                resError('用户不存在');
                exit();
            }

            // 修改密码
            theme('user')->updatePassword($uid, $password);

            resOK(true, '重置成功');
            exit();
        },
        'args' => array(
            'account' => theme('args')->account(true),
            'code' => theme('args')->code(true),
            'password' => theme('args')->password(true),
        ),
        'permission_callback' => '__return_true',
    ));

    // 修改密码（需要登录）
    register_rest_route(WP_V2_NAMESPACE, '/users/password', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $password = $parameters['password'];

            // 修改密码
            $current_user_id = get_current_user_id();
            theme('user')->updatePassword($current_user_id, $password);

            resOK(true, '修改成功');
            exit();
        },
        'args' => array(
            'password' => theme('args')->password(true),
        ),
        'permission_callback' => function ($request) {
            return is_user_logged_in();
        },
    ));

    // 绑定/换绑手机号（需要登录）
    // 流程：输入新号码、短信验证码验证、后台安全验证、重新登录、完成
    register_rest_route(WP_V2_NAMESPACE, '/users/bind/phone', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $phone = $parameters['phone'];
            $code = $parameters['code'];

            // 校验验证码
            Validator::validateCacheCode($phone, $code);

            // 验证码通过，则检查新的手机号有没有绑定过其他账号
            $user_exist = theme('user')->exists($phone);
            if ($user_exist) {
                resError('手机号已绑定其他账号');
                exit();
            }

            // 修改
            $current_user_id = get_current_user_id();
            theme('user')->updatePhone($current_user_id, $phone);

            resOK(true, '绑定成功');
            exit();
        },
        'args' => array(
            'phone' => theme('args')->phone(true),
            'code' => theme('args')->code(true),
        ),
        'permission_callback' => function ($request) {
            return is_user_logged_in();
        },
    ));

    // 绑定/换绑邮箱（需要登录）
    // 流程：输入新邮箱、邮箱验证码验证、后台安全验证、重新登录、完成
    register_rest_route(WP_V2_NAMESPACE, '/users/bind/email', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $email = $parameters['email'];
            $code = $parameters['code'];

            // 校验验证码
            Validator::validateCacheCode($email, $code);

            // 验证码通过，则检查新的邮箱有没有绑定过其他账号
            $user_exist = theme('user')->exists($email);
            if ($user_exist) {
                resError('邮箱已绑定其他账号');
                exit();
            }

            // 修改
            $current_user_id = get_current_user_id();
            theme('user')->updateEmail($current_user_id, $email);

            resOK(true, '绑定成功');
            exit();
        },
        'args' => array(
            'email' => theme('args')->email(true),
            'code' => theme('args')->code(true),
        ),
        'permission_callback' => function ($request) {
            return is_user_logged_in();
        },
    ));

    // 用户是否存在
    register_rest_route(WP_V2_NAMESPACE, '/users/exists', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $account = $parameters['account'];

            $user_id = theme('user')->exists($account);
            if ($user_id) {
                resOK(true, '用户已经存在');
                exit();
            }

            resOK(false, '可以注册');
            exit();
        },
        'args' => array(
            'account' => theme('args')->account(true),
        ),
        'permission_callback' => '__return_true',
    ));

    // 通过账户删除用户（需要登录且有权限）
    register_rest_route(WP_V2_NAMESPACE, '/users/delete', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $account = $parameters['account'];

            $user_id = theme('user')->exists($account);
            if ($user_id) {
                // 需要检查当前用户的操作权限
                if (current_user_can('delete_user', $user_id)) {
                    wp_delete_user($user_id);

                    theme('log')->debug('用户删除成功', $user_id, $account);
                    resOK(true, '用户删除成功');
                    exit();
                }

                resOK(false, '抱歉，您不能删除该用户');
                exit();
            }

            resOK(false, '用户不存在');
            exit();
        },
        'args' => array(
            'account' => theme('args')->account(true),
        ),
        'permission_callback' => function ($request) {
            return is_user_logged_in();
        },
    ));

    // 创建打赏订单并发起支付
    // 当携带了from，则订单和打赏记录的author设置为from对应用户；未携带，则会默认token对应用户
    register_rest_route(WP_V2_NAMESPACE, '/payment/donation', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $from = $parameters['from']; // 打赏人，必填
            $to = $parameters['to']; // 被打赏人，必填
            $amount = $parameters['amount']; // 打赏金额，必填
            $remark = $parameters['remark'] ? $parameters['remark'] : null; // 打赏留言，可选
            $method = $parameters['method']; // 支付通道，可选
            $device = $parameters['device']; // 支付设备类型，可选

            // 后端校验：使用WP内置方法即可（下面的args），不需要额外处理

            // 处理打赏人
            if ($from) {
                $from_user_id = theme('user')->exists($from);
                // 填写的打赏记录绑定账号不存在，则提示用户注册
                if (!$from_user_id) {
                    resError('打赏记录绑定账号不存在，请先注册');
                    exit();
                }
            }

            // 处理被打赏人
            $to_user_id = theme('user')->exists($to);
            // 不存在，退出
            if (!$to_user_id) {
                resError('被打赏人不存在');
                exit();
            }

            // 1. 创建order
            $name = '打赏-' . $to; // 支付通道显示的标题
            $orderService = theme('order');
            $order = $orderService->createOrder('donation', $amount, $name, $remark, $to_user_id, $method, $from_user_id);

            // 创建订单失败
            if (!$order['status']) {
                resError($order['msg']);
                exit();
            }

            // 2. 调取第三方支付
            $rs = theme('payment')->pay($method, $device, $order['data']);

            // 3. 输出结果
            $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
            exit();
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

    // 查询订单的支付结果（对象）
    register_rest_route(WP_V2_NAMESPACE, '/payment/find', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $method = $parameters['method']; // 支付通道
            $out_trade_no = $parameters['out_trade_no']; // 第三方单号

            $rs = theme('payment')->find($method, $out_trade_no);

            // 输出结果
            $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
            exit();
        },
        'args' => array(
            'method' => theme('args')->method(true),
            'out_trade_no' => theme('args')->out_trade_no(true),
        ),
        'permission_callback' => '__return_true',
    ));

    // // 查询订单的支付结果（布尔值）
    // register_rest_route(WP_V2_NAMESPACE, '/payment/query', array(
    //     'methods' => 'POST',
    //     'callback' => function ($request) {
    //         $parameters = $request->get_json_params();

    //         $method = $parameters['method']; // 支付通道
    //         $out_trade_no = $parameters['out_trade_no']; // 第三方单号

    //         $rs = theme('payment')->query($method, $out_trade_no);

    //         // 输出结果
    //         $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
    //         exit();
    //     },
    //     'args' => array(
    //         'method' => theme('args')->method(true),
    //         'out_trade_no' => theme('args')->out_trade_no(true),
    //     ),
    //     'permission_callback' => '__return_true',
    // ));

    // 请求后端检查支付结果并触发支付成功后的相关操作
    register_rest_route(WP_V2_NAMESPACE, '/payment/query', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $method = $parameters['method']; // 支付通道
            $out_trade_no = $parameters['out_trade_no']; // 第三方单号

            $rs = theme('payment')->query($method, $out_trade_no);

            if (!$rs['data']) {
                resOK(false, '订单未支付');
                exit();
            }

            // 支付成功，执行paySuccess操作（无论成功与否都往下继续执行）
            try {
                // 1. 通过out_trade_no拿到orderInfo
                $rs = theme('order')->getOrderByNo($out_trade_no);

                // 2. 触发支付成功后的操作paySuccess
                $paySuccessData = theme('payment')->paySuccess($method, $rs['data']);

                if (!$paySuccessData['status']) {
                    throw new \Exception($paySuccessData['msg']);
                }
            } catch (\Exception $e) {
                theme('log')->error($e->getMessage(), '/payment/check');
            }

            resOK(true, '订单支付成功');
            exit();
        },
        'args' => array(
            'method' => theme('args')->method(true),
            'out_trade_no' => theme('args')->out_trade_no(true),
        ),
        'permission_callback' => '__return_true',
    ));

    // 支付宝通知回调
    register_rest_route(WP_V2_NAMESPACE, '/payment/alipay/notify', array(
        'methods' => 'GET',
        'callback' => function ($request) {
            $rs = theme('payment')->notify('alipay');

            // 输出结果
            $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
            exit();
        },
        'permission_callback' => '__return_true',
    ));

    // 重新计算当前用户的数据统计结果（需登录态，仅可更新自己）
    register_rest_route(WP_V2_NAMESPACE, '/stat/refresh', array(
        'methods' => 'GET',
        'callback' => function ($request) {
            // 修改
            $current_user_id = get_current_user_id();
            $data = theme('stat')->refresh($current_user_id);

            // 输出结果
            resOK($data, '刷新成功');
            exit();
        },
        'permission_callback' => function ($request) {
            return is_user_logged_in();
        },
    ));

    // // Register a new endpoint: /wp/v2/user/<slug>
    // // https://stackoverflow.com/questions/56952400/wordpress-rest-api-receive-data-for-single-post-by-slug
    // register_rest_route(WP_V2_NAMESPACE, '/user/(?P<slug>[a-zA-Z0-9-]+)', array(
    //     'methods' => 'GET',
    //     'callback' => function ($request) {
    //         $slug = $request['slug'];
    //         $users = get_users([
    //             'slug' => $slug,
    //         ]);

    //         return $users[0];
    //     },
    //     'permission_callback' => '__return_true',
    // ));

    // // Register a new endpoint: /wp/v2/test/xxx
    // register_rest_route(WP_V2_NAMESPACE, '/test/(?P<id>\d+)', array(
    //     'methods' => 'GET',
    //     'callback' => 'my_awesome_func',
    //     'args' => array(
    //         'id' => array(
    //             'validate_callback' => function ($param, $request, $key) {
    //                 return is_numeric($param);
    //             },
    //         ),
    //     ),
    // ));

    // 测试接口
    register_rest_route(WP_V2_NAMESPACE, '/users/test_queue', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();
            $account = $parameters['account'];

            // $rs = theme('donation')->createDonation(3217, '1', '90', '感谢分享', 3219);

            // $queue = theme('queue');
            // $queue->add_async('test_queue', [$account]);
            // $queue->schedule_single(strtotime("+2 minutes"), 'test_queue', [$account]);

            // 输出结果
            $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
            exit();
        },
        'args' => array(
            'account' => theme('args')->account(true),
        ),
        'permission_callback' => '__return_true',
    ));
});

add_action('test_queue', function ($account) {
    // 发送邮箱验证码
    $mailService = theme('mail');
    $mailService->send($account);

    // // 执行发送手机验证码
    // $smsService = theme('sms');
    // $smsService->send($account);

    // // 转账测试
    // $paymentService = theme('payment');
    // $paymentService->transfer('alipay', '2023122712345', '0.1', '282818269@qq.com', '向明');
}, 10, 1);

/**
 * Core REST API
 *
 * 向 user 增加自定义字段
 *
 * 注意：get和set方法获取id的方式不同：$user['id'] 和 $user->ID
 *
 * 注意：Changing or removing data from core REST API endpoint responses can break plugins or WordPress core behavior, and should be avoided wherever possible.
 *
 * https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/
 *
 * https://developer.wordpress.org/reference/functions/sanitize_textarea_field/
 */
add_action('rest_api_init', function () {
    // 新增内容字段: permission, 谁可以看
    register_rest_field('post', 'permission', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return (int) get_post_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_post_meta($object->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'number',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    return absint($value);
                },
                'validate_callback' => function ($value) {
                    return is_numeric($value);
                },
            ),
        ),
    ));
    // 新增内容字段: lock, 当前用户是否可以看（需要登录态，仅读取，不更新）
    register_rest_field('post', 'lock', array(
        'get_callback' => function ($object, $field, $request) {
            // 当前登录用户是否能看
            $current_user_id = wp_get_current_user()->ID;
            $current_user_contribution = theme('stat')->getUserContribution($current_user_id, $object['author']);

            return $current_user_contribution < $object['permission'];
        },
    ));

    // 新增创作字段: creating, 正在创造什么？
    register_rest_field('user', 'creating', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            // Update the field/meta value.
            update_user_meta($object->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    return is_string($value);
                },
                // 'validate_callback' => function ($value) {
                //     // Valid if it contains exactly 10 English letters.
                //     return (bool) preg_match('/\A[a-z]{10}\Z/', $value);
                // },
            ),
        ),
    ));
    // 新增字段: avatar, 头像地址（不使用update_callback，单独上传后更新值）
    register_rest_field('user', 'avatar', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
    ));
    // 新增字段: background, 封面图地址（不使用update_callback，单独上传后更新值）
    register_rest_field('user', 'background', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
    ));
    // 新增字段: registered, 注册时间（不使用update_callback，注册时自动更新）
    register_rest_field('user', 'registered', array(
        'get_callback' => function ($object, $field, $request) {
            $user = get_user_by('id', $object['id']);
            if ($user) {
                // $registered_date = date('Y-m-d', strtotime($user->user_registered));
                return $user->user_registered;
            }
            return $user;
        },
    ));

    // 新增字段: realname, 真实姓名，仅自己可见，仅自己可更新（需要token）
    register_rest_field('user', 'realname', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            // Get field as single value from post meta, return false if current user not found
            return get_user_meta($current_user->ID, $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            $current_user = wp_get_current_user();

            // Update the field/meta value.
            update_user_meta($current_user->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    return is_string($value);
                },
            ),
        ),
    ));
    // 新增字段: gender, 性别，仅自己可见，仅自己可更新（需要token）
    register_rest_field('user', 'gender', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            // Get field as single value from post meta, return false if current user not found
            return get_user_meta($current_user->ID, $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            $current_user = wp_get_current_user();

            // Update the field/meta value.
            update_user_meta($current_user->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    return is_string($value);
                },
            ),
        ),
    ));
    // 新增字段: birthday 生日 仅自己可见，仅自己可更新（需要token）
    register_rest_field('user', 'birthday', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            // Get field as single value from post meta, return false if current user not found
            return get_user_meta($current_user->ID, $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            $current_user = wp_get_current_user();

            // Update the field/meta value.
            update_user_meta($current_user->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    return is_string($value);
                },
            ),
        ),
    ));
    // 新增字段: city 居住地，格式：['19', '19-2'] 仅自己可见，仅自己可更新（需要token）
    register_rest_field('user', 'city', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            // Get field as single value from post meta, return false if current user not found
            return get_user_meta($current_user->ID, $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            $current_user = wp_get_current_user();

            // Update the field/meta value.
            update_user_meta($current_user->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    return is_string($value);
                },
            ),
        ),
    ));
    // 新增字段: alipay, 支付宝账号，仅自己可见，仅自己可更新（需要token）
    register_rest_field('user', 'alipay', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            // Get field as single value from post meta, return false if current user not found
            return get_user_meta($current_user->ID, $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            $current_user = wp_get_current_user();

            // Update the field/meta value.
            update_user_meta($current_user->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'string',
            'arg_options' => theme('args')->phoneOrEmail(true),
        ),
    ));
    // 新增字段: wechat, 微信号，仅自己可见，仅自己可更新（需要token）
    register_rest_field('user', 'wechat', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            // Get field as single value from post meta, return false if current user not found
            return get_user_meta($current_user->ID, $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            $current_user = wp_get_current_user();

            // Update the field/meta value.
            update_user_meta($current_user->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    return is_string($value);
                },
            ),
        ),
    ));
    // 新增字段: QQ号，仅自己可见，仅自己可更新（需要token）
    register_rest_field('user', 'qq', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            // Get field as single value from post meta, return false if current user not found
            return get_user_meta($current_user->ID, $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            $current_user = wp_get_current_user();

            // Update the field/meta value.
            update_user_meta($current_user->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'string',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return sanitize_text_field($value);
                },
                'validate_callback' => function ($value) {
                    return is_string($value);
                },
            ),
        ),
    ));

    // 新增用户统计字段: income, 总收入（不使用update_callback，通过刷新接口更新）
    register_rest_field('user', 'income', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            $result = theme('stat')->getTotalIncome($current_user->ID);

            return $result;
        },
    ));
    // 新增用户统计字段: supporters, 总打赏人数（不使用update_callback，通过刷新接口更新）
    register_rest_field('user', 'supporters', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            $result = theme('stat')->getTotalSupporters($current_user->ID);

            return $result;
        },
    ));
    // 新增用户统计字段: views, 主页访问次数（不使用update_callback，通过刷新接口更新）
    register_rest_field('user', 'views', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            $result = theme('stat')->getUserViews($current_user->ID);

            return $result;
        },
    ));
    // 新增用户统计字段: posts, 已发布的动态数量（不使用update_callback，仅读取）
    register_rest_field('user', 'posts', array(
        'get_callback' => function ($object, $field, $request) {
            $result = count_user_posts($object['id'], 'post', true);

            return (int) $result;
        },
    ));
    // 新增用户统计字段: hasPayment, 收款信息是否完整（不使用update_callback，仅读取）
    register_rest_field('user', 'hasPayment', array(
        'get_callback' => function ($object, $field, $request) {
            $result = theme('user')->hasPayment($object['id']);

            return $result;
        },
    ));
    // 新增用户统计字段: hasSupporters, 是否有被打赏的记录（不使用update_callback，仅读取）
    register_rest_field('user', 'hasSupporters', array(
        'get_callback' => function ($object, $field, $request) {
            $result = theme('user')->hasSupporters($object['id']);

            return $result;
        },
    ));
    // 新增用户统计字段: hideGuide, 是否隐藏新手引导（需登录态，仅自己可见，仅自己可更新）
    register_rest_field('user', 'hideGuide', array(
        'get_callback' => function ($object, $field, $request) {
            $current_user = wp_get_current_user();

            // Get field as single value from post meta.
            return (bool) get_user_meta($current_user->ID, $field, true);
        },
        'update_callback' => function ($value, $object, $field) {
            $current_user = wp_get_current_user();

            // Update the field/meta value.
            update_user_meta($current_user->ID, $field, $value);
        },
        'schema' => array(
            'type' => 'number',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    // Make the value safe for storage.
                    return (int) $value;
                },
                'validate_callback' => function ($value) {
                    // Valid if it's [1,0]
                    return in_array($value, [1, 0]);
                },
            ),
        ),
    ));

    // 新增社交字段: following, 关注列表（需登录态，仅自己可见，仅自己可更新）
    register_rest_field('user', 'following', array(
        'get_callback' => function ($object, $field, $request, $object_type) {
            return theme('user')->getFollowing();
        },
        'update_callback' => function ($value, $object, $field, $request, $object_type) {
            $method = $request->get_method();

            theme('log')->debug('开始取消或增加关注', $method, $value);

            // 判定为取消关注
            if ($request->has_param('unFollow')) {
                theme('log')->debug('取消关注', $value);

                return theme('user')->unFollow($value);
            }

            // 判定为增加关注
            theme('log')->debug('增加关注', $value);

            return theme('user')->follow($value);
        },
        'schema' => array(
            'type' => 'number',
            'arg_options' => array(
                'sanitize_callback' => function ($value) {
                    return absint($value);
                },
                'validate_callback' => function ($value) {
                    return is_numeric($value);
                },
            ),
        ),
    ));
    // 新增社交字段: followed, 当前用户是否关注目标用户（不使用update_callback，仅读取）
    register_rest_field('user', 'followed', array(
        'get_callback' => function ($object, $field, $request) {
            return theme('user')->isFollowed($object['id']);
        },
    ));

    // 新增打赏字段: to, 被打赏人id（不使用update_callback，有单独接口处理更新逻辑）
    register_rest_field('donation', 'to', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return (int) get_post_meta($object['id'], $field, true);
        },
    ));
    // 新增打赏字段: amount, 金额（不使用update_callback，有单独接口处理更新逻辑）
    register_rest_field('donation', 'amount', array(
        'get_callback' => function ($object, $field, $request) {
            $rs = theme('donation')->getDonationById($object['id']);

            if (!$rs['data']) {
                return false;
            }

            return $rs['data']['amount'];
        },
    ));
    // 新增打赏字段: remark, 留言（不使用update_callback，有单独接口处理更新逻辑）
    register_rest_field('donation', 'remark', array(
        'get_callback' => function ($object, $field, $request) {
            $rs = theme('donation')->getDonationById($object['id']);

            if (!$rs['data']) {
                return false;
            }

            return $rs['data']['remark'];
        },
    ));

    // 新增订单字段: name, 服务名称（不使用update_callback，仅读取）
    register_rest_field('orders', 'name', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_post_meta($object['id'], $field, true);
        },
    ));
    // 新增订单字段: amount, 金额（不使用update_callback，仅读取）
    register_rest_field('orders', 'amount', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return (int) get_post_meta($object['id'], $field, true);
        },
    ));
    // 新增订单字段: method, 支付方式（不使用update_callback，仅读取）
    register_rest_field('orders', 'method', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_post_meta($object['id'], $field, true);
        },
    ));
});
