# Launch Project

产品发布和展示系统

## 功能特性

- ✅ 产品自定义文章类型（products）
- ✅ 产品分类（product_category）
- ✅ 产品标签（product_tag）
- ✅ 完整的 REST API 支持
- ✅ 产品元数据（标语、官网、Logo等）
- ✅ 浏览量和点击量统计支持
- ✅ REST API 响应包含完整的分类和标签信息

## 启用项目

在 `.env` 或 `.env.local` 文件中设置：

```bash
ACTIVE_PROJECT=Launch
```

## 项目结构

```
Launch/
├── bootstrap.php           # 项目启动文件
├── Structure/              # 数据结构定义
│   ├── posttypes.php      # 自定义文章类型和元字段
│   └── rest-api.php       # REST API 扩展
├── Services/              # 业务逻辑服务（待扩展）
└── Api/                   # REST API 端点（待扩展）
```

## REST API 端点

### 1. 获取产品列表

```
GET /wp/v2/products
```

### 2. 获取单个产品

```
GET /wp/v2/products/{id}
```

响应包含：
- 标准产品信息（title, content, author等）
- 自定义元字段：
  - `tagline`: 产品标语
  - `website_url`: 产品官网
  - `logo_url`: 产品 Logo
  - `is_featured`: 是否精选
  - `product_views`: 浏览量
  - `product_clicks`: 点击量
- 完整的分类和标签信息：
  - `product_categories_details`: [{id, name, slug, description, count}, ...]
  - `product_tags_details`: [{id, name, slug, description, count}, ...]

### 3. 获取产品分类

```
GET /wp/v2/product_category
GET /wp/v2/product_category/{id}
```

### 4. 获取产品标签

```
GET /wp/v2/product_tag
GET /wp/v2/product_tag/{id}
```

## 集成 Analytics 服务

Launch 项目可以利用共享的 `AnalyticsService` 来追踪产品的浏览和点击：

```php
// 追踪产品浏览
theme('analytics')->trackView($product_id, 'products');

// 追踪产品官网点击
theme('analytics')->trackClick($product_id, 'products');

// 获取产品统计数据
$stats = theme('analytics')->getAnalytics($product_id, 'products');
// 返回: ['views' => 100, 'clicks' => 25, 'conversion_rate' => 25.0]
```

## 待扩展功能

- [ ] ProductService：产品业务逻辑服务
- [ ] ProductApi：产品相关 REST API 端点（如浏览/点击追踪）
- [ ] 产品搜索和筛选优化
- [ ] 产品推荐算法
- [ ] 社交分享功能

## 开发注意事项

1. **元字段注册**：所有元字段在 `posttypes.php` 中通过 `register_post_meta()` 注册，启用 `show_in_rest` 以支持 REST API
2. **REST API 扩展**：使用 `rest_prepare_products` 过滤器添加额外的响应数据
3. **代码规范**：遵循 PSR-12，代码和注释使用英文
4. **共享服务**：充分利用 `app/Services/` 中的共享服务（Analytics、Payment等）

## 数据库表

使用 WordPress 标准表结构：
- `wp_posts` (post_type = 'products')
- `wp_postmeta` (产品元数据)
- `wp_terms` + `wp_term_taxonomy` (分类和标签)
- `wp_term_relationships` (产品与分类/标签的关联)
