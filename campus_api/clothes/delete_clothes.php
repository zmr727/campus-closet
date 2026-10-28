<?php
/**
 * 删除自己发布的服装（删）
 * POST: id, user_id
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$id = isset($in['id']) ? intval($in['id']) : 0;
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;

if ($userId <= 0) {
    json_err('请先登录');
}
if ($id <= 0) {
    json_err('缺少服装 id');
}

$conn = db_connect();
$col = mysqli_query($conn, "SHOW COLUMNS FROM clothes LIKE 'user_id'");
$hasUserId = $col && mysqli_num_rows($col) > 0;

$checkSql = $hasUserId
    ? "SELECT id, user_id FROM clothes WHERE id={$id} LIMIT 1"
    : "SELECT id FROM clothes WHERE id={$id} LIMIT 1";
$check = mysqli_query($conn, $checkSql);
$row = $check ? mysqli_fetch_assoc($check) : null;
if (!$row) {
    mysqli_close($conn);
    json_err('服装不存在或已删除');
}

if ($hasUserId) {
    $owner = intval(isset($row['user_id']) ? $row['user_id'] : 0);
    if ($owner > 0 && $owner !== $userId) {
        mysqli_close($conn);
        json_err('只能删除自己发布的服装');
    }
}

if (!mysqli_query($conn, "DELETE FROM clothes WHERE id={$id}")) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('删除失败: ' . $err);
}

// 顺带清理收藏
mysqli_query($conn, "DELETE FROM favorites WHERE clothes_id={$id}");

mysqli_close($conn);
json_ok(array('id' => $id), '已删除');
