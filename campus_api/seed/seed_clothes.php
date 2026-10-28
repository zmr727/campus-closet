<?php
/**
 * 建 clothes 表并写入首页固定展示数据
 * 浏览器打开一次：http://127.0.0.1/miniprogram/campus_api/seed/seed_clothes.php
 */
require_once __DIR__ . '/../config.php';

$conn = db_connect();

mysqli_query($conn, 'DROP TABLE IF EXISTS clothes');
$sql = "CREATE TABLE clothes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(100) NOT NULL COMMENT '名称',
  cover VARCHAR(255) NOT NULL COMMENT '封面图路径',
  price DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '租金起/天',
  category VARCHAR(50) DEFAULT NULL COMMENT '所属类型/分类',
  size_info VARCHAR(100) DEFAULT NULL COMMENT '尺码',
  tag VARCHAR(30) NOT NULL DEFAULT 'recommend' COMMENT 'calendar/hot/recommend/buyer/cate',
  date_label VARCHAR(20) DEFAULT NULL COMMENT '日历日期如7.21',
  intro VARCHAR(255) DEFAULT NULL COMMENT '简介',
  seller VARCHAR(64) DEFAULT NULL COMMENT '卖家昵称',
  fav_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数',
  available_from DATE DEFAULT NULL COMMENT '可租开始日期',
  available_to DATE DEFAULT NULL COMMENT '可租结束日期',
  PRIMARY KEY (id),
  KEY idx_tag (tag),
  KEY idx_category (category),
  KEY idx_available (available_from, available_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if (!mysqli_query($conn, $sql)) {
    json_err('建表失败: ' . mysqli_error($conn));
}

// title, cover, price, category, size, tag, date, intro, seller, fav, available_from, available_to
$defaultFrom = '2026-07-01';
$defaultTo = '2026-12-31';

$rows = array(
    // 上新日历
    array('礼服长裙', '/images/home/cal1.png', 35, '礼服', 'S/M/L', 'calendar', '7.21', '粉色礼服长裙，适合晚会与拍照', '校园小桃', 12, $defaultFrom, $defaultTo),
    array('礼服短裙', '/images/home/cal2.png', 28, '礼服', 'S/M/L', 'calendar', '7.22', '白色短礼服，轻便日常可穿', '阿宁同学', 8, $defaultFrom, $defaultTo),
    array('正装西装', '/images/home/cal3.png', 25, '正装', 'M/L/XL', 'calendar', '7.23', '黑色西装外套，面试答辩适用', '学长衣柜', 15, $defaultFrom, $defaultTo),
    array('学士服', '/images/home/new_xueshifu.png', 20, '正装', '均码', 'calendar', '7.24', '毕业季学士服上新', '毕业季补给站', 36, $defaultFrom, $defaultTo),
    array('汉服', '/images/home/new_hanfu.png', 40, '汉服', '均码可调', 'calendar', '7.25', '汉服上新可租', '夜子溪', 28, $defaultFrom, $defaultTo),

    // 热租榜
    array('礼服', '/images/home/hot1.png', 30, '礼服', 'S/M/L', 'hot', '', '热门礼服款', '校园小桃', 42, $defaultFrom, $defaultTo),
    array('学士服', '/images/home/hot2.png', 20, '正装', '均码', 'hot', '', '毕业季热租学士服', '毕业季补给站', 58, $defaultFrom, $defaultTo),
    array('马面裙套装', '/images/home/hot3.png', 26, '汉服', 'S/M', 'hot', '', '马面裙套装热租', '汉服铺子', 33, $defaultFrom, $defaultTo),

    // 推荐
    array('夜子溪原创原款汉服', '/images/home/rec.png', 30, '汉服', 'S/M/L', 'recommend', '', '原创汉服，拍照出片', '夜子溪', 66, $defaultFrom, $defaultTo),
    array('魔法袍套装', '/images/home/rec_magic_robe.png', 35, 'Cos', 'S/M/L', 'recommend', '', '魔法袍可租，拍照出片', '阿宁同学', 19, $defaultFrom, $defaultTo),
    array('泰国校服套装', '/images/home/rec_thai_uniform.png', 28, '校服', 'S/M', 'recommend', '', '泰式校服风格可租', '校园小桃', 11, $defaultFrom, $defaultTo),
    array('黑色西装外套', '/images/home/rec_xizhuang_model.png', 24, '正装', 'M/L', 'recommend', '', '面试答辩适用', '学长衣柜', 17, $defaultFrom, $defaultTo),
    array('马面裙套装', '/images/home/new_mamianskirt.png', 26, '汉服', 'S/M', 'recommend', '', '马面裙套装可租', '汉服铺子', 24, $defaultFrom, $defaultTo),

    // 分类页（部分可租时段不同，方便按租期筛选演示）
    array('新中式日常套装', '/images/home/cate_xinzhongshi.png', 38, '新中式', 'S/M/L', 'cate', '', '新中式改良可租', '汉服铺子', 18, '2026-07-01', '2026-12-31'),
    array('蓝色系汉服', '/images/home/rec.png', 50, '汉服', 'L', 'cate', '', '蓝色系汉服出片', '夜子溪', 44, '2026-07-01', '2026-12-31'),
    array('黑金配色马面裙', '/images/home/cate_mingzhi.png', 40, '明制', 'S', 'cate', '', '明制马面裙可租', '汉服铺子', 27, '2026-07-10', '2026-09-30'),
    array('民国旗袍套装', '/images/home/cate_minguo.png', 45, '民国风', 'S/M', 'cate', '', '民国风拍照可租', '阿宁同学', 16, '2026-08-01', '2026-11-30'),
    array('设计师款连衣裙', '/images/home/cate_shejishi.png', 55, '设计师款', 'S/M', 'cate', '', '小众设计师款', '校园小桃', 13, '2026-07-01', '2026-10-31'),
    array('泰式校服毕业季拍照', '/images/home/rec_thai_uniform.png', 40, '学院风', 'S', 'cate', '', '泰式校服毕业拍照', '校园小桃', 31, '2026-07-01', '2026-07-31'),
    array('品牌女装通勤套', '/images/home/cate_pinpai.png', 42, '品牌女装', 'M', 'cate', '', '品牌女装日常可租', '学长衣柜', 12, '2026-07-01', '2026-12-31'),
    array('粉色洛丽塔套装', '/images/home/cate_lolita.png', 48, '洛丽塔', 'S/M', 'cate', '', '洛丽塔甜美可租', '阿宁同学', 22, '2026-07-15', '2026-12-31'),
    array('魔法袍cos套装', '/images/home/rec_magic_robe.png', 35, 'cos服', 'S/M/L', 'cate', '', '主题cos可租', '阿宁同学', 25, '2026-07-01', '2026-12-31'),
    array('节日主题服装', '/images/home/cate_zhuti.png', 36, '主题服装', '均码', 'cate', '', '活动主题装可租', '毕业季补给站', 10, '2026-09-01', '2026-10-15'),
    array('旅游拍照连衣裙', '/images/home/cate_lvyou.png', 39, '旅游拍照', 'S/M', 'cate', '', '出游拍照可租', '校园小桃', 14, '2026-07-01', '2026-12-31'),
    array('面试套装', '/images/home/rec_xizhuang_model.png', 50, '职业装', 'M', 'cate', '', '面试答辩职业装', '学长衣柜', 29, '2026-07-20', '2026-08-20'),
);

$n = 0;
foreach ($rows as $r) {
    $title = mysqli_real_escape_string($conn, $r[0]);
    $cover = mysqli_real_escape_string($conn, $r[1]);
    $price = floatval($r[2]);
    $cat = mysqli_real_escape_string($conn, $r[3]);
    $size = mysqli_real_escape_string($conn, $r[4]);
    $tag = mysqli_real_escape_string($conn, $r[5]);
    $date = mysqli_real_escape_string($conn, $r[6]);
    $intro = mysqli_real_escape_string($conn, $r[7]);
    $seller = mysqli_real_escape_string($conn, $r[8]);
    $fav = intval($r[9]);
    $from = mysqli_real_escape_string($conn, $r[10]);
    $to = mysqli_real_escape_string($conn, $r[11]);
    $dateSql = $date === '' ? 'NULL' : "'{$date}'";
    $q = "INSERT INTO clothes (title, cover, price, category, size_info, tag, date_label, intro, seller, fav_count, available_from, available_to)
          VALUES ('{$title}', '{$cover}', {$price}, '{$cat}', '{$size}', '{$tag}', {$dateSql}, '{$intro}', '{$seller}', {$fav}, '{$from}', '{$to}')";
    if (mysqli_query($conn, $q)) {
        $n++;
    }
}

$countRes = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM clothes');
$row = mysqli_fetch_assoc($countRes);
mysqli_close($conn);

json_ok(array(
    'inserted' => $n,
    'total' => intval($row['c']),
    'fields' => array('id','title','cover','price','category','size_info','tag','date_label','intro','seller','fav_count','available_from','available_to'),
), 'clothes 表已创建并写入固定数据');
