# campus_api 目录说明

按业务分组，小程序请求路径带文件夹名，例如：

`http://127.0.0.1/miniprogram/campus_api/user/login_phone.php`

| 目录 | 内容 |
|------|------|
| （根） | `config.php`、`ping.php`、`upload_cover.php`、`uploads/` |
| `user/` | 登录、学号、资料、校区、地址 |
| `clothes/` | 服装增删改查 |
| `order/` | 订单、支付、核销、评价 |
| `favorite/` | 收藏 |
| `message/` | 消息会话与聊天 |
| `buyershow/` | 买家秀与评论 |
| `vip/` | 会员 |
| `seed/` | 浏览器打开灌演示数据 |
| `sql/` | 建表参考 SQL |

子目录里的 PHP 通过 `require_once __DIR__ . '/../config.php';` 引入公共配置。
