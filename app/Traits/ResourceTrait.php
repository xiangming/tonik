<?php

namespace App\Traits;

trait ResourceTrait
{
    // service 返回格式化
    protected function format($data = [], $msg = 'success')
    {
        return ['status' => true, 'data' => $data, 'msg' => $msg];
    }
    protected function formatError($msg = 'error', $data = [])
    {
        return ['status' => false, 'data' => $data, 'msg' => $msg];
    }
}
