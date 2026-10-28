<?php
/** 更新校内交接地址 POST: user_id, address */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$address = isset($in['address']) ? trim($in['address']) : '';

if ($userId <= 0) {
    json_err('请先登录');
}
if ($address === '') {
    json_err('请填写交接地址');
}
if (mb_strlen($address, 'UTF-8') > 120) {
    json_err('地址过长，请控制在120字内');
}

$conn = db_connect();
$check = mysqli_query($conn, "SELECT id FROM users WHERE id={$userId} LIMIT 1");
if (!$check || !mysqli_fetch_assoc($check)) {
    mysqli_close($conn);
    json_err('用户不存在');
}

$addr = mysqli_real_escape_string($conn, $address);
if (!mysqli_query($conn, "UPDATE users SET address='{$addr}' WHERE id={$userId}")) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('保存失败: ' . $err);
}

$res = mysqli_query($conn, "SELECT * FROM users WHERE id={$userId} LIMIT 1");
$row = mysqli_fetch_assoc($res);
mysqli_close($conn);
json_ok(user_public($row), '地址已保存');
