<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM message_chats WHERE id={$id}");
        $msg = '已删除聊天 #' . $id;
    }
}

$threadId = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : 0;
$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '1=1';
if ($threadId > 0) {
    $where .= " AND c.thread_id={$threadId}";
}
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (c.content LIKE '%{$k}%' OR u.nickname LIKE '%{$k}%')";
}

$list = array();
$res = mysqli_query($conn, "SELECT c.id, c.thread_id, c.sender_id, c.content, c.created_at,
    u.nickname AS sender_name
    FROM message_chats c
    LEFT JOIN users u ON u.id = c.sender_id
    WHERE {$where}
    ORDER BY c.id DESC LIMIT 500");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

admin_header('聊天记录', 'chats.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
?>
<form class="toolbar" method="get">
  <?php if ($threadId > 0): ?>
    <input type="hidden" name="thread_id" value="<?php echo $threadId; ?>">
  <?php endif; ?>
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="搜索内容 / 发送者">
  <button class="btn" type="submit">搜索</button>
  <?php if ($threadId > 0 || $kw !== ''): ?><a class="btn gray" href="chats.php">全部聊天</a><?php endif; ?>
  <a class="btn gray" href="messages.php">返回会话列表</a>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>线程</th>
        <th>发送者</th>
        <th>内容</th>
        <th>时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="6" class="muted">暂无聊天记录</td></tr>
      <?php else: foreach ($list as $c): ?>
        <tr>
          <td><?php echo intval($c['id']); ?></td>
          <td>
            <a href="chats.php?thread_id=<?php echo intval($c['thread_id']); ?>"><?php echo intval($c['thread_id']); ?></a>
          </td>
          <td><?php echo h($c['sender_name'] ? $c['sender_name'] : ('#' . intval($c['sender_id']))); ?></td>
          <td class="cell-ellipsis"><?php echo h($c['content']); ?></td>
          <td><?php echo h($c['created_at']); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该聊天？');">
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
