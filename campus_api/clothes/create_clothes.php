<?php
/**
 * 发布服装（增）
 * POST JSON:
 * title, cover, price, category, size_info, intro, seller,
 * available_from, available_to, tag=cate|buyer|recommend
 */
require_once __DIR__ . '/../config.php';
// 处理完成后，后续代码可直接使用这些变量拼接 INSERT SQL 语句，将商品信息插入数据库 `clothes` 表
$in = read_input();
$title = isset($in['title']) ? trim($in['title']) : '';
$cover = isset($in['cover']) ? trim($in['cover']) : '';
$price = isset($in['price']) ? floatval($in['price']) : 0;
$category = isset($in['category']) ? trim($in['category']) : '';
$size = isset($in['size_info']) ? trim($in['size_info']) : '';
$intro = isset($in['intro']) ? trim($in['intro']) : '';
$seller = isset($in['seller']) ? trim($in['seller']) : '';
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$tag = isset($in['tag']) ? trim($in['tag']) : 'cate';
$from = isset($in['available_from']) ? trim($in['available_from']) : '';
$to = isset($in['available_to']) ? trim($in['available_to']) : '';

if ($title === '') {
    json_err('请填写名称');
}
if ($cover === '') {
    json_err('请上传封面图');
}
if ($tag !== 'buyer' && $price <= 0) {
    json_err('请填写租金');
}
if ($category === '') {
    json_err('请选择分类');
}

$allowTags = array('cate', 'buyer', 'recommend', 'hot', 'calendar');
if (!in_array($tag, $allowTags)) {
    $tag = 'cate';
}

$conn = db_connect();
$title = mysqli_real_escape_string($conn, $title);
$cover = mysqli_real_escape_string($conn, $cover);
$category = mysqli_real_escape_string($conn, $category);
$size = mysqli_real_escape_string($conn, $size);
$intro = mysqli_real_escape_string($conn, $intro);
$seller = mysqli_real_escape_string($conn, $seller);
$tag = mysqli_real_escape_string($conn, $tag);

$fromSql = 'NULL';
$toSql = 'NULL';
if ($from !== '' && $to !== '') {
    $fromSql = "'" . mysqli_real_escape_string($conn, $from) . "'";
    $toSql = "'" . mysqli_real_escape_string($conn, $to) . "'";
}

$userSql = $userId > 0 ? strval($userId) : 'NULL';
// 兼容尚未执行 seed_mine 的环境
$col = mysqli_query($conn, "SHOW COLUMNS FROM clothes LIKE 'user_id'");
$hasUserId = $col && mysqli_num_rows($col) > 0;

if ($hasUserId) {
    $sql = "INSERT INTO clothes (title, cover, price, category, size_info, tag, date_label, intro, seller, user_id, fav_count, available_from, available_to)
            VALUES ('{$title}', '{$cover}', {$price}, '{$category}', '{$size}', '{$tag}', NULL, '{$intro}', '{$seller}', {$userSql}, 0, {$fromSql}, {$toSql})";
} else {
    $sql = "INSERT INTO clothes (title, cover, price, category, size_info, tag, date_label, intro, seller, fav_count, available_from, available_to)
            VALUES ('{$title}', '{$cover}', {$price}, '{$category}', '{$size}', '{$tag}', NULL, '{$intro}', '{$seller}', 0, {$fromSql}, {$toSql})";
}

if (!mysqli_query($conn, $sql)) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('发布失败: ' . $err);
}

$id = intval(mysqli_insert_id($conn));
mysqli_close($conn);

json_ok(array('id' => $id), '发布成功');
// 小程序用户填写表单提交数据 → PHP 接收清洗并做安全防护 → SQL 写入 MySQL 数据表存储 → 数据库操作结果原路返回给小程序展示反馈。