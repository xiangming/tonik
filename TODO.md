# TODO

整理待办事项。

## 待实施

### Phase 2（短期）
- [ ] 完善 service 状态管理（`editOrderStatus()` 目前是空 stub，pending/in_progress/completed/cancelled 未实现）
- [ ] product 区分数字商品和实体商品（product 目前仅是 taxonomy term，无独立 post_type）
- [ ] 实现会员到期前提醒功能（续费逻辑存在，但主动提醒邮件/定时任务未实现）
- [ ] 移除 from_user_id 冗余（createOrder 同时写了 post_author 和 from_user_id meta，二者相同）
- [ ] **审查 OrderService 和 PaymentService 的日志记录（避免生产环境日志爆炸）**
  - 建议：只保留 error 级别日志，删除 debug/log 操作日志
  - 原因：每笔订单/支付触发大量日志，生产环境会导致磁盘爆满

### Phase 3（长期）
- [ ] 会员权益管理系统
- [ ] 数字商品自动交付系统
- [ ] 委托工作流管理（需求沟通、修改、交付）
- [ ] 添加订单状态字段（pending/paid/shipped/completed）
- [ ] 考虑使用自定义表存储核心订单字段（性能优化）
