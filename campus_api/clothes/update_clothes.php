<?php
/**
 * 修改自己发布的服装（改）
 * POST: id, user_id, title, cover, price, category, size_info, intro, available_from, available_to
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$id = isset($in['id']) ? intval($in['id']) : 0;
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$title = isset($in['title']) ? trim($in['title']) : '';
$cover = isset($in['cover']) ? trim($in['cover']) : '';
$price = isset($in['price']) ? floatval($in['price']) : 0;
$category = isset($in['category']) ? trim($in['category']) : '';
$size = isset($in['size_info']) ? trim($in['size_info']) : '';
$intro = isset($in['intro']) ? trim($in['intro']) : '';
$from = isset($in['available_from']) ? trim($in['available_from']) : '';
$to = isset($in['available_to']) ? trim($in['available_to']) : '';

if ($userId <= 0) {
    json_err('请先登录');
}
if ($id <= 0) {
    json_err('缺少服装 id');
}
if ($title === '') {
    json_err('请填写名称');
}
if ($cover === '') {
    json_err('请上传封面图');
}
if ($price <= 0) {
    json_err('请填写租金');
}
if ($category === '') {
    json_err('请选择分类');
}

$conn = db_connect();
$col = mysqli_query($conn, "SHOW COLUMNS FROM clothes LIKE 'user_id'");
$hasUserId = $col && mysqli_num_rows($col) > 0;

$checkSql = $hasUserId
    ? "SELECT id, user_id, seller FROM clothes WHERE id={$id} LIMIT 1"
    : "SELECT id, seller FROM clothes WHERE id={$id} LIMIT 1";
$check = mysqli_query($conn, $checkSql);
$row = $check ? mysqli_fetch_assoc($check) : null;
if (!$row) {
    mysqli_close($conn);
    json_err('服装不存在');
}

if ($hasUserId) {
    $owner = intval(isset($row['user_id']) ? $row['user_id'] : 0);
    if ($owner > 0 && $owner !== $userId) {
        mysqli_close($conn);
        json_err('只能修改自己发布的服装');
    }
}

$title = mysqli_real_escape_string($conn, $title);
$cover = mysqli_real_escape_string($conn, $cover);
$category = mysqli_real_escape_string($conn, $category);
$size = mysqli_real_escape_string($conn, $size);
$intro = mysqli_real_escape_string($conn, $intro);

$fromSql = 'NULL';
$toSql = 'NULL';
if ($from !== '' && $to !== '') {
    $fromSql = "'" . mysqli_real_escape_string($conn, $from) . "'";
    $toSql = "'" . mysqli_real_escape_string($conn, $to) . "'";
}

$sql = "UPDATE clothes SET
          title='{$title}',
          cover='{$cover}',
          price={$price},
          category='{$category}',
          size_info='{$size}',
          intro='{$intro}',
          available_from={$fromSql},
          available_to={$toSql}
        WHERE id={$id}";

if ($hasUserId) {
    // 若旧数据无 user_id，保存时补上当前用户
    $sql = "UPDATE clothes SET
              title='{$title}',
              cover='{$cover}',
              price={$price},
              category='{$category}',
              size_info='{$size}',
              intro='{$intro}',
              available_from={$fromSql},
              available_to={$toSql},
              user_id={$userId}
            WHERE id={$id}";
}

if (!mysqli_query($conn, $sql)) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('保存失败: ' . $err);
}

mysqli_close($conn);
json_ok(array('id' => $id), '已保存修改');
