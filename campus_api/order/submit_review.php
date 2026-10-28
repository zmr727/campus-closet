<?php
/**
 * 提交订单评价（双方各自评价对方，互不影响）
 * POST: user_id, order_id, score(1-5), content, tags(数组或逗号串)
 *
 * 租客(side=rent) 评价出借方
 * 出借方(side=lend) 评价租客
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$orderId = isset($in['order_id']) ? intval($in['order_id']) : 0;
$score = isset($in['score']) ? intval($in['score']) : 0;
$content = isset($in['content']) ? trim($in['content']) : '';
$tagsIn = isset($in['tags']) ? $in['tags'] : '';

if ($userId <= 0) json_err('请先登录');
if ($orderId <= 0) json_err('缺少订单');
if ($score < 1 || $score > 5) json_err('请选择 1~5 星评价');
if (mb_strlen($content) > 200) json_err('评价最多200字');

$tagsArr = array();
if (is_array($tagsIn)) {
    $tagsArr = $tagsIn;
} elseif (is_string($tagsIn) && $tagsIn !== '') {
    $tagsArr = preg_split('/[,，]/', $tagsIn);
}
$tagsClean = array();
foreach ($tagsArr as $t) {
    $t = trim(strval($t));
    if ($t !== '' && mb_strlen($t) <= 12) {
        $tagsClean[] = $t;
    }
    if (count($tagsClean) >= 5) break;
}
$tagsStr = implode(',', $tagsClean);

$conn = db_connect();

function ensure_review_cols($conn) {
    $cols = array(
        'review_score' => "ADD COLUMN review_score TINYINT UNSIGNED DEFAULT NULL COMMENT '我对对方的评分1-5' AFTER finished_at",
        'review_content' => "ADD COLUMN review_content VARCHAR(255) DEFAULT NULL COMMENT '评价内容' AFTER review_score",
        'review_tags' => "ADD COLUMN review_tags VARCHAR(255) DEFAULT NULL COMMENT '评价标签' AFTER review_content",
        'reviewed_at' => "ADD COLUMN reviewed_at DATETIME DEFAULT NULL COMMENT '我方评价时间' AFTER review_tags"
    );
    foreach ($cols as $name => $ddl) {
        $col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '{$name}'");
        if (!$col || mysqli_num_rows($col) === 0) {
            mysqli_query($conn, "ALTER TABLE orders {$ddl}");
        }
    }
}
ensure_review_cols($conn);

$res = mysqli_query($conn, "SELECT * FROM orders WHERE id={$orderId} AND user_id={$userId} LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
if (!$row) {
    mysqli_close($conn);
    json_err('订单不存在');
}

$side = $row['side'];
$status = $row['status'];
$can = ($side === 'rent' && $status === 'review') || ($side === 'lend' && $status === 'rated');
if (!$can) {
    mysqli_close($conn);
    json_err('当前订单不在待评价状态');
}
if (!empty($row['reviewed_at'])) {
    mysqli_close($conn);
    json_err('你已评价过，无需重复提交');
}

$contentEsc = mysqli_real_escape_string($conn, $content);
$tagsEsc = mysqli_real_escape_string($conn, $tagsStr);
// 把评价写进当前这条订单，并标成已完
$sql = "UPDATE orders SET
          review_score={$score},
          review_content='{$contentEsc}',
          review_tags='{$tagsEsc}',
          reviewed_at=NOW(),
          finished_at=NOW()
        WHERE id={$orderId} AND user_id={$userId}";
if (!mysqli_query($conn, $sql)) {
    mysqli_close($conn);
    json_err('保存失败');
}

$peerName = ($side === 'rent')
    ? (isset($row['seller_name']) ? $row['seller_name'] : '出借方')
    : (isset($row['buyer_name']) ? $row['buyer_name'] : '租客');

mysqli_close($conn);
json_ok(array(
    'id' => $orderId,
    'side' => $side,
    'score' => $score,
    'content' => $content,
    'tags' => $tagsClean,
    'peerName' => $peerName,
    'reviewedAt' => date('Y-m-d H:i:s')
), '评价已提交');
