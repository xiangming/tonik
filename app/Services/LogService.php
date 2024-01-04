<?php

namespace App\Services;

/**
 * 向 /wp-content/debug.log 写入log
 * 需要在wp-config.php中开启配置项（建议只在需要调试时才开启，否则会导致网站报错）
 */
class LogService extends BaseService
{
    public function log($log, $namespace = null)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                $message = print_r($log, true);
            } else {
                $message = $log;
            }

            if ($namespace) {
                error_log('[' . $namespace . ']: ' . $message);
            } else {
                error_log($message);
            }
        }
    }
}
