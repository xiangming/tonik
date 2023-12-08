<?php

namespace App\Validators;

use function Tonik\Theme\App\resError;

/**
 * Class Validator.
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
     * 验证email格式
     *
     * @return true on success, exit() on failure
     */
    public static function validateEmail($value, $message = '邮箱')
    {
        $value = strtolower($value);
        if (!is_email($value)) {
            resError($message . '格式不正确');
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
}
