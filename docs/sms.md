# 发送短信 `POST`

**URL**: `/api/sms/v1/send`

**Method**: `POST`

**Auth required**: `YES`

## 请求参数

### phoneNumber `string` `required`

要发送的手机号

### phoneCountryCode `string`

国际码，中国大陆手机号可不填。国际短信必填。只要数字，不要带 `+`。

### scene `string`

短信/邮件场景，指定发送此短信的目的，将对应使用不同的模板来发送。

> TODO: 每个手机号/邮箱同一 scene 在一分钟内只能发送一次。

- SCENE_LOGIN: 用于用户登录
- SCENE_REGISTER: 用于用户注册
- SCENE_RESET_PASSWORD: 用于重置密码
- SCENE_BIND_PHONE: 用于绑定手机号
- SCENE_UNBIND_PHONE: 用于解绑手机号
- SCENE_COMPLETE_PHONE: 用于在注册/登录时补全手机号信息
- SCENE_IDENTITY_VERIFICATION: 用于进行用户实名认证
- SCENE_DELETE_ACCOUNT: 用于注销账号

> 1. 短信通道将在系统内部处理，调用者不需要关注。
> 2. 验证码是调用者自己生成并传给 SMS 系统的，并不是 SMS 系统需要关注的内容。
> 3. 该字段可能会在需要的时候，增加新的字段，但是旧字段会保持不动。
> 4. SMS 系统内部会根据场景定义不同的短信类，从而实现复用。

## 响应参数

```json
{
  "code": "XXX",
  "data": {
    // 返回的数据，如果有
  },
  "message": ""
}
```

## 示例代码

```js
const data = { phoneNumber: `${phoneNumber}` };
fetch(API_URL, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Authorization: `Bearer ${token}`,
  },
  body: JSON.stringify(data),
}).then((res) => res.json());
```
