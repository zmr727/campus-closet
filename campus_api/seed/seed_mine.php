<?php
/**
 * 我的页相关表：orders / favorites / vip_members
 * 并为 clothes 补充 user_id（发布者）
 *
 * 浏览器打开一次：
 * http://127.0.0.1/miniprogram/campus_api/seed/seed_mine.php
 */
require_once __DIR__ . '/../config.php';

$conn = db_connect();

// clothes.user_id
$col = mysqli_query($conn, "SHOW COLUMNS FROM clothes LIKE 'user_id'");
if (!$col || mysqli_num_rows($col) === 0) {
    mysqli_query($conn, "ALTER TABLE clothes ADD COLUMN user_id INT UNSIGNED DEFAULT NULL COMMENT '发布者用户id' AFTER seller");
}

// 把种子卖家昵称映射到演示用户
$sellerMap = array(
    '校园小桃' => 0,
    '阿宁同学' => 0,
    '学长衣柜' => 0,
    '毕业季补给站' => 0,
    '夜子溪' => 0,
    '汉服铺子' => 0,
);

function uid_by_name($conn, $name) {
    $n = mysqli_real_escape_string($conn, $name);
    $res = mysqli_query($conn, "SELECT id FROM users WHERE nickname='{$n}' LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        return intval($row['id']);
    }
    // 没有则按手机号查小桃
    if ($name === '校园小桃') {
        $res = mysqli_query($conn, "SELECT id FROM users WHERE username='13800000001' LIMIT 1");
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            return intval($row['id']);
        }
    }
    return 0;
}

$uidTao = uid_by_name($conn, '校园小桃');
if ($uidTao <= 0) {
    // 确保有演示用户
    $pwd = mysqli_real_escape_string($conn, password_hash('123456', PASSWORD_DEFAULT));
    mysqli_query($conn, "INSERT INTO users (username, password, nickname, student_id)
                         VALUES ('13800000001', '{$pwd}', '校园小桃', '2023001')");
    $uidTao = intval(mysqli_insert_id($conn));
}
$uidTest = uid_by_name($conn, '测试用户');
if ($uidTest <= 0) {
    $pwd = mysqli_real_escape_string($conn, password_hash('123456', PASSWORD_DEFAULT));
    mysqli_query($conn, "INSERT INTO users (username, password, nickname, student_id)
                         VALUES ('13800000002', '{$pwd}', '测试用户', NULL)");
    $uidTest = intval(mysqli_insert_id($conn));
}
$uidYue = uid_by_name($conn, '阿月');
$uidYe = uid_by_name($conn, '夜子溪');
$uidHan = uid_by_name($conn, '汉服铺子');

$sellerMap['校园小桃'] = $uidTao;
// 其他卖家各自独立账号，禁止再挂到小桃名下
$pwdSeller = mysqli_real_escape_string($conn, password_hash('123456', PASSWORD_DEFAULT));
$sellerMap['夜子溪'] = $uidYe > 0 ? $uidYe : upsert_user_simple($conn, '13800000014', '夜子溪', '', $pwdSeller);
$sellerMap['汉服铺子'] = $uidHan > 0 ? $uidHan : upsert_user_simple($conn, '13800000015', '汉服铺子', '', $pwdSeller);
$sellerMap['阿宁同学'] = upsert_user_simple($conn, '13800000011', '阿宁同学', '', $pwdSeller);
$sellerMap['学长衣柜'] = upsert_user_simple($conn, '13800000012', '学长衣柜', '', $pwdSeller);
$sellerMap['毕业季补给站'] = upsert_user_simple($conn, '13800000013', '毕业季补给站', '', $pwdSeller);

foreach ($sellerMap as $seller => $uid) {
    if ($uid <= 0) continue;
    $s = mysqli_real_escape_string($conn, $seller);
    mysqli_query($conn, "UPDATE clothes SET user_id={$uid} WHERE seller='{$s}'");
}

mysqli_query($conn, 'DROP TABLE IF EXISTS orders');
mysqli_query($conn, 'DROP TABLE IF EXISTS favorites');
mysqli_query($conn, 'DROP TABLE IF EXISTS vip_members');

$sqlOrders = "CREATE TABLE orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_no VARCHAR(32) NOT NULL COMMENT '同一笔交易买卖双方共享',
  user_id INT UNSIGNED NOT NULL COMMENT '订单所属用户（各自收件箱）',
  peer_id INT UNSIGNED DEFAULT NULL COMMENT '对方用户id',
  clothes_id INT UNSIGNED NOT NULL,
  title VARCHAR(100) NOT NULL,
  cover VARCHAR(255) DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  size_info VARCHAR(32) DEFAULT NULL,
  days INT UNSIGNED NOT NULL DEFAULT 1,
  total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  side VARCHAR(10) NOT NULL COMMENT 'rent|lend',
  status VARCHAR(20) NOT NULL COMMENT 'rent:pay/pickup/renting/return/review; lend:pay/deliver/lending/reclaim/rated',
  buyer_name VARCHAR(64) DEFAULT NULL COMMENT '出租侧租客名',
  seller_name VARCHAR(64) DEFAULT NULL COMMENT '租用侧卖家名',
  pickup_code VARCHAR(16) DEFAULT NULL COMMENT '取件码',
  station VARCHAR(64) DEFAULT NULL COMMENT '取件驿站',
  paid_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_side_status (user_id, side, status),
  KEY idx_order_no (order_no),
  KEY idx_clothes (clothes_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!mysqli_query($conn, $sqlOrders)) {
    json_err('建 orders 失败: ' . mysqli_error($conn));
}

$sqlFav = "CREATE TABLE favorites (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  clothes_id INT UNSIGNED NOT NULL,
  title VARCHAR(100) DEFAULT NULL,
  cover VARCHAR(255) DEFAULT NULL,
  price DECIMAL(10,2) DEFAULT 0,
  category VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_clothes (user_id, clothes_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!mysqli_query($conn, $sqlFav)) {
    json_err('建 favorites 失败: ' . mysqli_error($conn));
}

$sqlVip = "CREATE TABLE vip_members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  plan VARCHAR(32) NOT NULL DEFAULT 'semester' COMMENT 'semester/year',
  opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expire_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!mysqli_query($conn, $sqlVip)) {
    json_err('建 vip_members 失败: ' . mysqli_error($conn));
}

function pickup_code() {
    return str_pad(strval(mt_rand(0, 999999)), 6, '0', STR_PAD_LEFT);
}

$stations = array('大学城南驿站', '图书馆东侧驿站', '一食堂取还点', '西门便利店驿站');

function clothes_row($conn, $id) {
    $id = intval($id);
    $res = mysqli_query($conn, "SELECT id, title, cover, price, category FROM clothes WHERE id={$id} LIMIT 1");
    return $res ? mysqli_fetch_assoc($res) : null;
}

function insert_order($conn, $row) {
    $sql = sprintf(
        "INSERT INTO orders (order_no, user_id, peer_id, clothes_id, title, cover, price, size_info, days, total_price, side, status, buyer_name, seller_name, pickup_code, station, paid_at, created_at)
         VALUES ('%s', %d, %s, %d, '%s', '%s', %s, '%s', %d, %s, '%s', '%s', %s, %s, %s, %s, %s, '%s')",
        mysqli_real_escape_string($conn, $row['order_no']),
        intval($row['user_id']),
        empty($row['peer_id']) ? 'NULL' : intval($row['peer_id']),
        intval($row['clothes_id']),
        mysqli_real_escape_string($conn, $row['title']),
        mysqli_real_escape_string($conn, $row['cover']),
        floatval($row['price']),
        mysqli_real_escape_string($conn, $row['size_info']),
        intval($row['days']),
        floatval($row['total_price']),
        mysqli_real_escape_string($conn, $row['side']),
        mysqli_real_escape_string($conn, $row['status']),
        empty($row['buyer_name']) ? 'NULL' : ("'" . mysqli_real_escape_string($conn, $row['buyer_name']) . "'"),
        empty($row['seller_name']) ? 'NULL' : ("'" . mysqli_real_escape_string($conn, $row['seller_name']) . "'"),
        empty($row['pickup_code']) ? 'NULL' : ("'" . mysqli_real_escape_string($conn, $row['pickup_code']) . "'"),
        empty($row['station']) ? 'NULL' : ("'" . mysqli_real_escape_string($conn, $row['station']) . "'"),
        empty($row['paid_at']) ? 'NULL' : ("'" . mysqli_real_escape_string($conn, $row['paid_at']) . "'"),
        mysqli_real_escape_string($conn, $row['created_at'])
    );
    return mysqli_query($conn, $sql);
}

function user_name($conn, $uid) {
    $uid = intval($uid);
    $res = mysqli_query($conn, "SELECT nickname FROM users WHERE id={$uid} LIMIT 1");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return $row && $row['nickname'] ? $row['nickname'] : ('用户' . $uid);
}

/** 一笔交易写两条：买家 rent + 卖家 lend */
function insert_pair($conn, $orderNo, $buyerId, $sellerId, $clothes, $size, $days, $rentStatus, $lendStatus, $code, $station, $paidAt, $createdAt) {
    $price = floatval($clothes['price']);
    $total = $price * $days;
    $buyerName = user_name($conn, $buyerId);
    $sellerName = user_name($conn, $sellerId);
    $base = array(
        'order_no' => $orderNo,
        'clothes_id' => intval($clothes['id']),
        'title' => $clothes['title'],
        'cover' => $clothes['cover'],
        'price' => $price,
        'size_info' => $size,
        'days' => $days,
        'total_price' => $total,
        'pickup_code' => $code,
        'station' => $station,
        'paid_at' => $paidAt,
        'created_at' => $createdAt,
    );
    $ok1 = insert_order($conn, array_merge($base, array(
        'user_id' => $buyerId,
        'peer_id' => $sellerId,
        'side' => 'rent',
        'status' => $rentStatus,
        'buyer_name' => null,
        'seller_name' => $sellerName,
    )));
    $ok2 = insert_order($conn, array_merge($base, array(
        'user_id' => $sellerId,
        'peer_id' => $buyerId,
        'side' => 'lend',
        'status' => $lendStatus,
        'buyer_name' => $buyerName,
        'seller_name' => null,
    )));
    return ($ok1 ? 1 : 0) + ($ok2 ? 1 : 0);
}

// 确保阿月存在
if ($uidYue <= 0) {
    $pwd = mysqli_real_escape_string($conn, password_hash('123456', PASSWORD_DEFAULT));
    $uidYue = upsert_user_simple($conn, '13800000003', '阿月', '2023003', $pwd);
}

function upsert_user_simple($conn, $username, $nickname, $student, $pwd) {
    $u = mysqli_real_escape_string($conn, $username);
    $n = mysqli_real_escape_string($conn, $nickname);
    $s = $student === null ? 'NULL' : ("'" . mysqli_real_escape_string($conn, $student) . "'");
    $res = mysqli_query($conn, "SELECT id FROM users WHERE username='{$u}' LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        return intval($row['id']);
    }
    mysqli_query($conn, "INSERT INTO users (username, password, nickname, student_id)
                         VALUES ('{$u}', '{$pwd}', '{$n}', {$s})");
    return intval(mysqli_insert_id($conn));
}

$c1 = clothes_row($conn, 1);   // 小桃的礼服长裙
$c6 = clothes_row($conn, 6);   // 小桃的礼服(热租)
$c9 = clothes_row($conn, 9);   // 夜子溪汉服
$c11 = clothes_row($conn, 11); // 小桃泰服
$c10 = clothes_row($conn, 10); // 阿宁魔法袍 — 可能挂在小桃 user_id
$c13 = clothes_row($conn, 13); // 汉服铺子马面裙

$now = time();
$orderN = 0;

// 1) 测试用户租小桃的礼服长裙 — 待付款（双方都能看到）
if ($c1 && $uidTest > 0) {
    $orderN += insert_pair(
        $conn, 'ON' . ($now) . 'A', $uidTest, $uidTao, $c1, 'M', 2,
        'pay', 'pay', null, null, null, date('Y-m-d H:i:s', $now - 3600)
    );
}

// 2) 阿月租小桃的礼服 — 已付款待取衣 / 卖家待交衣
if ($c6 && $uidYue > 0) {
    $code = pickup_code();
    $orderN += insert_pair(
        $conn, 'ON' . ($now) . 'B', $uidYue, $uidTao, $c6, 'S', 3,
        'pickup', 'deliver', $code, $stations[0],
        date('Y-m-d H:i:s', $now - 80000), date('Y-m-d H:i:s', $now - 86400)
    );
}

// 3) 小桃租夜子溪汉服 — 租赁中 / 卖家出租中
$sellerYe = $uidYe > 0 ? $uidYe : $uidTao;
if ($c9) {
    // 若夜子溪不存在则卖家仍是小桃，跳过自买自卖
    if ($sellerYe !== $uidTao) {
        $code = pickup_code();
        $orderN += insert_pair(
            $conn, 'ON' . ($now) . 'C', $uidTao, $sellerYe, $c9, 'M', 5,
            'renting', 'lending', $code, $stations[1],
            date('Y-m-d H:i:s', $now - 2 * 86400), date('Y-m-d H:i:s', $now - 2 * 86400)
        );
    } else {
        // 夜子溪账号没有时：小桃作为买家，测试用户当卖家演示
        $code = pickup_code();
        $orderN += insert_pair(
            $conn, 'ON' . ($now) . 'C', $uidTao, $uidTest, $c9, 'M', 5,
            'renting', 'lending', $code, $stations[1],
            date('Y-m-d H:i:s', $now - 2 * 86400), date('Y-m-d H:i:s', $now - 2 * 86400)
        );
    }
}

// 4) 测试用户租小桃泰服 — 待付款
if ($c11 && $uidTest > 0) {
    $orderN += insert_pair(
        $conn, 'ON' . ($now) . 'D', $uidTest, $uidTao, $c11, 'S', 2,
        'pay', 'pay', null, null, null, date('Y-m-d H:i:s', $now - 7200)
    );
}

// 收藏：各人不同
$favN = 0;
function add_fav($conn, $uid, $cid, &$favN) {
    $c = clothes_row($conn, $cid);
    if (!$c || $uid <= 0) return;
    $sql = sprintf(
        "INSERT IGNORE INTO favorites (user_id, clothes_id, title, cover, price, category) VALUES (%d, %d, '%s', '%s', %s, '%s')",
        $uid,
        intval($c['id']),
        mysqli_real_escape_string($conn, $c['title']),
        mysqli_real_escape_string($conn, $c['cover']),
        floatval($c['price']),
        mysqli_real_escape_string($conn, isset($c['category']) ? $c['category'] : '')
    );
    if (mysqli_query($conn, $sql) && mysqli_affected_rows($conn) > 0) $favN++;
}
add_fav($conn, $uidTao, 9, $favN);
add_fav($conn, $uidTao, 10, $favN);
add_fav($conn, $uidTest, 1, $favN);
add_fav($conn, $uidTest, 6, $favN);
add_fav($conn, $uidYue, 11, $favN);

// VIP：测试用户已开通，小桃未开通
mysqli_query($conn, "INSERT INTO vip_members (user_id, plan, opened_at, expire_at) VALUES
 ({$uidTest}, 'semester', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY))");

mysqli_close($conn);

json_ok(array(
    'tables' => array('orders', 'favorites', 'vip_members'),
    'per_user' => true,
    'seed' => array(
        'orders' => $orderN,
        'favorites' => $favN,
        'accounts' => array(
            '13800000001 校园小桃' => '主要看「我出租的」+ 自己租的汉服',
            '13800000002 测试用户' => '主要看「我租到的」待付款，且已开 VIP',
            '13800000003 阿月' => '有一条待取衣（取件码）',
        ),
    ),
    'tip' => '不同账号登录只看到自己的订单/收藏/会员，不是全站同一份数据',
), '我的页相关表已重建（按用户隔离）');
