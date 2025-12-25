# Services - 通用基础设施

这里只放**所有项目都可以复用**的基础服务。

## 📦 现有服务

### AnalyticsService 📊 **NEW**
**通用数据分析服务** - 为所有项目提供统一的数据追踪和统计能力。

```php
// 追踪浏览（适用于任何 post_type）
theme('analytics')->trackView($post_id, 'post');
theme('analytics')->trackView($site_id, 'site');
theme('analytics')->trackView($donation_id, 'donation');

// 获取统计数据
$stats = theme('analytics')->getAnalytics($post_id, 'post');
// 返回: ['views' => 1250, 'clicks' => 45, 'conversion_rate' => 3.6]

// 获取热门内容
$top = theme('analytics')->getTopByViews('post', 10, 30);
```

**使用场景：**
- 所有需要统计浏览/点击的项目
- 未来可独立为 SaaS 产品

### 其他服务

- `BaseService.php` - 服务基类，提供统一响应格式
- `LogService.php` - 日志服务
- `MailService.php` - 邮件服务
- `SmsService.php` - 短信服务
- `PaymentService.php` - 通用支付服务（支付宝、微信支付基础封装）
- `QueueService.php` - 队列服务
- `ToolService.php` - 工具类服务
- `ArgsService.php` - 参数处理服务
- `UserService.php` - 用户服务（基础版本，项目可覆盖）

## ✅ 应该放在这里

- **跨项目复用** - 多个项目都需要的功能（如 Analytics）
- **通用业务逻辑** - 不特定于某个项目的逻辑
- **基础设施服务** - 日志、队列、支付等
- **可产品化的功能** - 未来可能独立为产品的服务

## ❌ 不应该放在这里

- 项目特定的业务逻辑 → 放在 `app/Projects/{ProjectName}/Services/`
- 只有某个项目使用的服务
- 包含项目定制字段的服务

## 👉 项目特定代码应该放在

`app/Projects/{ProjectName}/Services/`

例如：
- `app/Projects/Fans/Services/DonationService.php` - Fans 项目的打赏服务
