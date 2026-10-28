<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM clothes WHERE id={$id}");
        $msg = '已删除服装 #' . $id;
    }
}

$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$where = '1=1';
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (title LIKE '%{$k}%' OR category LIKE '%{$k}%' OR seller LIKE '%{$k}%')";
}
if ($tag !== '' && $tag !== 'all') {
    $t = mysqli_real_escape_string($conn, $tag);
    $where .= " AND tag='{$t}'";
}

$list = array();
$res = mysqli_query($conn, "SELECT id, title, cover, price, category, tag, seller, fav_count, available_from, available_to FROM clothes WHERE {$where} ORDER BY id DESC LIMIT 300");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

$tags = array('all' => '全部', 'calendar' => '上新日历', 'hot' => '热租', 'recommend' => '推荐', 'cate' => '分类', 'buyer' => '买家秀');

admin_header('服装管理', 'clothes.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
?>
<form class="toolbar" method="get">
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="搜索标题 / 分类 / 卖家">
  <select name="tag">
    <?php foreach ($tags as $k => $label): ?>
      <option value="<?php echo h($k); ?>" <?php echo $tag === $k ? 'selected' : ''; ?>><?php echo h($label); ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">筛选</button>
  <a class="btn" href="clothes_edit.php">新增服装</a>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>封面</th>
        <th>标题</th>
        <th>价格</th>
        <th>分类</th>
        <th>标签</th>
        <th>卖家</th>
        <th>收藏</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="9" class="muted">暂无服装</td></tr>
      <?php else: foreach ($list as $c): ?>
        <tr>
          <td><?php echo intval($c['id']); ?></td>
          <td>
            <?php $cover = admin_media($c['cover']); ?>
            <?php if ($cover): ?>
              <img class="thumb" src="<?php echo h($cover); ?>" alt=""
                onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'thumb-fallback',textContent:'无'}))">
            <?php else: ?>
              <span class="thumb-fallback">无</span>
            <?php endif; ?>
          </td>
          <td><?php echo h($c['title']); ?></td>
          <td>￥<?php echo number_format(floatval($c['price']), 2); ?></td>
          <td><?php echo h($c['category'] ? $c['category'] : '-'); ?></td>
          <td><?php echo h($c['tag']); ?></td>
          <td><?php echo h($c['seller'] ? $c['seller'] : '-'); ?></td>
          <td><?php echo intval($c['fav_count']); ?></td>
          <td>
            <a class="btn gray sm" href="clothes_edit.php?id=<?php echo intval($c['id']); ?>">编辑</a>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该服装？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo intval($c['id']); ?>">
              <button class="btn danger sm" type="submit">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
