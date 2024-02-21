<?php

namespace App\Services;

/**
 * 向 /wp-content/debug.log 写入log
 * 需要在wp-config.php中开启配置项（建议只在需要调试时才开启，否则会导致网站报错）
 */
class LogService extends BaseService
{
    public function _print($log = null)
    {
        if (is_array($log) || is_object($log)) {
            $message = print_r($log, true);
        } else {
            $message = $log;
        }

        return $message;
    }

    public function _log($namespace = null, $logs = null)
    {
        if (true === WP_DEBUG) {
            $message = '';
            foreach ($logs as $piece) {
                $message .= ' ' . $this->_print($piece);
            }

            error_log($namespace . $message);
        }
    }

    public function log(...$log)
    {
        $this->_log('[普通]', $log);
    }

    public function debug(...$log)
    {
        $this->_log('[调试]', $log);
    }

    public function error(...$log)
    {
        $this->_log('[错误]', $log);
        // TODO: 邮件告警
    }
}
