<?php
/**
 * 消息列表（按登录用户隔离）
 * POST: user_id, type=buyer|mine|order|system
 * 对方昵称/头像以 users 表最新资料为准
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$type = isset($in['type']) ? trim($in['type']) : 'buyer';
$allow = array('buyer', 'mine', 'order', 'system');
if (!in_array($type, $allow)) {
    $type = 'buyer';
}
if ($userId <= 0) {
    json_err('请先登录后查看消息');
}

$conn = db_connect();

$badges = array('buyer' => 0, 'mine' => 0, 'order' => 0, 'system' => 0);
$bres = mysqli_query($conn, "SELECT msg_type, SUM(unread) AS u FROM messages WHERE owner_id={$userId} GROUP BY msg_type");
if ($bres) {
    while ($b = mysqli_fetch_assoc($bres)) {
        $t = $b['msg_type'];
        if (isset($badges[$t])) {
            $badges[$t] = intval($b['u']);
        }
    }
}

$t = mysqli_real_escape_string($conn, $type);
$sql = "SELECT m.id, m.owner_id, m.peer_id, m.thread_id, m.msg_type, m.name, m.preview,
               m.time_label, m.avatar, m.unread, m.is_old,
               u.nickname AS peer_nickname, u.avatar AS peer_avatar
        FROM messages m
        LEFT JOIN users u ON u.id = m.peer_id
        WHERE m.owner_id={$userId} AND m.msg_type='{$t}'
        ORDER BY m.is_old ASC, m.id ASC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    json_err('查询失败: ' . $err . '，请先打开 seed_messages.php');
}

$list = array();
$oldList = array();
while ($row = mysqli_fetch_assoc($res)) {
    $name = $row['name'];
    $avatar = $row['avatar'] ? $row['avatar'] : '/images/avatar/a1.png';
    // 有对方用户时，优先用最新资料
    if (!empty($row['peer_id'])) {
        if (!empty($row['peer_nickname'])) {
            $name = $row['peer_nickname'];
        }
        if (!empty($row['peer_avatar'])) {
            $avatar = $row['peer_avatar'];
        }
    }
    $item = array(
        'id' => intval($row['id']),
        'thread_id' => intval($row['thread_id']),
        'peer_id' => $row['peer_id'] ? intval($row['peer_id']) : null,
        'msg_type' => $row['msg_type'],
        'name' => $name,
        'preview' => $row['preview'],
        'time_label' => $row['time_label'],
        'avatar' => media_url($avatar),
        'unread' => intval($row['unread']),
        'is_old' => intval($row['is_old']) === 1,
    );
    if ($item['is_old']) {
        $oldList[] = $item;
    } else {
        $list[] = $item;
    }
}
mysqli_close($conn);

$titles = array(
    'buyer' => '买家咨询会话',
    'mine' => '我发起的咨询',
    'order' => '订单相关通知',
    'system' => '系统通知',
);

json_ok(array(
    'list' => $list,
    'oldList' => $oldList,
    'badges' => $badges,
    'sectionTitle' => isset($titles[$type]) ? $titles[$type] : '消息',
));
