# Site 项目

Site 项目提供线索管理和站点管理功能，集成了通用的 Analytics 服务。

## 📁 目录结构

```
Site/
├── Structure/
│   └── posttypes.php        # Lead & Site 自定义文章类型
├── Meta/
│   ├── LeadMeta.php         # Lead REST 字段
│   └── SiteMeta.php         # Site REST 字段
├── bootstrap.php            # 项目启动文件
├── README.md
└── QUICKSTART.md
```

**架构说明：**
- 直接使用 WordPress REST API v2：`/wp/v2/sites` 和 `/wp/v2/leads`
- 无需自定义 Service 层和 API 端点
- Meta 字段通过 `register_post_meta` 暴露到 REST API

## 🚀 激活项目

在环境变量中配置：

```bash
# .env 或 .env.local
ACTIVE_PROJECT=Site
```

## 📦 功能模块

### 1. Lead（线索管理）

**自定义文章类型：** `lead`

**Meta 字段：**
- `product_id` - 产品ID
- `source` - 线索来源
- `ip_address` - 提交者IP
- `user_agent` - 用户代理
- `referer` - 来源页面

**REST API 操作：**
```bash
# 创建线索
POST /wp-json/wp/v2/leads
Content-Type: application/json

{
  "title": "user@example.com",
  "status": "publish",
  "meta": {
    "product_id": "prod_123",
    "source": "landing_page"
  }
}

# 获取线索列表
GET /wp-json/wp/v2/leads?per_page=20&meta_key=source&meta_value=landing_page

# 获取单个线索
GET /wp-json/wp/v2/leads/123

# 更新线索
PUT /wp-json/wp/v2/leads/123
Body: { "meta": { "source": "new_source" } }

# 删除线索
DELETE /wp-json/wp/v2/leads/123
```

**PHP 操作：**
```php
// 创建线索
$lead_id = wp_insert_post([
    'post_type' => 'lead',
    'post_title' => 'user@example.com',
    'post_status' => 'publish',
    'meta_input' => [
        'product_id' => 'prod_123',
        'source' => 'landing_page',
    ],
]);

// 查询线索
$leads = get_posts([
    'post_type' => 'lead',
    'posts_per_page' => 20,
    'meta_query' => [[
        'key' => 'source',
        'value' => 'landing_page',
    ]],
]);
```

### 2. Site（站点管理）

**自定义文章类型：** `site`

**Meta 字段：**
- `site_data` - 站点数据（JSON）
- `site_settings` - 站点配置（JSON）
- `user_input` - 用户输入（JSON）
- `local_key` - 本地密钥
- `site_views` - 浏览量（由 AnalyticsService 管理）
- `site_clicks` - 点击量（由 AnalyticsService 管理）

**REST API 操作：**
```bash
# 创建站点
POST /wp-json/wp/v2/sites
Content-Type: application/json

{
  "title": "My Site",
  "content": "Site description",
  "status": "publish",
  "meta": {
    "site_data": "{\"key\":\"value\"}",
    "site_settings": "{\"theme\":\"dark\"}"
  }
}

# 获取站点列表
GET /wp-json/wp/v2/sites?per_page=20&orderby=date

# 获取单个站点（包含meta字段）
GET /wp-json/wp/v2/sites/123

# 更新站点
PUT /wp-json/wp/v2/sites/123
Body: { "title": "New Title", "meta": { "site_settings": "..." } }

# 删除站点
DELETE /wp-json/wp/v2/sites/123?force=true
```

**PHP 操作：**
```php
// 创建站点
$site_id = wp_insert_post([
    'post_type' => 'site',
    'post_title' => 'My Site',
    'post_content' => 'Site description',
    'post_status' => 'publish',
    'meta_input' => [
        'site_data' => wp_json_encode(['key' => 'value']),
        'site_settings' => wp_json_encode(['theme' => 'dark']),
    ],
]);

// 获取站点
$site = get_post($site_id);
$site_data = json_decode(get_post_meta($site_id, 'site_data', true), true);

// 更新站点
wp_update_post(['ID' => $site_id, 'post_title' => 'New Title']);
update_post_meta($site_id, 'site_settings', wp_json_encode(['theme' => 'light']));
```

**Analytics 追踪：**
```php
// 追踪浏览
theme('analytics')->trackView($site_id, 'site');

// 追踪点击
theme('analytics')->trackClick($site_id, 'site');

// 获取统计数据
$stats = theme('analytics')->getAnalytics($site_id, 'site');
// 返回: ['views' => 100, 'clicks' => 10, 'conversion_rate' => 10.0]
```

## 📊 Analytics 集成

Site 项目使用通用的 **AnalyticsService**（位于 `app/Services/AnalyticsService.php`）。

**使用示例：**
```php
// 追踪站点数据
theme('analytics')->trackView($site_id, 'site');
theme('analytics')->trackClick($site_id, 'site');

// 获取统计数据
$stats = theme('analytics')->getAnalytics($site_id, 'site');
// 返回: ['views' => 100, 'clicks' => 10, 'conversion_rate' => 10.0]

// 获取趋势数据
$trend = theme('analytics')->getTrend($site_id, 'site', 7, 'views');

// 获取热门站点
$top = theme('analytics')->getTopByViews('site', 10, 30);
```

**REST API：**
```bash
# 追踪浏览/点击
POST /wp-json/analytics/v1/track
Body: { "post_type": "site", "post_id": 123, "action": "view" }

# 获取趋势数据
GET /wp-json/analytics/v1/site/123/trends?days=7&metric=views

# 获取热门站点
GET /wp-json/analytics/v1/site/top?limit=10&days=30
```

## 🔧 开发指南

### 添加新的 Lead 字段

1. 在 [Structure/posttypes.php](Structure/posttypes.php) 中用 `register_post_meta` 注册字段
2. 在 [Meta/LeadMeta.php](Meta/LeadMeta.php) 中用 `register_rest_field` 暴露到 REST API
3. 字段自动可通过 `/wp/v2/leads` 端点访问

### 添加新的 Site 功能

1. 在 [Structure/posttypes.php](Structure/posttypes.php) 中添加新的 meta 字段
2. 在 [Meta/SiteMeta.php](Meta/SiteMeta.php) 中定义 REST 字段映射
3. 使用 `theme('analytics')` 追踪相关数据
4. 需要自定义端点时，在 [bootstrap.php](bootstrap.php) 中用 `register_rest_route` 注册

## 📝 注意事项

1. **Analytics 是通用服务**
   - 不要在 Site 项目中重复实现统计功能
   - 统一使用 `theme('analytics')` 进行数据追踪
   - 所有项目都可以使用相同的 Analytics API

2. **权限控制**
   - WordPress REST API 默认需要登录才能创建/修改内容
   - 如需公开访问（如表单提交），需在 `register_post_type` 中设置 `capability_type`
   - 敏感字段在 `register_post_meta` 中设置 `show_in_rest => false`

3. **数据验证**
   - Meta 字段在 `register_post_meta` 中设置 `sanitize_callback` 进行验证
   - JSON 字段需自定义 `sanitize_callback` 验证格式
   - 邮箱/重复性验证可通过 WordPress hooks 实现

## 🎯 与其他项目的关系

- **独立性：** Site 项目完全独立，可单独启用/禁用
- **Analytics 共享：** 使用通用 AnalyticsService，与 Fans 等其他项目共享
- **未来扩展：** 可以添加更多站点相关功能（SEO、性能监控等）

## 📚 相关文档

- [Projects 架构说明](../README.md)
- [AnalyticsService 文档](../../Services/README.md)
- [REST API 文档](../../../docs/)
