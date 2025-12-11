<?php

namespace Tonik\Theme\App\Setup;

/*
|-----------------------------------------------------------
| Theme Custom Actions
|-----------------------------------------------------------
|
| 此文件用于注册通用的 WordPress Actions
| 
| 项目特定的 Actions 在 app/Projects/{ProjectName}/Setup/actions.php 中注册
|
 */

/**
 * 示例：主题激活时执行的操作
 */
function theme_activation()
{
    // 通用的主题激活逻辑
}
add_action('after_switch_theme', 'Tonik\Theme\App\Setup\theme_activation');
