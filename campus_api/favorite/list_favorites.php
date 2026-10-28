<?php
/**
 * 收藏列表
 * POST/GET: user_id
 */
require_once __DIR__ . '/../config.php';

$in = array_merge($_GET, read_input());
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
if ($userId <= 0) json_err('请先登录');

$conn = db_connect();
$sql = "SELECT id, clothes_id, title, cover, price, category, created_at
        FROM favorites WHERE user_id={$userId} ORDER BY id DESC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    mysqli_close($conn);
    json_err('查询失败: ' . mysqli_error($conn) . '，请先打开 seed_mine.php');
}

$list = array();
while ($row = mysqli_fetch_assoc($res)) {
    $list[] = array(
        'id' => intval($row['clothes_id']),
        'favId' => intval($row['id']),
        'title' => $row['title'],
        'cover' => media_url($row['cover']),
        'price' => floatval($row['price']),
        'category' => $row['category'],
    );
}
mysqli_close($conn);
json_ok(array('list' => $list));
