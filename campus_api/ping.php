<?php
require_once __DIR__ . '/config.php';
$conn = db_connect();
$res = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM users');
if (!$res) {
    json_err(mysqli_error($conn) . ' — 请先打开 seed_demo.php');
}
$row = mysqli_fetch_assoc($res);
mysqli_close($conn);
json_ok(array('db' => 'miniprogram', 'users' => intval($row['c'])), '连接成功');
