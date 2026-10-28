<?php
/**
 * 建买家秀模块表并写入演示数据
 * 浏览器打开一次：http://127.0.0.1/miniprogram/campus_api/seed/seed_buyer_shows.php
 *
 * buyer_shows          买家秀帖子（独立于 clothes）
 * buyer_show_comments  买家秀评论
 */
require_once __DIR__ . '/../config.php';

$conn = db_connect();

mysqli_query($conn, 'DROP TABLE IF EXISTS buyer_show_comments');
mysqli_query($conn, 'DROP TABLE IF EXISTS buyer_shows');

$sqlShows = "CREATE TABLE buyer_shows (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL COMMENT '发布用户id',
  nickname VARCHAR(64) DEFAULT '' COMMENT '发布者昵称',
  title VARCHAR(100) NOT NULL COMMENT '标题',
  cover VARCHAR(255) NOT NULL COMMENT '实拍封面',
  content VARCHAR(500) DEFAULT '' COMMENT '分享文案',
  clothes_id INT UNSIGNED DEFAULT NULL COMMENT '关联租赁衣服id，可空',
  like_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
  comment_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论数',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  KEY idx_clothes (clothes_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!mysqli_query($conn, $sqlShows)) {
    json_err('建 buyer_shows 失败: ' . mysqli_error($conn));
}

$sqlComments = "CREATE TABLE buyer_show_comments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  show_id INT UNSIGNED NOT NULL COMMENT '买家秀id',
  user_id INT UNSIGNED DEFAULT NULL COMMENT '评论用户id',
  nickname VARCHAR(64) DEFAULT '' COMMENT '评论者昵称',
  content VARCHAR(300) NOT NULL COMMENT '评论内容',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_show (show_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!mysqli_query($conn, $sqlComments)) {
    json_err('建 buyer_show_comments 失败: ' . mysqli_error($conn));
}

$shows = array(
    array(null, '校园小桃', '买家秀·礼服', '/images/home/hot1.png', '第一次租礼服参加晚会，出片啦！', 9, null),
    array(null, '毕业季补给站', '买家秀·学士服', '/images/home/hot2.png', '毕业典礼现场实拍，租的超合身', 21, null),
    array(null, '阿宁同学', '买家秀·礼服现场', '/images/home/cal1.png', '晚会跟拍分享，推荐这家店', 7, null),
    array(null, '校园小桃', '买家秀·白裙出街', '/images/home/cal2.png', '日常出街穿搭，校园租赁真方便', 5, null),
);

$n = 0;
$showIds = array();
foreach ($shows as $r) {
    $uid = $r[0] === null ? 'NULL' : intval($r[0]);
    $nick = mysqli_real_escape_string($conn, $r[1]);
    $title = mysqli_real_escape_string($conn, $r[2]);
    $cover = mysqli_real_escape_string($conn, $r[3]);
    $content = mysqli_real_escape_string($conn, $r[4]);
    $likes = intval($r[5]);
    $cid = $r[6] === null ? 'NULL' : intval($r[6]);
    $q = "INSERT INTO buyer_shows (user_id, nickname, title, cover, content, clothes_id, like_count, comment_count)
          VALUES ({$uid}, '{$nick}', '{$title}', '{$cover}', '{$content}', {$cid}, {$likes}, 0)";
    if (mysqli_query($conn, $q)) {
        $showIds[] = intval(mysqli_insert_id($conn));
        $n++;
    }
}

$demoComments = array(
    array(0, '阿月', '裙子好好看！求链接～'),
    array(0, '林林', '颜色和实物一样吗？'),
    array(1, '小桃', '毕业照也想租这套'),
    array(1, '橙子', '尺码准不准呀'),
    array(2, '米米', '晚会出片了恭喜！'),
    array(3, '阿白', '白裙好清爽，想租'),
);

$cn = 0;
foreach ($demoComments as $c) {
    $idx = $c[0];
    if (!isset($showIds[$idx])) continue;
    $sid = $showIds[$idx];
    $nick = mysqli_real_escape_string($conn, $c[1]);
    $text = mysqli_real_escape_string($conn, $c[2]);
    $q = "INSERT INTO buyer_show_comments (show_id, user_id, nickname, content)
          VALUES ({$sid}, NULL, '{$nick}', '{$text}')";
    if (mysqli_query($conn, $q)) {
        $cn++;
        mysqli_query($conn, "UPDATE buyer_shows SET comment_count = comment_count + 1 WHERE id={$sid}");
    }
}

mysqli_close($conn);

json_ok(array(
    'shows' => $n,
    'comments' => $cn,
    'tables' => array('buyer_shows', 'buyer_show_comments'),
), '买家秀模块表已创建并写入演示数据');
