<?php
/**
 * 为WP增加登录注册接口。
 * 注意！不要往这里面加代码！后期可能单独拿出来作为插件使用。
 */

use App\MailService;
use App\Sms\SmsService;
use App\Validators\Validator;
use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\resOK;

// WP REST API 命名空间
define('WP_V2_NAMESPACE', 'wp/v2'); // WP REST API

/**
 * 生成随机字符串（小写字母和数字）
 */
function generateRandomString($length = 5)
{
    $randomString = md5(uniqid(rand(), true));
    $randomString = substr($randomString, 0, $length);

    return $randomString;
}

/**
 * 通过user meta查找user
 *
 * @usage $user_data = getUserByMeta('phone',$phone);
 *
 * @return The user object on success, false on failure.
 */
if (!function_exists('getUserByMeta')) {
    function getUserByMeta($meta_key, $meta_value)
    {
        $args = array(
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
            'meta_compare' => '=',
        );

        $users = get_users($args);

        if (empty($users)) {
            return false;
        }

        return $users[0];
    }
}

/**
 * 检查验证码获取频率
 *
 * @return 无返回值
 */
function checkCodeLimit($uid)
{
    $code_saved = get_user_meta($uid, 'code', true);
    if (!empty($code_saved)) {
        $code_saved = explode('-', $code_saved);
        $expired = $code_saved[1] + 60; // 限制60s获取一次
        if ($expired > time()) {
            resError('验证码获取太频繁，请一分钟后再尝试');
            exit();
        }
    }
}

/**
 * 检查账号是否存在
 * @param string $account 邮箱、手机、用户名
 *
 * @return The user ID on success, false on failure.
 */
if (!function_exists('userExists')) {
    function userExists($account)
    {
        if (is_email($account)) {
            $user = get_user_by('email', $account);
            if ($user) {
                return $user->ID;
            }

            return false;
        }

        if (Validator::isPhone($account)) {
            $user = getUserByMeta('phone', $account);
            if ($user) {
                return $user->ID;
            }

            // 临时账号也应该检查，否则同一个手机号会产生很多临时账号
            $user = getUserByMeta('phone_temp', $account);
            if ($user) {
                return $user->ID;
            }

            return false;
        }

        return username_exists($account);
    }
}

/**
 * 通过邮件、手机号或者随机值创建新用户
 *
 * 如果用户（正式或者临时）存在，则返回uid。
 *
 * @param $role 创建的账号角色
 *
 * @return uid on success, exit() on failure
 */
function createUser($account = null, $password = null, $role = 'subscriber')
{
    // 用户已存在，则直接返回
    $user_id = userExists($account);
    if ($user_id) {
        return $user_id;
    }

    // 没有传入password时，自动生成一个
    if (!isset($password)) {
        $password = wp_generate_password(12, false);
    }

    // 密码格式验证
    Validator::validateLength($password, '密码', 1, 6, 20);

    // 随机生成5位用户名
    $user_login = generateRandomString();

    // prepare the default args
    $args = array();
    $args['user_login'] = $user_login;
    $args['user_pass'] = $password;
    $args['display_name'] = $user_login;
    $args['role'] = $role;

    // 是邮箱
    if (is_email($account)) {
        $args['display_name'] = explode("@", $account)[0]; // 截取邮箱@前作为昵称
        // $args['user_email'] = $account; // 邮箱尚未验证，不能转正
    }

    // 是手机号
    if (Validator::isPhone($account)) {
        $args['display_name'] = substr_replace($account, '****', 3, 4); // 隐藏手机号中间四位
    }

    // https://developer.wordpress.org/reference/functions/wp_insert_user/
    $uid = wp_insert_user($args);

    // 账号创建失败，输出错误信息
    if (is_wp_error($uid)) {
        $errmsg = $uid->get_error_message();
        resError($errmsg);
        exit();
    }

    // save as phone_temp, will transfer to phone after register
    if (Validator::isPhone($account)) {
        update_user_meta($uid, 'phone_temp', $account);
    }

    // if ( is_email($account) ) {
    //     // 截取邮箱@前作为昵称
    //     $display_name = explode("@",$account)[0];
    //     update_user_meta($uid, 'nickname', $display_name);
    // }

    // 账号创建成功，返回用户ID
    return $uid;
}

/**
 * 保存或者更新user_meta上的验证码
 *
 * @return  无
 */
function saveCodeToUserMeta($uid, $code)
{
    // 将时间戳和验证码一起保存，用于计算有效期和获取频率（以往经验，邮件发送的结果不可信，这里我们提前保存验证码用于频率限制）
    $new_code = $code . '-' . time();
    update_user_meta($uid, 'code', $new_code);
}

/**
 * 转正手机临时账号
 *
 * @return  uid on success, false on failure
 */
function updatePhoneFromPhoneTemp($uid)
{
    $phone_temp = get_user_meta($uid, 'phone_temp', true);
    if (isset($phone_temp)) {
        update_user_meta($uid, 'phone', $phone_temp);
        delete_user_meta($uid, 'phone_temp');
        return $uid;
    }
    return false;
}

/**
 * 更新密码
 *
 * @return true on success, exit() on failure
 */
function updatePassword($uid, $password)
{
    // 密码格式验证
    Validator::validateLength($password, '密码', true, 6, 20);

    // 更新用户密码
    $result = wp_update_user(
        array(
            'ID' => $uid,
            'user_pass' => $password,
        )
    );

    // 更新密码出错
    if (is_wp_error($result)) {
        $message = $result->get_error_message();
        resError($message);
        exit();
    }

    // TODO: 邮件通知？
    return true;
}

/**
 * 验证验证码
 *
 * @return true on success, exit() on failure
 */
function validateCode($uid, $code)
{
    // 验证码格式验证
    Validator::validateInt($code, '验证码', true, 1000, 9999);

    // 获取数据库中保存的验证码
    $code_saved = get_user_meta($uid, 'code', true);
    $code_saved = explode('-', $code_saved);
    $expired = $code_saved[1] + 1800; // 30分钟有效
    $code_saved = $code_saved[0];

    if ($expired < time()) {
        resError('验证码已失效，请重新获取');
        exit();
    }

    if ($code !== $code_saved) {
        resError('验证码不正确，请重新输入');
        exit();
    }

    // 验证成功，删除code字段
    delete_user_meta($uid, 'code');

    return true;
}

/**
 * 向/wp/v2/users/增加一些子接口
 */
add_action('rest_api_init', function () {
    /**
     * 发送短信或者邮件二维码，应用场景：手机号注册、绑定邮箱、绑定手机
     *
     * 当请求未携带jwt时，判定为注册请求，自动生成phone_temp新用户
     * 当请求携带jwt时，判定为绑定手机号，向jwt用户添加phone_temp字段
     *
     * @param   [string or number]  $account  账号（邮箱、手机或者用户名），必填
     *
     * https://github.com/qingwuit/qwshop/blob/98d00761dad7c1151c79175d6349b302cf4d63af/app/Qingwuit/Services/SmsService.php
     *
     * @return  发送的结果
     */
    register_rest_route(WP_V2_NAMESPACE, '/users/code', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();
            $account = sanitize_text_field($parameters['account']);

            // 通过请求头获取用户，否则创建新用户
            $token = getJwtTokenFromRequest($request);
            if ($token) {
                validateToken($token);
                $uid = getUserIdFromJwtToken($token);
            } else {
                // TODO: 不需要创建账号，使用PHP缓存即可：cache()->get($this->getLoginKey($mobile));
                $uid = createUser($account);
            }

            // 获取验证码
            if (is_email($account)) {
                // 发送邮箱验证码
                $code = MailService::sendCodeEmail($account, '重置密码');
            } else if (Validator::isPhone($account)) {
                // 执行发送手机验证码
                $code = SmsService::send($account);
            }

            if ($uid && $code) {
                saveCodeToUserMeta($uid, $code);
                return resOK('发送成功');
            }

            return resError('发送失败，请稍后重试');
        },
        'args' => array(
            'account' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return is_email($param) || Validator::isPhone($param);
                },
            ),
        ),
        'permission_callback' => function () {
            return true;
        },
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
            $account = sanitize_text_field($parameters['account']);
            $code = sanitize_text_field($parameters['code']);
            $password = sanitize_text_field($parameters['password']);

            // 后端数据格式校验
            Validator::required($account, '手机号');
            Validator::required($code, '验证码');
            Validator::required($password, '密码');
            Validator::validatePhone($account);

            // 正式账号是否存在？
            $user = getUserByMeta('phone', $account);
            if ($user) {
                resError('用户已经存在');
                exit();
            }

            $user = getUserByMeta('phone_temp', $account);
            if ($user) {
                $uid = $user->ID;

                validateCode($uid, $code);

                // 验证码验证通过，则转正临时账号
                updatePhoneFromPhoneTemp($uid);

                // 更新用户密码
                updatePassword($uid, $password);

                resOK('注册成功');
                exit();
            }

            resError('注册失败');
            exit();
        },
        'args' => array(
            'account' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return Validator::isPhone($param);
                },
            ),
            'code' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                },
            ),
            'password' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return Validator::validateLength($param, '密码', true, 6, 20);
                },
            ),
        ),
    ));

    // Register a new endpoint: /wp/v2/users/validation
    register_rest_route(WP_V2_NAMESPACE, '/users/validation', array(
        'methods' => 'GET',
        'callback' => function ($request) {
            $parameters = $request->get_json_params();
            $account = sanitize_text_field($parameters['account']);

            $user_id = userExists($account);
            if ($user_id) {
                resError('已被注册');
                exit();
            }

            resOK('可以注册');
            exit();
        },
        'args' => array(
            'account' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return Validator::isPhone($param);
                },
            ),
        ),
    ));
}, 15);
