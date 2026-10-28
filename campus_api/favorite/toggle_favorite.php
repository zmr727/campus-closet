<?php
/**
 * 切换收藏
 * POST: user_id, clothes_id  或 id(=clothes_id)
 * 返回: favorited, fav_count
 */
require_once __DIR__ . '/../config.php';

$in = read_input();
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$clothesId = isset($in['clothes_id']) ? intval($in['clothes_id']) : (isset($in['id']) ? intval($in['id']) : 0);

if ($userId <= 0) json_err('请先登录');
if ($clothesId <= 0) json_err('缺少商品');

$conn = db_connect();
$cres = mysqli_query($conn, "SELECT id, title, cover, price, category, fav_count FROM clothes WHERE id={$clothesId} LIMIT 1");
$c = $cres ? mysqli_fetch_assoc($cres) : null;
if (!$c) {
    mysqli_close($conn);
    json_err('商品不存在');
}

$exists = mysqli_query($conn, "SELECT id FROM favorites WHERE user_id={$userId} AND clothes_id={$clothesId} LIMIT 1");
$had = $exists && mysqli_fetch_assoc($exists);

if ($had) {
    mysqli_query($conn, "DELETE FROM favorites WHERE user_id={$userId} AND clothes_id={$clothesId}");
    mysqli_query($conn, "UPDATE clothes SET fav_count = IF(fav_count > 0, fav_count - 1, 0) WHERE id={$clothesId}");
    $favorited = false;
} else {
    $title = mysqli_real_escape_string($conn, $c['title']);
    $cover = mysqli_real_escape_string($conn, $c['cover']);
    $cat = mysqli_real_escape_string($conn, isset($c['category']) ? $c['category'] : '');
    $price = floatval($c['price']);
    mysqli_query($conn, "INSERT INTO favorites (user_id, clothes_id, title, cover, price, category)
                         VALUES ({$userId}, {$clothesId}, '{$title}', '{$cover}', {$price}, '{$cat}')");
    mysqli_query($conn, "UPDATE clothes SET fav_count = fav_count + 1 WHERE id={$clothesId}");
    $favorited = true;
}

$fres = mysqli_query($conn, "SELECT fav_count FROM clothes WHERE id={$clothesId} LIMIT 1");
$frow = $fres ? mysqli_fetch_assoc($fres) : null;
mysqli_close($conn);

json_ok(array(
    'favorited' => $favorited,
    'fav_count' => $frow ? intval($frow['fav_count']) : 0,
    'id' => $clothesId,
));
