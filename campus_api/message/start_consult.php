<?php
/**
 * 从商品详情发起咨询：复用/新建双方会话，写入首条消息
 * POST: user_id(买家), clothes_id, content
 * 返回：买家侧收件箱 message_id，可直接进 chat
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$clothesId = isset($in['clothes_id']) ? intval($in['clothes_id']) : 0;
$content = isset($in['content']) ? trim($in['content']) : '';

if ($userId <= 0) json_err('请先登录');
if ($clothesId <= 0) json_err('缺少商品');
if ($content === '') json_err('请输入咨询内容');
if (mb_strlen($content) > 500) json_err('内容过长');

$conn = db_connect();

$col = mysqli_query($conn, "SHOW COLUMNS FROM clothes LIKE 'user_id'");
$hasOwner = $col && mysqli_num_rows($col) > 0;
$fields = $hasOwner
    ? 'id, title, seller, user_id'
    : 'id, title, seller';
$res = mysqli_query($conn, "SELECT {$fields} FROM clothes WHERE id={$clothesId} LIMIT 1");
$c = $res ? mysqli_fetch_assoc($res) : null;
if (!$c) {
    mysqli_close($conn);
    json_err('商品不存在');
}

$sellerId = ($hasOwner && isset($c['user_id'])) ? intval($c['user_id']) : 0;
if ($sellerId <= 0) {
    $sellerName = isset($c['seller']) ? trim($c['seller']) : '';
    if ($sellerName !== '') {
        $sn = mysqli_real_escape_string($conn, $sellerName);
        $ur = mysqli_query($conn, "SELECT id FROM users WHERE nickname='{$sn}' LIMIT 1");
        $urow = $ur ? mysqli_fetch_assoc($ur) : null;
        if ($urow) $sellerId = intval($urow['id']);
    }
}
if ($sellerId <= 0) {
    mysqli_close($conn);
    json_err('该商品暂无绑定卖家，无法咨询');
}
if ($sellerId === $userId) {
    mysqli_close($conn);
    json_err('不能咨询自己发布的衣物');
}

function fetch_user_brief($conn, $id) {
    $id = intval($id);
    $r = mysqli_query($conn, "SELECT id, nickname, avatar FROM users WHERE id={$id} LIMIT 1");
    $row = $r ? mysqli_fetch_assoc($r) : null;
    if (!$row) {
        return array(
            'id' => $id,
            'nickname' => '校园用户',
            'avatar' => '/images/avatar/a1.png',
        );
    }
    return array(
        'id' => intval($row['id']),
        'nickname' => $row['nickname'] ? $row['nickname'] : '校园用户',
        'avatar' => $row['avatar'] ? $row['avatar'] : '/images/avatar/a1.png',
    );
}

$buyer = fetch_user_brief($conn, $userId);
$seller = fetch_user_brief($conn, $sellerId);
$contentEsc = mysqli_real_escape_string($conn, $content);
$buyerNameEsc = mysqli_real_escape_string($conn, $buyer['nickname']);
$sellerNameEsc = mysqli_real_escape_string($conn, $seller['nickname']);
$buyerAvatarEsc = mysqli_real_escape_string($conn, $buyer['avatar']);
$sellerAvatarEsc = mysqli_real_escape_string($conn, $seller['avatar']);

// 已有双方会话则复用 thread
$mineId = 0;
$threadId = 0;
$exist = mysqli_query(
    $conn,
    "SELECT id, thread_id FROM messages
     WHERE owner_id={$userId} AND peer_id={$sellerId}
       AND msg_type IN ('mine','buyer')
     ORDER BY id DESC LIMIT 1"
);
if ($exist && ($erow = mysqli_fetch_assoc($exist))) {
    $mineId = intval($erow['id']);
    $threadId = intval($erow['thread_id']);
}

if ($threadId <= 0) {
    $tmax = mysqli_query($conn, 'SELECT IFNULL(MAX(thread_id), 1000) AS m FROM messages');
    $mrow = $tmax ? mysqli_fetch_assoc($tmax) : null;
    $threadId = intval($mrow ? $mrow['m'] : 1000) + 1;

    // 买家侧：我的咨询
    $ok1 = mysqli_query(
        $conn,
        "INSERT INTO messages (owner_id, peer_id, thread_id, msg_type, name, preview, time_label, avatar, unread, is_old)
         VALUES ({$userId}, {$sellerId}, {$threadId}, 'mine', '{$sellerNameEsc}', '{$contentEsc}', '刚刚', '{$sellerAvatarEsc}', 0, 0)"
    );
    if (!$ok1) {
        $err = mysqli_error($conn);
        mysqli_close($conn);
        json_err('创建会话失败: ' . $err);
    }
    $mineId = intval(mysqli_insert_id($conn));

    // 卖家侧：买家咨询
    $ok2 = mysqli_query(
        $conn,
        "INSERT INTO messages (owner_id, peer_id, thread_id, msg_type, name, preview, time_label, avatar, unread, is_old)
         VALUES ({$sellerId}, {$userId}, {$threadId}, 'buyer', '{$buyerNameEsc}', '{$contentEsc}', '刚刚', '{$buyerAvatarEsc}', 1, 0)"
    );
    if (!$ok2) {
        $err = mysqli_error($conn);
        mysqli_close($conn);
        json_err('创建对方收件箱失败: ' . $err);
    }
} else {
    // 复用：更新双方预览；卖家未读 +1
    mysqli_query(
        $conn,
        "UPDATE messages SET preview='{$contentEsc}', time_label='刚刚', is_old=0, name='{$sellerNameEsc}', avatar='{$sellerAvatarEsc}'
         WHERE id={$mineId}"
    );
    mysqli_query(
        $conn,
        "UPDATE messages SET preview='{$contentEsc}', time_label='刚刚', is_old=0,
                unread = unread + 1, name='{$buyerNameEsc}', avatar='{$buyerAvatarEsc}'
         WHERE thread_id={$threadId} AND owner_id={$sellerId}"
    );
    // 若卖家侧没有收件箱行（异常数据），补一条
    $chk = mysqli_query($conn, "SELECT id FROM messages WHERE thread_id={$threadId} AND owner_id={$sellerId} LIMIT 1");
    if (!$chk || mysqli_num_rows($chk) === 0) {
        mysqli_query(
            $conn,
            "INSERT INTO messages (owner_id, peer_id, thread_id, msg_type, name, preview, time_label, avatar, unread, is_old)
             VALUES ({$sellerId}, {$userId}, {$threadId}, 'buyer', '{$buyerNameEsc}', '{$contentEsc}', '刚刚', '{$buyerAvatarEsc}', 1, 0)"
        );
    }
}

$okChat = mysqli_query(
    $conn,
    "INSERT INTO message_chats (thread_id, sender_id, content)
     VALUES ({$threadId}, {$userId}, '{$contentEsc}')"
);
if (!$okChat) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('发送失败: ' . $err);
}

mysqli_close($conn);
json_ok(array(
    'message_id' => $mineId,
    'thread_id' => $threadId,
    'seller_id' => $sellerId,
    'seller_name' => $seller['nickname'],
), '已发送给卖家');
