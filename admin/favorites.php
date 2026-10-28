<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM favorites WHERE id={$id}");
        $msg = '已删除收藏 #' . $id;
    }
}

$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '1=1';
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (f.title LIKE '%{$k}%' OR f.category LIKE '%{$k}%' OR u.nickname LIKE '%{$k}%' OR u.phone LIKE '%{$k}%')";
}

$list = array();
$res = mysqli_query($conn, "SELECT f.id, f.user_id, f.clothes_id, f.title, f.cover, f.price, f.category, f.created_at,
    u.nickname, u.phone
    FROM favorites f
    LEFT JOIN users u ON u.id = f.user_id
    WHERE {$where}
    ORDER BY f.id DESC LIMIT 300");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

admin_header('收藏管理', 'favorites.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
?>
<form class="toolbar" method="get">
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="搜索商品 / 分类 / 用户">
  <button class="btn" type="submit">搜索</button>
  <?php if ($kw !== ''): ?><a class="btn gray" href="favorites.php">清空</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>封面</th>
        <th>商品</th>
        <th>分类</th>
        <th>价格</th>
        <th>用户</th>
        <th>衣物ID</th>
        <th>收藏时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="9" class="muted">暂无收藏</td></tr>
      <?php else: foreach ($list as $f): ?>
        <tr>
          <td><?php echo intval($f['id']); ?></td>
          <td>
            <?php $cover = admin_media($f['cover']); ?>
            <?php if ($cover): ?>
              <img class="thumb" src="<?php echo h($cover); ?>" alt=""
                onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'thumb-fallback',textContent:'无'}))">
            <?php else: ?>
              <span class="thumb-fallback">无</span>
            <?php endif; ?>
          </td>
          <td><?php echo h($f['title'] ? $f['title'] : '-'); ?></td>
          <td><?php echo h($f['category'] ? $f['category'] : '-'); ?></td>
          <td>￥<?php echo number_format(floatval($f['price']), 2); ?></td>
          <td><?php echo h($f['nickname'] ? $f['nickname'] : ('#' . intval($f['user_id']))); ?></td>
          <td><?php echo intval($f['clothes_id']); ?></td>
          <td><?php echo h($f['created_at']); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该收藏？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo intval($f['id']); ?>">
              <button class="btn danger sm" type="submit">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
