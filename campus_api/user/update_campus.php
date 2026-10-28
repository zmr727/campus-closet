<?php
/** 更新当前校区 POST: user_id, campus */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$campus = isset($in['campus']) ? trim($in['campus']) : '';

if ($userId <= 0) {
    json_err('请先登录');
}
if ($campus === '') {
    json_err('请选择校区');
}

$allow = array('大学城校区', '五山校区', '国际校区', '南校区');
if (!in_array($campus, $allow, true)) {
    json_err('不支持的校区');
}

$conn = db_connect();

// 兼容：没有 campus 字段就自动加上
$col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'campus'");
if (!$col || mysqli_num_rows($col) === 0) {
    if (!mysqli_query($conn, "ALTER TABLE users ADD COLUMN campus VARCHAR(64) DEFAULT NULL COMMENT '当前校区' AFTER address")) {
        $err = mysqli_error($conn);
        mysqli_close($conn);
        json_err('升级用户表失败: ' . $err);
    }
}

$check = mysqli_query($conn, "SELECT id FROM users WHERE id={$userId} LIMIT 1");
if (!$check || !mysqli_fetch_assoc($check)) {
    mysqli_close($conn);
    json_err('用户不存在');
}

$c = mysqli_real_escape_string($conn, $campus);
if (!mysqli_query($conn, "UPDATE users SET campus='{$c}' WHERE id={$userId}")) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('保存失败: ' . $err);
}

$res = mysqli_query($conn, "SELECT * FROM users WHERE id={$userId} LIMIT 1");
$row = mysqli_fetch_assoc($res);
mysqli_close($conn);
json_ok(user_public($row), '校区已更新');
