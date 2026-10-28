<?php
/**
 * 演示：补几条租客评价，方便详情页 / 收到的评价联调
 * 浏览器打开本文件即可
 */
require_once __DIR__ . '/../config.php';

$conn = db_connect();

$cols = array(
    'review_score' => "ADD COLUMN review_score TINYINT UNSIGNED DEFAULT NULL COMMENT '我对对方的评分1-5' AFTER finished_at",
    'review_content' => "ADD COLUMN review_content VARCHAR(255) DEFAULT NULL COMMENT '评价内容' AFTER review_score",
    'review_tags' => "ADD COLUMN review_tags VARCHAR(255) DEFAULT NULL COMMENT '评价标签' AFTER review_content",
    'reviewed_at' => "ADD COLUMN reviewed_at DATETIME DEFAULT NULL COMMENT '我方评价时间' AFTER review_tags"
);
foreach ($cols as $name => $ddl) {
    $c = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '{$name}'");
    if (!$c || mysqli_num_rows($c) === 0) {
        mysqli_query($conn, "ALTER TABLE orders {$ddl}");
    }
}

$msgs = array();

// 找一件有订单的衣服：优先已有 rent 侧评价态/完成态，否则随便取一条 rent 订单改成已评价
$res = mysqli_query($conn, "SELECT id, order_no, clothes_id, user_id, peer_id, title, status, reviewed_at
                            FROM orders WHERE side='rent' ORDER BY id DESC LIMIT 20");
$updated = 0;
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        if (!empty($row['reviewed_at'])) continue;
        $id = intval($row['id']);
        $score = 5;
        $content = '衣物成色很好，交接顺利，下次还想租。';
        $tags = '衣物完好,交接方便,沟通顺畅';
        $contentEsc = mysqli_real_escape_string($conn, $content);
        $tagsEsc = mysqli_real_escape_string($conn, $tags);
        mysqli_query($conn, "UPDATE orders SET
            status='review',
            review_score={$score},
            review_content='{$contentEsc}',
            review_tags='{$tagsEsc}',
            reviewed_at=NOW(),
            finished_at=NOW()
          WHERE id={$id}");
        $updated++;
        $msgs[] = "已写入租客评价 order#{$id} clothes#{$row['clothes_id']}「{$row['title']}」";

        // 若有出借方同行，也写一条对方评租客（方便「收到的评价」两边都有）
        $ono = mysqli_real_escape_string($conn, $row['order_no']);
        $lend = mysqli_query($conn, "SELECT id, reviewed_at FROM orders WHERE order_no='{$ono}' AND side='lend' LIMIT 1");
        $lrow = $lend ? mysqli_fetch_assoc($lend) : null;
        if ($lrow && empty($lrow['reviewed_at'])) {
            $lid = intval($lrow['id']);
            $c2 = mysqli_real_escape_string($conn, '租客很爱护衣物，按时归还，体验很好。');
            $t2 = mysqli_real_escape_string($conn, '爱护衣物,按时归还,沟通友好');
            mysqli_query($conn, "UPDATE orders SET
                status='rated',
                review_score=5,
                review_content='{$c2}',
                review_tags='{$t2}',
                reviewed_at=NOW(),
                finished_at=NOW()
              WHERE id={$lid}");
            $msgs[] = "已写入出借方评价 order#{$lid}";
        }
        if ($updated >= 2) break;
    }
}

if ($updated === 0) {
    $msgs[] = '没有可写入的租客订单（可能都已评价，或还没有订单）。可先走完一单到评价态再刷新本页。';
}

mysqli_close($conn);

header('Content-Type: text/html; charset=utf-8');
echo '<h2>seed_reviews 完成</h2><ul>';
foreach ($msgs as $m) {
    echo '<li>' . htmlspecialchars($m, ENT_QUOTES, 'UTF-8') . '</li>';
}
echo '</ul><p>然后：打开商品详情看「租客评价」；登录后进 我的 → 收到的评价。</p>';
