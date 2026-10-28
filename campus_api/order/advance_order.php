<?php
/**
 * 推进订单状态（买卖双方同 order_no 同步）
 * POST: user_id, order_id, action, code(核销时必填)
 *
 * confirm_pickup  出借方输入取件码 → 租赁中/出租中
 * start_return    租客申请归还（或出借方提醒）→ 生成归还码，进入待归还/待收回
 * confirm_return  出借方输入归还码 → 评价/待评价
 * 评价请走 submit_review.php（双方各自评价对方）
 */
require_once __DIR__ . '/../config.php';
// 收参数 + 做合法性检查
$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$orderId = isset($in['order_id']) ? intval($in['order_id']) : 0;
$action = isset($in['action']) ? trim($in['action']) : '';
$code = isset($in['code']) ? trim($in['code']) : '';

$allow = array('confirm_pickup', 'start_return', 'confirm_return');
if ($userId <= 0) json_err('请先登录');
if ($orderId <= 0) json_err('缺少订单');
if (!in_array($action, $allow, true)) json_err('未知操作（评价请调用 submit_review.php）');

$conn = db_connect();

function ensure_order_col($conn, $name, $ddl) {
    $col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '{$name}'");
    if ($col && mysqli_num_rows($col) > 0) return;
    mysqli_query($conn, "ALTER TABLE orders {$ddl}");
}
ensure_order_col($conn, 'picked_at', "ADD COLUMN picked_at DATETIME DEFAULT NULL COMMENT '取衣时间' AFTER paid_at");
ensure_order_col($conn, 'returned_at', "ADD COLUMN returned_at DATETIME DEFAULT NULL COMMENT '归还时间' AFTER picked_at");
ensure_order_col($conn, 'finished_at', "ADD COLUMN finished_at DATETIME DEFAULT NULL COMMENT '完成时间' AFTER returned_at");
ensure_order_col($conn, 'return_code', "ADD COLUMN return_code VARCHAR(16) DEFAULT NULL COMMENT '归还核销码' AFTER pickup_code");

$res = mysqli_query($conn, "SELECT * FROM orders WHERE id={$orderId} AND user_id={$userId} LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
if (!$row) {
    mysqli_close($conn);
    json_err('订单不存在');
}

$side = $row['side'];
$status = $row['status'];
$orderNo = isset($row['order_no']) ? $row['order_no'] : '';
if ($orderNo === '') {
    mysqli_close($conn);
    json_err('订单缺少 order_no，请重建订单表');
}
$noEsc = mysqli_real_escape_string($conn, $orderNo);

$rentNext = null;
$lendNext = null;
$extraBoth = '';
$msg = '已更新';
$returnCodeOut = null;
// 根据 $action 校验谁能操作、当前状态对不对、码对不对，然后定好买卖双方下一步状
if ($action === 'confirm_pickup') {
    // 仅出借方核销取件码
    if (!($side === 'lend' && $status === 'deliver')) {
        mysqli_close($conn);
        json_err('请使用出借方账号，在待交衣中输入取件码核销');
    }
    if ($code === '') {
        mysqli_close($conn);
        json_err('请输入租客出示的取件码');
    }
    $expect = isset($row['pickup_code']) ? trim($row['pickup_code']) : '';
    if ($expect === '' || $code !== $expect) {
        mysqli_close($conn);
        json_err('取件码不正确');
    }
    $rentNext = 'renting';
    $lendNext = 'lending';
    $extraBoth = ', picked_at=NOW()';
    $msg = '取件码核销成功，衣物进入租赁中';
} elseif ($action === 'start_return') {
    if (!(($side === 'rent' && $status === 'renting') || ($side === 'lend' && $status === 'lending'))) {
        mysqli_close($conn);
        json_err('当前状态不能发起归还');
    }
    $returnCode = str_pad(strval(mt_rand(0, 999999)), 6, '0', STR_PAD_LEFT);
    $rcEsc = mysqli_real_escape_string($conn, $returnCode);
    $rentNext = 'return';
    $lendNext = 'reclaim';
    $extraBoth = ", return_code='{$rcEsc}'";
    $returnCodeOut = $returnCode;
    $msg = '已生成归还码，请出借方核销后确认收回';
} elseif ($action === 'confirm_return') {
    // 仅出借方核销归还码
    if (!($side === 'lend' && $status === 'reclaim')) {
        mysqli_close($conn);
        json_err('请使用出借方账号，在待收回中输入归还码核销');
    }
    if ($code === '') {
        mysqli_close($conn);
        json_err('请输入租客出示的归还码');
    }
    $expect = isset($row['return_code']) ? trim($row['return_code']) : '';
    if ($expect === '' || $code !== $expect) {
        mysqli_close($conn);
        json_err('归还码不正确');
    }
    $rentNext = 'review';
    $lendNext = 'rated';
    $extraBoth = ', returned_at=NOW()';
    $msg = '归还码核销成功，可进行评价';
}

mysqli_query($conn, "UPDATE orders SET status='{$rentNext}'{$extraBoth} WHERE order_no='{$noEsc}' AND side='rent'");
mysqli_query($conn, "UPDATE orders SET status='{$lendNext}'{$extraBoth} WHERE order_no='{$noEsc}' AND side='lend'");

$newStatus = ($side === 'lend') ? $lendNext : $rentNext;
mysqli_close($conn);

$out = array(
    'id' => $orderId,
    'action' => $action,
    'status' => $newStatus,
    'side' => $side,
);
if ($returnCodeOut !== null) {
    $out['returnCode'] = $returnCodeOut;
}
json_ok($out, $msg);
