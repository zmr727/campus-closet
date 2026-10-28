-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-08-27 16:10:31
-- 服务器版本： 10.4.28-MariaDB
-- PHP 版本： 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `miniprogram`
--

-- --------------------------------------------------------

--
-- 表的结构 `buyer_shows`
--

CREATE TABLE `buyer_shows` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT '发布用户id',
  `nickname` varchar(64) DEFAULT '' COMMENT '发布者昵称',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `cover` varchar(255) NOT NULL COMMENT '实拍封面',
  `content` varchar(500) DEFAULT '' COMMENT '分享文案',
  `clothes_id` int(10) UNSIGNED DEFAULT NULL COMMENT '关联租赁衣服id，可空',
  `like_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
  `comment_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论数',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `buyer_shows`
--

INSERT INTO `buyer_shows` (`id`, `user_id`, `nickname`, `title`, `cover`, `content`, `clothes_id`, `like_count`, `comment_count`, `created_at`) VALUES
(1, NULL, '校园小桃', '买家秀·礼服', '/images/home/hot1.png', '第一次租礼服参加晚会，出片啦！', NULL, 9, 2, '2026-07-26 00:25:11'),
(2, NULL, '毕业季补给站', '买家秀·学士服', '/images/home/hot2.png', '毕业典礼现场实拍，租的超合身', NULL, 21, 2, '2026-07-26 00:25:11'),
(3, NULL, '阿宁同学', '买家秀·礼服现场', '/images/home/cal1.png', '晚会跟拍分享，推荐这家店', NULL, 7, 1, '2026-07-26 00:25:11'),
(4, NULL, '校园小桃', '买家秀·白裙出街', '/images/home/cal2.png', '日常出街穿搭，校园租赁真方便', NULL, 5, 1, '2026-07-26 00:25:11'),
(5, 1, '校园小桃1', '主持套装', 'http://127.0.0.1/miniprogram/campus_api/uploads/c_20260726101616_3666.jpg', '推荐推荐！', NULL, 0, 1, '2026-07-26 16:16:36');

-- --------------------------------------------------------

--
-- 表的结构 `buyer_show_comments`
--

CREATE TABLE `buyer_show_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `show_id` int(10) UNSIGNED NOT NULL COMMENT '买家秀id',
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT '评论用户id',
  `nickname` varchar(64) DEFAULT '' COMMENT '评论者昵称',
  `content` varchar(300) NOT NULL COMMENT '评论内容',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `buyer_show_comments`
--

INSERT INTO `buyer_show_comments` (`id`, `show_id`, `user_id`, `nickname`, `content`, `created_at`) VALUES
(1, 1, NULL, '阿月', '裙子好好看！求链接～', '2026-07-26 00:25:11'),
(2, 1, NULL, '林林', '颜色和实物一样吗？', '2026-07-26 00:25:11'),
(3, 2, NULL, '小桃', '毕业照也想租这套', '2026-07-26 00:25:11'),
(4, 2, NULL, '橙子', '尺码准不准呀', '2026-07-26 00:25:11'),
(5, 3, NULL, '米米', '晚会出片了恭喜！', '2026-07-26 00:25:11'),
(6, 4, NULL, '阿白', '白裙好清爽，想租', '2026-07-26 00:25:11'),
(7, 5, 8, '阿月', '好好看，求连接！', '2026-07-26 16:17:58');

-- --------------------------------------------------------

--
-- 表的结构 `clothes`
--

CREATE TABLE `clothes` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL COMMENT '名称',
  `cover` varchar(255) NOT NULL COMMENT '封面图路径',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '租金起/天',
  `category` varchar(50) DEFAULT NULL COMMENT '所属类型/分类',
  `size_info` varchar(100) DEFAULT NULL COMMENT '尺码',
  `tag` varchar(30) NOT NULL DEFAULT 'recommend' COMMENT 'calendar/hot/recommend/buyer/cate',
  `date_label` varchar(20) DEFAULT NULL COMMENT '日历日期如7.21',
  `intro` varchar(255) DEFAULT NULL COMMENT '简介',
  `seller` varchar(64) DEFAULT NULL COMMENT '卖家昵称',
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT '发布者用户id',
  `fav_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数',
  `available_from` date DEFAULT NULL COMMENT '可租开始日期',
  `available_to` date DEFAULT NULL COMMENT '可租结束日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `clothes`
--

INSERT INTO `clothes` (`id`, `title`, `cover`, `price`, `category`, `size_info`, `tag`, `date_label`, `intro`, `seller`, `user_id`, `fav_count`, `available_from`, `available_to`) VALUES
(1, '礼服长裙', '/images/home/cal1.png', 50.00, '礼服', 'S/M/L', 'calendar', '7.21', '粉色礼服长裙，适合晚会与拍照', '校园小桃', 1, 12, '2026-07-01', '2026-12-31'),
(2, '礼服短裙', '/images/home/cal2.png', 28.00, '礼服', 'S/M/L', 'calendar', '7.22', '白色短礼服，轻便日常可穿', '阿宁同学', 16, 8, '2026-07-01', '2026-12-31'),
(3, '正装西装', '/images/home/cal3.png', 25.00, '正装', 'M/L/XL', 'calendar', '7.23', '黑色西装外套，面试答辩适用', '学长衣柜', 17, 15, '2026-07-01', '2026-12-31'),
(4, '马面裙套装', '/images/home/new_mamianskirt.png', 26.00, '汉服', 'S/M', 'calendar', '7.24', '马面裙套装可租', '汉服铺子', 19, 36, '2026-07-01', '2026-12-31'),
(5, '汉服', '/images/home/new_hanfu.png', 40.00, '汉服', '均码可调', 'calendar', '7.25', '汉服上新可租', '夜子溪', 14, 28, '2026-07-01', '2026-12-31'),
(6, '礼服', '/images/home/hot1.jpg', 30.00, '礼服', 'S/M/L', 'hot', NULL, '热门礼服款', '校园小桃', 1, 42, '2026-07-01', '2026-12-31'),
(7, '学士服', '/images/home/hot2.jpg', 20.00, '正装', '均码', 'hot', NULL, '毕业季热租学士服', '毕业季补给站', 19, 58, '2026-07-01', '2026-12-31'),
(8, '马面裙套装', '/images/home/hot3.jpg', 26.00, '汉服', 'S/M', 'hot', NULL, '马面裙套装热租', '汉服铺子', 15, 33, '2026-07-01', '2026-12-31'),
(9, '夜子溪原创原款汉服', '/images/home/rec.jpg', 30.00, '汉服', 'S/M/L', 'recommend', NULL, '原创汉服，拍照出片', '夜子溪', 14, 65, '2026-07-01', '2026-12-31'),
(10, '魔法袍套装', '/images/home/rec_magic_robe.jpg', 35.00, 'Cos', 'S/M/L', 'recommend', NULL, '魔法袍可租，拍照出片', '阿宁同学', 16, 19, '2026-07-01', '2026-12-31'),
(11, '泰国校服套装', '/images/home/rec_thai_uniform.jpg', 28.00, '校服', 'S/M', 'recommend', NULL, '泰式校服风格可租', '校园小桃', 1, 11, '2026-07-01', '2026-12-31'),
(12, '黑色西装外套', '/images/home/rec_xizhuang_model.jpg', 24.00, '正装', 'M/L', 'recommend', NULL, '面试答辩适用', '学长衣柜', 17, 17, '2026-07-01', '2026-12-31'),
(13, '马面裙套装', '/images/home/new_mamianskirt.png', 26.00, '汉服', 'S/M', 'recommend', NULL, '马面裙套装可租', '汉服铺子', 15, 24, '2026-07-01', '2026-12-31'),
(14, '买家秀·礼服', '/images/home/hot1.jpg', 0.00, '买家秀', '', 'buyer', NULL, '礼服实拍分享', '校园小桃', 1, 9, '2026-07-01', '2026-12-31'),
(15, '买家秀·学士服', '/images/home/hot2.jpg', 0.00, '买家秀', '', 'buyer', NULL, '毕业典礼现场', '毕业季补给站', 19, 21, '2026-07-01', '2026-12-31'),
(16, '买家秀·礼服现场', '/images/home/cal1.png', 0.00, '买家秀', '', 'buyer', NULL, '晚会实拍分享', '阿宁同学', 16, 7, '2026-07-01', '2026-12-31'),
(17, '买家秀·白裙出街', '/images/home/cal2.png', 0.00, '买家秀', '', 'buyer', NULL, '日常穿搭分享', '校园小桃', 1, 5, '2026-07-01', '2026-12-31'),
(18, '新中式日常套装', '/images/home/cate_xinzhongshi.jpg', 38.00, '新中式', 'S/M/L', 'cate', NULL, '新中式改良可租', '汉服铺子', 15, 18, '2026-07-01', '2026-12-31'),
(19, '蓝色系汉服', '/images/home/rec.jpg', 50.00, '汉服', 'L', 'cate', NULL, '蓝色系汉服出片', '夜子溪', 14, 44, '2026-07-01', '2026-12-31'),
(20, '黑金配色马面裙', '/images/home/cate_mingzhi.jpg', 40.00, '明制', 'S', 'cate', NULL, '明制马面裙可租', '汉服铺子', 15, 27, '2026-07-10', '2026-09-30'),
(21, '民国旗袍套装', '/images/home/cate_minguo.jpg', 45.00, '民国风', 'S/M', 'cate', NULL, '民国风拍照可租', '阿宁同学', 16, 16, '2026-08-01', '2026-11-30'),
(23, '泰式校服毕业季拍照', '/images/home/rec_thai_uniform.jpg', 40.00, '学院风', 'S', 'cate', NULL, '泰式校服毕业拍照', '校园小桃', 1, 31, '2026-07-01', '2026-07-31'),
(24, '品牌女装通勤套', '/images/home/cate_pinpai.jpg', 42.00, '品牌女装', 'M', 'cate', NULL, '品牌女装日常可租', '学长衣柜', 17, 12, '2026-07-01', '2026-12-31'),
(25, '粉色洛丽塔套装', '/images/home/cate_lolita.jpg', 48.00, '洛丽塔', 'S/M', 'cate', NULL, '洛丽塔甜美可租', '阿宁同学', 16, 22, '2026-07-15', '2026-12-31'),
(26, '魔法袍cos套装', '/images/home/rec_magic_robe.jpg', 35.00, 'cos服', 'S/M/L', 'cate', NULL, '主题cos可租', '阿宁同学', 16, 25, '2026-07-01', '2026-12-31'),
(27, '节日主题服装', '/images/home/cate_zhuti.jpg', 36.00, '主题服装', '均码', 'cate', NULL, '活动主题装可租', '毕业季补给站', 19, 10, '2026-09-01', '2026-10-15'),
(28, '旅游拍照连衣裙', '/images/home/cate_lvyou.jpg', 39.00, '旅游拍照', 'S/M', 'cate', NULL, '出游拍照可租', '校园小桃', 1, 14, '2026-07-01', '2026-12-31'),
(29, '面试套装', '/images/home/rec_xizhuang_model.jpg', 50.00, '职业装', 'M', 'cate', NULL, '面试答辩职业装', '学长衣柜', 17, 29, '2026-07-20', '2026-08-20'),
(32, '正装套装', 'http://127.0.0.1/miniprogram/campus_api/uploads/c_20260727153907_1120.jpg', 20.00, '职业装', 'M', 'cate', NULL, '适合面试、主持等场合', '校园小桃', 1, 0, '2026-07-27', '2026-08-26'),
(33, '女士职业正装', 'http://127.0.0.1/miniprogram/campus_api/uploads/c_20260728030736_8742.jpg', 30.00, '职业装', 'M', 'cate', NULL, '蓝色系职业正装', '校园小桃1', 1, 0, '2026-07-28', '2026-08-27'),
(34, '职业正装示例2', 'http://127.0.0.1/miniprogram/campus_api/uploads/c_20260728035131_9076.jpg', 30.00, '职业装', 'M', 'cate', NULL, '111', '校园小桃1', 1, 0, '2026-07-28', '2026-08-27'),
(35, '新中式套装', 'http://127.0.0.1/miniprogram/campus_api/uploads/c_20260827152728_8902.jpg', 30.00, '新中式', 'S', 'cate', NULL, '粉色系新中式套装', '汉服铺子', 15, 0, '2026-08-27', '2026-09-26');

-- --------------------------------------------------------

--
-- 表的结构 `favorites`
--

CREATE TABLE `favorites` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `clothes_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `category` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `clothes_id`, `title`, `cover`, `price`, `category`, `created_at`) VALUES
(2, 1, 10, '魔法袍套装', '/images/home/rec_magic_robe.png', 35.00, 'Cos', '2026-07-26 11:17:45'),
(3, 2, 1, '礼服长裙', '/images/home/cal1.png', 35.00, '礼服', '2026-07-26 11:17:45'),
(4, 2, 6, '礼服', '/images/home/hot1.png', 30.00, '礼服', '2026-07-26 11:17:45'),
(5, 8, 11, '泰国校服套装', '/images/home/rec_thai_uniform.png', 28.00, '校服', '2026-07-26 11:17:45');

-- --------------------------------------------------------

--
-- 表的结构 `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL COMMENT '收件箱所属用户',
  `peer_id` int(10) UNSIGNED DEFAULT NULL COMMENT '对方用户，通知可空',
  `thread_id` int(10) UNSIGNED NOT NULL COMMENT '对话线程，双方共享',
  `msg_type` varchar(20) NOT NULL COMMENT 'buyer/mine/order/system',
  `name` varchar(64) NOT NULL COMMENT '列表显示名',
  `preview` varchar(255) NOT NULL,
  `time_label` varchar(32) DEFAULT '',
  `avatar` varchar(255) DEFAULT NULL,
  `unread` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_old` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `messages`
--

INSERT INTO `messages` (`id`, `owner_id`, `peer_id`, `thread_id`, `msg_type`, `name`, `preview`, `time_label`, `avatar`, `unread`, `is_old`, `created_at`) VALUES
(1, 1, 8, 1001, 'buyer', '阿月', '可以的', '刚刚', '/images/avatar/a2.png', 0, 0, '2026-07-26 01:04:10'),
(2, 8, 1, 1001, 'mine', '校园小桃1', '可以的', '刚刚', '/images/mine/avatar.png', 0, 0, '2026-07-26 01:04:10'),
(3, 1, 9, 1002, 'buyer', '玲玲', '汉服租期能否延长到周日？', '周四', '/images/avatar/a3.png', 0, 0, '2026-07-26 01:04:10'),
(4, 9, 1, 1002, 'mine', '校园小桃1', '汉服租期能否延长到周日？', '周四', '/images/mine/avatar.png', 0, 0, '2026-07-26 01:04:10'),
(5, 1, 10, 1003, 'buyer', '阿白', '学士服配件是否一起出租？', '周三', '/images/avatar/a4.png', 0, 0, '2026-07-26 01:04:10'),
(6, 10, 1, 1003, 'mine', '校园小桃1', '学士服配件是否一起出租？', '周三', '/images/mine/avatar.png', 0, 0, '2026-07-26 01:04:10'),
(7, 1, 11, 1004, 'buyer', '橙子', '礼服可以提前一天试穿吗？', '周二', '/images/avatar/a5.png', 0, 0, '2026-07-26 01:04:10'),
(8, 11, 1, 1004, 'mine', '校园小桃1', '礼服可以提前一天试穿吗？', '周二', '/images/mine/avatar.png', 0, 0, '2026-07-26 01:04:10'),
(9, 1, 12, 1005, 'buyer', '米米', '想租粉色汉服，周末有空吗？', '周一', '/images/avatar/a6.png', 0, 0, '2026-07-26 01:04:10'),
(10, 12, 1, 1005, 'mine', '校园小桃1', '想租粉色汉服，周末有空吗？', '周一', '/images/mine/avatar.png', 0, 0, '2026-07-26 01:04:10'),
(11, 1, 13, 1006, 'buyer', '小鱼', '尺码不确定，能帮忙看看吗？', '周一', '/images/avatar/a1.png', 0, 0, '2026-07-26 01:04:10'),
(12, 13, 1, 1006, 'mine', '校园小桃1', '尺码不确定，能帮忙看看吗？', '周一', '/images/mine/avatar.png', 0, 0, '2026-07-26 01:04:10'),
(13, 1, 16, 1009, 'buyer', '阿柠', 'Lolita裙押金怎么退？', '上周', '/images/avatar/a8.png', 0, 1, '2026-07-26 01:04:10'),
(14, 16, 1, 1009, 'mine', '校园小桃1', 'Lolita裙押金怎么退？', '上周', '/images/mine/avatar.png', 0, 0, '2026-07-26 01:04:10'),
(15, 1, 17, 1010, 'buyer', '学长衣柜', '111', '刚刚', '/images/avatar/a7.png', 0, 0, '2026-07-26 01:04:10'),
(16, 17, 1, 1010, 'mine', '校园小桃1', '111', '刚刚', '/images/mine/avatar.png', 0, 0, '2026-07-26 01:04:10'),
(17, 2, 14, 1007, 'mine', '夜子溪', '你好，蓝色系汉服还有 M 码吗？', '今天', '/images/avatar/a9.png', 0, 0, '2026-07-26 01:04:10'),
(18, 14, 2, 1007, 'buyer', '测试用户', '你好，蓝色系汉服还有 M 码吗？', '今天', '/images/avatar/a8.png', 1, 0, '2026-07-26 01:04:10'),
(19, 2, 15, 1008, 'mine', '汉服铺子', '马面裙这周末可以取衣吗？', '昨天', '/images/avatar/a4.png', 1, 0, '2026-07-26 01:04:10'),
(20, 15, 2, 1008, 'buyer', '测试用户', '马面裙这周末可以取衣吗？', '昨天', '/images/avatar/a8.png', 0, 0, '2026-07-26 01:04:10'),
(21, 1, NULL, 2001, 'order', '订单通知', '你的租赁订单已确认，请按时取衣', '周一', '/images/avatar/a9.png', 0, 0, '2026-07-26 01:04:10'),
(22, 1, NULL, 2002, 'system', '系统通知', '发布内容审核通过，已上架展示', '周一', '/images/avatar/a10.png', 0, 0, '2026-07-26 01:04:10'),
(23, 2, NULL, 2003, 'order', '订单通知', '归还提醒：学士服将于明日到期', '今天', '/images/avatar/a9.png', 1, 0, '2026-07-26 01:04:10'),
(24, 2, NULL, 2004, 'system', '系统通知', '欢迎使用 CAMPUS 衣橱消息', '今天', '/images/avatar/a10.png', 0, 0, '2026-07-26 01:04:10'),
(25, 2, 1, 2005, 'mine', '校园小桃1', 'hello consult test', '刚刚', '/images/mine/avatar.png', 0, 0, '2026-07-27 22:04:07'),
(26, 1, 2, 2005, 'buyer', '测试用户', 'hello consult test', '刚刚', '/images/avatar/a8.png', 1, 0, '2026-07-27 22:04:07'),
(27, 1, 15, 2006, 'mine', '汉服铺子', '111', '刚刚', '/images/avatar/a4.png', 0, 0, '2026-07-27 22:07:09'),
(28, 15, 1, 2006, 'buyer', '校园小桃1', '111', '刚刚', '/images/mine/avatar.png', 0, 0, '2026-07-27 22:07:09'),
(29, 5, 16, 2007, 'mine', '阿宁同学', '你好，阿宁同学，我想咨询「民国旗袍套装」的租赁细节', '刚刚', '/images/avatar/a8.png', 0, 0, '2026-08-27 19:16:44'),
(30, 16, 5, 2007, 'buyer', '琳琳', '你好，阿宁同学，我想咨询「民国旗袍套装」的租赁细节', '刚刚', '/images/avatar/a1.png', 1, 0, '2026-08-27 19:16:44');

-- --------------------------------------------------------

--
-- 表的结构 `message_chats`
--

CREATE TABLE `message_chats` (
  `id` int(10) UNSIGNED NOT NULL,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `content` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `message_chats`
--

INSERT INTO `message_chats` (`id`, `thread_id`, `sender_id`, `content`, `created_at`) VALUES
(1, 1001, 8, 'JK套装还可以再便宜一点吗？', '2026-07-26 01:04:10'),
(2, 1001, 1, '可以的，周末活动价再减 5 元～', '2026-07-26 01:04:10'),
(3, 1001, 8, '好的，那我周末来取', '2026-07-26 01:04:10'),
(4, 1002, 9, '汉服租期能否延长到周日？', '2026-07-26 01:04:10'),
(5, 1002, 1, '可以延长，补一天租金就行', '2026-07-26 01:04:10'),
(6, 1003, 10, '学士服配件是否一起出租？', '2026-07-26 01:04:10'),
(7, 1003, 1, '帽子绶带都可以一起租哦', '2026-07-26 01:04:10'),
(8, 1004, 11, '礼服可以提前一天试穿吗？', '2026-07-26 01:04:10'),
(9, 1005, 12, '想租粉色汉服，周末有空吗？', '2026-07-26 01:04:10'),
(10, 1006, 13, '尺码不确定，能帮忙看看吗？', '2026-07-26 01:04:10'),
(11, 1009, 16, 'Lolita裙押金怎么退？', '2026-07-26 01:04:10'),
(12, 1009, 1, '归还检查无误后原路退回', '2026-07-26 01:04:10'),
(13, 1010, 17, '毕业照想租学士服，还有货吗？', '2026-07-26 01:04:10'),
(14, 1007, 2, '你好，蓝色系汉服还有 M 码吗？', '2026-07-26 01:04:10'),
(15, 1007, 14, '有的，这周末可以取', '2026-07-26 01:04:10'),
(16, 1008, 2, '马面裙这周末可以取衣吗？', '2026-07-26 01:04:10'),
(17, 1008, 15, '可以，周六下午驿站见', '2026-07-26 01:04:10'),
(18, 2001, 1, '你的租赁订单已确认，请按时取衣', '2026-07-26 01:04:10'),
(19, 2002, 1, '发布内容审核通过，已上架展示', '2026-07-26 01:04:10'),
(20, 2003, 2, '归还提醒：学士服将于明日到期', '2026-07-26 01:04:10'),
(21, 2004, 2, '欢迎使用 CAMPUS 衣橱消息', '2026-07-26 01:04:10'),
(22, 1001, 1, '可以的', '2026-07-26 15:57:58'),
(23, 2005, 2, 'hello consult test', '2026-07-27 22:04:07'),
(24, 2006, 1, '你好，汉服铺子，我想咨询「黑金配色马面裙」的租赁细节', '2026-07-27 22:07:09'),
(25, 2006, 15, '111', '2026-07-27 22:09:51'),
(26, 1010, 1, '你好，学长衣柜，我想咨询「正装西装」的租赁细节', '2026-07-28 09:17:37'),
(27, 1010, 17, '111', '2026-07-28 09:19:00'),
(28, 2007, 5, '你好，阿宁同学，我想咨询「民国旗袍套装」的租赁细节', '2026-08-27 19:16:44');

-- --------------------------------------------------------

--
-- 表的结构 `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_no` varchar(32) NOT NULL COMMENT '同一笔交易买卖双方共享',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '订单所属用户（各自收件箱）',
  `peer_id` int(10) UNSIGNED DEFAULT NULL COMMENT '对方用户id',
  `clothes_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `size_info` varchar(32) DEFAULT NULL,
  `days` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `side` varchar(10) NOT NULL COMMENT 'rent|lend',
  `status` varchar(20) NOT NULL COMMENT 'rent:pay/pickup/renting/return/review; lend:pay/deliver/lending/reclaim/rated',
  `buyer_name` varchar(64) DEFAULT NULL COMMENT '出租侧租客名',
  `seller_name` varchar(64) DEFAULT NULL COMMENT '租用侧卖家名',
  `pickup_code` varchar(16) DEFAULT NULL COMMENT '取件码',
  `return_code` varchar(16) DEFAULT NULL COMMENT '归还核销码',
  `station` varchar(64) DEFAULT NULL COMMENT '取件驿站',
  `paid_at` datetime DEFAULT NULL,
  `picked_at` datetime DEFAULT NULL COMMENT '取衣时间',
  `returned_at` datetime DEFAULT NULL COMMENT '归还时间',
  `finished_at` datetime DEFAULT NULL COMMENT '完成时间',
  `review_score` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '我对对方的评分1-5',
  `review_content` varchar(255) DEFAULT NULL COMMENT '评价内容',
  `review_tags` varchar(255) DEFAULT NULL COMMENT '评价标签',
  `reviewed_at` datetime DEFAULT NULL COMMENT '我方评价时间',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `orders`
--

INSERT INTO `orders` (`id`, `order_no`, `user_id`, `peer_id`, `clothes_id`, `title`, `cover`, `price`, `size_info`, `days`, `total_price`, `side`, `status`, `buyer_name`, `seller_name`, `pickup_code`, `return_code`, `station`, `paid_at`, `picked_at`, `returned_at`, `finished_at`, `review_score`, `review_content`, `review_tags`, `reviewed_at`, `created_at`) VALUES
(1, 'ON1785035865A', 2, 1, 1, '礼服长裙', '/images/home/cal1.png', 35.00, 'M', 2, 70.00, 'rent', 'pay', NULL, '校园小桃', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-26 04:17:45'),
(2, 'ON1785035865A', 1, 2, 1, '礼服长裙', '/images/home/cal1.png', 35.00, 'M', 2, 70.00, 'lend', 'pay', '测试用户', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-26 04:17:45'),
(3, 'ON1785035865B', 8, 1, 6, '礼服', '/images/home/hot1.png', 30.00, 'S', 3, 90.00, 'rent', 'pickup', NULL, '校园小桃', '958399', NULL, '大学城南驿站', '2026-07-25 07:04:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-25 05:17:45'),
(4, 'ON1785035865B', 1, 8, 6, '礼服', '/images/home/hot1.png', 30.00, 'S', 3, 90.00, 'lend', 'deliver', '阿月', NULL, '958399', NULL, '大学城南驿站', '2026-07-25 07:04:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-25 05:17:45'),
(5, 'ON1785035865C', 1, 14, 9, '夜子溪原创原款汉服', '/images/home/rec.png', 30.00, 'M', 5, 150.00, 'rent', 'review', NULL, '夜子溪', '078065', '995604', '图书馆东侧驿站', '2026-07-24 05:17:45', NULL, '2026-07-26 19:11:20', '2026-07-28 09:55:00', 5, '111', '描述相符', '2026-07-28 09:55:00', '2026-07-24 05:17:45'),
(6, 'ON1785035865C', 14, 1, 9, '夜子溪原创原款汉服', '/images/home/rec.png', 30.00, 'M', 5, 150.00, 'lend', 'rated', '校园小桃', NULL, '078065', '995604', '图书馆东侧驿站', '2026-07-24 05:17:45', NULL, '2026-07-26 19:11:20', '2026-07-26 19:11:33', 5, '租客很好', '爱护衣物,按时归还,沟通友好,守约诚信,欢迎再租', '2026-07-26 19:11:33', '2026-07-24 05:17:45'),
(7, 'ON1785035865D', 2, 1, 11, '泰国校服套装', '/images/home/rec_thai_uniform.png', 28.00, 'S', 2, 56.00, 'rent', 'review', NULL, '校园小桃', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-26 18:04:31', 5, '衣物成色很好，交接顺利，下次还想租。', '衣物完好,交接方便,沟通顺畅', '2026-07-26 18:04:31', '2026-07-26 03:17:45'),
(8, 'ON1785035865D', 1, 2, 11, '泰国校服套装', '/images/home/rec_thai_uniform.png', 28.00, 'S', 2, 56.00, 'lend', 'rated', '测试用户', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-26 18:04:31', 5, '租客很爱护衣物，按时归还，体验很好。', '爱护衣物,按时归还,沟通友好', '2026-07-26 18:04:31', '2026-07-26 03:17:45'),
(9, 'ON20260726093436997', 1, 16, 10, '魔法袍套装', '/images/home/rec_magic_robe.png', 35.00, 'S', 1, 35.00, 'rent', 'review', NULL, '阿柠', '544650', '544586', '一食堂取还点', '2026-07-26 15:34:42', '2026-07-26 17:44:37', '2026-07-26 17:46:30', '2026-07-26 18:04:31', 5, '衣物成色很好，交接顺利，下次还想租。', '衣物完好,交接方便,沟通顺畅', '2026-07-26 18:04:31', '2026-07-26 15:34:36'),
(10, 'ON20260726093436997', 16, 1, 10, '魔法袍套装', '/images/home/rec_magic_robe.png', 35.00, 'S', 1, 35.00, 'lend', 'rated', '校园小桃', NULL, '544650', '544586', '一食堂取还点', '2026-07-26 15:34:42', '2026-07-26 17:44:37', '2026-07-26 17:46:30', '2026-07-26 18:04:31', 5, '租客很爱护衣物，按时归还，体验很好。', '爱护衣物,按时归还,沟通友好', '2026-07-26 18:04:31', '2026-07-26 15:34:36'),
(11, 'ON20260727155645448', 1, 15, 20, '黑金配色马面裙', '/images/home/cate_mingzhi.png', 40.00, 'S', 1, 40.00, 'rent', 'review', NULL, '汉服铺子', '410139', '699374', '一食堂取还点', '2026-07-27 22:07:47', '2026-07-27 22:10:17', '2026-07-27 22:12:10', '2026-07-27 22:13:58', 5, '衣服很好', '描述相符', '2026-07-27 22:13:58', '2026-07-27 21:56:45'),
(12, 'ON20260727155645448', 15, 1, 20, '黑金配色马面裙', '/images/home/cate_mingzhi.png', 40.00, 'S', 1, 40.00, 'lend', 'rated', '校园小桃1', NULL, '410139', '699374', '一食堂取还点', '2026-07-27 22:07:47', '2026-07-27 22:10:17', '2026-07-27 22:12:10', '2026-07-27 22:12:35', 5, '租客很好', '爱护衣物', '2026-07-27 22:12:35', '2026-07-27 21:56:45'),
(13, 'ON20260728031640157', 1, 17, 3, '正装西装', '/images/home/cal3.png', 25.00, 'M', 1, 25.00, 'rent', 'return', NULL, '学长衣柜', '291738', '029135', '一食堂取还点', '2026-07-28 09:16:48', '2026-07-28 09:19:12', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-28 09:16:40'),
(14, 'ON20260728031640157', 17, 1, 3, '正装西装', '/images/home/cal3.png', 25.00, 'M', 1, 25.00, 'lend', 'reclaim', '校园小桃1', NULL, '291738', '029135', '一食堂取还点', '2026-07-28 09:16:48', '2026-07-28 09:19:12', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-28 09:16:40'),
(15, 'ON20260728035240783', 1, 15, 13, '马面裙套装', '/images/home/new_mamianskirt.png', 26.00, 'S', 1, 26.00, 'rent', 'pickup', NULL, '汉服铺子', '396951', NULL, '图书馆东侧驿站', '2026-07-28 09:52:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-28 09:52:40'),
(16, 'ON20260728035240783', 15, 1, 13, '马面裙套装', '/images/home/new_mamianskirt.png', 26.00, 'S', 1, 26.00, 'lend', 'deliver', '校园小桃1', NULL, '396951', NULL, '图书馆东侧驿站', '2026-07-28 09:52:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-28 09:52:40'),
(17, 'ON20260827151457426', 1, 15, 18, '新中式日常套装', '/images/home/cate_xinzhongshi.jpg', 38.00, 'S', 1, 38.00, 'rent', 'review', NULL, '汉服铺子', '610790', '894192', '一食堂取还点', '2026-08-27 21:15:39', '2026-08-27 21:20:02', '2026-08-27 21:22:35', NULL, NULL, NULL, NULL, NULL, '2026-08-27 21:14:57'),
(18, 'ON20260827151457426', 15, 1, 18, '新中式日常套装', '/images/home/cate_xinzhongshi.jpg', 38.00, 'S', 1, 38.00, 'lend', 'rated', '校园小桃1', NULL, '610790', '894192', '一食堂取还点', '2026-08-27 21:15:39', '2026-08-27 21:20:02', '2026-08-27 21:22:35', '2026-08-27 21:23:32', 5, '买家很好', '爱护衣物,按时归还', '2026-08-27 21:23:32', '2026-08-27 21:14:57');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `openid` varchar(64) DEFAULT NULL COMMENT '微信openid(演示)',
  `login_type` varchar(16) DEFAULT NULL COMMENT 'phone|wechat',
  `password` varchar(255) DEFAULT NULL,
  `nickname` varchar(64) DEFAULT NULL,
  `real_name` varchar(64) DEFAULT NULL COMMENT '真实姓名',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像路径',
  `student_id` varchar(32) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL COMMENT '校内交接地址',
  `campus` varchar(64) DEFAULT NULL COMMENT '当前校区'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `phone`, `openid`, `login_type`, `password`, `nickname`, `real_name`, `avatar`, `student_id`, `address`, `campus`) VALUES
(1, '13800000001', '13800000001', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '校园小桃1', '张小桃', '/images/mine/avatar.png', '2023001', '梅园3号楼 201宿舍', '大学城校区'),
(2, '13800000002', '13800000002', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '测试用户', '王测试', '/images/avatar/a8.png', NULL, '兰园5号楼 412宿舍', '大学城校区'),
(3, 'wx_8532f459067debb4', NULL, 'wx_8532f459067debb4', 'wechat', NULL, '朱梦茹', NULL, '/images/avatar/a1.png', '2024116', NULL, NULL),
(4, '13900001111', '13900001111', NULL, 'phone', NULL, '139****1111', NULL, '/images/avatar/a1.png', NULL, NULL, NULL),
(5, '13092373802', '13092373802', NULL, 'phone', NULL, '琳琳', NULL, '/images/avatar/a1.png', '2024117', NULL, NULL),
(6, 'wx_1862804a5931d7b8', NULL, 'wx_1862804a5931d7b8', 'wechat', NULL, '朱梦茹', NULL, '/images/avatar/a1.png', '2024116', NULL, NULL),
(7, 'wx_61aea7f605bcdbd7', NULL, 'wx_61aea7f605bcdbd7', 'wechat', NULL, '一一', NULL, '/images/avatar/a1.png', '2024101', NULL, NULL),
(8, '13800000003', '13800000003', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '阿月', '李阿月', '/images/avatar/a2.png', '2023003', '竹园2号楼 108宿舍', '大学城校区'),
(9, '13800000004', '13800000004', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '玲玲', '陈玲玲', '/images/avatar/a3.png', '2023004', '梅园1号楼 305宿舍', '五山校区'),
(10, '13800000005', '13800000005', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '阿白', '白小白', '/images/avatar/a4.png', NULL, '兰园3号楼 210宿舍', '大学城校区'),
(11, '13800000006', '13800000006', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '橙子', '黄橙子', '/images/avatar/a5.png', NULL, '菊园6号楼 401宿舍', '南校区'),
(12, '13800000007', '13800000007', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '米米', '周米米', '/images/avatar/a6.png', NULL, '梅园4号楼 112宿舍', '大学城校区'),
(13, '13800000008', '13800000008', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '小鱼', '刘小鱼', '/images/avatar/a1.png', NULL, '兰园8号楼 509宿舍', '国际校区'),
(14, '13800000009', '13800000009', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '夜子溪', '叶子', '/images/avatar/a9.png', '2024333', '竹园5号楼 220宿舍', '大学城校区'),
(15, '13800000010', '13800000010', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '汉服铺子', '李李', '/images/avatar/a4.png', '2022111', NULL, NULL),
(16, '13800000011', '13800000011', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '阿宁同学', '宁阿宁', '/images/avatar/a8.png', '2023011', '梅园2号楼 318宿舍', '大学城校区'),
(17, '13800000012', '13800000012', NULL, 'phone', '$2y$10$Vjc08XGT33uvPXKF4iPfauxowOQL7KISDGw6sW/K.EasP2j8LLjUC', '学长衣柜', '孙学长', '/images/avatar/a7.png', '2020111', '兰园1号楼 101宿舍', '五山校区'),
(18, 'wx_2f2aa7c10a507549', NULL, 'wx_2f2aa7c10a507549', 'wechat', NULL, '张三', NULL, NULL, '2024111', NULL, NULL),
(19, '13800000013', '13800000013', NULL, 'phone', '$2y$10$0Qev/WBK3MBXgkPZ1An7S.5AaYymCi63YEojvQr9T3TMb6PkwSEDq', '毕业季补给站', '郑补给', '/images/avatar/a10.png', NULL, '西门驿站旁', '大学城校区'),
(20, '13800000015', '13800000015', NULL, 'phone', '$2y$10$QQr3W6/tPF/uFBpcn1MTsO9aLg5R7Q2SpZpDRp4RDhR56CZxxpPsO', '汉服铺子', '吴汉服', '/images/avatar/a9.png', NULL, '东门商业街', '大学城校区'),
(21, '13800001111', '13800001111', NULL, 'phone', NULL, '138****1111', '王王', NULL, '2021111', NULL, NULL),
(22, 'wx_2c2bb099d4bad0da', NULL, 'wx_2c2bb099d4bad0da', 'wechat', NULL, '微信用户', '朱昱豪', NULL, '2019777', NULL, NULL),
(23, 'wx_9b9c41a108e12348', NULL, 'wx_9b9c41a108e12348', 'wechat', NULL, '微信用户', '朱雪成', NULL, '2018999', NULL, NULL),
(24, 'wx_a396045fe6b90892', NULL, 'wx_a396045fe6b90892', 'wechat', NULL, '微信用户', NULL, NULL, NULL, NULL, NULL),
(25, '18052323465', '18052323465', NULL, 'phone', NULL, '180****3465', NULL, NULL, NULL, NULL, NULL),
(26, '13011115678', '13011115678', NULL, 'phone', NULL, '130****5678', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `vip_members`
--

CREATE TABLE `vip_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `plan` varchar(32) NOT NULL DEFAULT 'semester' COMMENT 'semester/year',
  `opened_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expire_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `vip_members`
--

INSERT INTO `vip_members` (`id`, `user_id`, `plan`, `opened_at`, `expire_at`) VALUES
(1, 2, 'semester', '2026-07-26 11:17:45', '2026-11-23 11:17:45'),
(2, 1, 'semester', '2026-07-26 15:54:11', '2026-11-23 15:54:11');

--
-- 转储表的索引
--

--
-- 表的索引 `buyer_shows`
--
ALTER TABLE `buyer_shows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_clothes` (`clothes_id`);

--
-- 表的索引 `buyer_show_comments`
--
ALTER TABLE `buyer_show_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_show` (`show_id`);

--
-- 表的索引 `clothes`
--
ALTER TABLE `clothes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tag` (`tag`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_available` (`available_from`,`available_to`);

--
-- 表的索引 `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_clothes` (`user_id`,`clothes_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- 表的索引 `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_owner_type` (`owner_id`,`msg_type`),
  ADD KEY `idx_thread` (`thread_id`);

--
-- 表的索引 `message_chats`
--
ALTER TABLE `message_chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_thread` (`thread_id`);

--
-- 表的索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_side_status` (`user_id`,`side`,`status`),
  ADD KEY `idx_order_no` (`order_no`),
  ADD KEY `idx_clothes` (`clothes_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_username` (`username`);

--
-- 表的索引 `vip_members`
--
ALTER TABLE `vip_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user` (`user_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `buyer_shows`
--
ALTER TABLE `buyer_shows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `buyer_show_comments`
--
ALTER TABLE `buyer_show_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `clothes`
--
ALTER TABLE `clothes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- 使用表AUTO_INCREMENT `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- 使用表AUTO_INCREMENT `message_chats`
--
ALTER TABLE `message_chats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- 使用表AUTO_INCREMENT `vip_members`
--
ALTER TABLE `vip_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
