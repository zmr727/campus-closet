<?php
/**
 * 评价页拉取订单摘要
 * GET/POST: user_id, order_id
 */
require_once __DIR__ . '/../config.php';

$in = array_merge($_GET, read_input());
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$orderId = isset($in['order_id']) ? intval($in['order_id']) : 0;

if ($userId <= 0) json_err('请先登录');
if ($orderId <= 0) json_err('缺少订单');

$conn = db_connect();
$res = mysqli_query($conn, "SELECT * FROM orders WHERE id={$orderId} AND user_id={$userId} LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
if (!$row) {
    mysqli_close($conn);
    json_err('订单不存在');
}

$side = $row['side'];
$status = $row['status'];
$reviewed = !empty($row['reviewed_at']);
$canReview = !$reviewed && (
    ($side === 'rent' && $status === 'review') ||
    ($side === 'lend' && $status === 'rated')
);

$peerName = ($side === 'rent')
    ? (isset($row['seller_name']) && $row['seller_name'] !== '' ? $row['seller_name'] : '出借方')
    : (isset($row['buyer_name']) && $row['buyer_name'] !== '' ? $row['buyer_name'] : '租客');

$roleTip = ($side === 'rent') ? '评价出借方' : '评价租客';
$tags = ($side === 'rent')
    ? array('衣物完好', '描述相符', '交接方便', '沟通顺畅', '下次还租')
    : array('爱护衣物', '按时归还', '沟通友好', '守约诚信', '欢迎再租');

mysqli_close($conn);
json_ok(array(
    'id' => intval($row['id']),
    'side' => $side,
    'status' => $status,
    'title' => $row['title'],
    'cover' => media_url($row['cover']),
    'peerName' => $peerName,
    'roleTip' => $roleTip,
    'tagOptions' => $tags,
    'canReview' => $canReview,
    'reviewed' => $reviewed,
    'reviewScore' => isset($row['review_score']) ? intval($row['review_score']) : 0,
    'reviewContent' => isset($row['review_content']) ? $row['review_content'] : '',
    'reviewTags' => isset($row['review_tags']) && $row['review_tags'] !== ''
        ? explode(',', $row['review_tags'])
        : array()
));
