<?php
/**
 * 取消待付款订单（买卖双方一起取消）
 * POST: user_id, order_id
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$orderId = isset($in['order_id']) ? intval($in['order_id']) : 0;

if ($userId <= 0) json_err('请先登录');
if ($orderId <= 0) json_err('缺少订单');

$conn = db_connect();
$res = mysqli_query($conn, "SELECT id, status, order_no, side FROM orders WHERE id={$orderId} AND user_id={$userId} LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
if (!$row) {
    mysqli_close($conn);
    json_err('订单不存在');
}
if ($row['status'] !== 'pay') {
    mysqli_close($conn);
    json_err('仅待付款订单可取消');
}

$orderNo = isset($row['order_no']) ? $row['order_no'] : '';
if ($orderNo !== '') {
    $noEsc = mysqli_real_escape_string($conn, $orderNo);
    mysqli_query($conn, "DELETE FROM orders WHERE order_no='{$noEsc}'");
} else {
    mysqli_query($conn, "DELETE FROM orders WHERE id={$orderId} AND user_id={$userId}");
}
mysqli_close($conn);
json_ok(array('id' => $orderId), '已取消');
