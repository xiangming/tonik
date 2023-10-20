<?php

namespace App\Validators;

/**
 * Class Validator.
 */
class Validator
{
    /**
     * 是否MD5值（验证token格式）
     */
    public static function isMD5($str)
    {
        return preg_match("/^([a-fA-F0-9]{32})$/", $str);
    }

    /**
     * 验证URL
     * @param $value    要验证的值
     * @param $message  错误信息
     */
    public static function isURL($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * 验证URL
     * @param $value    要验证的值
     * @param $message  错误信息
     */
    public static function isInt($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * 验证手机号格式
     * @param $value    要验证的值
     * @param $message  错误信息
     */
    public static function isPhone($value)
    {
        return filter_var($value, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^1[3,4,5,6,7,8,9]\d{9}$/")));
    }

    /**
     * 验证required
     * @param $value    要验证的值
     * @param $message  错误信息
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
     * @param $value 要验证的值
     * @param $message  错误信息
     * @param $required   是否必填项，默认值否
     */
    public static function validateInt($value, $message, $required = false, $min = 0, $max = PHP_INT_MAX)
    {
        if (empty($value)) {
            // 不允许为空
            if ($required === true) {
                resError($message . '不能为空');
                exit();
            }
        } else if (!filter_var($value, FILTER_VALIDATE_INT, array("options" => array("min_range" => $min, "max_range" => $max)))) {
            resError($message . '格式不正确');
            exit();
        }
        return true;
    }

    /**
     * 验证int范围
     * @param $value 要验证的值
     * @param $message  错误信息
     * @param $required   必须项，默认值否
     */
    public static function validateIntOptions($value, $message, $required = false, $min = 0, $max = PHP_INT_MAX)
    {
        if (empty($value)) {
            // 不允许为空
            if ($required === true) {
                resError('请选择' . $message);
                exit();
            }
        } else if (!filter_var($value, FILTER_VALIDATE_INT, array("options" => array("min_range" => $min, "max_range" => $max)))) {
            resError('请选择正确的' . $message);
            exit();
        }
        return true;
    }

    /**
     * in_array验证
     * @param $value    要验证的值
     * @param $array    指定数组
     * @param $message  错误信息
     * @param $required 是否必填项，默认值否
     * @用法 validateArray($purpose, ['1','2'], 'purpose', 1);
     */
    public static function validateArray($value, $array, $message, $required = false)
    {
        if (empty($value)) {
            // 不允许为空
            if ($required === true) {
                resError('请选择' . $message);
                exit();
            }
        } else if (!in_array($value, $array)) {
            resError('请选择正确的' . $message);
            exit();
        }
        return true;
    }

    /**
     * 验证长度
     * @param $value 要验证的值
     * @param $message  错误信息
     * @param $required 是否必填项，默认值否
     * @param $min   最少位数，默认值2
     * @param $max   最多位数，默认值50，每个中文字符占3位
     * @用法 validateLength($password,'密码',1,6,20);
     */
    public static function validateLength($value, $message, $required = false, $min = 2, $max = 50)
    {
        if (empty($value)) {
            // 不允许为空
            if ($required === true) {
                resError($message . '不能为空');
                exit();
            }
        } else if (mb_strlen($value) < $min || mb_strlen($value) > $max) {
            resError($message . '应为' . $min . '-' . $max . '位字符组成，请正确输入');
            exit();
        }
        return true;
    }

    /**
     * 验证email
     * @param $value    要验证的值
     * @param $message  错误信息
     * @param $required 是否必须项，默认值否
     * @用法 validateEmail($email,'邮箱',1);
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
     * @param $value    要验证的值
     * @param $message  错误信息
     * @param $required 是否必须项，默认值否
     * @用法 validatePhone($phone,'手机号码',1);
     */
    public static function validatePhone($value, $message = '手机号')
    {
        if (!isPhone($value)) {
            resError($message . '格式不正确');
            exit();
        }
        return true;
    }
}
