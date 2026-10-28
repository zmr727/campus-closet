<?php
/**
 * 我的页订单角标数量
 * POST/GET: user_id
 * 评价类角标只统计「尚未评价」的订单
 */
require_once __DIR__ . '/../config.php';

$in = array_merge($_GET, read_input());
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;

if ($userId <= 0) {
    json_err('请先登录');
}

$rentKeys = array('pay', 'pickup', 'renting', 'return', 'review');
$lendKeys = array('pay', 'deliver', 'lending', 'reclaim', 'rated');

$rent = array();
$lend = array();
foreach ($rentKeys as $k) {
    $rent[$k] = 0;
}
foreach ($lendKeys as $k) {
    $lend[$k] = 0;
}

$conn = db_connect();

$hasReviewed = false;
$col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'reviewed_at'");
if ($col && mysqli_num_rows($col) > 0) {
    $hasReviewed = true;
}

$sql = "SELECT side, status, COUNT(*) AS cnt FROM orders WHERE user_id={$userId}";
if ($hasReviewed) {
    // 待评价角标：已评价的不计入
    $sql .= " AND NOT (
                (side='rent' AND status='review' AND reviewed_at IS NOT NULL)
                OR (side='lend' AND status='rated' AND reviewed_at IS NOT NULL)
              )";
}
$sql .= " GROUP BY side, status";

$res = mysqli_query($conn, $sql);
if (!$res) {
    mysqli_close($conn);
    json_err('查询失败');
}

while ($row = mysqli_fetch_assoc($res)) {
    $side = $row['side'];
    $st = $row['status'];
    $cnt = intval($row['cnt']);
    if ($side === 'rent' && isset($rent[$st])) {
        $rent[$st] = $cnt;
    } elseif ($side === 'lend' && isset($lend[$st])) {
        $lend[$st] = $cnt;
    }
}
mysqli_close($conn);

$rentTotal = 0;
foreach ($rent as $v) {
    $rentTotal += $v;
}
$lendTotal = 0;
foreach ($lend as $v) {
    $lendTotal += $v;
}

json_ok(array(
    'rent' => $rent,
    'lend' => $lend,
    'rentTotal' => $rentTotal,
    'lendTotal' => $lendTotal
));
