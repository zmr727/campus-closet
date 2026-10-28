<?php
/** 更新昵称/头像 POST: user_id, nickname?, avatar? */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$nickname = isset($in['nickname']) ? trim($in['nickname']) : null;
$avatar = isset($in['avatar']) ? trim($in['avatar']) : null;

if ($userId <= 0) {
    json_err('请先登录');
}
if ($nickname === null && $avatar === null) {
    json_err('没有要修改的内容');
}
if ($nickname !== null) {
    if ($nickname === '') {
        json_err('昵称不能为空');
    }
    if (mb_strlen($nickname, 'UTF-8') > 32) {
        json_err('昵称请控制在32字内');
    }
}
if ($avatar !== null && $avatar === '') {
    json_err('请选择头像');
}

$conn = db_connect();
$check = mysqli_query($conn, "SELECT id FROM users WHERE id={$userId} LIMIT 1");
if (!$check || !mysqli_fetch_assoc($check)) {
    mysqli_close($conn);
    json_err('用户不存在');
}

$sets = array();
if ($nickname !== null) {
    $sets[] = "nickname='" . mysqli_real_escape_string($conn, $nickname) . "'";
}
if ($avatar !== null) {
    $sets[] = "avatar='" . mysqli_real_escape_string($conn, $avatar) . "'";
}

if (!mysqli_query($conn, 'UPDATE users SET ' . implode(', ', $sets) . " WHERE id={$userId}")) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('保存失败: ' . $err);
}

$res = mysqli_query($conn, "SELECT * FROM users WHERE id={$userId} LIMIT 1");
$row = mysqli_fetch_assoc($res);

// 同步到别人收件箱里「关于我」的展示名/头像（peer_id=我）
if ($row) {
    $sync = array();
    if ($nickname !== null) {
        $sync[] = "name='" . mysqli_real_escape_string($conn, $row['nickname']) . "'";
    }
    if ($avatar !== null) {
        $av = isset($row['avatar']) ? $row['avatar'] : '';
        $sync[] = "avatar='" . mysqli_real_escape_string($conn, $av) . "'";
    }
    if ($sync) {
        // messages 表可能尚未创建，失败忽略
        @mysqli_query($conn, 'UPDATE messages SET ' . implode(', ', $sync) . " WHERE peer_id={$userId}");
    }
}

mysqli_close($conn);
json_ok(user_public($row), '资料已更新');
