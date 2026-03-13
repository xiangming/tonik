# Billing Module

计费系统模块 — 基于积分的计费系统，支持 LTD、订阅等多种计费模式。

## 📦 功能特性

- ✅ 单一积分池设计，逻辑清晰
- ✅ 懒惰重置机制（无需 Cron Job）
- ✅ 支持多种用户等级：free / monthly / yearly / ltd
- ✅ 自动订阅过期检查
- ✅ REST API 端点（JWT 认证）
- ✅ 可配置的 Meta 字段前缀

## 🏗️ 架构

```
app/Modules/Billing/
├── Services/
│   └── BillingService.php    # 核心业务逻辑
├── Api/
│   └── BillingApi.php         # REST API 端点
└── bootstrap.php              # 模块入口
```

## 🔌 安装使用

### 1. 在项目中启用模块

编辑项目的 `bootstrap.php`（例如 `app/Projects/Site/bootstrap.php`）：

```php
$modules = ['Analytics', 'Billing'];  // 添加 Billing
foreach ($modules as $module) {
    $path = get_template_directory() . "/app/Modules/{$module}/bootstrap.php";
    if (file_exists($path)) {
        require_once $path;
    }
}
```

### 2. 配置参数

在 WordPress 数据库的 `wp_options` 表中添加配置（或通过代码/插件设置）：

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `billing_meta_prefix` | `app` | User Meta 字段前缀 |
| `billing_initial_credits` | `10` | 新用户注册赠送积分 |

**设置方式：**
```php
update_option('billing_meta_prefix', 'app');
update_option('billing_initial_credits', 10);
```

## 📡 API 端点

### 1. 获取积分信息

```bash
GET /wp-json/wp/v2/credits
Authorization: Bearer {jwt_token}
```

**响应：**
```json
{
  "credits": 1000,
  "tier": "monthly",
  "isLtd": false,
  "expiresAt": "2026-03-28T00:00:00+08:00"
}
```

### 2. 消耗积分

```bash
POST /wp-json/wp/v2/credits/consume
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "cost": 3
}
```

**响应：**
```json
{
  "success": true,
  "remainingCredits": 997,
  "message": "积分消耗成功"
}
```

### 3. 获取统计信息（公开）

```bash
GET /wp-json/wp/v2/billing/stats
```

**响应：**
```json
{
  "totalUsers": 156,
  "ltdCount": 73
}
```

## 🔧 服务调用

在代码中使用 Billing 服务：

```php
// 获取积分信息
$billing = theme('billing');
$info = $billing->getCredits($user_id);

// 消耗积分
$result = $billing->consumeCredits($user_id, 5);

// 获取统计信息
$stats = $billing->getStats();

// 订阅购买处理
$billing->handleSubscriptionPurchase($user_id, 'monthly', 30, 1);   // 月费：30积分/月，续1个月
$billing->handleSubscriptionPurchase($user_id, 'yearly', 100, 12);  // 年费：100积分/月，续12个月
$billing->handleSubscriptionPurchase($user_id, 'ltd', 100, 1200);   // LTD：100积分/月，续100年

// 订阅续费处理
$billing->handleSubscriptionRenewal($user_id, 'monthly', 30, 1);    // 续1个月
$billing->handleSubscriptionRenewal($user_id, 'yearly', 100, 12);   // 续12个月
$billing->handleSubscriptionRenewal($user_id, 'ltd', 100, 1200);    // LTD续费（罕见但支持）
```

## 📊 数据库字段

所有字段存储在 `wp_usermeta` 表中，前缀可配置（默认 `app`）：

| 字段 | 类型 | 用途 |
|------|------|------|
| `{prefix}_credits` | integer | 当前可用积分 |
| `{prefix}_tier` | string | 用户等级：free/monthly/yearly/ltd |
| `{prefix}_expires_at` | timestamp | 订阅到期时间（monthly/yearly/ltd 用户，LTD 为100年后） |
| `{prefix}_reset_at` | timestamp | 下次积分重置时间（monthly/yearly/ltd 用户） |
| `{prefix}_monthly_credits` | integer | 月度积分配额（monthly/yearly/ltd 用户） |

## ⚙️ 工作原理

### 懒惰重置（Lazy Reset）

订阅用户的月度积分重置不依赖 Cron Job，而是在调用 `getCredits()` 时自动检查：

```php
if ($now >= $reset_at) {
    // 重置积分为月度配额
    update_user_meta($user_id, "{$prefix}_credits", $monthly_credits);
    // 设置下次重置时间
    update_user_meta($user_id, "{$prefix}_reset_at", strtotime('+1 month', $reset_at));
}
```

### 订阅过期检查

同样采用懒惰检查，在 `getCredits()` 和 `consumeCredits()` 时自动判断订阅是否过期，如已过期自动降级为 free。

## 🧪 测试示例

```bash
# 1. 获取 JWT Token
curl -X POST http://wp.local/wp-json/jwt-auth/v1/token \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# 保存返回的 token
TOKEN="your_jwt_token_here"

# 2. 获取积分信息
curl -X GET http://wp.local/wp-json/wp/v2/credits \
  -H "Authorization: Bearer $TOKEN"

# 3. 消耗积分
curl -X POST http://wp.local/wp-json/wp/v2/credits/consume \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"cost": 5}'

# 4. 获取统计信息（无需认证）
curl http://wp.local/wp-json/wp/v2/billing/stats
```

## 📝 注意事项

1. **认证方式**：所有积分相关接口需要 JWT Token 认证
2. **单一积分池**：所有积分统一存储在 `credits` 字段，订阅用户每月重置
3. **过期处理**：订阅过期后积分清零，降级为 free 用户
4. **并发问题**：当前设计不考虑并发消耗问题（根据项目需求决定）

## 🔗 相关文档

- [计费系统设计.md](../../../docs/计费系统设计.md) - 完整设计文档
- [前端支付接入文档.md](../../../docs/前端支付接入文档.md) - 支付集成指南
