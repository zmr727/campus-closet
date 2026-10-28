<?php
/**
 * 创建租用订单（买卖双方各一条）
 * POST: user_id(买家), clothes_id, size, days, total_price
 */
require_once __DIR__ . '/../config.php';
// 用户在商品详情页发起租赁下单，小程序携带登录用户 ID、商品 ID、租赁信息传给此 PHP 接口，后端校验参数合法后生成租赁订单
$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$clothesId = isset($in['clothes_id']) ? intval($in['clothes_id']) : 0;
$size = isset($in['size']) ? trim($in['size']) : '均码';
$days = isset($in['days']) ? intval($in['days']) : 1;
$total = isset($in['total_price']) ? floatval($in['total_price']) : 0;

if ($userId <= 0) json_err('请先登录');
if ($clothesId <= 0) json_err('缺少商品');
if ($days <= 0) $days = 1;

$conn = db_connect();

// 兼容旧表：无 order_no 时提示重建
$col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'order_no'");
if (!$col || mysqli_num_rows($col) === 0) {
    mysqli_close($conn);
    json_err('订单表需升级，请先打开 seed_mine.php');
}

$res = mysqli_query($conn, "SELECT id, title, cover, price, seller, user_id FROM clothes WHERE id={$clothesId} LIMIT 1");
$c = $res ? mysqli_fetch_assoc($res) : null;
if (!$c) {
    mysqli_close($conn);
    json_err('商品不存在');
}

$price = floatval($c['price']);
if ($total <= 0) $total = $price * $days;
// 先认衣服上的发布者 id；旧数据没绑 id 时，就用卖家昵称去 users 里对一下，好把订单挂到正确的卖家账号上
$sellerId = isset($c['user_id']) ? intval($c['user_id']) : 0;
if ($sellerId <= 0) {
    // 按卖家昵称找用户
    $sellerName = isset($c['seller']) ? $c['seller'] : '';
    if ($sellerName !== '') {
        $sn = mysqli_real_escape_string($conn, $sellerName);
        $ur = mysqli_query($conn, "SELECT id FROM users WHERE nickname='{$sn}' LIMIT 1");
        $urow = $ur ? mysqli_fetch_assoc($ur) : null;
        if ($urow) $sellerId = intval($urow['id']);
    }
}
if ($sellerId <= 0) {
    mysqli_close($conn);
    json_err('该商品暂无绑定卖家，无法下单');
}
if ($sellerId === $userId) {
    mysqli_close($conn);
    json_err('不能租用自己发布的衣物');
}
// 下单前把买卖双方的显示名查出来，后面写入订单（或消息）用
$br = mysqli_query($conn, "SELECT nickname FROM users WHERE id={$userId} LIMIT 1");
$brow = $br ? mysqli_fetch_assoc($br) : null;
$buyerName = $brow && $brow['nickname'] ? $brow['nickname'] : '租客';
$sr = mysqli_query($conn, "SELECT nickname FROM users WHERE id={$sellerId} LIMIT 1");
$srow = $sr ? mysqli_fetch_assoc($sr) : null;
$sellerName = $srow && $srow['nickname'] ? $srow['nickname'] : (isset($c['seller']) ? $c['seller'] : '卖家');

$orderNo = 'ON' . date('YmdHis') . mt_rand(100, 999);
$title = mysqli_real_escape_string($conn, $c['title']);
$cover = mysqli_real_escape_string($conn, $c['cover']);
$sizeEsc = mysqli_real_escape_string($conn, $size);
$orderNoEsc = mysqli_real_escape_string($conn, $orderNo);
$buyerEsc = mysqli_real_escape_string($conn, $buyerName);
$sellerEsc = mysqli_real_escape_string($conn, $sellerName);
//  一笔单拆成两条订单记录——租客侧 rent、卖家侧 lend，两边「我的订单」都能看到这笔交易
$sqlRent = "INSERT INTO orders (order_no, user_id, peer_id, clothes_id, title, cover, price, size_info, days, total_price, side, status, seller_name)
            VALUES ('{$orderNoEsc}', {$userId}, {$sellerId}, {$clothesId}, '{$title}', '{$cover}', {$price}, '{$sizeEsc}', {$days}, {$total}, 'rent', 'pay', '{$sellerEsc}')";
$sqlLend = "INSERT INTO orders (order_no, user_id, peer_id, clothes_id, title, cover, price, size_info, days, total_price, side, status, buyer_name)
            VALUES ('{$orderNoEsc}', {$sellerId}, {$userId}, {$clothesId}, '{$title}', '{$cover}', {$price}, '{$sizeEsc}', {$days}, {$total}, 'lend', 'pay', '{$buyerEsc}')";

if (!mysqli_query($conn, $sqlRent)) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('下单失败: ' . $err);
}
$rentId = intval(mysqli_insert_id($conn));
if (!mysqli_query($conn, $sqlLend)) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('下单失败(卖家侧): ' . $err);
}
mysqli_close($conn);
// 下单成功后，把结果打包成 JSON 回给小程序
json_ok(array(
    'id' => $rentId,
    'orderNo' => $orderNo,
    'clothesId' => $clothesId,
    'status' => 'pay',
    'totalPrice' => $total,
), '下单成功');
// $变量 是这个接口内部算题用的；算完后有价值的会存库或包进返回给小程序的 JSON，不会整份 $ 列表直接传出去