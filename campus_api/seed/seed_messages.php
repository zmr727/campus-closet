<?php
/**
 * 差异化消息：每人只看自己收件箱
 * owner_id = 收件箱主人
 * thread_id = 双方共享的对话（聊天记录挂在 thread 上）
 *
 * 演示账号（密码 123456）：
 *   13800000001 校园小桃 —— 主要看「买家咨询」
 *   13800000002 测试用户 —— 主要看「我的咨询」
 *
 * 浏览器打开：http://127.0.0.1/miniprogram/campus_api/seed/seed_messages.php
 */
require_once __DIR__ . '/../config.php';

$conn = db_connect();

$col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'avatar'");
if (!$col || mysqli_num_rows($col) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL COMMENT '头像路径' AFTER nickname");
}

$pwd = mysqli_real_escape_string($conn, password_hash('123456', PASSWORD_DEFAULT));

function upsert_user($conn, $username, $nickname, $avatar, $student, $pwd) {
    $u = mysqli_real_escape_string($conn, $username);
    $n = mysqli_real_escape_string($conn, $nickname);
    $a = mysqli_real_escape_string($conn, $avatar);
    $s = $student === null ? 'NULL' : ("'" . mysqli_real_escape_string($conn, $student) . "'");
    $res = mysqli_query($conn, "SELECT id FROM users WHERE username='{$u}' LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $id = intval($row['id']);
        mysqli_query($conn, "UPDATE users SET nickname='{$n}', avatar='{$a}', student_id={$s}, password='{$pwd}' WHERE id={$id}");
        return $id;
    }
    mysqli_query($conn, "INSERT INTO users (username, password, nickname, avatar, student_id)
                         VALUES ('{$u}', '{$pwd}', '{$n}', '{$a}', {$s})");
    return intval(mysqli_insert_id($conn));
}

$uidTao = upsert_user($conn, '13800000001', '校园小桃', '/images/avatar/a7.png', '2023001', $pwd);
$uidTest = upsert_user($conn, '13800000002', '测试用户', '/images/avatar/a8.png', null, $pwd);
$uidYue = upsert_user($conn, '13800000003', '阿月', '/images/avatar/a2.png', '2023003', $pwd);
$uidLing = upsert_user($conn, '13800000004', '玲玲', '/images/avatar/a3.png', '2023004', $pwd);
$uidBai = upsert_user($conn, '13800000005', '阿白', '/images/avatar/a4.png', null, $pwd);
$uidOrange = upsert_user($conn, '13800000006', '橙子', '/images/avatar/a5.png', null, $pwd);
$uidMimi = upsert_user($conn, '13800000007', '米米', '/images/avatar/a6.png', null, $pwd);
$uidYu = upsert_user($conn, '13800000008', '小鱼', '/images/avatar/a1.png', null, $pwd);
$uidYe = upsert_user($conn, '13800000009', '夜子溪', '/images/avatar/a9.png', null, $pwd);
$uidHan = upsert_user($conn, '13800000010', '汉服铺子', '/images/avatar/a4.png', null, $pwd);

mysqli_query($conn, 'DROP TABLE IF EXISTS message_chats');
mysqli_query($conn, 'DROP TABLE IF EXISTS messages');

$sql = "CREATE TABLE messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id INT UNSIGNED NOT NULL COMMENT '收件箱所属用户',
  peer_id INT UNSIGNED DEFAULT NULL COMMENT '对方用户，通知可空',
  thread_id INT UNSIGNED NOT NULL COMMENT '对话线程，双方共享',
  msg_type VARCHAR(20) NOT NULL COMMENT 'buyer/mine/order/system',
  name VARCHAR(64) NOT NULL COMMENT '列表显示名',
  preview VARCHAR(255) NOT NULL,
  time_label VARCHAR(32) DEFAULT '',
  avatar VARCHAR(255) DEFAULT NULL,
  unread INT UNSIGNED NOT NULL DEFAULT 0,
  is_old TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_owner_type (owner_id, msg_type),
  KEY idx_thread (thread_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!mysqli_query($conn, $sql)) {
    json_err('建 messages 失败: ' . mysqli_error($conn));
}

$sqlChat = "CREATE TABLE message_chats (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  thread_id INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NOT NULL,
  content VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_thread (thread_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!mysqli_query($conn, $sqlChat)) {
    json_err('建 message_chats 失败: ' . mysqli_error($conn));
}

function add_inbox($conn, $owner, $peer, $thread, $type, $name, $preview, $time, $avatar, $unread, $old) {
    $name = mysqli_real_escape_string($conn, $name);
    $preview = mysqli_real_escape_string($conn, $preview);
    $time = mysqli_real_escape_string($conn, $time);
    $avatar = mysqli_real_escape_string($conn, $avatar);
    $peerSql = $peer ? intval($peer) : 'NULL';
    $q = "INSERT INTO messages (owner_id, peer_id, thread_id, msg_type, name, preview, time_label, avatar, unread, is_old)
          VALUES (" . intval($owner) . ", {$peerSql}, " . intval($thread) . ", '{$type}', '{$name}', '{$preview}', '{$time}', '{$avatar}', " . intval($unread) . ", " . intval($old) . ")";
    return mysqli_query($conn, $q);
}

function add_chat($conn, $thread, $sender, $content) {
    $content = mysqli_real_escape_string($conn, $content);
    return mysqli_query($conn, "INSERT INTO message_chats (thread_id, sender_id, content)
                                VALUES (" . intval($thread) . ", " . intval($sender) . ", '{$content}')");
}

// ===== 线程 1001：阿月 ↔ 校园小桃 =====
$t = 1001;
add_inbox($conn, $uidTao, $uidYue, $t, 'buyer', '阿月', 'JK套装还可以再便宜一点吗？', '15:43', '/images/avatar/a2.png', 1, 0);
add_inbox($conn, $uidYue, $uidTao, $t, 'mine', '校园小桃', 'JK套装还可以再便宜一点吗？', '15:43', '/images/avatar/a7.png', 0, 0);
add_chat($conn, $t, $uidYue, 'JK套装还可以再便宜一点吗？');
add_chat($conn, $t, $uidTao, '可以的，周末活动价再减 5 元～');
add_chat($conn, $t, $uidYue, '好的，那我周末来取');

// ===== 线程 1002：玲玲 ↔ 校园小桃 =====
$t = 1002;
add_inbox($conn, $uidTao, $uidLing, $t, 'buyer', '玲玲', '汉服租期能否延长到周日？', '周四', '/images/avatar/a3.png', 0, 0);
add_inbox($conn, $uidLing, $uidTao, $t, 'mine', '校园小桃', '汉服租期能否延长到周日？', '周四', '/images/avatar/a7.png', 0, 0);
add_chat($conn, $t, $uidLing, '汉服租期能否延长到周日？');
add_chat($conn, $t, $uidTao, '可以延长，补一天租金就行');

// ===== 线程 1003：阿白 ↔ 校园小桃 =====
$t = 1003;
add_inbox($conn, $uidTao, $uidBai, $t, 'buyer', '阿白', '学士服配件是否一起出租？', '周三', '/images/avatar/a4.png', 0, 0);
add_inbox($conn, $uidBai, $uidTao, $t, 'mine', '校园小桃', '学士服配件是否一起出租？', '周三', '/images/avatar/a7.png', 0, 0);
add_chat($conn, $t, $uidBai, '学士服配件是否一起出租？');
add_chat($conn, $t, $uidTao, '帽子绶带都可以一起租哦');

// ===== 线程 1004：橙子 ↔ 校园小桃 =====
$t = 1004;
add_inbox($conn, $uidTao, $uidOrange, $t, 'buyer', '橙子', '礼服可以提前一天试穿吗？', '周二', '/images/avatar/a5.png', 0, 0);
add_inbox($conn, $uidOrange, $uidTao, $t, 'mine', '校园小桃', '礼服可以提前一天试穿吗？', '周二', '/images/avatar/a7.png', 0, 0);
add_chat($conn, $t, $uidOrange, '礼服可以提前一天试穿吗？');

// ===== 线程 1005：米米 ↔ 校园小桃 =====
$t = 1005;
add_inbox($conn, $uidTao, $uidMimi, $t, 'buyer', '米米', '想租粉色汉服，周末有空吗？', '周一', '/images/avatar/a6.png', 0, 0);
add_inbox($conn, $uidMimi, $uidTao, $t, 'mine', '校园小桃', '想租粉色汉服，周末有空吗？', '周一', '/images/avatar/a7.png', 0, 0);
add_chat($conn, $t, $uidMimi, '想租粉色汉服，周末有空吗？');

// ===== 线程 1006：小鱼 ↔ 校园小桃 =====
$t = 1006;
add_inbox($conn, $uidTao, $uidYu, $t, 'buyer', '小鱼', '尺码不确定，能帮忙看看吗？', '周一', '/images/avatar/a1.png', 0, 0);
add_inbox($conn, $uidYu, $uidTao, $t, 'mine', '校园小桃', '尺码不确定，能帮忙看看吗？', '周一', '/images/avatar/a7.png', 0, 0);
add_chat($conn, $t, $uidYu, '尺码不确定，能帮忙看看吗？');

// ===== 折叠：一周前（仅小桃买家咨询可见折叠入口）=====
$uidNing = upsert_user($conn, '13800000011', '阿宁同学', '/images/avatar/a8.png', null, $pwd);
$uidBei = upsert_user($conn, '13800000012', '小北', '/images/avatar/a9.png', null, $pwd);

$t = 1009;
add_inbox($conn, $uidTao, $uidNing, $t, 'buyer', '阿柠', 'Lolita裙押金怎么退？', '上周', '/images/avatar/a8.png', 0, 1);
add_inbox($conn, $uidNing, $uidTao, $t, 'mine', '校园小桃', 'Lolita裙押金怎么退？', '上周', '/images/avatar/a7.png', 0, 0);
add_chat($conn, $t, $uidNing, 'Lolita裙押金怎么退？');
add_chat($conn, $t, $uidTao, '归还检查无误后原路退回');

$t = 1010;
add_inbox($conn, $uidTao, $uidBei, $t, 'buyer', '小北', '毕业照想租学士服，还有货吗？', '上周', '/images/avatar/a9.png', 0, 1);
add_inbox($conn, $uidBei, $uidTao, $t, 'mine', '校园小桃', '毕业照想租学士服，还有货吗？', '上周', '/images/avatar/a7.png', 0, 0);
add_chat($conn, $t, $uidBei, '毕业照想租学士服，还有货吗？');

// ===== 线程 1007：测试用户 ↔ 夜子溪（测试用户的「我的咨询」）=====
$t = 1007;
add_inbox($conn, $uidTest, $uidYe, $t, 'mine', '夜子溪', '你好，蓝色系汉服还有 M 码吗？', '今天', '/images/avatar/a9.png', 0, 0);
add_inbox($conn, $uidYe, $uidTest, $t, 'buyer', '测试用户', '你好，蓝色系汉服还有 M 码吗？', '今天', '/images/avatar/a8.png', 1, 0);
add_chat($conn, $t, $uidTest, '你好，蓝色系汉服还有 M 码吗？');
add_chat($conn, $t, $uidYe, '有的，这周末可以取');

// ===== 线程 1008：测试用户 ↔ 汉服铺子 =====
$t = 1008;
add_inbox($conn, $uidTest, $uidHan, $t, 'mine', '汉服铺子', '马面裙这周末可以取衣吗？', '昨天', '/images/avatar/a4.png', 1, 0);
add_inbox($conn, $uidHan, $uidTest, $t, 'buyer', '测试用户', '马面裙这周末可以取衣吗？', '昨天', '/images/avatar/a8.png', 0, 0);
add_chat($conn, $t, $uidTest, '马面裙这周末可以取衣吗？');
add_chat($conn, $t, $uidHan, '可以，周六下午驿站见');

// ===== 校园小桃的订单/系统（仅小桃可见）=====
$t = 2001;
add_inbox($conn, $uidTao, null, $t, 'order', '订单通知', '你的租赁订单已确认，请按时取衣', '周一', '/images/avatar/a9.png', 1, 0);
add_chat($conn, $t, $uidTao, '你的租赁订单已确认，请按时取衣');

$t = 2002;
add_inbox($conn, $uidTao, null, $t, 'system', '系统通知', '发布内容审核通过，已上架展示', '周一', '/images/avatar/a10.png', 0, 0);
add_chat($conn, $t, $uidTao, '发布内容审核通过，已上架展示');

// ===== 测试用户的订单/系统（仅测试用户可见，内容不同）=====
$t = 2003;
add_inbox($conn, $uidTest, null, $t, 'order', '订单通知', '归还提醒：学士服将于明日到期', '今天', '/images/avatar/a9.png', 1, 0);
add_chat($conn, $t, $uidTest, '归还提醒：学士服将于明日到期');

$t = 2004;
add_inbox($conn, $uidTest, null, $t, 'system', '系统通知', '欢迎使用 CAMPUS 衣橱消息', '今天', '/images/avatar/a10.png', 0, 0);
add_chat($conn, $t, $uidTest, '欢迎使用 CAMPUS 衣橱消息');

mysqli_close($conn);

json_ok(array(
    'accounts' => array(
        array('username' => '13800000001', 'nickname' => '校园小桃', 'id' => $uidTao, 'see' => '买家咨询为主'),
        array('username' => '13800000002', 'nickname' => '测试用户', 'id' => $uidTest, 'see' => '我的咨询为主'),
        array('username' => '13800000003', 'nickname' => '阿月', 'id' => $uidYue, 'see' => '与小桃的对话'),
    ),
    'password' => '123456',
    'tip' => '换账号登录后消息列表不同；同一对话双方都能看到聊天内容',
), '差异化消息已就绪');
