# GitHub Copilot Instructions

## 🏗️ Architecture Overview

**Multi-Project WordPress Theme** built on [Tonik Framework](https://github.com/tonik/theme)

**本地开发地址**: `http://wp.local/api/wp/v2/`

```
┌─ Shared Infrastructure (app/Services, app/Http)
│  Payment, Mail, SMS, Queue, Analytics - reusable across all projects
│
└─ Project-Specific Code (app/Projects/{ProjectName})
   Fans: donation system, custom post types (donation, orders)
   Sites: lead generation, site builder (lead, site post types)
```

**Key Concept**: One codebase → Multiple deployments. Each WP instance runs one project via `ACTIVE_PROJECT=Fans` in `.env`.

## 📂 Directory Structure

```
app/
├── Services/       # Shared infrastructure services (payment, mail, analytics)
├── Projects/       # Project-specific code (isolated by directory)
│   ├── Fans/       # Donation/reward system
│   │   ├── Services/       # Business logic (DonationService, StatService)
│   │   ├── Structure/      # Custom post types (donation, orders)
│   │   ├── Api/            # REST endpoints
│   │   └── bootstrap.php   # Project initialization
│   └── Sites/      # Site builder & lead gen
├── Http/           # REST API endpoints (shared)
├── Setup/          # WordPress hooks/filters
└── Structure/      # Shared post types/taxonomies
```

## 🎯 Development Patterns

### 1. REST API Meta Fields Registration

**Critical Pattern**: Use `register_post_meta()` with `show_in_rest: true` for REST API exposure.

```php
// Reference: app/Projects/Sites/Structure/posttypes.php
register_post_meta('site', 'site_data', [
    'type' => 'string',
    'single' => true,
    'show_in_rest' => true,
    'sanitize_callback' => function($value) {
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $value : '';
    },
    'auth_callback' => function() {
        return current_user_can('edit_posts');
    },
]);
```

### 2. Project-Specific Code Organization

**When to create new project code** (`app/Projects/{Name}/`):
- Project-specific custom post types
- Business logic unique to one project
- Project-specific REST endpoints

**When to use shared services** (`app/Services/`):
- Payment processing (all projects use same Alipay/Wechat)
- Analytics tracking (reusable across projects)
- Mail, SMS, logging (infrastructure)

### 3. Service Registration

Services bind to theme container in `bootstrap.php`:

```php
// app/Projects/Fans/bootstrap.php
add_action('after_setup_theme', function () {
    theme()->bind('donation', function () {
        return new DonationService();
    });
}, 10);

// Usage anywhere:
theme('donation')->create($data);
```

### 4. Payment Integration Pattern

**Universal Payment API** supports 5 order types: `donation`, `membership`, `product`, `service`, `recharge`.

```php
// POST /wp/v2/payment/create
{
  "type": "membership",
  "amount": 99,              // Yuan (元), not cents!
  "title": "VIP Annual",
  "method": "wechat",        // alipay | wechat
  "device": "wap",           // web | wap | app | scan
  "plan_id": 3,              // Flat structure - no nesting
  "duration_months": 12
}
```

**Hook for payment success**:
```php
add_action('payment_success_membership', function($order_id, $payment_data) {
    // Activate membership, extend expiry
}, 10, 2);
```

**Critical**: Amount is in YUAN (元), backend auto-converts to cents for Wechat.

### 5. Analytics Pattern (Shared Service)

Use `AnalyticsService` for tracking any post type:

```php
// Track views/clicks
theme('analytics')->trackView($post_id, 'site');
theme('analytics')->trackClick($post_id, 'donation');

// Get stats
$stats = theme('analytics')->getAnalytics($site_id, 'site');
// ['views' => 1250, 'clicks' => 45, 'conversion_rate' => 3.6]

// Top content
$top = theme('analytics')->getTopByViews('site', 10, 30); // top 10 in 30 days
```

## 🔧 Build & Development Workflow

### Commands
```bash
# PHP dependencies
composer install

# Frontend assets (webpack-based)
pnpm install
pnpm dev        # Development build
pnpm prod       # Production build (must run before deploying!)

# CRITICAL: Always run `pnpm prod` before deployment or theme breaks!
```

### Environment Configuration
```bash
# .env or .env.local
ACTIVE_PROJECT=Fans           # Load Fans project
# ACTIVE_PROJECT=Sites        # Load Sites project
# ACTIVE_PROJECT=             # Pure base environment (no project)

# Payment credentials (per project)
ALIPAY_APP_ID=xxx
WECHAT_MCH_ID=xxx
```

### Project Loading Sequence
1. Core services loaded (`config/app.php` autoload)
2. Filters/actions registered (`app/Setup/filters.php`)
3. Project bootstrap loaded if `ACTIVE_PROJECT` set (`bootstrap/theme.php`)
4. Project services registered (`after_setup_theme` hook)
5. REST API fields/endpoints registered (`rest_api_init` hook)

## 📝 Coding Conventions

### Code Style
- **PSR-12** for PHP
- English for code/comments
- Chinese for Git commits: `<type>: <description>`
  - `feat: 新增会员订阅功能`
  - `fix: 修复支付回调错误`
  - `refactor: 重构打赏服务`

### File Organization
- **Always code in English** (variables, functions, comments)
- **Add blank lines** between logical code blocks
- **Function-level comments only** for new code - preserve existing comments
- **Namespace**: Match directory structure (`App\Projects\Fans\Services`)

### REST API Conventions
- Endpoint: `/wp/v2/{resource}/{action}`
- Response format via `BaseService`:
  ```php
  return $this->success($data);  // {code: 0, message: "success", data: ...}
  return $this->error("message"); // {code: 1, message: "...", data: ""}
  ```

### Common Pitfalls
- ❌ DON'T nest custom fields in `custom_meta` - use flat structure
- ❌ DON'T create Meta classes for REST fields - use `register_post_meta()`
- ✅ DO register meta in `init` hook immediately after post type registration
- ✅ DO use `show_in_rest: true` for REST API exposure
- ✅ DO add `auth_callback` for protected fields

## 🚀 Adding New Features

### New Custom Post Type + REST Fields
```php
// In app/Projects/{Project}/Structure/posttypes.php

function register_my_post_type() {
    register_post_type('my_type', [
        'show_in_rest' => true,
        'rest_base' => 'my-types',
        'supports' => ['title', 'custom-fields'],
        // ... other args
    ]);
    
    // Immediately register meta fields (same function)
    register_post_meta('my_type', 'my_field', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
}
add_action('init', 'App\Projects\MyProject\Structure\register_my_post_type');
```

### New REST Endpoint
```php
// In app/Projects/{Project}/Api/MyApi.php
class MyApi {
    public static function register() {
        register_rest_route('wp/v2', '/my-resource/action', [
            'methods' => 'POST',
            'callback' => [self::class, 'handleAction'],
            'permission_callback' => '__return_true', // or custom check
        ]);
    }
}

// In bootstrap.php
add_action('rest_api_init', function() {
    MyApi::register();
});
```

### New Payment Order Type
1. Add hook handler: `add_action('payment_success_{type}', 'handler', 10, 2);`
2. Frontend calls: `POST /wp/v2/payment/create` with `type: 'new_type'`
3. Backend triggers hook after payment confirmation

## 📚 Key Files Reference

- **[README.md](README.md)**: Deployment, multi-project architecture
- **[REFACTOR.md](REFACTOR.md)**: Project separation refactor history
- **[docs/前端支付接入文档.md](docs/前端支付接入文档.md)**: Payment API specs (5 order types)
- **[app/Projects/README.md](app/Projects/README.md)**: Project code organization guide
- **[app/Services/README.md](app/Services/README.md)**: Shared services guide

## 🔍 Debugging

- **Logs**: Check `wp-content/debug.log`
- **Payment**: Enable sandbox mode in `.env` (see `PaymentService.php`)
- **REST errors**: Use `theme('log')->error($message)` in services
- **Project not loading**: Verify `ACTIVE_PROJECT` in `.env` matches directory name exactly
