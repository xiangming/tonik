<?php

/**
 * Social Project Bootstrap
 *
 * 社交项目启动文件（girls / wall 前端所用的后端）
 *
 * 包含：
 * - 用户档案扩展字段（photos, tags, phone）
 */

namespace Tonik\Theme\App\Projects\Social;

use App\Projects\Social\Meta\UserMeta;

// 获取主题目录路径
$theme_dir = get_template_directory();

// ============================================
// 基础依赖加载
// ============================================
$dependencies = [
    '/app/Traits/ResourceTrait.php',
    '/app/Services/BaseService.php',
    '/app/Services/UserService.php',
];

foreach ($dependencies as $file) {
    $path = $theme_dir . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// 加载 UserMeta 类
require_once __DIR__ . '/Meta/UserMeta.php';

// ============================================
// 注册 REST API 字段
// ============================================
add_action('rest_api_init', function () {
    UserMeta::register();
});
