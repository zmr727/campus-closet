# CAMPUS 衣橱 — 校园服装租赁微信小程序

课程作业项目：校园场景下的服装租赁小程序，覆盖浏览、发布、下单、取还核销、评价、收藏、消息等完整闭环。

## 技术栈

- 前端：微信小程序（微信开发者工具）
- 后端：PHP 接口
- 数据库：MySQL（库名 `miniprogram`）
- 本地环境：XAMPP（Apache + MySQL）

## 功能概览

- 登录 / 学号认证
- 首页、分类、搜索、服装详情
- 发布租赁服装、草稿箱
- 订单：支付、取件核销、归还、评价
- 收藏、买家秀、消息咨询
- 简易 Web 管理后台（`admin/`）

## 目录说明

```text
github-ready/
├── miniprogram/   # 微信小程序前端（开发者工具打开此目录）
├── campus_api/    # PHP 接口
├── admin/         # 管理后台
├── sql/           # 数据库备份
├── docs/          # 实验报告与结构说明
└── README.md
```

> 本目录由作业提交包整理而来，便于上传 GitHub 或本地复现。

## 本地运行

### 1. 准备环境

1. 安装 [XAMPP](https://www.apachefriends.org/)，启动 **Apache** 和 **MySQL**
2. 安装微信开发者工具

### 2. 部署后端

将本仓库中的 `campus_api` 和 `admin` 放到 XAMPP 网站目录下，使访问路径为：

```text
http://127.0.0.1/miniprogram/campus_api/
http://127.0.0.1/miniprogram/admin/
```

常见做法：在 `C:\xampp\htdocs\` 下新建 `miniprogram` 文件夹，把 `campus_api`、`admin` 拷进去。

### 3. 导入数据库

1. 打开 phpMyAdmin（一般是 `http://127.0.0.1/phpmyadmin`）
2. 新建数据库 `miniprogram`（编码 utf8 / utf8mb4）
3. 导入 `sql/miniprogram.sql`

默认连接（见 `campus_api/config.php`）：

- 主机：`127.0.0.1`
- 用户：`root`
- 密码：空（XAMPP 默认）
- 库名：`miniprogram`

### 4. 打开小程序

1. 微信开发者工具 → 导入项目 → 选择本仓库的 `miniprogram/` 目录
2. 若使用自己的小程序账号，将 `miniprogram/project.config.json` 里的 `appid` 从 `touristappid` 改成你的 AppID（仓库内不放置真实 AppID）
3. 详情 → 本地设置 → 勾选 **不校验合法域名、web-view（业务域名）、TLS 版本以及 HTTPS 证书**
4. 编译运行

接口地址由 `miniprogram/utils/request.js` 自动拼接为：

```text
http://127.0.0.1/miniprogram/campus_api
```

可用浏览器访问 `http://127.0.0.1/miniprogram/campus_api/ping.php` 检查后端是否通。

### 5. 真机调试（可选）

1. 手机与电脑连同一 WiFi
2. 用 `ipconfig` 查看电脑 WLAN IPv4
3. 修改 `miniprogram/utils/request.js` 中的 `REAL_DEVICE_HOST`
4. 确保手机浏览器能打开该地址下的 `ping.php`

## 管理后台

浏览器打开：

```text
http://127.0.0.1/miniprogram/admin/
```

具体账号以实验报告 / 本地配置为准。

## 说明

- 本项目为课程实践作品，数据库连接使用本地开发默认配置，请勿用于生产环境。
- 公开仓库中的小程序 `appid` 使用占位值 `touristappid`，请勿把真实 AppID / AppSecret 提交到 Git。
- 大图等静态资源在 `campus_api/static/`；小程序包内主要保留图标。
- 更细的页面 ↔ 接口对应关系见 `docs/代码结构说明.md`。
