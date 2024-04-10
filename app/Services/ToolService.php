<?php

namespace App\Services;

use function Tonik\Theme\App\theme;

class ToolService extends BaseService
{
    /**
     * 生成随机字符串（小写字母和数字）
     *
     * 5位重复的概率是六千万分之一，用于post slug，后期不够用可以增加一位
     * 4位重复的概率是一百六十万分之一，用于user slug，后期不够用可以增加一位
     * 3位的总数是46,656，用于保留单位
     */
    public function generateRandomString($length = 5)
    {
        $randomString = md5(uniqid(rand(), true));
        $randomString = substr($randomString, 0, $length);

        return $randomString;
    }

    public function generateTradeNo()
    {
        $out_trade_no = date('YmdHis') . '00' . mt_rand(10000, 99999);

        return $out_trade_no;
    }

    // /**
    //  * 从 api 请求里面获取 jwt-token
    //  *
    //  * @param   [type]  $request  api请求参数集合
    //  *
    //  * @return  token on success, false on failure.
    //  */
    // public function getJwtTokenFromRequest($request)
    // {
    //     $headers = $request->get_headers();
    //     $token = $headers['authorization'] ? substr($headers['authorization'][0], 7) : false;
    //     return $token;
    // }

    // /**
    //  * 从JWT Token里面解析出user id
    //  * https://developer.wordpress.org/reference/functions/get_user_id_from_string/
    //  *
    //  * @return  ID on success, false on failure.
    //  */
    // public function getUserIdFromJwtToken($token)
    // {
    //     $array = explode(".", $token);
    //     $user = json_decode(base64_decode($array[1]))->data->user;
    //     if ($user) {
    //         return $user->id;
    //     }

    //     return false;
    // }

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
        // theme('log')->debug('saveCode uid', $uid);
        // theme('log')->debug('saveCode code', $code);
        // theme('log')->debug('saveCode new_code', $new_code);
    }

    /**
     * 保存或者更新cache里面的验证码，有效期1小时
     *
     * @return  无
     */
    public function saveCacheCode($account, $code)
    {
        // 将时间戳和验证码一起保存，用于计算有效期和获取频率（以往经验，邮件发送的结果不可信，这里我们提前保存验证码用于频率限制）
        $new_code = $code . '-' . time();
        set_transient($account . '_code', $new_code, HOUR_IN_SECONDS);
        theme('log')->debug('saveCacheCode account', $account);
        theme('log')->debug('saveCacheCode code', $code);
        theme('log')->debug('saveCacheCode new_code', $new_code);
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
}
