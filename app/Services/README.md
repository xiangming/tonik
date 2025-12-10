# Services - 通用基础设施

这里只放**所有项目都可以复用**的基础服务。

## ✅ 应该放在这里

- `BaseService.php` - 服务基类，提供统一响应格式
- `LogService.php` - 日志服务
- `MailService.php` - 邮件服务
- `SmsService.php` - 短信服务
- `PaymentService.php` - 通用支付服务（支付宝、微信支付基础封装）
- `QueueService.php` - 队列服务
- `ToolService.php` - 工具类服务
- `ArgsService.php` - 参数处理服务

## ❌ 不应该放在这里

- 项目特定的业务逻辑
- 只有某个项目使用的服务
- 包含项目定制字段的服务

## 👉 项目特定代码应该放在

`app/Projects/{ProjectName}/Services/`

例如：
- `app/Projects/Fans/Services/DonationService.php` - Fans 项目的打赏服务
- `app/Projects/Fans/Services/OrderService.php` - Fans 项目的订单服务
