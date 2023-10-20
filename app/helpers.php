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
 * @param   $message  接口message
 * @param   $data     接口data，可选
 *
 * @return  标准接口json
 */
function resOK($message = 'success', $data = null)
{
    print_r(json_encode(['code' => 0, 'message' => $message, 'data' => $data]));
    return;
}
function resError($message = 'error', $data = null)
{
    print_r(json_encode(['code' => 1, 'message' => $message, 'data' => $data]));
    return;
}

/**
 * 是否admin用户
 * @since 1.0
 */
if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        return current_user_can('manage_options') ? true : false;
    }
}
