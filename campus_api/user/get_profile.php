<?php
// 根据用户 id，从数据库查出最新个人资料，返回给小程序。
/** 读取个人资料 POST/GET: user_id */
require_once __DIR__ . '/../config.php';
// 校验是否已登录
$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
if ($userId <= 0 && isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
}
if ($userId <= 0) {
    json_err('请先登录');
}

$conn = db_connect();
$res = mysqli_query($conn, "SELECT * FROM users WHERE id={$userId} LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_close($conn);

if (!$row) {
    json_err('用户不存在');
}
json_ok(user_public($row));
