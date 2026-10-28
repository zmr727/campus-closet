<?php
/**
 * 商品详情：租客对该商品/出借方的评价列表
 * GET/POST: clothes_id, limit(可选，默认20)
 */
require_once __DIR__ . '/../config.php';

$in = array_merge($_GET, read_input());
$clothesId = isset($in['clothes_id']) ? intval($in['clothes_id']) : 0;
$limit = isset($in['limit']) ? intval($in['limit']) : 20;
if ($clothesId <= 0) json_err('缺少商品');
if ($limit <= 0 || $limit > 50) $limit = 20;

$conn = db_connect();

$col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'reviewed_at'");
if (!$col || mysqli_num_rows($col) === 0) {
    mysqli_close($conn);
    json_ok(array(
        'list' => array(),
        'count' => 0,
        'avgScore' => 0,
        'scoreText' => '暂无评分'
    ));
}

$sql = "SELECT o.review_score, o.review_content, o.review_tags, o.reviewed_at, o.title,
               o.user_id, u.nickname, u.avatar
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        WHERE o.clothes_id={$clothesId}
          AND o.side='rent'
          AND o.reviewed_at IS NOT NULL
          AND o.review_score IS NOT NULL
        ORDER BY o.reviewed_at DESC
        LIMIT {$limit}";
$res = mysqli_query($conn, $sql);

$list = array();
$sum = 0;
$count = 0;
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $score = intval($row['review_score']);
        $sum += $score;
        $count++;
        $tags = array();
        if (!empty($row['review_tags'])) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $row['review_tags']))));
        }
        $list[] = array(
            'score' => $score,
            'content' => $row['review_content'] ? $row['review_content'] : '',
            'tags' => $tags,
            'timeText' => $row['reviewed_at'] ? date('Y-m-d', strtotime($row['reviewed_at'])) : '',
            'nickname' => $row['nickname'] ? $row['nickname'] : '校园用户',
            'avatar' => media_url($row['avatar'] ? $row['avatar'] : '/images/mine/avatar.png')
        );
    }
}

// 总数与均分（不限 limit）
$agg = mysqli_query($conn, "SELECT COUNT(*) AS cnt, AVG(review_score) AS avg_score
                            FROM orders
                            WHERE clothes_id={$clothesId}
                              AND side='rent'
                              AND reviewed_at IS NOT NULL
                              AND review_score IS NOT NULL");
$avgScore = 0;
$totalCount = $count;
if ($agg && ($ar = mysqli_fetch_assoc($agg))) {
    $totalCount = intval($ar['cnt']);
    $avgScore = $ar['avg_score'] !== null ? round(floatval($ar['avg_score']), 1) : 0;
}
mysqli_close($conn);

json_ok(array(
    'list' => $list,
    'count' => $totalCount,
    'avgScore' => $avgScore,
    'scoreText' => $totalCount > 0 ? strval($avgScore) : '暂无评分'
));
