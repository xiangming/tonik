# Analytics 前端接入文档

## 📖 概述

Analytics 是通用数据分析服务，为所有项目提供统一的**浏览量**、**点击量**追踪和统计分析功能。

**核心特性：**
- ✅ 多维度统计（总计/今日/本周/本月）
- ✅ 历史趋势数据（最近90天）
- ✅ 转化率自动计算
- ✅ 热门内容排行
- ✅ 最后浏览时间记录

---

## 🚀 快速开始

### 1. 追踪浏览/点击事件

**端点：** `POST /wp-json/analytics/v1/track`

**请求示例：**
```javascript
// 追踪浏览
fetch('/wp-json/analytics/v1/track', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    post_type: 'site',  // 文章类型：site, lead, donation 等
    post_id: 123,       // 文章ID
    action: 'view'      // 动作：view 或 click
  })
});

// 追踪点击
fetch('/wp-json/analytics/v1/track', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    post_type: 'site',
    post_id: 123,
    action: 'click'     // 点击事件
  })
});
```

**参数说明：**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `post_type` | string | ✅ | 文章类型，如 `site`, `donation`, `lead` |
| `post_id` | integer | ✅ | 文章ID |
| `action` | string | ✅ | 动作类型：`view`（浏览）或 `click`（点击）|

**响应示例：**
```json
{
  "code": 0,
  "message": "追踪成功",
  "data": {
    "post_id": 123,
    "action": "view"
  }
}
```

---

### 2. 获取统计数据

#### 方式一：通过 REST API 获取（读取 meta 字段）

**端点：** `GET /wp-json/wp/v2/{post_type}/{id}`

统计数据会自动包含在文章响应的 `meta` 字段中（需先注册 `register_post_meta`）。

**响应示例：**
```json
{
  "id": 123,
  "title": "我的站点",
  "meta": {
    "site_views": 1250,              // 总浏览量
    "site_clicks": 45,               // 总点击量
    "site_views_today": 30,          // 今日浏览量
    "site_clicks_today": 2,          // 今日点击量
    "site_views_week": 180,          // 本周浏览量
    "site_clicks_week": 8,           // 本周点击量
    "site_views_month": 720,         // 本月浏览量
    "site_clicks_month": 28,         // 本月点击量
    "site_views_daily": "{...}",     // 每日详细数据（JSON字符串）
    "site_clicks_daily": "{...}",    // 每日点击数据（JSON字符串）
    "site_last_viewed": "2026-01-18 10:30:00"  // 最后浏览时间
  }
}
```

**前端使用示例：**
```typescript
interface SiteAnalytics {
  site_views: number;
  site_clicks: number;
  site_views_today: number;
  site_clicks_today: number;
  site_views_week: number;
  site_clicks_week: number;
  site_views_month: number;
  site_clicks_month: number;
  site_views_daily: string;      // JSON 字符串
  site_clicks_daily: string;     // JSON 字符串
  site_last_viewed: string;      // MySQL 时间格式
}

// 获取站点数据
const response = await fetch('/wp-json/wp/v2/site/123');
const site = await response.json();

// 计算转化率
const conversionRate = site.meta.site_views > 0 
  ? (site.meta.site_clicks / site.meta.site_views * 100).toFixed(2) 
  : 0;

console.log(`转化率：${conversionRate}%`);
```

---

#### 方式二：使用趋势数据端点（推荐）

**端点：** `GET /wp-json/analytics/v1/{post_type}/{id}/trends?days=30`

**功能：** 获取完整统计数据 + 趋势图数据（最近N天的每日数据）。

**请求示例：**
```javascript
// 获取最近7天趋势
fetch('/wp-json/analytics/v1/site/123/trends?days=7')
  .then(res => res.json())
  .then(data => console.log(data));

// 获取最近30天趋势
fetch('/wp-json/analytics/v1/donation/456/trends?days=30')
  .then(res => res.json())
  .then(data => console.log(data));
```

**参数说明：**
| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| `days` | integer | ❌ | 30 | 获取最近N天的趋势数据（范围：1-90）|

**响应示例：**
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "post_id": 123,
    "post_type": "site",
    "trends": {
      "views": {
        "2026-01-11": 45,
        "2026-01-12": 52,
        "2026-01-13": 38,
        "2026-01-14": 61,
        "2026-01-15": 48,
        "2026-01-16": 55,
        "2026-01-17": 42
      },
      "clicks": {
        "2026-01-11": 2,
        "2026-01-12": 3,
        "2026-01-13": 1,
        "2026-01-14": 4,
        "2026-01-15": 2,
        "2026-01-16": 3,
        "2026-01-17": 1
      }
    },
    "stats": {
      "today": {
        "views": 30,
        "clicks": 2
      },
      "week": {
        "views": 180,
        "clicks": 8
      },
      "month": {
        "views": 720,
        "clicks": 28
      },
      "total": {
        "views": 1250,
        "clicks": 45
      },
      "last_viewed": "2026-01-18 10:30:00"
    }
  }
}
```

**前端使用示例（含图表渲染）：**
```typescript
import { Line } from 'react-chartjs-2';

async function fetchTrends(postType: string, postId: number, days: number = 7) {
  const response = await fetch(
    `/wp-json/analytics/v1/${postType}/${postId}/trends?days=${days}`
  );
  return response.json();
}

function AnalyticsChart({ postType, postId }: Props) {
  const [data, setData] = useState(null);

  useEffect(() => {
    fetchTrends(postType, postId, 30).then(result => {
      setData(result.data);
    });
  }, [postType, postId]);

  if (!data) return <div>加载中...</div>;

  const chartData = {
    labels: Object.keys(data.trends.views), // 日期数组
    datasets: [
      {
        label: '浏览量',
        data: Object.values(data.trends.views),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
      },
      {
        label: '点击量',
        data: Object.values(data.trends.clicks),
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
      }
    ]
  };

  return (
    <div>
      <div className="stats-grid">
        <div>今日浏览：{data.stats.today.views}</div>
        <div>本周浏览：{data.stats.week.views}</div>
        <div>本月浏览：{data.stats.month.views}</div>
        <div>总浏览量：{data.stats.total.views}</div>
      </div>
      <Line data={chartData} />
    </div>
  );
}
```

---

### 3. 获取热门内容排行

**端点：** `GET /wp-json/analytics/v1/{post_type}/top?period=week&limit=10`

**请求示例：**
```javascript
// 获取本周热门站点（前10名）
fetch('/wp-json/analytics/v1/site/top?period=week&limit=10')
  .then(res => res.json())
  .then(data => console.log(data));

// 获取本月热门打赏（前20名）
fetch('/wp-json/analytics/v1/donation/top?period=month&limit=20')
  .then(res => res.json())
  .then(data => console.log(data));

// 获取历史总排行
fetch('/wp-json/analytics/v1/site/top?period=total&limit=50')
  .then(res => res.json())
  .then(data => console.log(data));
```

**参数说明：**
| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| `period` | string | ❌ | `total` | 统计周期：`total`（总计）/ `week`（本周）/ `month`（本月）|
| `limit` | integer | ❌ | 10 | 返回数量（范围：1-100）|

**响应示例：**
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "post_type": "site",
    "period": "week",
    "limit": 10,
    "items": [
      {
        "id": 123,
        "title": "热门站点 A",
        "views": 1250,
        "url": "https://example.com/site/123"
      },
      {
        "id": 456,
        "title": "热门站点 B",
        "views": 980,
        "url": "https://example.com/site/456"
      }
    ]
  }
}
```

**前端使用示例：**
```typescript
function TopSites() {
  const [topSites, setTopSites] = useState([]);

  useEffect(() => {
    fetch('/wp-json/analytics/v1/site/top?period=week&limit=10')
      .then(res => res.json())
      .then(data => setTopSites(data.data.items));
  }, []);

  return (
    <div className="top-list">
      <h3>本周热门站点</h3>
      {topSites.map((site, index) => (
        <div key={site.id} className="top-item">
          <span className="rank">#{index + 1}</span>
          <a href={site.url}>{site.title}</a>
          <span className="views">{site.views} 浏览</span>
        </div>
      ))}
    </div>
  );
}
```

---

## 🔧 后端集成（注册 Meta 字段）

### 步骤1：注册 Analytics Meta 字段

在项目的 `Structure/posttypes.php` 中注册所有分析相关的 meta 字段：

```php
// 在注册 post_type 之后立即注册 meta 字段
function register_my_post_type() {
    // 1. 注册自定义文章类型
    register_post_type('mytype', [
        'show_in_rest' => true,
        'rest_base' => 'mytypes',
        'supports' => ['title', 'custom-fields'],
        // ... 其他配置
    ]);
    
    // 2. 注册 Analytics Meta 字段（必须在同一个函数中）
    $post_type_prefix = 'mytype'; // 根据实际类型修改
    
    // 总浏览量和点击量
    register_post_meta('mytype', "{$post_type_prefix}_views", [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_clicks", [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    // 今日数据
    register_post_meta('mytype', "{$post_type_prefix}_views_today", [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_clicks_today", [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    // 本周数据
    register_post_meta('mytype', "{$post_type_prefix}_views_week", [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_clicks_week", [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    // 本月数据
    register_post_meta('mytype', "{$post_type_prefix}_views_month", [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_clicks_month", [
        'type' => 'integer',
        'single' => true,
        'default' => 0,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
    ]);
    
    // 每日详细数据（JSON）
    register_post_meta('mytype', "{$post_type_prefix}_views_daily", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_clicks_daily", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    // 最后浏览时间
    register_post_meta('mytype', "{$post_type_prefix}_last_viewed", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    // 辅助字段（用于日期检查，不暴露给 REST API）
    register_post_meta('mytype', "{$post_type_prefix}_views_today_date", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_clicks_today_date", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_views_week_start", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_clicks_week_start", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_views_month_start", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
    
    register_post_meta('mytype', "{$post_type_prefix}_clicks_month_start", [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => false,
    ]);
}
add_action('init', 'App\Projects\MyProject\Structure\register_my_post_type');
```

**⚠️ 关键点：**
1. **统一命名规则：** `{post_type}_{metric}` 格式（如 `site_views`, `donation_clicks`）
2. **必须设置 `show_in_rest: true`** 才能在 REST API 中访问
3. **所有字段必须在 `init` hook 中注册**，与 post_type 注册在同一个函数中

---

### 步骤2：在业务逻辑中使用 AnalyticsService

```php
// 在 Service 或 Controller 中调用
$analytics = theme('analytics');

// 追踪浏览
$analytics->trackView('site', $site_id);

// 追踪点击
$analytics->trackClick('donation', $donation_id);

// 获取统计数据
$stats = $analytics->getAnalytics('site', $site_id);
// 返回：['views' => 1250, 'clicks' => 45, 'conversion_rate' => 3.6, ...]
```

---

## 📊 数据结构说明

### Meta 字段列表（按 post_type 前缀）

| 字段名 | 类型 | REST | 说明 |
|--------|------|------|------|
| `{type}_views` | integer | ✅ | 总浏览量 |
| `{type}_clicks` | integer | ✅ | 总点击量 |
| `{type}_views_today` | integer | ✅ | 今日浏览量（每日0点自动重置）|
| `{type}_clicks_today` | integer | ✅ | 今日点击量 |
| `{type}_views_week` | integer | ✅ | 本周浏览量（每周一自动重置）|
| `{type}_clicks_week` | integer | ✅ | 本周点击量 |
| `{type}_views_month` | integer | ✅ | 本月浏览量（每月1号自动重置）|
| `{type}_clicks_month` | integer | ✅ | 本月点击量 |
| `{type}_views_daily` | string (JSON) | ✅ | 每日详细数据（保留90天）|
| `{type}_clicks_daily` | string (JSON) | ✅ | 每日点击详情（保留90天）|
| `{type}_last_viewed` | string | ✅ | 最后浏览时间（MySQL 格式）|
| `{type}_views_today_date` | string | ❌ | 辅助字段：今日日期标记 |
| `{type}_clicks_today_date` | string | ❌ | 辅助字段：点击日期标记 |
| `{type}_views_week_start` | string | ❌ | 辅助字段：本周起始日期 |
| `{type}_clicks_week_start` | string | ❌ | 辅助字段：本周起始日期 |
| `{type}_views_month_start` | string | ❌ | 辅助字段：本月起始日期 |
| `{type}_clicks_month_start` | string | ❌ | 辅助字段：本月起始日期 |

**示例：** 对于 `site` 类型，字段为 `site_views`, `site_clicks`, `site_views_today` 等。

---

### 每日数据 JSON 格式

```json
{
  "2026-01-11": 45,
  "2026-01-12": 52,
  "2026-01-13": 38,
  "2026-01-14": 61,
  "2026-01-15": 48
}
```

**说明：**
- Key：日期（YYYY-MM-DD 格式）
- Value：当天的计数
- 自动保留最近90天，超过的数据会被清理

---

## 💡 最佳实践

### 1. 页面浏览追踪

```typescript
// 在页面加载时自动追踪浏览
useEffect(() => {
  // 使用防抖避免重复追踪
  const trackView = debounce(() => {
    fetch('/wp-json/analytics/v1/track', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        post_type: 'site',
        post_id: siteId,
        action: 'view'
      })
    });
  }, 1000);
  
  trackView();
}, [siteId]);
```

### 2. 按钮点击追踪

```typescript
function DownloadButton({ siteId }: Props) {
  const handleClick = async () => {
    // 先追踪点击
    await fetch('/wp-json/analytics/v1/track', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        post_type: 'site',
        post_id: siteId,
        action: 'click'
      })
    });
    
    // 再执行实际操作
    window.location.href = '/download';
  };
  
  return <button onClick={handleClick}>下载</button>;
}
```

### 3. 实时显示统计数据

```typescript
function SiteStats({ siteId }: Props) {
  const [stats, setStats] = useState(null);
  
  useEffect(() => {
    // 使用轮询或 WebSocket 实时更新
    const interval = setInterval(async () => {
      const res = await fetch(`/wp-json/wp/v2/site/${siteId}`);
      const data = await res.json();
      setStats(data.meta);
    }, 30000); // 每30秒刷新
    
    return () => clearInterval(interval);
  }, [siteId]);
  
  if (!stats) return null;
  
  return (
    <div className="stats">
      <div>👁️ {stats.site_views} 浏览</div>
      <div>👆 {stats.site_clicks} 点击</div>
      <div>
        📈 转化率：
        {(stats.site_views > 0 
          ? (stats.site_clicks / stats.site_views * 100).toFixed(2) 
          : 0
        )}%
      </div>
    </div>
  );
}
```

### 4. 避免重复追踪（防抖）

```typescript
import { debounce } from 'lodash';

const trackView = debounce((postType: string, postId: number) => {
  fetch('/wp-json/analytics/v1/track', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      post_type: postType,
      post_id: postId,
      action: 'view'
    })
  });
}, 2000, { leading: true, trailing: false }); // 2秒内只触发一次

// 使用
trackView('site', 123);
```

---

## ⚠️ 注意事项

### 1. 命名约定必须严格遵守
- **PHP 追踪：** `theme('analytics')->trackView('site', $id)`
- **Meta 字段：** `site_views`, `site_clicks`, `site_views_today` 等
- **不要混用命名！** 必须保持 `{post_type}_` 前缀一致

### 2. 权限控制
- **追踪端点：** 公开访问（`permission_callback: '__return_true'`）
- **Meta 字段写入：** 只能通过 PHP 后端（`auth_callback` 限制）
- 前端只能**读取**统计数据，不能直接修改

### 3. 性能优化
- **每日数据清理：** 自动保留90天，超过会被删除
- **批量查询：** 使用 `getBatchAnalytics()` 而非循环调用
- **缓存策略：** 前端可缓存统计数据，不必每次请求

### 4. 时区问题
- 所有日期/时间使用 WordPress 时区设置（`current_time('mysql')`）
- 确保服务器时区与业务时区一致

---

## 🔗 相关文件

- **服务类：** [app/Services/AnalyticsService.php](../app/Services/AnalyticsService.php)
- **REST 端点：** [app/Http/analytics.php](../app/Http/analytics.php)
- **使用示例：** [app/Projects/Sites/Structure/posttypes.php](../app/Projects/Sites/Structure/posttypes.php)（查看 `site` 类型如何注册 meta）

---

## 📞 常见问题

**Q：为什么追踪后立即查询，数据没变化？**  
A：检查是否正确注册了 `register_post_meta` 并设置 `show_in_rest: true`。

**Q：如何获取多个文章的统计数据？**  
A：使用 `getBatchAnalytics($post_type, $post_ids)` 批量查询。

**Q：能否自定义统计周期（如最近7天）？**  
A：使用 `/trends` 端点获取原始每日数据，前端自行聚合计算。

**Q：如何重置某篇文章的统计数据？**  
A：调用 `theme('analytics')->resetAnalytics($post_id, $post_type)`。

---

生成时间：2026-01-18  
版本：v2.0
