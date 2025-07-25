<?php

namespace Tonik\Theme\App;

use Tonik\Gin\Asset\Asset;
use Tonik\Gin\Foundation\Theme;
use Tonik\Gin\Template\Template;

/**
 * Gets theme instance.
 *
 * @param string|null $key
 * @param array $parameters
 *
 * @return \Tonik\Gin\Foundation\Theme
 */
function theme($key = null, $parameters = [])
{
    if (null !== $key) {
        return Theme::getInstance()->get($key, $parameters);
    }

    return Theme::getInstance();
}

/**
 * Gets theme config instance.
 *
 * @param string|null $key
 *
 * @return array
 */
function config($key = null)
{
    if (null !== $key) {
        return theme('config')->get($key);
    }

    return theme('config');
}

/**
 * Renders template file with data.
 *
 * @param  string $file Relative path to the template file.
 * @param  array  $data Dataset for the template.
 *
 * @return void
 */
function template($file, $data = [])
{
    $template = new Template(config());

    return $template
        ->setFile($file)
        ->render($data);
}

/**
 * Gets asset instance.
 *
 * @param  string $file Relative file path to the asset file.
 *
 * @return \Tonik\Gin\Asset\Asset
 */
function asset($file)
{
    $asset = new Asset(config());

    return $asset->setFile($file);
}

/**
 * Gets asset file from public directory.
 *
 * @param  string $file Relative file path to the asset file.
 *
 * @return string
 */
function asset_path($file)
{
    return asset($file)->getUri();
}

/**
 * 接口正确返回函数
 * 
 * 错误码请阅读：https://flowus.cn/arvin/51fac2b2-d532-48b9-b287-2c5c2b123ba2
 *
 * @param   $code     接口错误码
 * @param   $data     接口需要返回的数据
 * @param   $message  接口提示信息
 *
 * @return  标准接口json
 */
function resOK($data = null, $message = 'success', $code = 0)
{
    return new \WP_REST_Response(['code' => $code, 'message' => $message, 'data' => $data], 200);
}
function resError($message = 'error', $data = null, $code = 1)
{
    return new \WP_REST_Response(['code' => $code, 'message' => $message, 'data' => $data], 400);
}
