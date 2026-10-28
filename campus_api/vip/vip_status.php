<?php
/**
 * 查询会员状态
 * POST/GET: user_id
 */
require_once __DIR__ . '/../config.php';

$in = array_merge($_GET, read_input());
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
if ($userId <= 0) json_err('请先登录');

$conn = db_connect();
$res = mysqli_query($conn, "SELECT plan, opened_at, expire_at FROM vip_members WHERE user_id={$userId} LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_close($conn);

if (!$row) {
    json_ok(array('isVip' => false));
}

$expire = $row['expire_at'];
$active = true;
if ($expire && strtotime($expire) < time()) {
    $active = false;
}

json_ok(array(
    'isVip' => $active,
    'plan' => $row['plan'],
    'openedAt' => $row['opened_at'],
    'expireAt' => $expire,
));
