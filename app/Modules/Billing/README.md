# Billing Module

计费系统模块 — 基于积分的计费系统，支持订阅（monthly/yearly/ltd）多种计费模式。

> **API 接入文档**见 [docs/Billing-前端接入文档.md](../../../docs/Billing-前端接入文档.md)

## 架构

```
app/Modules/Billing/
├── Services/
│   └── BillingService.php    # 核心业务逻辑
├── Api/
│   └── BillingApi.php         # REST API 端点
└── bootstrap.php              # 模块入口
```

## 启用模块

在项目的 `bootstrap.php` 中引入：

```php
$modules = ['Analytics', 'Billing'];
foreach ($modules as $module) {
    $path = get_template_directory() . "/app/Modules/{$module}/bootstrap.php";
    if (file_exists($path)) {
        require_once $path;
    }
}
```

## 配置项

通过 `wp_options` 配置（默认值在代码中定义，无需手动设置）：

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `billing_meta_prefix` | `app` | User Meta 字段前缀 |
| `billing_initial_credits` | `10` | 新用户注册赠送积分 |
| `billing_monthly_credits` | `30` | 所有付费 tier 的月度积分（monthly/yearly/ltd 共用） |

## 服务调用

```php
// 获取积分信息（含懒惰重置和过期检查）
$info = theme('billing')->getCredits($user_id);
// ['credits' => 30, 'tier' => 'monthly', 'isLtd' => false, 'expiresAt' => '2026-...']

// 消耗积分
$result = theme('billing')->consumeCredits($user_id, 5);
// ['success' => true, 'remainingCredits' => 25]

// 订阅购买（duration_months 可选，不传则按 tier 使用默认时长）
theme('billing')->handleSubscriptionPurchase($user_id, 'monthly');   // 默认 1 个月
theme('billing')->handleSubscriptionPurchase($user_id, 'yearly');    // 默认 12 个月
theme('billing')->handleSubscriptionPurchase($user_id, 'ltd');       // 默认 1200 个月

// 订阅续费
theme('billing')->handleSubscriptionRenewal($user_id, 'monthly');
```

## User Meta 字段

前缀可配置（默认 `app`），共 4 个字段：

| Meta Key | 类型 | 说明 |
|----------|------|------|
| `{prefix}_credits` | integer | 当前可用积分 |
| `{prefix}_tier` | string | 用户等级：free / monthly / yearly / ltd |
| `{prefix}_expires_at` | timestamp | 订阅到期时间（ltd 为 100 年后） |
| `{prefix}_reset_at` | timestamp | 下次积分重置时间 |

## 支付集成

支付成功后在 `bootstrap.php` 中注册 Hook：

```php
add_action('payment_success_membership', function($order_id, $payment_data) {
    $user_id = /* 从 order 读取 */;
    $tier = $payment_data['tier'];  // monthly | yearly | ltd
    $duration_months = match($tier) {
        'monthly' => 1,
        'yearly'  => 12,
        'ltd'     => 1200,
    };
    theme('billing')->handleSubscriptionPurchase($user_id, $tier, $duration_months);
}, 10, 2);
```
