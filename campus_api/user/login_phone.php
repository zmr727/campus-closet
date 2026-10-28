<?php
/** 手机号登录（演示）POST: phone, code */
// 先加载上一层目录里的 config.php
require_once __DIR__ . '/../config.php';
// 从请求里取出手机号和验证码
$in = read_input();
$phone = isset($in['phone']) ? trim($in['phone']) : '';
$code = isset($in['code']) ? trim($in['code']) : '';
// 参数校验：前端传来的手机号、验证码不能为空。
if ($phone === '') {
    json_err('请输入手机号');
}
if ($code === '') {
    json_err('请输入验证码');
}
// 连数据库，并把手机号处理成能安全拼进 SQL 的字符串。
$conn = db_connect();
$u = mysqli_real_escape_string($conn, $phone);

// 优先按 phone 查，兼容旧数据 username=手机号
// 先按手机号在 users 里查一条记录；查到了 $row 就是用户数据，查不到 $row 就是 null，后面据此决定登录还是新建用户
$res = mysqli_query($conn, "SELECT * FROM users WHERE phone='{$u}' OR username='{$u}' LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
// 如果数据库里还没有这个手机号，就自动注册一个新用户，然后再把用户信息查出来。
if (!$row) {
    $nick = mysqli_real_escape_string($conn, preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $phone));
    $sql = "INSERT INTO users (username, phone, login_type, nickname)
            VALUES ('{$u}', '{$u}', 'phone', '{$nick}')";
    if (!mysqli_query($conn, $sql)) {
        $err = mysqli_error($conn);
        mysqli_close($conn);
        json_err('登录失败: ' . $err);
    }
    $id = mysqli_insert_id($conn);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id={$id} LIMIT 1");
    $row = mysqli_fetch_assoc($res);
} else {
    // 补齐拆分字段
    //  老用户登录时，如果 phone 或 login_type 是空的，就用当前手机号补上；补完再查一次，保证返回给前端的数据是完整的。
    $id = intval($row['id']);
    $needPhone = !isset($row['phone']) || $row['phone'] === '' || $row['phone'] === null;
    $needType = !isset($row['login_type']) || $row['login_type'] === '' || $row['login_type'] === null;
    $sets = array();
    if ($needPhone) {
        $sets[] = "phone='{$u}'";
    }
    if ($needType) {
        $sets[] = "login_type='phone'";
    }
    if ($sets) {
        mysqli_query($conn, 'UPDATE users SET ' . implode(', ', $sets) . " WHERE id={$id}");
        $res = mysqli_query($conn, "SELECT * FROM users WHERE id={$id} LIMIT 1");
        $row = mysqli_fetch_assoc($res);
    }
}
// 关掉数据库连接，把用户信息整理好后成功返回给小程序。
mysqli_close($conn);
json_ok(user_public($row), '登录成功');
