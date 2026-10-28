<?php
/**
 * 服装详情
 * POST/GET: id
 */
require_once __DIR__ . '/../config.php';

$in = array_merge($_GET, read_input());
$id = isset($in['id']) ? intval($in['id']) : 0;
if ($id <= 0) {
    json_err('缺少商品 id');
}

$conn = db_connect();
$sql = "SELECT id, title, cover, price, category, size_info, tag, date_label, intro, seller, fav_count, available_from, available_to
        FROM clothes WHERE id={$id} LIMIT 1";
$res = mysqli_query($conn, $sql);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_close($conn);

if (!$row) {
    json_err('商品不存在');
}
$row['id'] = intval($row['id']);
$row['price'] = floatval($row['price']);
$row['fav_count'] = intval($row['fav_count']);
$row['cover'] = media_url($row['cover']);
json_ok($row);
/**
 *1. 连接数据库，根据商品 id 查询 clothes 服装表的全部详情字段，只查 1 条
*2. 读取查询结果，立刻关闭数据库连接
*3. 无查询结果则返回「商品不存在」错误
*4. 统一格式化 id、价格、收藏数的数据类型
*5. 将完整商品详情以成功 JSON 格式返回给小程序 / 网页前端 */