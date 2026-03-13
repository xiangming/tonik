# Billing System — API 接入文档

> 本文档面向前端/接入方。记录已测试的端点、字段、错误码和集成要点。

**本地开发地址：** `http://wp.local/api/wp/v2/`  
**wp-json 已重写为 `/api`**，所有端点路径统一使用 `/api/wp/v2/`。

---

## 认证方式

除 `GET /billing/stats` 外，所有接口需要 JWT Token：

```
Authorization: Bearer {jwt_token}
```

获取 Token：
```bash
POST http://wp.local/api/jwt-auth/v1/token
Content-Type: application/json

{"username": "xxx", "password": "xxx"}
```

响应：`{"token": "eyJ...", "user_email": "...", ...}`

---

## 端点列表

### 1. 获取用户积分

`GET /api/wp/v2/credits`

**认证：** 必需

**响应：**
```json
{
  "credits": 67,
  "tier": "free",
  "isLtd": false,
  "expiresAt": null
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| `credits` | integer | 当前可用积分 |
| `tier` | string | 用户等级：`free` \| `monthly` \| `yearly` \| `ltd` |
| `isLtd` | boolean | 是否为 LTD 用户（`tier === "ltd"`） |
| `expiresAt` | string\|null | 订阅到期时间（ISO 8601）。`monthly`/`yearly`/`ltd` 均有值，`free` 为 `null`。LTD 到期时间为购买后 100 年 |

**行为说明：**
- 调用此接口会自动触发**懒惰重置**：若当前时间超过 `reset_at`，积分重置为月度配额（`billing_monthly_credits`，默认 30）
- 调用此接口会自动触发**过期检查**：若订阅已过期，tier 自动降级为 `free`，积分清零

**错误响应：**
```json
// 401 - Token 无效或未提供
{"code": "rest_forbidden", "message": "..."}
```

---

### 2. 消耗积分

`POST /api/wp/v2/credits/consume`

**认证：** 必需

**请求体：**
```json
{"cost": 3}
```

| 字段 | 类型 | 必需 | 说明 |
|------|------|------|------|
| `cost` | integer | 是 | 消耗积分数，必须 > 0 |

**成功响应（200）：**
```json
{
  "success": true,
  "remainingCredits": 124,
  "message": "积分消耗成功"
}
```

**错误响应：**
```json
// 400 - 积分不足
{
  "code": "insufficient_credits",
  "message": "积分不足，当前余额：2，需要：3",
  "data": {
    "status": 400,
    "current_credits": 2,
    "required_credits": 3
  }
}
```

**行为说明：**
- 消耗前会先调用一次积分获取，确保懒惰重置先于扣除执行
- `cost` 参数由 REST 层校验（integer, min: 1），无需前端额外处理

---

### 3. 充值积分（仅 Debug 环境）

`POST /api/wp/v2/credits/recharge`

**认证：** 必需  
**限制：** 仅在 `WP_DEBUG=true` 时可用，生产环境返回 403

**请求体：**
```json
{"credits": 30}
```

**成功响应：**
```json
{
  "success": true,
  "message": "成功充值 30 积分",
  "currentCredits": 127
}
```

---

## 数据模型

### User Meta 字段

前缀由 `billing_meta_prefix` 配置（默认 `app`）。

| Meta Key | 类型 | 说明 |
|----------|------|------|
| `{prefix}_credits` | integer | 当前可用积分 |
| `{prefix}_tier` | string | 用户等级，未设置则为 `free` |
| `{prefix}_expires_at` | timestamp | 订阅到期时间（unix）；`ltd` 为100年后 |
| `{prefix}_reset_at` | timestamp | 下次积分重置时间（unix）|

**Free 用户**不写入 `_tier` 字段（读取时默认 `free`），也不设置 `_expires_at` / `_reset_at`。

### WordPress Options（后台配置）

| Key | 默认值 | 说明 |
|-----|--------|------|
| `billing_meta_prefix` | `app` | Meta 字段前缀 |
| `billing_initial_credits` | `10` | 新用户注册赠送积分 |
| `billing_monthly_credits` | `30` | 所有付费 tier 的月度积分（monthly/yearly/ltd 共用） |

---

## 订阅与支付集成

### LTD 设计说明

LTD 不是独立逻辑，而是 **100年有效期的年费用户**（`duration_months=1200`）。  
月度积分与 monthly/yearly 用户相同，由 `billing_monthly_credits` 配置决定。

### 支付成功 Hook

前端调用 `POST /api/wp/v2/payment/create`，后端支付回调触发：

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

### 创建会员订单（前端）

```bash
POST /api/wp/v2/payment/create
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "membership",
  "amount": 99,
  "title": "月度会员",
  "method": "wechat",
  "device": "wap",
  "tier": "monthly",
  "duration_months": 1
}
```

`tier` 可选值：`monthly` / `yearly` / `ltd`

---

## 用户等级说明

| tier | 月度积分 | 有效期 | expiresAt |
|------|----------|--------|-----------|
| `free` | -- | 永久 | `null` |
| `monthly` | 30（可配置） | 1个月 | ISO 8601 |
| `yearly` | 30（可配置） | 12个月 | ISO 8601 |
| `ltd` | 30（可配置） | 100年 | ISO 8601（100年后） |

---

## 前端集成示例

```typescript
const BASE = 'http://wp.local/api/wp/v2';

// 获取积分
const getCredits = async (token: string) => {
  const res = await fetch(`${BASE}/credits`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  return res.json();
  // { credits: 67, tier: 'free', isLtd: false, expiresAt: null }
};

// 消耗积分
const consumeCredits = async (token: string, cost: number) => {
  const res = await fetch(`${BASE}/credits/consume`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ cost }),
  });

  if (!res.ok) {
    const err = await res.json();
    if (err.code === 'insufficient_credits') {
      // 积分不足，引导升级
    }
    throw err;
  }

  return res.json();
  // { success: true, remainingCredits: 124, message: '积分消耗成功' }
};

// 营销展示（无需登录）
const getStats = async () => {
  const res = await fetch(`${BASE}/billing/stats`);
  return res.json();
  // { totalUsers: 52629, ltdCount: 0 }
};
```

---

**最后更新：** 2026-03-12  
**测试环境：** `http://wp.local`，用户 ID 1，WP_DEBUG=true
