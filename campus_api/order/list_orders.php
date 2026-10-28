<?php
/**
 * 订单列表
 * POST/GET: user_id, side=rent|lend, status=可选
 */
require_once __DIR__ . '/../config.php';

$in = array_merge($_GET, read_input());
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$side = isset($in['side']) ? trim($in['side']) : 'rent';
$status = isset($in['status']) ? trim($in['status']) : '';

if ($userId <= 0) {
    json_err('请先登录');
}
if ($side !== 'rent' && $side !== 'lend') {
    $side = 'rent';
}

$conn = db_connect();

// 兼容旧表：补归还码 / 评价字段
$col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'return_code'");
if (!$col || mysqli_num_rows($col) === 0) {
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN return_code VARCHAR(16) DEFAULT NULL COMMENT '归还核销码' AFTER pickup_code");
}
$reviewCols = array(
    'review_score' => "ADD COLUMN review_score TINYINT UNSIGNED DEFAULT NULL COMMENT '我对对方的评分1-5' AFTER finished_at",
    'review_content' => "ADD COLUMN review_content VARCHAR(255) DEFAULT NULL COMMENT '评价内容' AFTER review_score",
    'review_tags' => "ADD COLUMN review_tags VARCHAR(255) DEFAULT NULL COMMENT '评价标签' AFTER review_content",
    'reviewed_at' => "ADD COLUMN reviewed_at DATETIME DEFAULT NULL COMMENT '我方评价时间' AFTER review_tags"
);
foreach ($reviewCols as $name => $ddl) {
    $c = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '{$name}'");
    if (!$c || mysqli_num_rows($c) === 0) {
        mysqli_query($conn, "ALTER TABLE orders {$ddl}");
    }
}

$where = "user_id={$userId} AND side='" . mysqli_real_escape_string($conn, $side) . "'";
if ($status !== '') {
    $where .= " AND status='" . mysqli_real_escape_string($conn, $status) . "'";
}

$sql = "SELECT * FROM orders WHERE {$where} ORDER BY id DESC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    mysqli_close($conn);
    json_err('查询失败: ' . mysqli_error($conn) . '，请先打开 seed_mine.php');
}

$rentLabels = array(
    'pay' => '待付款', 'pickup' => '待取衣', 'renting' => '租赁中',
    'return' => '待归还', 'review' => '评价'
);
$lendLabels = array(
    'pay' => '待付款', 'deliver' => '待交衣', 'lending' => '出租中',
    'reclaim' => '待收回', 'rated' => '待评价'
);

$list = array();
$orderNos = array();
while ($row = mysqli_fetch_assoc($res)) {
    $labels = $row['side'] === 'lend' ? $lendLabels : $rentLabels;
    $st = $row['status'];
    $ono = isset($row['order_no']) ? $row['order_no'] : '';
    if ($ono !== '') $orderNos[$ono] = true;
    $list[] = array(
        'id' => intval($row['id']),
        'orderNo' => $ono,
        'userId' => intval($row['user_id']),
        'clothesId' => intval($row['clothes_id']),
        'title' => $row['title'],
        'cover' => media_url($row['cover']),
        'price' => floatval($row['price']),
        'size' => $row['size_info'],
        'days' => intval($row['days']),
        'totalPrice' => floatval($row['total_price']),
        'side' => $row['side'],
        'status' => $st,
        'statusLabel' => isset($labels[$st]) ? $labels[$st] : $st,
        'buyerName' => $row['buyer_name'],
        'station' => $row['station'],
        'paidAt' => $row['paid_at'],
        'createdAt' => $row['created_at'],
        'timeText' => $row['created_at'] ? date('n-j H:i', strtotime($row['created_at'])) : '',
        'showPay' => ($row['side'] === 'rent' && $st === 'pay'),
        'showPickupCodeBox' => ($row['side'] === 'rent' && $st === 'pickup' && !empty($row['pickup_code'])),
        'showVerifyPickup' => ($row['side'] === 'lend' && $st === 'deliver'),
        'showStartReturn' => ($row['side'] === 'rent' && $st === 'renting') || ($row['side'] === 'lend' && $st === 'lending'),
        'showReturnCodeBox' => ($row['side'] === 'rent' && $st === 'return' && !empty($row['return_code'])),
        'showVerifyReturn' => ($row['side'] === 'lend' && $st === 'reclaim'),
        'showReview' => (
            (($row['side'] === 'rent' && $st === 'review') || ($row['side'] === 'lend' && $st === 'rated'))
            && empty($row['reviewed_at'])
        ),
        'hasReviewed' => !empty($row['reviewed_at']),
        'reviewScore' => isset($row['review_score']) ? intval($row['review_score']) : 0,
        'reviewContent' => isset($row['review_content']) ? $row['review_content'] : '',
        'peerName' => ($row['side'] === 'rent')
            ? (isset($row['seller_name']) ? $row['seller_name'] : '')
            : (isset($row['buyer_name']) ? $row['buyer_name'] : ''),
        'pickupCode' => $row['pickup_code'],
        'returnCode' => isset($row['return_code']) ? $row['return_code'] : '',
        'pickedAt' => isset($row['picked_at']) ? $row['picked_at'] : null,
        'returnedAt' => isset($row['returned_at']) ? $row['returned_at'] : null,
        'finishedAt' => isset($row['finished_at']) ? $row['finished_at'] : null,
        'reviewedAt' => isset($row['reviewed_at']) ? $row['reviewed_at'] : null,
        'hasPeerReview' => false,
        'peerReviewScore' => 0,
        'peerReviewContent' => '',
        'peerReviewTags' => array()
    );
}

// 附带「对方给我的评价」（同 order_no 另一侧）
if (count($orderNos) > 0) {
    $nos = array();
    foreach (array_keys($orderNos) as $n) {
        $nos[] = "'" . mysqli_real_escape_string($conn, $n) . "'";
    }
    $peerSql = "SELECT order_no, side, review_score, review_content, review_tags, reviewed_at
                FROM orders
                WHERE order_no IN (" . implode(',', $nos) . ")
                  AND reviewed_at IS NOT NULL
                  AND review_score IS NOT NULL";
    $peerRes = mysqli_query($conn, $peerSql);
    $peerMap = array();
    if ($peerRes) {
        while ($p = mysqli_fetch_assoc($peerRes)) {
            $key = $p['order_no'] . '|' . $p['side'];
            $tags = array();
            if (!empty($p['review_tags'])) {
                $tags = array_values(array_filter(array_map('trim', explode(',', $p['review_tags']))));
            }
            $peerMap[$key] = array(
                'score' => intval($p['review_score']),
                'content' => $p['review_content'] ? $p['review_content'] : '',
                'tags' => $tags
            );
        }
    }
    foreach ($list as &$item) {
        if ($item['orderNo'] === '') continue;
        $peerSide = $item['side'] === 'rent' ? 'lend' : 'rent';
        $pk = $item['orderNo'] . '|' . $peerSide;
        if (isset($peerMap[$pk])) {
            $item['hasPeerReview'] = true;
            $item['peerReviewScore'] = $peerMap[$pk]['score'];
            $item['peerReviewContent'] = $peerMap[$pk]['content'];
            $item['peerReviewTags'] = $peerMap[$pk]['tags'];
        }
    }
    unset($item);
}

mysqli_close($conn);
json_ok(array('list' => $list));
