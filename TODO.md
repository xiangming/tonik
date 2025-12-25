# TODO

整理待办事项。

## 待实施

### Phase 1（立即）✅ 已完成
- [x] 使用 Taxonomy 管理订单类型
- [x] 新增 membership 订单类型
- [x] 实现会员支付成功处理钩子

### Phase 2（短期）
- [ ] 完善 service 状态管理（pending/in_progress/completed/cancelled）
- [ ] product 区分数字商品和实体商品
- [ ] 实现会员自动续费提醒功能
- [ ] 移除 from_user_id 冗余（统一使用 post_author）
- [ ] 统一 name → title 字段命名
- [ ] getOrderByNo() 移除 related_id 抽象层，直接返回具体字段
- [ ] **审查 OrderService 和 PaymentService 的日志记录（避免生产环境日志爆炸）**
  - 建议：只保留 error 级别日志，删除 debug/log 操作日志
  - 原因：每笔订单/支付触发大量日志，生产环境会导致磁盘爆满

### Phase 3（长期）
- [ ] 会员权益管理系统
- [ ] 数字商品自动交付系统
- [ ] 委托工作流管理（需求沟通、修改、交付）
- [ ] 添加订单状态字段（pending/paid/shipped/completed）
- [ ] 分离内部订单号和支付通道订单号
- [ ] 考虑使用自定义表存储核心订单字段（性能优化）

## 其他

- [ ] 不提供 JWT 接口，避免被滥用，改为管理后台去获取并长期有效，用户自行刷新。
