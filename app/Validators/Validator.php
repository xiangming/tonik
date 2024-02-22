<?php

namespace App\Validators;

use function Tonik\Theme\App\resError;
use function Tonik\Theme\App\theme;

/**
 * Class Validator.
 * 
 * 约定：validate开头的函数，return true on success, exit() on failure
 */
class Validator
{
    /**
     * 是否MD5值
     *
     * @return true on success, false on failure
     */
    public static function isMD5($str)
    {
        return preg_match("/^([a-fA-F0-9]{32})$/", $str);
    }

    /**
     * 验证URL
     *
     * @return true on success, false on failure
     */
    public static function isURL($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * 验证整数
     *
     * @return true on success, false on failure
     */
    public static function isInt($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * 验证手机号格式
     *
     * @return true on success, false on failure
     */
    public static function isPhone($value)
    {
        return filter_var($value, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^1[3,4,5,6,7,8,9]\d{9}$/")));
    }

    /**
     * 验证required
     *
     * @return true on success, exit() on failure
     */
    public static function required($value, $message)
    {
        if (empty($value)) {
            resError($message . '不能为空');
            exit();
        }

        return true;
    }

    /**
     * 验证int
     *
     * @return true on success, exit() on failure
     */
    public static function validateInt($value, $message, $min = 0, $max = PHP_INT_MAX)
    {
        if (!filter_var($value, FILTER_VALIDATE_INT, array("options" => array("min_range" => $min, "max_range" => $max)))) {
            resError($message . '格式不正确');
            exit();
        }

        return true;
    }

    /**
     * 验证int范围
     *
     * @return true on success, exit() on failure
     */
    public static function validateIntOptions($value, $message, $min = 0, $max = PHP_INT_MAX)
    {
        if (!filter_var($value, FILTER_VALIDATE_INT, array("options" => array("min_range" => $min, "max_range" => $max)))) {
            resError('请选择正确的' . $message);
            exit();
        }
        return true;
    }

    /**
     * in_array验证
     * @用法 validateArray($purpose, ['1','2'], 'purpose', 1);
     *
     * @return true on success, exit() on failure
     */
    public static function validateArray($value, $array, $message)
    {
        if (!in_array($value, $array)) {
            resError('请选择正确的' . $message);
            exit();
        }

        return true;
    }

    /**
     * 验证长度
     * @param $value 要验证的值
     * @param $min   最少位数，默认值2
     * @param $max   最多位数，默认值50，每个中文字符占3位
     * @用法 validateLength($password,'密码',1,6,20);
     *
     * @return true on success, exit() on failure
     */
    public static function validateLength($value, $message, $min = 2, $max = 50)
    {
        if (mb_strlen($value) < $min || mb_strlen($value) > $max) {
            resError($message . '应为' . $min . '-' . $max . '位字符组成，请正确输入');
            exit();
        }

        return true;
    }

    /**
     * 验证phone
     *
     * @return true on success, exit() on failure
     */
    public static function validatePhone($value, $message = '手机号')
    {
        if (!static::isPhone($value)) {
            resError($message . '格式不正确');
            exit();
        }

        return true;
    }

    /**
     * 验证url
     * @用法 validateURL($company_logo,'公司LOGO地址');
     *
     * @return true on success, exit() on failure
     */
    public static function validateURL($value, $message)
    {
        if (!isURL($value)) {
            resError($message . '格式不正确');
            exit();
        }

        return true;
    }

    /**
     * 验证日期
     *
     * @return true on success, exit() on failure
     */
    public static function validateDate($value, $message)
    {
        if (!strtotime($value)) {
            resError('请选择正确的' . $message);
            exit();
        }

        return true;
    }

    // /**
    //  * 验证验证码
    //  *
    //  * @return true on success, exit() on failure
    //  */
    // public static function validateCode($uid, $code)
    // {
    //     // theme('log')->debug('validateCode uid', $uid);
    //     // theme('log')->debug('validateCode code', $code);

    //     // 验证码格式验证
    //     Validator::validateInt($code, '验证码', 1000, 9999);

    //     // 获取数据库中保存的验证码
    //     $code_saved = get_user_meta($uid, 'code', true);
    //     $code_saved = explode('-', $code_saved);
    //     $expired = $code_saved[1] + 1800; // 30分钟有效
    //     $code_saved = $code_saved[0];

    //     if ($expired < time()) {
    //         resError('验证码已失效，请重新获取');
    //         exit();
    //     }

    //     if ($code !== $code_saved) {
    //         resError('验证码不正确，请重新输入');
    //         exit();
    //     }

    //     // 验证成功，删除code字段
    //     delete_user_meta($uid, 'code');

    //     return true;
    // }

    /**
     * 校验cache里面的验证码
     *
     * @return true on success, exit() on failure
     */
    public static function validateCacheCode($account, $code)
    {
        theme('log')->debug('validateCacheCode account', $account);
        theme('log')->debug('validateCacheCode code', $code);

        // 验证码格式验证
        Validator::validateInt($code, '验证码', 1000, 9999);

        // 获取数据库中保存的验证码
        $code_saved = get_transient($account . '_code');
        theme('log')->debug('validateCacheCode code_saved', $code_saved);
        $code_saved = explode('-', $code_saved);
        $expired = $code_saved[1] + HOUR_IN_SECONDS;
        $code_saved = $code_saved[0];

        if ($expired < time()) {
            delete_transient($account . '_code');

            resError('验证码已失效，请重新获取');
            exit();
        }

        if ($code !== $code_saved) {
            // 这里不能删除瞬态，用户还有机会重新输入
            resError('验证码不正确，请重新输入');
            exit();
        }

        // 验证成功，删除cache上的code
        delete_transient($account . '_code');

        return true;
    }

    /**
     * 检查验证码获取频率
     *
     * @return true on success, exit() on failure
     */
    public static function validateCodeLimit($account, $limited = 60)
    {
        $code_saved = get_transient($account . '_code');

        if ($code_saved) {
            $code_saved = explode('-', $code_saved);
            $expired = $code_saved[1] + $limited; // 限制60s获取一次
            if ($expired > time()) {
                resError('验证码获取太频繁，请稍后重试');
                exit();
            }
        }

        return true;
    }
}
