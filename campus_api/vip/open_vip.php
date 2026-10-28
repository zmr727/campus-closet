<?php
/**
 * 开通校园会员（演示）
 * POST: user_id, plan=semester|year
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$plan = isset($in['plan']) ? trim($in['plan']) : 'semester';
if ($userId <= 0) json_err('请先登录');
if ($plan !== 'year') $plan = 'semester';

$days = $plan === 'year' ? 365 : 120;
$conn = db_connect();
$planEsc = mysqli_real_escape_string($conn, $plan);

$res = mysqli_query($conn, "SELECT id FROM vip_members WHERE user_id={$userId} LIMIT 1");
$had = $res && mysqli_fetch_assoc($res);
if ($had) {
    $sql = "UPDATE vip_members SET plan='{$planEsc}', opened_at=NOW(), expire_at=DATE_ADD(NOW(), INTERVAL {$days} DAY)
            WHERE user_id={$userId}";
} else {
    $sql = "INSERT INTO vip_members (user_id, plan, opened_at, expire_at)
            VALUES ({$userId}, '{$planEsc}', NOW(), DATE_ADD(NOW(), INTERVAL {$days} DAY))";
}

if (!mysqli_query($conn, $sql)) {
    mysqli_close($conn);
    json_err('开通失败: ' . mysqli_error($conn) . '，请先打开 seed_mine.php');
}

$q = mysqli_query($conn, "SELECT plan, opened_at, expire_at FROM vip_members WHERE user_id={$userId} LIMIT 1");
$row = $q ? mysqli_fetch_assoc($q) : null;
mysqli_close($conn);

json_ok(array(
    'isVip' => true,
    'plan' => $row ? $row['plan'] : $plan,
    'openedAt' => $row ? $row['opened_at'] : null,
    'expireAt' => $row ? $row['expire_at'] : null,
), '开通成功');
