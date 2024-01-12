<?php

namespace App\Services;

use App\Validators\Validator;
use function Tonik\Theme\App\theme;

class UserService extends BaseService
{
    /**
     * 检查账号是否存在
     *
     * @param string $account 邮箱、手机、用户名
     *
     * @return The user ID on success, false on failure.
     */
    public function exists($account)
    {
        if (is_email($account)) {
            $user = get_user_by('email', $account);
            if ($user) {
                return $user->ID;
            }

            return false;
        }

        if (Validator::isPhone($account)) {
            $user = $this->getUserByMeta('phone', $account);
            if ($user) {
                return $user->ID;
            }

            // 临时账号也应该检查，否则同一个手机号会产生很多临时账号
            $user = $this->getUserByMeta('phone_temp', $account);
            if ($user) {
                return $user->ID;
            }

            return false;
        }

        return username_exists($account);
    }

    /**
     * 通过邮件、手机号或者随机值创建新用户
     *
     * 如果用户（正式或者临时）存在，则返回uid。
     *
     * @param string $account  邮件、手机号或者随机值
     * @param string $password  密码
     * @param string $role 账号角色
     *
     * @return int uid
     */
    public function create($account = null, $password = null, $role = 'subscriber')
    {
        // 用户已存在，则直接返回
        $user_id = $this->exists($account);
        if ($user_id) {
            return $this->format($user_id);
        }

        // 没有传入password时，自动生成一个
        if (!isset($password)) {
            $password = wp_generate_password(8, false);
        }

        // 密码格式验证
        Validator::validateLength($password, '密码', 6, 20);

        // 随机生成5位用户名
        $user_login = theme('tool')->generateRandomString(5);

        // 准备数据
        $args = array();
        $args['user_login'] = $user_login;
        $args['user_pass'] = $password;
        $args['display_name'] = $user_login;
        $args['role'] = $role;

        // // 如果是邮箱
        // if (is_email($account)) {
        //     $args['display_name'] = explode("@", $account)[0]; // 截取邮箱@前作为昵称
        //     // $args['user_email'] = $account; // 邮箱尚未验证，不能转正
        // }

        // // 如果是手机号
        // if (Validator::isPhone($account)) {
        //     $args['display_name'] = substr_replace($account, '****', 3, 4); // 隐藏手机号中间四位
        // }

        // https://developer.wordpress.org/reference/functions/wp_insert_user/
        $in_id = wp_insert_user($args);

        // 账号创建失败，输出错误信息
        if (is_wp_error($in_id)) {
            $errmsg = $in_id->get_error_message();

            theme('log')->error($errmsg, 'user create error');

            return $this->formatError($errmsg);
        }

        // save as phone_temp, will transfer to phone after phone validated
        if (Validator::isPhone($account)) {
            update_user_meta($in_id, 'phone_temp', $account);
        }

        theme('log')->debug($in_id, 'user create success');

        return $this->format($in_id);
    }

    /**
     * 通过user meta查找user
     *
     * @usage $user = theme('user')->getUserByMeta('phone',$phone);
     *
     * @return The user object on success, false on failure.
     */
    public function getUserByMeta($meta_key, $meta_value)
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
