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


    // Controller 返回格式化

    // 成功返回数据
    protected function success($data = [], $msg = "success")
    {
        return ['code' => 0, 'msg' => $msg, 'data' => $data];
    }

    // 失败返回数据
    protected function error($msg = "error", $data = [])
    {
        return ['code' => 1, 'msg' => $msg, 'data' => $data];
    }

    // 自定义返回数据
    protected function auto($code, $msg = "Other", $data = [])
    {
        return ['code' => $code, 'msg' => $msg, 'data' => $data];
    }

    // 快捷返回数据处理
    protected function handle($data)
    {
        return $data['status'] ? $this->success($data['data'], $data['msg']) : $this->error($data['msg'], $data['data']);
    }
}
