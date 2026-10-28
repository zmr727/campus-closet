<?php
/**
 * 发送消息（写入共享 thread，并同步双方收件箱预览）
 * POST: message_id(我的收件箱行id), user_id, content
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$mid = isset($in['message_id']) ? intval($in['message_id']) : 0;
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$content = isset($in['content']) ? trim($in['content']) : '';

if ($mid <= 0 || $userId <= 0) {
    json_err('参数不完整，请先登录');
}
if ($content === '') {
    json_err('请输入内容');
}

$conn = db_connect();
$res = mysqli_query($conn, "SELECT * FROM messages WHERE id={$mid} AND owner_id={$userId} LIMIT 1");
if (!$res || !($row = mysqli_fetch_assoc($res))) {
    mysqli_close($conn);
    json_err('会话不存在或无权发送');
}

$threadId = intval($row['thread_id']);
$peerId = $row['peer_id'] ? intval($row['peer_id']) : 0;
$contentEsc = mysqli_real_escape_string($conn, $content);

$sql = "INSERT INTO message_chats (thread_id, sender_id, content)
        VALUES ({$threadId}, {$userId}, '{$contentEsc}')";
if (!mysqli_query($conn, $sql)) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('发送失败: ' . $err);
}
$cid = intval(mysqli_insert_id($conn));

// 更新自己收件箱预览
mysqli_query($conn, "UPDATE messages SET preview='{$contentEsc}', time_label='刚刚' WHERE id={$mid}");

// 更新对方收件箱预览 + 未读+1（同一 thread）
if ($peerId > 0) {
    mysqli_query($conn, "UPDATE messages
                         SET preview='{$contentEsc}', time_label='刚刚', unread = unread + 1
                         WHERE thread_id={$threadId} AND owner_id={$peerId}");
}

mysqli_close($conn);
json_ok(array(
    'id' => $cid,
    'thread_id' => $threadId,
    'sender_id' => $userId,
    'content' => $content,
    'is_me' => true,
), '发送成功');
