<?php
/**
 * 买家秀详情（含评论）
 * POST/GET: id
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$id = isset($in['id']) ? intval($in['id']) : 0;
if ($id <= 0 && isset($_GET['id'])) {
    $id = intval($_GET['id']);
}
if ($id <= 0) {
    json_err('缺少买家秀 id');
}

$conn = db_connect();
$res = mysqli_query($conn, "SELECT * FROM buyer_shows WHERE id={$id} LIMIT 1");
if (!$res || !($row = mysqli_fetch_assoc($res))) {
    mysqli_close($conn);
    json_err('买家秀不存在');
}

$show = array(
    'id' => intval($row['id']),
    'user_id' => $row['user_id'] ? intval($row['user_id']) : null,
    'nickname' => $row['nickname'],
    'title' => $row['title'],
    'cover' => media_url($row['cover']),
    'content' => $row['content'],
    'clothes_id' => $row['clothes_id'] ? intval($row['clothes_id']) : null,
    'like_count' => intval($row['like_count']),
    'comment_count' => intval($row['comment_count']),
    'created_at' => $row['created_at'],
);

$comments = array();
$cres = mysqli_query($conn, "SELECT id, user_id, nickname, content, created_at
                             FROM buyer_show_comments WHERE show_id={$id} ORDER BY id ASC");
if ($cres) {
    while ($c = mysqli_fetch_assoc($cres)) {
        $comments[] = array(
            'id' => intval($c['id']),
            'user_id' => $c['user_id'] ? intval($c['user_id']) : null,
            'nickname' => $c['nickname'],
            'content' => $c['content'],
            'created_at' => $c['created_at'],
        );
    }
}

mysqli_close($conn);
json_ok(array(
    'show' => $show,
    'comments' => $comments,
));
