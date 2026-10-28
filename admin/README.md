# CAMPUS 管理后台

浏览器打开：

http://127.0.0.1/miniprogram/admin/login.php

演示账号：

- 用户名：`admin`
- 密码：`admin123`

## 功能模块（与数据库表对应）

| 导航 | 表 | 说明 |
|------|-----|------|
| 数据概览 | dashboard | KPI、图表、全库一览 |
| 用户管理 | `users` | 搜索、删除 |
| 会员管理 | `vip_members` | 套餐、到期时间 |
| 服装管理 | `clothes` | 搜索、筛选、新增、编辑、删除 |
| 订单管理 | `orders` | 筛选、改状态、删除 |
| 评价管理 | `orders.review` | 订单评价字段查看、清除 |
| 收藏管理 | `favorites` | 用户收藏关系 |
| 买家秀 | `buyer_shows` | 帖子管理 |
| 秀评论 | `buyer_show_comments` | 买家秀评论 |
| 消息会话 | `messages` | 收件箱会话列表 |
| 聊天记录 | `message_chats` | 对话正文 |

与小程序共用同一个 MySQL 库 `miniprogram`。
