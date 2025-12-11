<?php

/**
 * Projects Configuration
 * 
 * 项目特定代码配置
 * 
 * 通过环境变量控制加载哪个项目的定制代码
 */

return [
    /**
     * 当前激活的项目
     * 
     * 通常一个环境只运行一个项目
     * 
     * 可选值：'Fans', 'Project2', false
     * false 表示不加载任何项目特定代码（纯净基础环境）
     */
    'active' => $_ENV['ACTIVE_PROJECT'] ?? false,
    
    /**
     * 项目代码根路径
     */
    'path' => get_template_directory() . '/app/Projects',
];
