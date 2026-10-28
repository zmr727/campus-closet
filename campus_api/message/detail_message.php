<?php
/**
 * 会话详情（校验收件箱归属）
 * POST: id(收件箱行id), user_id
 * 对方昵称/头像以 users 最新资料为准
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$id = isset($in['id']) ? intval($in['id']) : 0;
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
if ($id <= 0 || $userId <= 0) {
    json_err('参数不完整，请先登录');
}

$conn = db_connect();
$res = mysqli_query($conn, "SELECT * FROM messages WHERE id={$id} AND owner_id={$userId} LIMIT 1");
if (!$res || !($row = mysqli_fetch_assoc($res))) {
    mysqli_close($conn);
    json_err('会话不存在或无权查看');
}

mysqli_query($conn, "UPDATE messages SET unread=0 WHERE id={$id} AND owner_id={$userId}");

$threadId = intval($row['thread_id']);
$peerId = $row['peer_id'] ? intval($row['peer_id']) : 0;
$name = $row['name'];
$avatar = $row['avatar'] ? $row['avatar'] : '/images/avatar/a1.png';

if ($peerId > 0) {
    $ures = mysqli_query($conn, "SELECT nickname, avatar FROM users WHERE id={$peerId} LIMIT 1");
    if ($ures && ($u = mysqli_fetch_assoc($ures))) {
        if (!empty($u['nickname'])) {
            $name = $u['nickname'];
        }
        if (!empty($u['avatar'])) {
            $avatar = $u['avatar'];
        }
        // 顺带把收件箱缓存刷成最新，减少两边不一致
        $nEsc = mysqli_real_escape_string($conn, $name);
        $aEsc = mysqli_real_escape_string($conn, $avatar);
        mysqli_query($conn, "UPDATE messages SET name='{$nEsc}', avatar='{$aEsc}' WHERE id={$id}");
    }
}

$session = array(
    'id' => intval($row['id']),
    'thread_id' => $threadId,
    'peer_id' => $peerId > 0 ? $peerId : null,
    'msg_type' => $row['msg_type'],
    'name' => $name,
    'preview' => $row['preview'],
    'avatar' => media_url($avatar),
    'time_label' => $row['time_label'],
);

$chats = array();
$cres = mysqli_query($conn, "SELECT id, sender_id, content, created_at
                             FROM message_chats WHERE thread_id={$threadId} ORDER BY id ASC");
if ($cres) {
    while ($c = mysqli_fetch_assoc($cres)) {
        $sid = intval($c['sender_id']);
        $chats[] = array(
            'id' => intval($c['id']),
            'sender_id' => $sid,
            'content' => $c['content'],
            'created_at' => $c['created_at'],
            'is_me' => $sid === $userId,
        );
    }
}

mysqli_close($conn);
json_ok(array(
    'session' => $session,
    'chats' => $chats,
));
