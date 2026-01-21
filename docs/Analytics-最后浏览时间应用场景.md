# Analytics - 最后浏览时间应用场景

`last_viewed` 字段记录内容的最后浏览时间，提供丰富的业务应用场景。

## 📊 应用场景

### 1. 内容活跃度判断 ⭐

**场景：** 识别冷门内容，自动归档或优化提醒

**实现代码：**
```php
// 判断内容是否"冷门"或"过时"
$last_viewed = get_post_meta($post_id, 'site_last_viewed', true);
$days_inactive = (time() - strtotime($last_viewed)) / 86400;

if ($days_inactive > 30) {
    // 30天无人浏览 - 可能需要优化或归档
    echo "这个站点可能需要更新内容了";
}

// 批量查询冷门内容
$inactive_sites = get_posts([
    'post_type' => 'site',
    'meta_query' => [
        [
            'key' => 'site_last_viewed',
            'value' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'compare' => '<',
            'type' => 'DATETIME',
        ],
    ],
]);
```

**应用：**
- 识别需要优化的冷门内容
- 自动归档长期无人访问的站点
- 内容质量评估和改进建议

---

### 2. 实时活跃展示 🔥

**场景：** 前端展示"最近有人浏览"，增加内容可信度

**实现代码：**
```php
// 后端：格式化时间
$last_viewed = get_post_meta($site_id, 'site_last_viewed', true);
$time_ago = human_time_diff(strtotime($last_viewed), current_time('timestamp'));
echo "最后浏览: {$time_ago}前";

// 输出示例:
// "最后浏览: 2分钟前"
// "最后浏览: 3小时前"
// "最后浏览: 2天前"
```

**前端展示：**
```html
<!-- 站点卡片 -->
<div class="site-card">
  <h3>我的站点</h3>
  <div class="meta">
    <span class="badge hot">🔥 5分钟前有人浏览</span>
    <span class="stats">本周 312 次浏览</span>
  </div>
</div>

<!-- 动态样式 -->
<style>
.badge.hot { 
  color: #ff4444; 
  animation: pulse 2s infinite; 
}
</style>
```

**心理效应：**
- 增加内容可信度（"有人在看"）
- 营造活跃氛围
- 提高用户参与度

---

### 3. 热门内容推荐 📈

**场景：** 推荐"最近活跃的热门内容"，而非历史累计高但已过时的内容

**实现代码：**
```php
// 获取"最近热门"（浏览量高 + 最近还在被访问）
$hot_sites = get_posts([
    'post_type' => 'site',
    'posts_per_page' => 10,
    'meta_query' => [
        [
            'key' => 'site_last_viewed',
            'value' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'compare' => '>=',
            'type' => 'DATETIME',
        ],
    ],
    'meta_key' => 'site_views_week',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);

// 结果：7天内有浏览 + 本周浏览量排序 = "最近活跃的热门站点"
```

**REST API：**
```php
// 注册端点
register_rest_route('sites/v1', '/sites/trending', [
    'methods' => 'GET',
    'callback' => function() {
        return [
            'success' => true,
            'data' => get_trending_sites([
                'recent_days' => 7,
                'limit' => 10,
            ]),
        ];
    },
]);
```

**应用场景：**
- 首页推荐位
- 侧边栏"正在流行"
- 邮件推送"本周热门"
- 移动端"发现"页面

---

### 4. 缓存策略优化 ⚡

**场景：** 根据访问活跃度调整缓存时间，提高性能

**实现代码：**
```php
/**
 * 智能缓存 TTL
 * 热门内容缓存时间短（保持新鲜）
 * 冷门内容缓存时间长（减少服务器压力）
 */
function get_smart_cache_ttl($post_id, $post_type = 'post') {
    $last_viewed = get_post_meta($post_id, $post_type . '_last_viewed', true);
    
    if (!$last_viewed) {
        return 3600; // 从未访问 - 缓存1小时
    }
    
    $hours_since = (time() - strtotime($last_viewed)) / 3600;
    
    if ($hours_since < 1) {
        return 300;      // 1小时内访问过 - 缓存5分钟（很热门）
    } elseif ($hours_since < 24) {
        return 1800;     // 24小时内 - 缓存30分钟（较热门）
    } elseif ($hours_since < 168) {
        return 3600;     // 1周内 - 缓存1小时（正常）
    } else {
        return 7200;     // 超过1周 - 缓存2小时（冷门）
    }
}

// 使用
$cache_key = "site_data_{$site_id}";
$cache_ttl = get_smart_cache_ttl($site_id, 'site');
$data = wp_cache_get($cache_key);

if (false === $data) {
    $data = get_site_data($site_id);
    wp_cache_set($cache_key, $data, '', $cache_ttl);
}
```

**好处：**
- 热门内容更频繁刷新（保持数据新鲜）
- 冷门内容减少查询（降低服务器负载）
- 自动平衡性能和实时性

---

### 5. 数据清理和归档 🗂️

**场景：** 自动归档或删除长期无人访问的站点

**实现代码：**
```php
/**
 * 定时任务：归档无人访问的站点
 * 
 * 添加到 cron job 或后台任务
 */
function archive_inactive_sites() {
    $threshold = date('Y-m-d H:i:s', strtotime('-90 days'));
    
    $sites = get_posts([
        'post_type' => 'site',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'site_last_viewed',
                'value' => $threshold,
                'compare' => '<',
                'type' => 'DATETIME',
            ],
        ],
    ]);
    
    $archived_count = 0;
    
    foreach ($sites as $site) {
        // 方案1：改为草稿状态
        wp_update_post([
            'ID' => $site->ID,
            'post_status' => 'draft',
        ]);
        
        // 方案2：添加标签
        wp_set_post_terms($site->ID, ['inactive'], 'site_tag', true);
        
        // 方案3：发送提醒邮件给站点所有者
        $author_email = get_the_author_meta('email', $site->post_author);
        wp_mail($author_email, '站点长期无访问提醒', '您的站点已90天无人访问...');
        
        $archived_count++;
    }
    
    // 记录日志
    if (function_exists('theme') && theme('log')) {
        theme('log')->info("归档了 {$archived_count} 个无活跃站点");
    }
    
    return $archived_count;
}

// 注册 WP Cron 任务
add_action('wp', function() {
    if (!wp_next_scheduled('archive_inactive_sites_cron')) {
        wp_schedule_event(time(), 'daily', 'archive_inactive_sites_cron');
    }
});

add_action('archive_inactive_sites_cron', 'archive_inactive_sites');
```

**应用：**
- 自动清理僵尸站点
- 释放数据库空间
- 提醒用户更新内容
- 数据库性能优化

---

### 6. 实时仪表盘 📊

**场景：** 管理后台显示实时活跃的站点

**实现代码：**
```php
/**
 * 获取最近活跃的站点
 */
function get_recent_activity($limit = 10) {
    $sites = get_posts([
        'post_type' => 'site',
        'posts_per_page' => $limit,
        'meta_key' => 'site_last_viewed',
        'orderby' => 'meta_value',
        'order' => 'DESC',
    ]);
    
    $activity = [];
    
    foreach ($sites as $site) {
        $last_viewed = get_post_meta($site->ID, 'site_last_viewed', true);
        
        $activity[] = [
            'site_id' => $site->ID,
            'title' => $site->post_title,
            'last_viewed' => $last_viewed,
            'time_ago' => human_time_diff(strtotime($last_viewed), current_time('timestamp')) . '前',
            'is_hot' => (time() - strtotime($last_viewed)) < 300, // 5分钟内
        ];
    }
    
    return $activity;
}

// REST API
register_rest_route('sites/v1', '/dashboard/recent-activity', [
    'methods' => 'GET',
    'callback' => function() {
        return [
            'success' => true,
            'data' => get_recent_activity(20),
        ];
    },
    'permission_callback' => function() {
        return current_user_can('edit_posts');
    },
]);
```

**前端展示：**
```javascript
// 实时活跃列表
fetch('/wp-json/sites/v1/dashboard/recent-activity')
  .then(res => res.json())
  .then(data => {
    const html = data.data.map(item => `
      <div class="activity-item ${item.is_hot ? 'hot' : ''}">
        <h4>${item.title}</h4>
        <span class="time">${item.time_ago}</span>
        ${item.is_hot ? '<span class="badge">🔥 正在浏览</span>' : ''}
      </div>
    `).join('');
    
    document.getElementById('activity-list').innerHTML = html;
  });

// 每30秒刷新一次
setInterval(() => {
  // 刷新逻辑
}, 30000);
```

**效果：**
- 用户实时看到哪些站点正在被访问
- 增强管理后台的"活跃感"
- 帮助运营人员监控热点

---

### 7. SEO 和内容策略 🎯

**场景：** 生成内容优化建议，指导 SEO 策略

**实现代码：**
```php
/**
 * 获取需要优化的内容列表
 */
function get_content_optimization_suggestions() {
    // 1. 过期内容：发布很久但从未被访问
    $never_viewed = get_posts([
        'post_type' => 'site',
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'site_last_viewed',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => 'site_last_viewed',
                'value' => '',
            ],
        ],
        'date_query' => [
            [
                'before' => '30 days ago',
            ],
        ],
    ]);
    
    // 2. 沉寂内容：60天无人看 + 总浏览量低
    $stale_content = get_posts([
        'post_type' => 'site',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'site_last_viewed',
                'value' => date('Y-m-d H:i:s', strtotime('-60 days')),
                'compare' => '<',
            ],
            [
                'key' => 'site_views',
                'value' => 100,
                'compare' => '<',
                'type' => 'NUMERIC',
            ],
        ],
    ]);
    
    // 3. 曾经热门但已冷却
    $cooling_content = get_posts([
        'post_type' => 'site',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'site_views',
                'value' => 500,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ],
            [
                'key' => 'site_last_viewed',
                'value' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'compare' => '<',
            ],
        ],
    ]);
    
    return [
        'never_viewed' => count($never_viewed) . ' 篇内容从未被访问',
        'stale_content' => count($stale_content) . ' 篇内容需要优化或删除',
        'cooling_content' => count($cooling_content) . ' 篇曾经热门的内容正在冷却',
        'suggestions' => [
            '考虑优化标题和描述',
            '更新内容以保持相关性',
            '增加内部链接',
            '推广到社交媒体',
        ],
    ];
}
```

**应用：**
- 定期生成内容优化报告
- 指导编辑团队工作重点
- 自动化 SEO 建议

---

### 8. A/B 测试和数据分析 🧪

**场景：** 分析内容发布到首次被访问的时间，评估推广效果

**实现代码：**
```php
/**
 * 计算内容的发现时间
 */
function analyze_content_discovery($post_id) {
    $published = get_post_time('U', false, $post_id);
    $first_view = strtotime(get_post_meta($post_id, 'site_last_viewed', true));
    
    if (!$first_view) {
        return [
            'discovered' => false,
            'message' => '内容尚未被访问',
        ];
    }
    
    $discovery_hours = ($first_view - $published) / 3600;
    
    return [
        'discovered' => true,
        'discovery_hours' => round($discovery_hours, 2),
        'discovery_time' => human_time_diff($published, $first_view),
        'speed_rating' => $discovery_hours < 1 ? '极快' : 
                         ($discovery_hours < 24 ? '正常' : '较慢'),
    ];
}

// 批量分析：评估发布时间的影响
function analyze_publish_time_effectiveness() {
    $sites = get_posts([
        'post_type' => 'site',
        'posts_per_page' => -1,
        'date_query' => [
            ['after' => '30 days ago'],
        ],
    ]);
    
    $by_hour = array_fill(0, 24, [
        'count' => 0,
        'avg_discovery_hours' => 0,
    ]);
    
    foreach ($sites as $site) {
        $hour = (int) get_post_time('G', false, $site->ID);
        $analysis = analyze_content_discovery($site->ID);
        
        if ($analysis['discovered']) {
            $by_hour[$hour]['count']++;
            $by_hour[$hour]['avg_discovery_hours'] += $analysis['discovery_hours'];
        }
    }
    
    // 计算平均值
    foreach ($by_hour as $hour => &$data) {
        if ($data['count'] > 0) {
            $data['avg_discovery_hours'] = round($data['avg_discovery_hours'] / $data['count'], 2);
        }
    }
    
    return $by_hour;
}
```

**用途：**
- 评估内容推广效果
- 优化发布时间
- 测试标题和封面图吸引力
- A/B 测试不同的内容策略

---

## 🎯 优先级建议

| 场景 | 优先级 | 实现难度 | 业务价值 |
|------|--------|---------|---------|
| 实时活跃展示 | ⭐⭐⭐ | 简单 | 高 |
| 热门内容推荐 | ⭐⭐⭐ | 中等 | 高 |
| 数据清理归档 | ⭐⭐ | 简单 | 中 |
| 内容活跃度判断 | ⭐⭐ | 简单 | 中 |
| 缓存策略优化 | ⭐⭐ | 中等 | 中 |
| 实时仪表盘 | ⭐ | 中等 | 低 |
| SEO 内容策略 | ⭐ | 简单 | 中 |
| A/B 测试分析 | ⭐ | 复杂 | 低 |

## 📝 实施建议

### 阶段 1：基础功能（立即实施）
- ✅ 确保 `last_viewed` 字段正确更新
- ✅ 前端展示"X分钟前有人浏览"
- ✅ 后台列表显示最后浏览时间

### 阶段 2：运营优化（1-2周内）
- 热门内容推荐（基于最近活跃度）
- 冷门内容识别和提醒
- 定时清理任务

### 阶段 3：高级功能（按需实施）
- 智能缓存策略
- 实时仪表盘
- SEO 优化建议
- A/B 测试分析

## 💡 技术要点

1. **时间格式统一**
   ```php
   // 使用 WordPress 标准时间格式
   current_time('mysql')  // "2025-12-25 14:30:22"
   ```

2. **查询优化**
   ```php
   // 使用 DATETIME 类型比较
   'type' => 'DATETIME'
   ```

3. **前端显示**
   ```php
   // 使用 WordPress 内置函数
   human_time_diff(strtotime($last_viewed), current_time('timestamp'))
   ```

4. **Cron 任务**
   ```php
   // 使用 WP Cron 而非系统 cron
   wp_schedule_event(time(), 'daily', 'your_hook');
   ```

## 🔗 相关文档

- [AnalyticsService 完整文档](../app/Services/README.md)
- [Site 项目文档](../app/Projects/Site/README.md)
- [WordPress Cron 文档](https://developer.wordpress.org/plugins/cron/)
