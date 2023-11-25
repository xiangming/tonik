<?php

namespace App\Services;

class MailService
{
    public static function getMailFrom()
    {
        return 'no-reply@' . $_SERVER['HTTP_HOST'];
    }

    public static function getMailBCC()
    {
        return get_option('admin_email');
    }

    public static function getMailHeader($html = false, $reply = null, $bcc = null, $from = null)
    {
        $headers = array();
        // html格式 or 文本格式（默认）
        if ($html) {
            array_push($headers, 'Content-Type: text/html; charset=UTF-8');
        }

        // 回复人
        if (isset($reply) && is_email($reply)) {
            array_push($headers, 'Reply-To: ' . $reply);
        }

        // 暗抄送
        if (isset($bcc) && is_email($bcc)) {
            array_push($headers, 'BCC: ' . $bcc);
        }

        // 未设置发件人或不是正确的邮箱地址
        if (!isset($from) || !is_email($from)) {
            $from = static::getMailFrom();
        }
        array_push($headers, 'From: ' . $from);

        return $headers;
    }

    public static function getMailFooter()
    {
        $msg = '此邮件由系统生成，如果不是本人操作，请忽略。<br><br>' . "\r\n\r\n";
        $msg .= get_bloginfo('name');
        return $msg;
    }

    /**
     * 发送验证码邮件
     *
     * @return code on success, false on failure
     */
    public static function sendCodeEmail($email, $topic = '进行操作')
    {
        // 生成一个验证码
        $code = rand(1000, 9999);

        $subject = '您正在' . $topic . '，验证码：' . $code;

        $headers = static::getMailHeader(true);

        $msg = '您好，<br><br>' . "\r\n\r\n";
        $msg .= '您正在' . $topic . '，验证码：' . $code . '，30分钟内有效。<br><br>' . "\r\n\r\n";
        $msg .= static::getMailFooter();

        // 发送邮件
        if (wp_mail($email, $subject, $msg, $headers)) {
            return $code;
        } else {
            return false;
        }
    }
}
