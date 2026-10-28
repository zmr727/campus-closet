<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM messages WHERE id={$id}");
        $msg = '已删除会话 #' . $id;
    }
}

$typeMap = array(
    'buyer' => '买家咨询',
    'mine' => '我的咨询',
    'order' => '订单通知',
    'system' => '系统消息',
);

$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$where = '1=1';
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (m.name LIKE '%{$k}%' OR m.preview LIKE '%{$k}%' OR u.nickname LIKE '%{$k}%')";
}
if ($type !== '' && isset($typeMap[$type])) {
    $where .= " AND m.msg_type='" . mysqli_real_escape_string($conn, $type) . "'";
}

$list = array();
$res = mysqli_query($conn, "SELECT m.id, m.owner_id, m.peer_id, m.thread_id, m.msg_type, m.name, m.preview,
    m.time_label, m.unread, m.is_old, m.created_at,
    u.nickname AS owner_name
    FROM messages m
    LEFT JOIN users u ON u.id = m.owner_id
    WHERE {$where}
    ORDER BY m.id DESC LIMIT 300");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

admin_header('消息会话', 'messages.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
?>
<form class="toolbar" method="get">
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="搜索会话名 / 预览 / 用户">
  <select name="type">
    <option value="">全部类型</option>
    <?php foreach ($typeMap as $k => $label): ?>
      <option value="<?php echo h($k); ?>" <?php echo $type === $k ? 'selected' : ''; ?>><?php echo h($label); ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">筛选</button>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>所属用户</th>
        <th>对方</th>
        <th>线程</th>
        <th>类型</th>
        <th>显示名</th>
        <th>预览</th>
        <th>未读</th>
        <th>时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="10" class="muted">暂无会话</td></tr>
      <?php else: foreach ($list as $m): ?>
        <tr>
          <td><?php echo intval($m['id']); ?></td>
          <td><?php echo h($m['owner_name'] ? $m['owner_name'] : ('#' . intval($m['owner_id']))); ?></td>
          <td><?php echo $m['peer_id'] ? intval($m['peer_id']) : '-'; ?></td>
          <td>
            <a href="chats.php?thread_id=<?php echo intval($m['thread_id']); ?>"><?php echo intval($m['thread_id']); ?></a>
          </td>
          <td><?php echo h(isset($typeMap[$m['msg_type']]) ? $typeMap[$m['msg_type']] : $m['msg_type']); ?></td>
          <td><?php echo h($m['name']); ?></td>
          <td class="cell-ellipsis"><?php echo h($m['preview']); ?></td>
          <td><?php echo intval($m['unread']); ?></td>
          <td><?php echo h($m['time_label'] ? $m['time_label'] : $m['created_at']); ?></td>
          <td>
            <a class="btn gray sm" href="chats.php?thread_id=<?php echo intval($m['thread_id']); ?>">聊天</a>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该会话？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo intval($m['id']); ?>">
              <button class="btn danger sm" type="submit">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
