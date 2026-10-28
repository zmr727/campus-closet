<?php
/**
 * 买家秀列表
 * POST/GET: keyword 可选
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$keyword = isset($in['keyword']) ? trim($in['keyword']) : '';
if ($keyword === '' && isset($_GET['keyword'])) {
    $keyword = trim($_GET['keyword']);
}

$conn = db_connect();
$where = '1=1';
if ($keyword !== '') {
    $kw = mysqli_real_escape_string($conn, $keyword);
    $where .= " AND (title LIKE '%{$kw}%' OR content LIKE '%{$kw}%' OR nickname LIKE '%{$kw}%')";
}

$sql = "SELECT id, user_id, nickname, title, cover, content, clothes_id, like_count, comment_count, created_at
        FROM buyer_shows WHERE {$where} ORDER BY id DESC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('查询失败: ' . $err . '，请先打开 seed_buyer_shows.php');
}

$list = array();
while ($row = mysqli_fetch_assoc($res)) {
    $list[] = array(
        'id' => intval($row['id']),
        'user_id' => $row['user_id'] ? intval($row['user_id']) : null,
        'nickname' => $row['nickname'],
        'title' => $row['title'],
        'cover' => media_url($row['cover']),
        'content' => $row['content'],
        'intro' => $row['content'],
        'clothes_id' => $row['clothes_id'] ? intval($row['clothes_id']) : null,
        'like_count' => intval($row['like_count']),
        'comment_count' => intval($row['comment_count']),
        'created_at' => $row['created_at'],
    );
}
mysqli_close($conn);
json_ok($list);
