<?php
/**
 * 发表买家秀评论
 * POST: show_id, content, user_id, nickname
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$showId = isset($in['show_id']) ? intval($in['show_id']) : 0;
$content = isset($in['content']) ? trim($in['content']) : '';
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$nickname = isset($in['nickname']) ? trim($in['nickname']) : '';

if ($showId <= 0) {
    json_err('缺少买家秀 id');
}
if ($content === '') {
    json_err('请输入评论内容');
}
if ($nickname === '') {
    $nickname = '校园用户';
}

$conn = db_connect();
$check = mysqli_query($conn, "SELECT id FROM buyer_shows WHERE id={$showId} LIMIT 1");
if (!$check || !mysqli_fetch_assoc($check)) {
    mysqli_close($conn);
    json_err('买家秀不存在');
}

$contentEsc = mysqli_real_escape_string($conn, $content);
$nicknameEsc = mysqli_real_escape_string($conn, $nickname);
$uidSql = $userId > 0 ? $userId : 'NULL';

$sql = "INSERT INTO buyer_show_comments (show_id, user_id, nickname, content)
        VALUES ({$showId}, {$uidSql}, '{$nicknameEsc}', '{$contentEsc}')";

if (!mysqli_query($conn, $sql)) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('评论失败: ' . $err);
}

$cid = intval(mysqli_insert_id($conn));
mysqli_query($conn, "UPDATE buyer_shows SET comment_count = comment_count + 1 WHERE id={$showId}");
mysqli_close($conn);

json_ok(array(
    'id' => $cid,
    'show_id' => $showId,
    'nickname' => $nickname,
    'content' => $content,
), '评论成功');
