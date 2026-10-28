<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        $res = mysqli_query($conn, "SELECT show_id FROM buyer_show_comments WHERE id={$id} LIMIT 1");
        $showId = 0;
        if ($res && ($row = mysqli_fetch_assoc($res))) $showId = intval($row['show_id']);
        mysqli_query($conn, "DELETE FROM buyer_show_comments WHERE id={$id}");
        if ($showId > 0) {
            mysqli_query($conn, "UPDATE buyer_shows SET comment_count = GREATEST(comment_count - 1, 0) WHERE id={$showId}");
        }
        $msg = '已删除评论 #' . $id;
    }
}

$showId = isset($_GET['show_id']) ? intval($_GET['show_id']) : 0;
$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '1=1';
if ($showId > 0) {
    $where .= " AND c.show_id={$showId}";
}
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (c.nickname LIKE '%{$k}%' OR c.content LIKE '%{$k}%' OR s.title LIKE '%{$k}%')";
}

$list = array();
$res = mysqli_query($conn, "SELECT c.id, c.show_id, c.user_id, c.nickname, c.content, c.created_at,
    s.title AS show_title
    FROM buyer_show_comments c
    LEFT JOIN buyer_shows s ON s.id = c.show_id
    WHERE {$where}
    ORDER BY c.id DESC LIMIT 300");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

admin_header('买家秀评论', 'show_comments.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
?>
<form class="toolbar" method="get">
  <?php if ($showId > 0): ?>
    <input type="hidden" name="show_id" value="<?php echo $showId; ?>">
  <?php endif; ?>
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="搜索昵称 / 内容 / 买家秀标题">
  <button class="btn" type="submit">搜索</button>
  <?php if ($showId > 0 || $kw !== ''): ?><a class="btn gray" href="show_comments.php">全部评论</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>买家秀</th>
        <th>评论者</th>
        <th>内容</th>
        <th>时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="muted">暂无评论</td></tr>
      <?php else: foreach ($list as $c): ?>
        <tr>
          <td><?php echo intval($c['id']); ?></td>
          <td>
            <a href="show_comments.php?show_id=<?php echo intval($c['show_id']); ?>">
              <?php echo h($c['show_title'] ? $c['show_title'] : ('#' . intval($c['show_id']))); ?>
            </a>
          </td>
          <td><?php echo h($c['nickname'] ? $c['nickname'] : ('#' . intval($c['user_id']))); ?></td>
          <td class="cell-ellipsis"><?php echo h($c['content']); ?></td>
          <td><?php echo h($c['created_at']); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该评论？');">
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
