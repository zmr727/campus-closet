<?php
/**
 * 发布买家秀
 * POST: title, cover, content, user_id, nickname, clothes_id(可选)
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$title = isset($in['title']) ? trim($in['title']) : '';
$cover = isset($in['cover']) ? trim($in['cover']) : '';
$content = isset($in['content']) ? trim($in['content']) : '';
if ($content === '' && isset($in['intro'])) {
    $content = trim($in['intro']);
}
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$nickname = isset($in['nickname']) ? trim($in['nickname']) : '';
$clothesId = isset($in['clothes_id']) ? intval($in['clothes_id']) : 0;

if ($title === '') {
    json_err('请填写标题');
}
if ($cover === '') {
    json_err('请上传实拍图');
}
if ($nickname === '') {
    $nickname = '校园用户';
}

$conn = db_connect();
$title = mysqli_real_escape_string($conn, $title);
$cover = mysqli_real_escape_string($conn, $cover);
$content = mysqli_real_escape_string($conn, $content);
$nickname = mysqli_real_escape_string($conn, $nickname);
$uidSql = $userId > 0 ? $userId : 'NULL';
$cidSql = $clothesId > 0 ? $clothesId : 'NULL';

$sql = "INSERT INTO buyer_shows (user_id, nickname, title, cover, content, clothes_id, like_count, comment_count)
        VALUES ({$uidSql}, '{$nickname}', '{$title}', '{$cover}', '{$content}', {$cidSql}, 0, 0)";

if (!mysqli_query($conn, $sql)) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('发布失败: ' . $err . '，请先打开 seed_buyer_shows.php');
}

$id = intval(mysqli_insert_id($conn));
mysqli_close($conn);
json_ok(array('id' => $id), '发布成功');
