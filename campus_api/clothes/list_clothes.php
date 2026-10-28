<?php
/**
 * 服装列表
 * POST/GET:
 *   tag=calendar|hot|recommend|buyer|cate|all
 *   keyword=
 *   category=所属类型
 *   rent_start=YYYY-MM-DD  rent_end=YYYY-MM-DD  （筛选：可租时段覆盖整段租期）
 */
require_once __DIR__ . '/../config.php';
// 接收前端参数`tag`，无传参则默认值`all`
$in = array_merge($_GET, read_input());
$tag = isset($in['tag']) ? trim($in['tag']) : 'all';
$keyword = isset($in['keyword']) ? trim($in['keyword']) : '';
$category = isset($in['category']) ? trim($in['category']) : '';
$seller = isset($in['seller']) ? trim($in['seller']) : '';
$userId = isset($in['user_id']) ? intval($in['user_id']) : 0;
$rentStart = isset($in['rent_start']) ? trim($in['rent_start']) : '';
$rentEnd = isset($in['rent_end']) ? trim($in['rent_end']) : '';

$conn = db_connect();
$where = '1=1';
// 按需筛选：只在用户指定标签时才增加标签过滤
if ($tag !== '' && $tag !== 'all') {
    $t = mysqli_real_escape_string($conn, $tag);
    $where .= " AND tag='{$t}'";
}
if ($category !== '' && $category !== '全部' && $category !== '全部分类') {
    $c = mysqli_real_escape_string($conn, $category);
    $where .= " AND category='{$c}'";
}
if ($seller !== '') {
    $s = mysqli_real_escape_string($conn, $seller);
    $where .= " AND seller='{$s}'";
}
$hasUserIdCol = false;
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM clothes LIKE 'user_id'");
if ($colCheck && mysqli_num_rows($colCheck) > 0) {
    $hasUserIdCol = true;
}
if ($userId > 0 && $hasUserIdCol) {
    $where .= " AND user_id={$userId}";
}
if ($keyword !== '') {
    $k = mysqli_real_escape_string($conn, $keyword);
    $where .= " AND (title LIKE '%{$k}%' OR category LIKE '%{$k}%' OR intro LIKE '%{$k}%')";
}
if ($rentStart !== '' && $rentEnd !== '') {
    $rs = mysqli_real_escape_string($conn, $rentStart);
    $re = mysqli_real_escape_string($conn, $rentEnd);
    // 可租时段必须完整覆盖所选租期
    $where .= " AND available_from IS NOT NULL AND available_to IS NOT NULL";
    $where .= " AND available_from <= '{$rs}' AND available_to >= '{$re}'";
}

$uidField = $hasUserIdCol ? ', user_id' : '';
$sql = "SELECT id, title, cover, price, category, size_info, tag, date_label, intro, seller{$uidField}, fav_count, available_from, available_to
        FROM clothes WHERE {$where} ORDER BY id ASC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    mysqli_close($conn);
    json_err('查询失败: ' . mysqli_error($conn) . '，请先打开 seed_clothes.php');
}
// 初始化空数组 `$list`，用来存放从数据库查询出来的每一条数据，最终作为接口返回的列表数据
$list = array();
while ($row = mysqli_fetch_assoc($res)) {
    $row['id'] = intval($row['id']);
    $row['price'] = floatval($row['price']);
    $row['fav_count'] = intval($row['fav_count']);
    $row['cover'] = media_url($row['cover']);
    $list[] = $row;
}
mysqli_close($conn);
json_ok($list);
