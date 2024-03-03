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
        theme('log')->debug('UserService exists start');

        // 使用邮箱检索
        if (is_email($account)) {
            $user = get_user_by('email', $account);
            if ($user) {
                return $user->ID;
            }

            return false;
        }

        // // 使用手机号检索
        // if (Validator::isPhone($account)) {
        //     $user = $this->getUserByMeta('phone', $account);
        //     if ($user) {
        //         return $user->ID;
        //     }

        //     return false;
        // }

        // 使用用户ID检索并排除手机号
        if (is_numeric($account) && !Validator::isPhone($account)) {
            $user = get_user_by('id', $account);
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
     * 注意！在执行此方法前，请确保account验证码已经校验通过
     *
     * 如果用户（正式或者临时）存在，则返回uid。
     *
     * @param string $account  邮件、手机号或者随机值
     * @param string $password  密码
     * @param string $role 账号角色
     *
     * @return 标准响应对象
     */
    public function createUser($account = null, $password = null, $role = 'subscriber')
    {
        theme('log')->debug('createUser start');

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

        // 随机生成uuid作为username
        $user_login = theme('tool')->generateRandomString();

        // 随机生成uuid作为user_slug（因为user_slug会对外暴露，不要和user_login相同）
        $user_slug = theme('tool')->generateRandomString();

        // 准备数据
        $args = array();
        $args['user_login'] = $user_login; // 需要隐藏
        $args['user_pass'] = $password;
        $args['user_nicename'] = $user_slug; // 别名
        $args['nickname'] = $user_slug; // 后台昵称
        $args['display_name'] = $user_slug; // 前台昵称
        $args['role'] = $role;

        // // 如果是邮箱
        // if (is_email($account)) {
        //     // $args['display_name'] = explode("@", $account)[0]; // 截取邮箱@前作为昵称
        //     // $args['user_email'] = $account; // 邮箱尚未验证，不能转正
        // }

        // 如果是手机号，则作为用户名使用（但是不要公开显示在nickname等位置）
        if (Validator::isPhone($account)) {
            $args['user_login'] = $account;
        }

        // https://developer.wordpress.org/reference/functions/wp_insert_user/
        $in_id = wp_insert_user($args);

        // 账号创建失败，输出错误信息
        if (is_wp_error($in_id)) {
            $errmsg = $in_id->get_error_message();

            theme('log')->error('createUser failed', $errmsg);

            return $this->formatError($errmsg);
        }

        // // save phone, it's already verified
        // if (Validator::isPhone($account)) {
        //     update_user_meta($in_id, 'phone', $account);
        // }

        // update email, it's already verified
        if (is_email($account)) {
            theme('tool')->updateEmail($in_id, $account);
        }

        theme('log')->log('createUser success', $in_id, $account, $password);

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
