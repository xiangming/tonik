<?php

namespace Tonik\Theme\App\Setup;

use App\Validators\Validator;
use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\resOK;
use function Tonik\Theme\App\theme;

define('WP_V2_NAMESPACE', 'wp/v2'); // WP REST API 命名空间
define('FEE_RATE', 0.06); // 平台费率

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
        $origin = get_http_origin();

        if ($origin) {
            $my_sites = array('http://localhost:3000', 'http://localhost:3300', 'https://chuchuang.work');
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
    }, 11, 1);
}, 15);

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
 * 打款 action handler.
 *
 * @return integer
 */
function transfer($donation)
{
    theme('log')->log($donation, 'donation find start');

    $paymentService = theme('payment');

    // 向支付通道查询一次订单状态
    $out_trade_no = get_the_title($donation['orderId']);
    $paymentName = get_post_meta($donation['orderId'], 'method', true); // 如果是在wp-admin设置的，可能没有method
    $rs = $paymentService->check($paymentName, $out_trade_no);

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

    // 计算扣除平台手续费后的实际金额
    $donation['amount'] = theme('tool')->calculateAmount($donation['amount'], FEE_RATE);

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
//                         $rs = theme('donation')->create($order['from_user_id'], $order['to_user_id'], $order['amount'], $order['remark'], $order['id']);

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
     * 当请求未携带jwt时，判定为注册请求，自动生成phone_temp新用户
     * 当请求携带jwt时，判定为绑定手机号，向jwt用户添加phone_temp字段
     *
     * @param   [string or number]  $account  账号（邮箱、手机或者用户名），必填
     *
     * @return  发送的结果
     */
    register_rest_route(WP_V2_NAMESPACE, '/users/code', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $account = $parameters['account'];

            // 通过请求头获取用户，否则创建新用户
            $token = theme('tool')->getJwtTokenFromRequest($request);
            if ($token) {
                $uid = theme('tool')->getUserIdFromJwtToken($token);
            } else {
                // TODO: 不需要创建账号，使用PHP缓存即可：cache()->get($this->getLoginKey($mobile));
                $uid = theme('user')->create($account);
            }

            if (!$uid) {
                resError('获取用户信息失败');
                exit();
            }

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

            // 保存到用户表，用于下次验证
            theme('tool')->saveCode($uid, $rs['data']);

            resOK('发送成功');
            exit();
        },
        'args' => array(
            'account' => theme('args')->account(true),
        ),
        'permission_callback' => '__return_true',
    ));

    /**
     * Register a new endpoint: /wp/v2/users/register
     *
     * 注册流程：
     * 1. 输入手机并获取验证码（/users/code）
     * 2. 输入拿到的验证码和密码请求注册 => phone正式账号已存在，则提示：“用户已经存在”（不放在1里面，可以避免别人通过获取验证码来判断是否已注册）
     * 3. 验证码验证通过，则转正临时账号：将phone_temp保存到phone，并删除phone_temp
     * 4. 提示: “注册成功”
     * 5. 前端跳转到登录表单
     */
    register_rest_route(WP_V2_NAMESPACE, '/users/register', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $account = $parameters['account'];
            $code = $parameters['code'];
            $password = $parameters['password'];

            // 后端数据格式校验

            // 正式账号是否存在？
            $user = theme('user')->getUserByMeta('phone', $account);
            if ($user) {
                resError('用户已经存在');
                exit();
            }

            $user = theme('user')->getUserByMeta('phone_temp', $account);
            if ($user) {
                $uid = $user->ID;

                Validator::validateCode($uid, $code);

                // 验证码验证通过，则转正临时账号
                theme('tool')->updatePhoneFromPhoneTemp($uid);

                // 设置用户密码，失败也没关系，用户可以找回密码
                if ($password) {
                    theme('tool')->updatePassword($uid, $password);
                }

                resOK('注册成功');
                exit();
            }

            resError('注册失败，验证码错误');
            exit();
        },
        'args' => array(
            'account' => theme('args')->account(true),
            'code' => theme('args')->code(true),
            'password' => theme('args')->password(false),
        ),
        'permission_callback' => '__return_true',
    ));

    /**
     * 用户是否存在
     *
     * Register a new endpoint: /wp/v2/users/exists
     */
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

    // Register a new endpoint: /wp/v2/donation
    register_rest_route(WP_V2_NAMESPACE, '/payment/donation', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $from = $parameters['from']; // 打赏人，必填
            $to = $parameters['to']; // 被打赏人，必填
            $amount = $parameters['amount']; // 打赏金额，必填
            $remark = $parameters['remark'] ? $parameters['remark'] : null; // 打赏留言，可选
            $method = $parameters['method'] ? $parameters['method'] : 'alipay'; // 支付通道，可选
            $device = $parameters['device'] ? $parameters['device'] : 'scan'; // 支付设备类型，可选

            // TODO: 一般性校验，使用WP内置方法即可（参考下面的args），不需要额外处理

            // 处理打赏人，打赏记录关联此账号，而不是当前token账号（可能会帮别人打赏）
            $from_user_id = theme('user')->exists($from);
            // 填写的账号不存在，则自动创建账号并关联打赏记录
            if (!$from_user_id) {
                $from_user_id = theme('user')->create($from);
            }

            // 处理被打赏人
            $to_user_id = theme('user')->exists($to);
            // 不存在，退出
            if (!$to_user_id) {
                resError('被打赏人不存在', $to);
                exit();
            }

            // 1. 创建order
            $name = '打赏-' . $to; // 支付通道显示的标题
            $orderService = theme('order');
            $rs = $orderService->createOrder('donation', $amount, $name, $remark, $to_user_id, $method);

            // 创建订单失败
            if (!$rs['status']) {
                resError($rs['msg']);
                exit();
            }

            // 2. 调取第三方支付
            $paymentService = theme('payment');
            $rs = $paymentService->pay($method, $device, $rs['data']);

            // 3. 输出结果
            $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
            exit();
        },
        'args' => array(
            'from' => theme('args')->account(true),
            'to' => theme('args')->account(true),
            'amount' => theme('args')->amount(true),
            'remark' => theme('args')->remark(false),
            'method' => theme('args')->method(true),
            'device' => theme('args')->device(true),
        ),
        'permission_callback' => '__return_true',
    ));

    // Register a new endpoint: /wp/v2/payment/find
    register_rest_route(WP_V2_NAMESPACE, '/payment/find', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $method = $parameters['method'] ? $parameters['method'] : 'alipay'; // 支付通道
            $out_trade_no = $parameters['out_trade_no'] ? $parameters['out_trade_no'] : null; // 第三方单号

            $paymentService = theme('payment');
            $rs = $paymentService->find($method, $out_trade_no);

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

    // Register a new endpoint: /wp/v2/payment/check
    register_rest_route(WP_V2_NAMESPACE, '/payment/check', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();

            $method = $parameters['method'] ? $parameters['method'] : 'alipay'; // 支付通道
            $out_trade_no = $parameters['out_trade_no'] ? $parameters['out_trade_no'] : null; // 第三方单号

            $rs = theme('payment')->check($method, $out_trade_no);

            // 支付成功，执行paySuccess操作
            if ($rs['data']) {
                theme('payment')->handlePaySuccess($method, $out_trade_no);
            }

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

    // Register a new endpoint: /wp/v2/payment/alipay/notify
    register_rest_route(WP_V2_NAMESPACE, '/payment/alipay/notify', array(
        'methods' => 'GET',
        'callback' => function ($request) {
            // $parameters = $request->get_json_params();
            // $account = $parameters['account'];

            $paymentService = theme('payment');
            $rs = $paymentService->notify('alipay');

            // 输出结果
            $rs['status'] ? resOK($rs['data']) : resError($rs['msg']);
            exit();
        },
        'permission_callback' => '__return_true',
    ));

    // Register a new endpoint: /wp/v2/test/xxx
    register_rest_route(WP_V2_NAMESPACE, '/test/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'my_awesome_func',
        'args' => array(
            'id' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                },
            ),
        ),
    ));

    // Register a new endpoint: /wp/v2/users/test_queue
    register_rest_route(WP_V2_NAMESPACE, '/users/test_queue', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();
            $account = $parameters['account'];

            $queue = theme('queue');
            $queue->add_async('test_queue', [$account]);
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
    // 新增字段: creating, 正在创造什么？
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
                    // Valid if it is string
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
        // 'update_callback' => function ($value, $object, $field) {
        //     // Update the field/meta value.
        //     update_user_meta($object->ID, $field, $value);
        // },
        // 'schema' => array(
        //     'type' => 'string',
        //     'arg_options' => array(
        //         'sanitize_callback' => function ($value) {
        //             // Make the value safe for storage.
        //             // https://developer.wordpress.org/reference/functions/sanitize_url/
        //             return sanitize_url($value, array('http', 'https'));
        //         },
        //         'validate_callback' => function ($value) {
        //             // Valid if it is valid url
        //             return (bool) Validator::isURL($value);
        //         },
        //     ),
        // ),
    ));

    // 新增字段: background, 封面图地址（不使用update_callback，单独上传后更新值）
    register_rest_field('user', 'background', array(
        'get_callback' => function ($object, $field, $request) {
            // Get field as single value from post meta.
            return get_user_meta($object['id'], $field, true);
        },
        // 'update_callback' => function ($value, $object, $field) {
        //     // Update the field/meta value.
        //     update_user_meta($object->ID, $field, $value);
        // },
        // 'schema' => array(
        //     'type' => 'string',
        //     'arg_options' => array(
        //         'sanitize_callback' => function ($value) {
        //             // Make the value safe for storage.
        //             return sanitize_url($value, array('http', 'https'));
        //         },
        //         'validate_callback' => function ($value) {
        //             // Valid if it is valid url
        //             return (bool) Validator::isURL($value);
        //         },
        //     ),
        // ),
    ));
    
    // 新增字段: realname, 真实姓名
    // FIXME: 字段仅添加到users/me接口（仅自己可见）
    register_rest_field('user', 'realname', array(
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
                    // Valid if it is string
                    return is_string($value);
                },
            ),
        ),
    ));

    // 新增字段: alipay, 支付宝账号
    // FIXME: 字段仅添加到users/me接口（仅自己可见）
    register_rest_field('user', 'alipay', array(
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
                    // Valid if it is string
                    return is_string($value);
                },
            ),
        ),
    ));

    // 新增字段: wechat, 微信账号
    // FIXME: 字段仅添加到users/me接口（仅自己可见）
    register_rest_field('user', 'wechat', array(
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
                    // Valid if it is string
                    return is_string($value);
                },
            ),
        ),
    ));
});
