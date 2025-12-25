# 前端 API 接入文档

## 认证方式

所有 API 请求需要在 Header 中携带 JWT Token：

```
Authorization: Bearer {your_token_here}
```

Token 获取方式：使用 `/wp-json/jwt-auth/v1/token` 端点登录获取。

---

## 一、WordPress REST API v2

### 1.1 线索 (Leads) 接口

#### 创建线索

**接口地址：** `POST /api/wp/v2/leads`

**请求参数：**

```json
{
  "title": "test@example.com",
  "status": "publish",
  "meta": {
    "product_id": "prod_123",
    "source": "api_test"
  }
}
```

**响应示例：**

```json
{
  "id": 4933,
  "title": {
    "rendered": "test@example.com"
  },
  "status": "publish",
  "meta": {
    "product_id": "prod_123",
    "source": "api_test"
  }
}
```

#### 查询线索列表

**接口地址：** `GET /api/wp/v2/leads`

**查询参数：**
- `per_page`: 每页数量 (默认: 10)
- `page`: 页码 (默认: 1)
- `search`: 搜索关键词
- `status`: 发布状态 (publish, draft, etc.)

**响应示例：**

```json
[
  {
    "id": 4933,
    "title": {
      "rendered": "测试线索"
    },
    "meta": {
      "product_id": "prod_123",
      "source": "api_test"
    }
  }
]
```

#### 获取单个线索

**接口地址：** `GET /api/wp/v2/leads/{id}`

**响应示例：** 同创建线索响应

---

### 1.2 站点 (Sites) 接口

#### 创建站点

**接口地址：** `POST /api/wp/v2/sites`

**请求参数：**

```json
{
  "title": "示例网站",
  "content": "这是网站的详细描述",
  "status": "publish",
  "meta": {
    "site_data": "{\"url\":\"https://example.com\",\"category\":\"博客\"}",
    "site_settings": "{\"featured\":false,\"status\":\"active\"}",
    "user_input": "{\"tags\":\"技术,教程\"}",
    "local_key": "unique_key_123"
  }
}
```

**响应示例：**

```json
{
  "id": 4934,
  "title": {
    "rendered": "示例网站"
  },
  "content": {
    "rendered": "<p>这是网站的详细描述</p>"
  },
  "status": "publish",
  "meta": {
    "site_data": "{\"url\":\"https://example.com\",\"category\":\"博客\"}",
    "site_settings": "{\"featured\":false,\"status\":\"active\"}",
    "user_input": "{\"tags\":\"技术,教程\"}",
    "local_key": "unique_key_123",
    "site_views": 0,
    "site_clicks": 0
  },
  "analytics": {
    "views": 0,
    "clicks": 0,
    "views_today": 0,
    "views_week": 0,
    "views_month": 0,
    "clicks_today": 0,
    "clicks_week": 0,
    "clicks_month": 0,
    "conversion_rate": 0,
    "conversion_rate_week": 0,
    "conversion_rate_month": 0,
    "last_viewed": null
  }
}
```

#### 查询站点列表

**接口地址：** `GET /api/wp/v2/sites`

**查询参数：**
- `per_page`: 每页数量 (默认: 10)
- `page`: 页码 (默认: 1)
- `search`: 搜索关键词
- `status`: 发布状态
- `orderby`: 排序字段 (date, title, modified, etc.)
- `order`: 排序方向 (asc, desc)

**响应示例：**

```json
[
  {
    "id": 4934,
    "title": {
      "rendered": "示例网站"
    },
    "meta": {
      "site_url": "https://example.com",
      "site_category": "博客"
    },
    "analytics": {
      "views": 3,
      "clicks": 1,
      "conversion_rate": 33.33
    }
  }
]
```

#### 获取单个站点

**接口地址：** `GET /api/wp/v2/sites/{id}`

**响应示例：** 同创建站点响应

#### 更新站点

**接口地址：** `PUT /api/wp/v2/sites/{id}` 或 `POST /api/wp/v2/sites/{id}`

**请求参数：** 同创建站点（可只传需要更新的字段）

#### 删除站点

**接口地址：** `DELETE /api/wp/v2/sites/{id}`

**查询参数：**
- `force`: 是否永久删除 (true/false, 默认: false)

---

## 二、Analytics API

### 2.1 追踪接口

#### 追踪浏览/点击事件

**接口地址：** `POST /api/analytics/v1/track`

**请求参数：**

```json
{
  "post_type": "site",
  "post_id": 4934,
  "action": "view"
}
```

**参数说明：**
- `post_type`: 内容类型 (site, lead, post, donation, etc.)
- `post_id`: 内容 ID
- `action`: 操作类型 (view 浏览 / click 点击)

**响应示例：**

```json
{
  "success": true,
  "post_id": 4934,
  "action": "view",
  "timestamp": "2025-12-25 10:30:00"
}
```

**错误响应：**

```json
{
  "code": "invalid_post",
  "message": "文章不存在",
  "data": {
    "status": 404
  }
}
```

---

### 2.2 趋势数据接口

#### 获取趋势数据

**接口地址：** `GET /api/analytics/v1/{post_type}/{post_id}/trends`

**路径参数：**
- `post_type`: 内容类型 (site, lead, etc.)
- `post_id`: 内容 ID

**查询参数：**
- `days`: 天数 (1-90, 默认: 7)
- `metric`: 指标类型 (views/clicks, 默认: views)

**示例请求：**

```
GET /api/analytics/v1/site/4934/trends?days=7
```

**响应示例：**

```json
{
  "post_id": 4934,
  "post_type": "site",
  "days": 7,
  "trends": {
    "views": {
      "2025-12-19": 0,
      "2025-12-20": 0,
      "2025-12-21": 0,
      "2025-12-22": 0,
      "2025-12-23": 0,
      "2025-12-24": 0,
      "2025-12-25": 3
    },
    "clicks": {
      "2025-12-19": 0,
      "2025-12-20": 0,
      "2025-12-21": 0,
      "2025-12-22": 0,
      "2025-12-23": 0,
      "2025-12-24": 0,
      "2025-12-25": 1
    }
  }
}
```

---

## 三、使用示例

### 3.1 JavaScript/TypeScript 示例

```typescript
// 配置
const API_BASE = 'http://wp.local/api';
const TOKEN = 'your_jwt_token_here';

// 通用请求函数
async function apiRequest(endpoint: string, options: RequestInit = {}) {
  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${TOKEN}`,
      ...options.headers,
    },
  });
  
  if (!response.ok) {
    throw new Error(`API Error: ${response.statusText}`);
  }
  
  return response.json();
}

// 创建站点
async function createSite(data: any) {
  return apiRequest('/wp/v2/sites', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

// 追踪浏览
async function trackView(postType: string, postId: number) {
  return apiRequest('/analytics/v1/track', {
    method: 'POST',
    body: JSON.stringify({
      post_type: postType,
      post_id: postId,
      action: 'view',
    }),
  });
}

// 获取趋势数据
async function getTrends(postType: string, postId: number, days: number = 7) {
  return apiRequest(`/analytics/v1/${postType}/${postId}/trends?days=${days}`);
}

// 使用示例
async function main() {
  // 创建站点
  const site = await createSite({
    title: '我的网站',
    status: 'publish',
    meta: {
      site_data: JSON.stringify({
        url: 'https://mysite.com',
        category: '技术博客',
      }),
    },
  });
  
  console.log('站点创建成功:', site.id);
  
  // 追踪浏览
  await trackView('site', site.id);
  
  // 获取趋势
  const trends = await getTrends('site', site.id, 7);
  console.log('趋势数据:', trends);
}
```

### 3.2 React Hook 示例

```typescript
import { useState, useEffect } from 'react';

// 自定义 Hook: 获取站点列表
function useSites(page = 1, perPage = 10) {
  const [sites, setSites] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  useEffect(() => {
    const fetchSites = async () => {
      try {
        setLoading(true);
        const data = await apiRequest(
          `/wp/v2/sites?page=${page}&per_page=${perPage}`
        );
        setSites(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };
    
    fetchSites();
  }, [page, perPage]);
  
  return { sites, loading, error };
}

// 自定义 Hook: 追踪浏览
function useTrackView(postType: string, postId: number) {
  useEffect(() => {
    if (postId) {
      trackView(postType, postId);
    }
  }, [postType, postId]);
}

// 组件示例
function SiteDetail({ siteId }: { siteId: number }) {
  const [site, setSite] = useState(null);
  const [trends, setTrends] = useState(null);
  
  // 追踪浏览
  useTrackView('site', siteId);
  
  useEffect(() => {
    // 获取站点详情
    apiRequest(`/wp/v2/sites/${siteId}`).then(setSite);
    
    // 获取趋势数据
    apiRequest(`/analytics/v1/site/${siteId}/trends?days=7`).then(setTrends);
  }, [siteId]);
  
  if (!site) return <div>Loading...</div>;
  
  return (
    <div>
      <h1>{site.title.rendered}</h1>
      <p>浏览量: {site.analytics.views}</p>
      <p>点击量: {site.analytics.clicks}</p>
      <p>转化率: {site.analytics.conversion_rate}%</p>
      
      {/* 趋势图表 */}
      {trends && <TrendsChart data={trends.trends} />}
    </div>
  );
}
```

---

## 四、注意事项

### 4.1 认证与安全
- 所有 API 请求必须携带有效的 JWT Token
- Token 有效期默认 7 天，过期后需重新登录获取
- 生产环境建议使用 HTTPS

### 4.2 请求限制
- 单次请求数据量建议不超过 100 条
- 如需大量数据，建议使用分页请求
- 避免频繁调用追踪接口（建议节流处理）

### 4.3 字段说明

#### 站点字段

**基础字段：**
- `title`: 站点标题 (必填)
- `content`: 站点描述
- `status`: 发布状态 (publish, draft, pending, etc.)

**自定义 Meta 字段：**
- `site_data` (JSON string): 站点数据
  - 可自由定义结构，如 `{"url":"https://...", "category":"..."}`
- `site_settings` (JSON string): 站点设置
  - 可自由定义结构，如 `{"featured":false, "status":"active"}`
- `user_input` (JSON string): 用户输入数据
  - 可自由定义结构，用于存储用户提交的表单数据
- `local_key` (string): 本地唯一标识

**Analytics Meta 字段（由系统自动维护）：**
- `site_views` (integer): 总浏览量
- `site_clicks` (integer): 总点击量
- `site_views_today` (integer): 今日浏览量
- `site_views_week` (integer): 本周浏览量
- `site_views_month` (integer): 本月浏览量
- `site_clicks_today` (integer): 今日点击量
- `site_clicks_week` (integer): 本周点击量
- `site_clicks_month` (integer): 本月点击量
- `site_views_daily` (JSON string): 每日浏览详细数据
- `site_clicks_daily` (JSON string): 每日点击详细数据
- `site_last_viewed` (string): 最后浏览时间

**Analytics 计算字段（只读，非 meta）：**
- `analytics` (object): 统计数据汇总
  - `views`: 总浏览量
  - `clicks`: 总点击量
  - `views_today`: 今日浏览量
  - `views_week`: 本周浏览量
  - `views_month`: 本月浏览量
  - `clicks_today`: 今日点击量
  - `clicks_week`: 本周点击量
  - `clicks_month`: 本月点击量
  - `conversion_rate`: 总转化率 (%)
  - `conversion_rate_week`: 本周转化率 (%)
  - `conversion_rate_month`: 本月转化率 (%)
  - `last_viewed`: 最后浏览时间

#### 线索字段

**基础字段：**
- `title`: 线索标题（通常填写邮箱）
- `status`: 发布状态

**自定义 Meta 字段：**
- `product_id` (string): 关联的产品 ID
- `source` (string): 线索来源
- `ip_address` (string): 提交者 IP 地址（不在 REST 中暴露，仅后台可见）
- `user_agent` (string): 用户代理（不在 REST 中暴露）
- `referer` (string): 来源页面（不在 REST 中暴露）

### 4.4 Analytics 字段说明

**实时字段：**
- `views`: 总浏览量
- `clicks`: 总点击量
- `views_today`: 今日浏览量
- `views_week`: 本周浏览量
- `views_month`: 本月浏览量
- `clicks_today`: 今日点击量
- `clicks_week`: 本周点击量
- `clicks_month`: 本月点击量

**计算字段：**
- `conversion_rate`: 总转化率 (%)
- `conversion_rate_week`: 本周转化率 (%)
- `conversion_rate_month`: 本月转化率 (%)
- `last_viewed`: 最后浏览时间

**趋势数据：**
- 支持查询 1-90 天的历史数据
- 自动清理 90 天以前的数据
- 按日期聚合，返回每日计数

---

## 五、错误处理

### 常见错误码

| 错误码 | 说明 | 处理方式 |
|--------|------|----------|
| 401 | 未授权 | 检查 Token 是否有效 |
| 403 | 无权限 | 检查用户权限 |
| 404 | 资源不存在 | 检查 ID 是否正确 |
| 400 | 参数错误 | 检查请求参数 |
| 500 | 服务器错误 | 联系后端开发 |

### 错误响应格式

```json
{
  "code": "rest_invalid_param",
  "message": "参数错误描述",
  "data": {
    "status": 400,
    "params": {
      "meta.site_url": "URL 格式不正确"
    }
  }
}
```

---

## 六、更新日志

### v1.0.0 (2025-12-25)
- ✅ 实现 WordPress REST API v2 端点
- ✅ 实现 Analytics 追踪和趋势接口
- ✅ 支持 Sites 和 Leads 两种内容类型
- ✅ 集成 JWT 认证
- ✅ 支持 90 天历史数据查询

---

## 七、联系方式

如有问题或建议，请联系后端开发团队。
