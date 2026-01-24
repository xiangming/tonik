# Launch 项目前端迁移指南

## 📋 概述

为了降低维护成本并利用 WordPress 原生功能，Launch 项目进行了架构简化：

- **使用默认 `post` 类型**代替自定义 CPT `products`
- **使用原生 taxonomy** (`category`, `post_tag`) 代替自定义 taxonomy
- **使用原生 sticky 功能**代替自定义 meta 字段

**影响范围**：所有产品相关的 REST API 调用需要调整

---

## 🔄 API Endpoint 变化

### ✅ 不变的部分
```bash
# 产品列表/详情 - endpoint 保持不变
GET /wp/v2/products
GET /wp/v2/products/{id}
POST /wp/v2/products
```

### ⚠️ Taxonomy Endpoints 变化

| 旧 API | 新 API | 说明 |
|--------|--------|------|
| `/wp/v2/product_category` | `/wp/v2/categories` | 使用原生分类 |
| `/wp/v2/product_tag` | `/wp/v2/tags` | 使用原生标签 |

---

## 📦 响应数据结构变化

### 1. 产品详情响应

#### 旧结构
```json
{
  "id": 5529,
  "title": {"rendered": "Notion"},
  "product_category": [663, 664],
  "product_tag": [665, 666],
  "product_categories_details": [
    {"id": 663, "name": "生产力工具", "slug": "productivity"}
  ],
  "product_tags_details": [
    {"id": 665, "name": "笔记", "slug": "note"}
  ],
  "meta": {
    "is_featured": true,
    "is_sticky": true
  }
}
```

#### 新结构 ✅
```json
{
  "id": 5529,
  "title": {"rendered": "Notion"},
  "categories": [663, 664],
  "tags": [665, 666],
  "categories_details": [
    {"id": 663, "name": "生产力工具", "slug": "productivity"}
  ],
  "tags_details": [
    {"id": 665, "name": "笔记", "slug": "note"}
  ],
  "sticky": true,
  "meta": {
    "tagline": "...",
    "website_url": "...",
    "logo_url": "...",
    "product_views": 120,
    "product_clicks": 45,
    "images": ["url1", "url2"]
  }
}
```

### 2. Meta 字段变化

| 旧字段 | 新字段 | 说明 |
|--------|--------|------|
| `is_featured` | ❌ 已删除 | 使用 `sticky` 代替 |
| `is_sticky` | ❌ 已删除 | 使用原生 `sticky` |
| - | ✅ `sticky` (原生) | 布尔值，表示是否置顶/精选 |

**保留的 Meta 字段**：
- `tagline` - 产品标语
- `website_url` - 官网链接
- `logo_url` - Logo URL
- `product_views` - 浏览量
- `product_clicks` - 点击量
- `images` - 产品图片数组

---

## 🔍 查询参数变化

### 获取精选/置顶产品

#### 旧方式 ❌
```javascript
// 不再支持
fetch('/wp/v2/products?meta_key=is_featured&meta_value=true')
fetch('/wp/v2/products?meta_key=is_sticky&meta_value=true')
```

#### 新方式 ✅
```javascript
// 使用原生 sticky 参数
fetch('/wp/v2/products?sticky=true')

// 置顶产品会自动排在列表前面
fetch('/wp/v2/products')  // sticky posts 自动优先
```

### 按分类/标签筛选

#### 旧方式 ❌
```javascript
fetch('/wp/v2/products?product_category=663')
fetch('/wp/v2/products?product_tag=665')
```

#### 新方式 ✅
```javascript
fetch('/wp/v2/products?categories=663')
fetch('/wp/v2/products?tags=665')
```

---

## 💻 代码迁移示例

### 1. 获取产品列表

```javascript
// ❌ 旧代码
const products = await fetch('/wp/v2/products').then(r => r.json());
products.forEach(p => {
  console.log(p.product_categories_details);  // 旧字段名
  console.log(p.meta.is_featured);  // 已删除
});

// ✅ 新代码
const products = await fetch('/wp/v2/products').then(r => r.json());
products.forEach(p => {
  console.log(p.categories_details);  // 新字段名
  console.log(p.sticky);  // 使用原生字段
});
```

### 2. 创建/更新产品

```javascript
// ❌ 旧代码
await fetch('/wp/v2/products/5529', {
  method: 'POST',
  body: JSON.stringify({
    product_category: [663],
    product_tag: [665],
    meta: {
      is_featured: true,
      is_sticky: true
    }
  })
});

// ✅ 新代码
await fetch('/wp/v2/products/5529', {
  method: 'POST',
  body: JSON.stringify({
    categories: [663],
    tags: [665],
    sticky: true,  // 原生字段，不在 meta 里
    meta: {
      tagline: "...",
      logo_url: "..."
    }
  })
});
```

### 3. 获取分类/标签列表

```javascript
// ❌ 旧代码
const categories = await fetch('/wp/v2/product_category').then(r => r.json());
const tags = await fetch('/wp/v2/product_tag').then(r => r.json());

// ✅ 新代码
const categories = await fetch('/wp/v2/categories').then(r => r.json());
const tags = await fetch('/wp/v2/tags').then(r => r.json());
```

### 4. 筛选精选产品

```javascript
// ❌ 旧代码
const featured = products.filter(p => p.meta.is_featured === true);

// ✅ 新代码
const featured = products.filter(p => p.sticky === true);
// 或直接查询
const featured = await fetch('/wp/v2/products?sticky=true').then(r => r.json());
```

---

## 🔧 完整迁移检查清单

### TypeScript 类型定义

```typescript
// ❌ 旧类型
interface Product {
  id: number;
  title: { rendered: string };
  product_category: number[];
  product_tag: number[];
  product_categories_details: Category[];
  product_tags_details: Tag[];
  meta: {
    is_featured: boolean;
    is_sticky: boolean;
    tagline: string;
    website_url: string;
    logo_url: string;
    product_views: number;
    product_clicks: number;
    images: string[];
  };
}

// ✅ 新类型
interface Product {
  id: number;
  title: { rendered: string };
  categories: number[];
  tags: number[];
  categories_details: Category[];
  tags_details: Tag[];
  sticky: boolean;  // 原生字段
  meta: {
    tagline: string;
    website_url: string;
    logo_url: string;
    product_views: number;
    product_clicks: number;
    images: string[];
  };
}
```

### React 组件示例

```jsx
// ❌ 旧代码
function ProductCard({ product }) {
  return (
    <div>
      {product.meta.is_featured && <Badge>精选</Badge>}
      {product.product_categories_details.map(cat => (
        <Tag key={cat.id}>{cat.name}</Tag>
      ))}
    </div>
  );
}

// ✅ 新代码
function ProductCard({ product }) {
  return (
    <div>
      {product.sticky && <Badge>精选</Badge>}
      {product.categories_details.map(cat => (
        <Tag key={cat.id}>{cat.name}</Tag>
      ))}
    </div>
  );
}
```

---

## 📝 搜索替换建议

在代码库中执行以下全局替换：

| 搜索 | 替换 | 备注 |
|------|------|------|
| `product_categories_details` | `categories_details` | 响应字段名 |
| `product_tags_details` | `tags_details` | 响应字段名 |
| `product_category` | `categories` | 参数/字段名 |
| `product_tag` | `tags` | 参数/字段名 |
| `meta.is_featured` | `sticky` | 精选标记 |
| `meta.is_sticky` | `sticky` | 置顶标记 |
| `/wp/v2/product_category` | `/wp/v2/categories` | API endpoint |
| `/wp/v2/product_tag` | `/wp/v2/tags` | API endpoint |

---

## ⚠️ 注意事项

1. **置顶排序自动生效**  
   原生 `sticky` 产品会自动排在普通产品前面，无需前端手动排序

2. **分类/标签 ID 保持不变**  
   迁移不影响现有数据，所有分类和标签 ID 保持原样

3. **Meta 字段向后兼容**  
   旧的 `is_featured` 和 `is_sticky` 数据会被忽略，不影响功能

4. **REST API 认证不变**  
   更新产品的认证方式保持不变（Application Passwords）

---

## 🧪 测试建议

### 1. 基础功能测试
```bash
# 获取产品列表
curl 'http://wp.local/wp/v2/products'

# 获取置顶产品
curl 'http://wp.local/wp/v2/products?sticky=true'

# 按分类筛选
curl 'http://wp.local/wp/v2/products?categories=663'

# 按标签筛选
curl 'http://wp.local/wp/v2/products?tags=665'
```

### 2. 响应数据验证
- ✅ `categories_details` 包含完整分类信息
- ✅ `tags_details` 包含完整标签信息
- ✅ `sticky` 字段存在且类型为 boolean
- ✅ `meta.is_featured` 和 `meta.is_sticky` 不存在

### 3. 功能测试
- [ ] 产品列表显示正常
- [ ] 精选产品筛选正常
- [ ] 分类/标签筛选正常
- [ ] 创建/更新产品正常
- [ ] 置顶产品自动排序正常

---

## 📞 技术支持

如有疑问，请联系后端团队或查看：
- WordPress REST API 文档: https://developer.wordpress.org/rest-api/
- 项目文档: `/docs/Launch-REST-API文档.md`
