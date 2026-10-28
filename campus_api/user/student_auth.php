<?php
/**
 * 学号认证 POST: user_id, student_id, real_name（均必填）
 * real_name → users.real_name（不影响 nickname）
 */
require_once __DIR__ . '/../config.php';
// 读小程序 POST 过来的 JSON，得到数组
// 从请求里取出「谁在认证、学号、真实姓名」三个值；缺了就给默认值（0 或空字符串），方便后面校验和更新数据库。
$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$studentId = isset($in['student_id']) ? trim($in['student_id']) : '';
$realName = isset($in['real_name']) ? trim($in['real_name']) : '';

if ($userId <= 0) {
    json_err('请先登录');
}
if ($studentId === '') {
    json_err('请填写学号');
}
if ($realName === '') {
    json_err('请填写真实姓名');
}
if (mb_strlen($realName, 'UTF-8') > 32) {
    json_err('真实姓名过长');
}

$conn = db_connect();

$col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'real_name'");
if (!$col || mysqli_num_rows($col) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN real_name VARCHAR(64) DEFAULT NULL COMMENT '真实姓名' AFTER nickname");
}

$sid = mysqli_real_escape_string($conn, $studentId);
$n = mysqli_real_escape_string($conn, $realName);

$check = mysqli_query($conn, "SELECT id FROM users WHERE id={$userId} LIMIT 1");
if (!$check || !mysqli_fetch_assoc($check)) {
    mysqli_close($conn);
    json_err('用户不存在');
}

$sql = "UPDATE users SET student_id='{$sid}', real_name='{$n}' WHERE id={$userId}";
if (!mysqli_query($conn, $sql)) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('认证失败: ' . $err);
}

$res = mysqli_query($conn, "SELECT * FROM users WHERE id={$userId} LIMIT 1");
$row = mysqli_fetch_assoc($res);
mysqli_close($conn);
json_ok(user_public($row), '认证成功');
