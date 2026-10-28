<?php
/**
 * 我收到的评价（对方评价我）
 * GET/POST: user_id, role=all|seller|renter
 *
 * seller: 别人租了我的衣服后，对我的评价（orders.side=rent 且 peer_id=我）
 * renter: 我租别人衣服后，出借方对我的评价（orders.side=lend 且 peer_id=我）
 */
require_once __DIR__ . '/../config.php';

$in = array_merge($_GET, read_input());
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$role = isset($in['role']) ? trim($in['role']) : 'all';
if ($userId <= 0) json_err('请先登录');
if (!in_array($role, array('all', 'seller', 'renter'), true)) $role = 'all';

$conn = db_connect();

$col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'reviewed_at'");
if (!$col || mysqli_num_rows($col) === 0) {
    mysqli_close($conn);
    json_ok(array(
        'list' => array(),
        'sellerCount' => 0,
        'renterCount' => 0,
        'avgAsSeller' => 0,
        'avgAsRenter' => 0
    ));
}

$where = "o.peer_id={$userId} AND o.reviewed_at IS NOT NULL AND o.review_score IS NOT NULL";
if ($role === 'seller') {
    $where .= " AND o.side='rent'";
} elseif ($role === 'renter') {
    $where .= " AND o.side='lend'";
} else {
    $where .= " AND o.side IN ('rent','lend')";
}

$sql = "SELECT o.id, o.side, o.clothes_id, o.title, o.cover, o.review_score, o.review_content,
               o.review_tags, o.reviewed_at, o.user_id AS from_user_id,
               u.nickname AS from_name, u.avatar AS from_avatar
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        WHERE {$where}
        ORDER BY o.reviewed_at DESC
        LIMIT 100";
$res = mysqli_query($conn, $sql);

$list = array();
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $side = $row['side'];
        $tags = array();
        if (!empty($row['review_tags'])) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $row['review_tags']))));
        }
        $list[] = array(
            'id' => intval($row['id']),
            'role' => $side === 'rent' ? 'seller' : 'renter',
            'roleLabel' => $side === 'rent' ? '作为出借方收到' : '作为租客收到',
            'clothesId' => intval($row['clothes_id']),
            'title' => $row['title'],
            'cover' => media_url($row['cover']),
            'score' => intval($row['review_score']),
            'content' => $row['review_content'] ? $row['review_content'] : '',
            'tags' => $tags,
            'timeText' => $row['reviewed_at'] ? date('Y-m-d H:i', strtotime($row['reviewed_at'])) : '',
            'fromName' => $row['from_name'] ? $row['from_name'] : '校园用户',
            'fromAvatar' => media_url($row['from_avatar'] ? $row['from_avatar'] : '/images/mine/avatar.png')
        );
    }
}

$sellerCount = 0;
$renterCount = 0;
$avgSeller = 0;
$avgRenter = 0;
$agg = mysqli_query($conn, "SELECT side, COUNT(*) AS cnt, AVG(review_score) AS avg_score
                            FROM orders
                            WHERE peer_id={$userId}
                              AND reviewed_at IS NOT NULL
                              AND review_score IS NOT NULL
                              AND side IN ('rent','lend')
                            GROUP BY side");
if ($agg) {
    while ($a = mysqli_fetch_assoc($agg)) {
        if ($a['side'] === 'rent') {
            $sellerCount = intval($a['cnt']);
            $avgSeller = $a['avg_score'] !== null ? round(floatval($a['avg_score']), 1) : 0;
        } elseif ($a['side'] === 'lend') {
            $renterCount = intval($a['cnt']);
            $avgRenter = $a['avg_score'] !== null ? round(floatval($a['avg_score']), 1) : 0;
        }
    }
}
mysqli_close($conn);

json_ok(array(
    'list' => $list,
    'sellerCount' => $sellerCount,
    'renterCount' => $renterCount,
    'avgAsSeller' => $avgSeller,
    'avgAsRenter' => $avgRenter
));
