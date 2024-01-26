<?php

namespace App\Services;

class ToolService extends BaseService
{
    /**
     * 生成随机字符串（小写字母和数字）
     * 5位重复的概率是六千万分之一，已经够用了
     * 4位重复的概率是一百六十万分之一，其实也够用了
     */
    public function generateRandomString($length = 5)
    {
        $randomString = md5(uniqid(rand(), true));
        $randomString = substr($randomString, 0, $length);

        return $randomString;
    }

    /**
     * 从 api 请求里面获取 jwt-token
     *
     * @param   [type]  $request  api请求参数集合
     *
     * @return  token on success, false on failure.
     */
    public function getJwtTokenFromRequest($request)
    {
        $headers = $request->get_headers();
        $token = $headers['authorization'] ? substr($headers['authorization'][0], 7) : false;
        return $token;
    }

    /**
     * 从JWT Token里面解析出user id
     * https://developer.wordpress.org/reference/functions/get_user_id_from_string/
     *
     * @return  ID on success, false on failure.
     */
    public function getUserIdFromJwtToken($token)
    {
        $array = explode(".", $token);
        $user = json_decode(base64_decode($array[1]))->data->user;
        if ($user) {
            return $user->id;
        }

        return false;
    }

    /**
     * 保存或者更新user_meta上的验证码
     *
     * @return  无
     */
    public function saveCode($uid, $code)
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
    public function updatePhoneFromPhoneTemp($uid)
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
     * @return uid or a WP_Error object if the user could not be updated.
     */
    public function updatePassword($uid, $password)
    {
        // 更新用户密码
        $result = wp_update_user(
            array(
                'ID' => $uid,
                'user_pass' => $password,
            )
        );

        // // 更新密码出错
        // if (is_wp_error($result)) {
        //     $message = $result->get_error_message();
        //     resError($message);
        //     exit();
        // }

        return $result;
    }

    /**
     * 更新 post_status
     * 用法 updatePostStatus($pid, 'publish');
     */
    public function updatePostStatus($pid, $status)
    {
        // https://developer.wordpress.org/reference/functions/wp_update_post/
        // The date does not have to be set for drafts. You can set the date and it will not be overridden.
        $result = wp_update_post(array(
            'ID' => $pid,
            // 'post_type'   => 'orders',
            'post_status' => $status,
        ), true);

        return $result;
    }

    /**
     * 计算扣除平台费用后的待结算金额
     */
    public function settledAmount($originalAmount, $feeRate)
    {
        // 计算手续费
        $fee = $originalAmount * $feeRate;

        // 扣除手续费
        $actualAmount = $originalAmount - $fee;

        return $actualAmount;
    }


    /**
     * 检查验证码获取频率
     */
    public static function checkCodeLimit($uid, $limited = 60)
    {
        $code_saved = get_user_meta($uid, 'code', true);

        if (!empty($code_saved)) {
            $code_saved = explode('-', $code_saved);
            $expired = $code_saved[1] + $limited; // 限制60s获取一次
            if ($expired > time()) {
                resError('验证码获取太频繁，请一分钟后再尝试');
                exit();
            }
        }

        return true;
    }
}
