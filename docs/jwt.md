# JWT

**URL**: `/api/jwt-auth/v1/token`

**Method**: `POST`

**Auth required**: `NO`

## 获取 token

```js
{
 "username": "USERNAME", // 账号
 "password": "PASSWORD" // 密码
}
```

### 成功的返回值

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L3dwIiwiaWF0IjoxNjgzMTkyNzEwLCJuYmYiOjE2ODMxOTI3MTAsImV4cCI6MTY4Mzc5NzUxMCwiZGF0YSI6eyJ1c2VyIjp7ImlkIjoiMSJ9fX0.poKNVNioxe5IfaYNoOTOXED5yd0P1AX_5_Wb8Kv7oIs",
  "user_email": "USER_EMAIL",
  "user_nicename": "USERNAME",
  "user_display_name": "USERNAME"
}
```

### 失败的返回值

账号错误：

```json
{
  "code": "[jwt_auth] invalid_username",
  "message": "<strong>错误：</strong>用户名<strong>1arvin</strong>未在本站点注册。如果您不确定您的用户名，请改用电子邮箱地址进行尝试。",
  "data": {
    "status": 403
  }
}
```

密码错误：

```json
{
  "code": "[jwt_auth] incorrect_password",
  "message": "<strong>错误：</strong>为用户名 <strong>arvin</strong> 指定的密码不正确。 <a href=\"http://localhost/wp/wp-login.php?action=lostpassword\">忘记密码？</a>",
  "data": {
    "status": 403
  }
}
```

请将拿到的 token 保存起来，以备下次使用，不要频繁获取。

## 使用 token

在请求需求授权的接口时，在 `headers` 里面携带 `Authorization`，即可自动完成授权。

```js
fetch(API_URL, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Authorization: `Bearer ${token}`,
  },
  body: JSON.stringify(data),
}).then((res) => res.json());
```

## 校验 token 是否有效

有时候我们本地有 token，但是可能已失效，导致登录态异常。如果需要，可以主动去验证。

> 不建议每次请求都去校验 token，增加了不必要的请求时间。默认情况下，token 有效期 7 天；

`POST: /api/jwt-auth/v1/token/validate`

只需要在 POST 的 header 里面加入一个 Authorization 即可。

```js
fetch(API_URL, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Authorization: `Bearer ${token}`,
  },
}).then((res) => res.json());
```

验证通过后的返回值：

```json
{
  "code": "jwt_auth_valid_token",
  "data": {
    "status": 200
  }
}
```

验证不通过后的返回值：

```json
{
  "code": "jwt_auth_invalid_token",
  "message": "Expired token",
  "data": {
    "status": 403
  }
}
```

## 结论

我们建议的流程：

1. 获取 token 并保存
2. 使用 token 发起请求
3. 遇到 token 失效的信息，则重新获取 token 并保存
