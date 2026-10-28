<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM buyer_show_comments WHERE show_id={$id}");
        mysqli_query($conn, "DELETE FROM buyer_shows WHERE id={$id}");
        $msg = '已删除买家秀 #' . $id;
    }
}

$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '1=1';
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (title LIKE '%{$k}%' OR nickname LIKE '%{$k}%' OR content LIKE '%{$k}%')";
}

$list = array();
$res = mysqli_query($conn, "SELECT id, user_id, nickname, title, cover, content, clothes_id, like_count, comment_count, created_at
    FROM buyer_shows WHERE {$where}
    ORDER BY id DESC LIMIT 200");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

admin_header('买家秀管理', 'buyershow.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
?>
<form class="toolbar" method="get">
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="搜索标题 / 昵称 / 文案">
  <button class="btn" type="submit">搜索</button>
  <?php if ($kw !== ''): ?><a class="btn gray" href="buyershow.php">清空</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>封面</th>
        <th>标题</th>
        <th>发布者</th>
        <th>文案</th>
        <th>关联衣物</th>
        <th>赞</th>
        <th>评论</th>
        <th>时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="10" class="muted">暂无买家秀</td></tr>
      <?php else: foreach ($list as $s): ?>
        <tr>
          <td><?php echo intval($s['id']); ?></td>
          <td>
            <?php $cover = admin_media($s['cover']); ?>
            <?php if ($cover): ?>
              <img class="thumb" src="<?php echo h($cover); ?>" alt=""
                onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'thumb-fallback',textContent:'无'}))">
            <?php else: ?>
              <span class="thumb-fallback">无</span>
            <?php endif; ?>
          </td>
          <td><?php echo h($s['title']); ?></td>
          <td><?php echo h($s['nickname'] ? $s['nickname'] : ('#' . intval($s['user_id']))); ?></td>
          <td class="cell-ellipsis"><?php echo h($s['content'] ? $s['content'] : '-'); ?></td>
          <td><?php echo $s['clothes_id'] ? intval($s['clothes_id']) : '-'; ?></td>
          <td><?php echo intval($s['like_count']); ?></td>
          <td>
            <a href="show_comments.php?show_id=<?php echo intval($s['id']); ?>"><?php echo intval($s['comment_count']); ?></a>
          </td>
          <td><?php echo h($s['created_at']); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该买家秀及评论？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo intval($s['id']); ?>">
              <button class="btn danger sm" type="submit">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
