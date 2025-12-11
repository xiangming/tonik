<?php

namespace Tonik\Theme\App\Setup;

/*
|-----------------------------------------------------------
| Theme Custom Filters
|-----------------------------------------------------------
|
| 此文件用于注册通用的 WordPress Filters
| 
| 项目特定的 Filters 在 app/Projects/{ProjectName}/Setup/filters.php 中注册
|
 */

use function Tonik\Theme\App\config;

/**
 * 示例：修改文章标题
 */
function modify_post_title($title, $id)
{
    // 通用的标题修改逻辑
    return $title;
}
// add_filter('the_title', 'Tonik\Theme\App\Setup\modify_post_title', 10, 2);
