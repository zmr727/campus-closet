<?php
/**
 * 支付订单 → 买家待取衣 / 卖家待交衣，生成取件码（双方同步）
 * POST: user_id, order_id
 */
require_once __DIR__ . '/../config.php';
// 把小程序传来的 user_id、order_id 读进来，变成安全的整数变量，供本文件后面用
$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$orderId = isset($in['order_id']) ? intval($in['order_id']) : 0;

if ($userId <= 0) json_err('请先登录');
if ($orderId <= 0) json_err('缺少订单');
// 这是支付成功后（pay_order.php）随机生成取件码和驿站
$stations = array('大学城南驿站', '图书馆东侧驿站', '一食堂取还点', '西门便利店驿站');
$code = str_pad(strval(mt_rand(0, 999999)), 6, '0', STR_PAD_LEFT);
$station = $stations[mt_rand(0, count($stations) - 1)];

$conn = db_connect();
$res = mysqli_query($conn, "SELECT * FROM orders WHERE id={$orderId} AND user_id={$userId} LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
if (!$row) {
    mysqli_close($conn);
    json_err('订单不存在');
}
if ($row['side'] !== 'rent') {
    mysqli_close($conn);
    json_err('请使用租客账号支付');
}
if ($row['status'] !== 'pay') {
    mysqli_close($conn);
    json_err('当前状态不可付款');
}
// 支付成功后改订单状态，并把取件码、驿站写进库，再返回给小程序
$codeEsc = mysqli_real_escape_string($conn, $code);
$stEsc = mysqli_real_escape_string($conn, $station);
$orderNo = isset($row['order_no']) ? $row['order_no'] : '';

if ($orderNo !== '') {
    $noEsc = mysqli_real_escape_string($conn, $orderNo);
    mysqli_query($conn, "UPDATE orders SET status='pickup', pickup_code='{$codeEsc}', station='{$stEsc}', paid_at=NOW()
                         WHERE order_no='{$noEsc}' AND side='rent'");
    mysqli_query($conn, "UPDATE orders SET status='deliver', pickup_code='{$codeEsc}', station='{$stEsc}', paid_at=NOW()
                         WHERE order_no='{$noEsc}' AND side='lend'");
} else {
    mysqli_query($conn, "UPDATE orders SET status='pickup', pickup_code='{$codeEsc}', station='{$stEsc}', paid_at=NOW()
                         WHERE id={$orderId} AND user_id={$userId}");
}
mysqli_close($conn);

json_ok(array(
    'id' => $orderId,
    'status' => 'pickup',
    'pickupCode' => $code,
    'station' => $station,
), '支付成功');
