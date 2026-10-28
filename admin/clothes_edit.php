<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$item = array(
    'title' => '',
    'cover' => '/images/home/rec.png',
    'price' => '20',
    'category' => '汉服',
    'size_info' => 'S/M/L',
    'tag' => 'recommend',
    'date_label' => '',
    'intro' => '',
    'seller' => '',
    'fav_count' => 0,
    'available_from' => date('Y-m-d'),
    'available_to' => date('Y-m-d', strtotime('+90 days')),
);

if ($id > 0) {
    $res = mysqli_query($conn, "SELECT * FROM clothes WHERE id={$id} LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $item = $row;
    } else {
        $id = 0;
    }
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $cover = isset($_POST['cover']) ? trim($_POST['cover']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $size_info = isset($_POST['size_info']) ? trim($_POST['size_info']) : '';
    $tag = isset($_POST['tag']) ? trim($_POST['tag']) : 'recommend';
    $date_label = isset($_POST['date_label']) ? trim($_POST['date_label']) : '';
    $intro = isset($_POST['intro']) ? trim($_POST['intro']) : '';
    $seller = isset($_POST['seller']) ? trim($_POST['seller']) : '';
    $fav_count = isset($_POST['fav_count']) ? intval($_POST['fav_count']) : 0;
    $available_from = isset($_POST['available_from']) ? trim($_POST['available_from']) : '';
    $available_to = isset($_POST['available_to']) ? trim($_POST['available_to']) : '';
    $editId = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($title === '') {
        $err = '请填写标题';
    } else {
        $title = mysqli_real_escape_string($conn, $title);
        $cover = mysqli_real_escape_string($conn, $cover);
        $category = mysqli_real_escape_string($conn, $category);
        $size_info = mysqli_real_escape_string($conn, $size_info);
        $tag = mysqli_real_escape_string($conn, $tag);
        $date_label = mysqli_real_escape_string($conn, $date_label);
        $intro = mysqli_real_escape_string($conn, $intro);
        $seller = mysqli_real_escape_string($conn, $seller);
        $af = $available_from !== '' ? "'" . mysqli_real_escape_string($conn, $available_from) . "'" : 'NULL';
        $at = $available_to !== '' ? "'" . mysqli_real_escape_string($conn, $available_to) . "'" : 'NULL';
        $dl = $date_label !== '' ? "'{$date_label}'" : 'NULL';

        if ($editId > 0) {
            $sql = "UPDATE clothes SET title='{$title}', cover='{$cover}', price={$price}, category='{$category}', size_info='{$size_info}', tag='{$tag}', date_label={$dl}, intro='{$intro}', seller='{$seller}', fav_count={$fav_count}, available_from={$af}, available_to={$at} WHERE id={$editId}";
            if (mysqli_query($conn, $sql)) {
                header('Location: clothes.php');
                exit;
            }
            $err = '保存失败: ' . mysqli_error($conn);
        } else {
            $sql = "INSERT INTO clothes (title, cover, price, category, size_info, tag, date_label, intro, seller, fav_count, available_from, available_to) VALUES ('{$title}','{$cover}',{$price},'{$category}','{$size_info}','{$tag}',{$dl},'{$intro}','{$seller}',{$fav_count},{$af},{$at})";
            if (mysqli_query($conn, $sql)) {
                header('Location: clothes.php');
                exit;
            }
            $err = '新增失败: ' . mysqli_error($conn);
        }
    }
    $item = array_merge($item, $_POST);
}

admin_header($id > 0 ? '编辑服装' : '新增服装', 'clothes.php');
if ($err) echo '<div class="msg err">' . h($err) . '</div>';
?>
<form class="form" method="post">
  <input type="hidden" name="id" value="<?php echo intval($id); ?>">
  <div class="row">
    <label>标题</label>
    <input type="text" name="title" required value="<?php echo h($item['title']); ?>">
  </div>
  <div class="row">
    <label>封面路径（如 /images/home/rec.png 或完整 URL）</label>
    <input type="text" name="cover" value="<?php echo h($item['cover']); ?>">
    <?php $preview = admin_media($item['cover']); if ($preview): ?>
      <div style="margin-top:8px"><img class="thumb" style="width:80px;height:80px" src="<?php echo h($preview); ?>" alt=""></div>
    <?php endif; ?>
  </div>
  <div class="row">
    <label>日租价格</label>
    <input type="number" step="0.01" name="price" value="<?php echo h($item['price']); ?>">
  </div>
  <div class="row">
    <label>分类</label>
    <input type="text" name="category" value="<?php echo h($item['category']); ?>">
  </div>
  <div class="row">
    <label>尺码</label>
    <input type="text" name="size_info" value="<?php echo h($item['size_info']); ?>">
  </div>
  <div class="row">
    <label>标签 tag</label>
    <select name="tag">
      <?php
      $tagOpts = array('calendar' => '上新日历', 'hot' => '热租', 'recommend' => '推荐', 'cate' => '分类', 'buyer' => '买家秀');
      foreach ($tagOpts as $k => $label):
      ?>
        <option value="<?php echo h($k); ?>" <?php echo $item['tag'] === $k ? 'selected' : ''; ?>><?php echo h($label); ?> (<?php echo h($k); ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="row">
    <label>上新日期文案（仅日历，如 7.24）</label>
    <input type="text" name="date_label" value="<?php echo h(isset($item['date_label']) ? $item['date_label'] : ''); ?>">
  </div>
  <div class="row">
    <label>简介</label>
    <textarea name="intro"><?php echo h(isset($item['intro']) ? $item['intro'] : ''); ?></textarea>
  </div>
  <div class="row">
    <label>卖家昵称</label>
    <input type="text" name="seller" value="<?php echo h(isset($item['seller']) ? $item['seller'] : ''); ?>">
  </div>
  <div class="row">
    <label>收藏数</label>
    <input type="number" name="fav_count" value="<?php echo intval(isset($item['fav_count']) ? $item['fav_count'] : 0); ?>">
  </div>
  <div class="row">
    <label>可租开始日期</label>
    <input type="date" name="available_from" value="<?php echo h(isset($item['available_from']) ? $item['available_from'] : ''); ?>">
  </div>
  <div class="row">
    <label>可租结束日期</label>
    <input type="date" name="available_to" value="<?php echo h(isset($item['available_to']) ? $item['available_to'] : ''); ?>">
  </div>
  <div class="actions">
    <button class="btn" type="submit">保存</button>
    <a class="btn gray" href="clothes.php">返回</a>
  </div>
</form>
<?php admin_footer(); ?>
