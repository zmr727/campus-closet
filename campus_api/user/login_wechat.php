<?php
/** 微信一键登录（演示）POST: code(可选) */
require_once __DIR__ . '/../config.php';
// 读前端传来的微信 code；有 code 就用 wx_ + md5(code) 前 16 位当演示 openid；
// 没 code 就用时间戳造一个。后面用这个 openid 查/建用户完成登录。
$in = read_input();
$code = isset($in['code']) ? trim($in['code']) : '';
$openid = $code !== '' ? ('wx_' . substr(md5($code), 0, 16)) : ('wx_' . date('YmdHis'));

$conn = db_connect();
$o = mysqli_real_escape_string($conn, $openid);

// 优先按 openid 查，兼容旧数据 username=wx_xxx
$res = mysqli_query($conn, "SELECT * FROM users WHERE openid='{$o}' OR username='{$o}' LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
// 微信 openid 在库里不存在时，自动建一个微信用户（默认昵称「微信用户」）；失败就报错，成功就查出新用户填进 $row，继续完成登录。
if (!$row) {
    $sql = "INSERT INTO users (username, openid, login_type, nickname)
            VALUES ('{$o}', '{$o}', 'wechat', '微信用户')";
    if (!mysqli_query($conn, $sql)) {
        $err = mysqli_error($conn);
        mysqli_close($conn);
        json_err('登录失败: ' . $err);
    }
    $id = mysqli_insert_id($conn);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id={$id} LIMIT 1");
    $row = mysqli_fetch_assoc($res);
} else {
    // 微信老用户登录时，如果 openid 或 login_type 是空的，就用当前 openid 补上；补完再查一次，保证返回给前端的用户数据完整。
    $id = intval($row['id']);
    $needOpen = !isset($row['openid']) || $row['openid'] === '' || $row['openid'] === null;
    $needType = !isset($row['login_type']) || $row['login_type'] === '' || $row['login_type'] === null;
    $sets = array();
    if ($needOpen) {
        $sets[] = "openid='{$o}'";
    }
    if ($needType) {
        $sets[] = "login_type='wechat'";
    }
    if ($sets) {
        mysqli_query($conn, 'UPDATE users SET ' . implode(', ', $sets) . " WHERE id={$id}");
        $res = mysqli_query($conn, "SELECT * FROM users WHERE id={$id} LIMIT 1");
        $row = mysqli_fetch_assoc($res);
    }
}

mysqli_close($conn);
json_ok(user_public($row), '登录成功');
