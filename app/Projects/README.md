# Projects - 项目特定代码

每个子目录代表一个独立项目的定制代码。

## 📁 目录结构

```
Projects/
├── Fans/           # Fans 项目（打赏系统）
│   ├── Services/   # 业务服务
│   ├── Setup/      # REST API 字段注册
│   ├── Structure/  # 自定义文章类型
│   └── bootstrap.php
│
├── Project2/       # 未来的项目2
│   └── bootstrap.php
│
└── ProjectN/       # 更多项目...
```

## 🚀 使用方法

### 激活项目

通过环境变量激活项目：

```bash
# .env 或 .env.local
ACTIVE_PROJECT=Fans
```

### 不加载任何项目（纯净基础环境）

```bash
ACTIVE_PROJECT=
```

## 📦 创建新项目

### 1. 创建项目目录结构

```bash
mkdir -p app/Projects/YourProject/{Services,Setup,Structure}
```

### 2. 创建启动文件 `bootstrap.php`

```php
<?php

namespace Tonik\Theme\App\Projects\YourProject;

// 加载服务类
require_once __DIR__ . '/Services/YourService.php';

// 注册服务
add_action('init', function () {
    theme()->bind('your_service', function () {
        return new YourService();
    });
}, 5);

// 加载自定义文章类型
if (file_exists(__DIR__ . '/Structure/posttypes.php')) {
    require_once __DIR__ . '/Structure/posttypes.php';
}

// 加载 REST API 字段
add_action('rest_api_init', function () {
    if (file_exists(__DIR__ . '/Setup/fields.php')) {
        require_once __DIR__ . '/Setup/fields.php';
    }
});
```

### 3. 配置环境变量

```bash
ACTIVE_PROJECT=YourProject
```

### 4. 开发项目代码

在 `Services/`, `Setup/`, `Structure/` 目录中组织项目代码。

## 🎯 设计原则

### ✅ 应该放在这里

- 项目特定的业务逻辑
- 项目特定的自定义文章类型
- 项目特定的 REST API 字段
- 项目特定的数据处理

### ❌ 不应该放在这里

- 可复用的通用服务 → 放在 `app/Services/`
- 通用的工具函数 → 放在 `app/helpers.php`
- 通用的 HTTP 处理 → 放在 `app/Http/`

## 📚 示例：Fans 项目

Fans 项目包含：
- **DonationService** - 打赏功能
- **OrderService** - 订单管理
- **StatService** - 统计服务
- **UserService** - 用户扩展（支持收款信息、关注等）
- **donation** & **orders** - 自定义文章类型
- REST API 字段注册

激活方式：
```bash
ACTIVE_PROJECT=Fans
```
