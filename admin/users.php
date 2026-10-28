<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM users WHERE id={$id}");
        $msg = '已删除用户 #' . $id;
    }
}

$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '1=1';
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (nickname LIKE '%{$k}%' OR phone LIKE '%{$k}%' OR username LIKE '%{$k}%' OR student_id LIKE '%{$k}%')";
}

$list = array();
$res = mysqli_query($conn, "SELECT id, username, phone, nickname, student_id, campus, login_type, avatar FROM users WHERE {$where} ORDER BY id DESC LIMIT 200");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

admin_header('用户管理', 'users.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
if ($err) echo '<div class="msg err">' . h($err) . '</div>';
?>
<form class="toolbar" method="get">
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="搜索昵称 / 手机号 / 学号">
  <button class="btn" type="submit">搜索</button>
  <?php if ($kw !== ''): ?><a class="btn gray" href="users.php">清空</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>头像</th>
        <th>昵称</th>
        <th>手机号</th>
        <th>学号</th>
        <th>校区</th>
        <th>登录方式</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="8" class="muted">暂无用户</td></tr>
      <?php else: foreach ($list as $u): ?>
        <tr>
          <td><?php echo intval($u['id']); ?></td>
          <td>
            <?php $av = admin_media($u['avatar']); ?>
            <?php if ($av): ?>
              <img class="thumb" src="<?php echo h($av); ?>" alt="">
            <?php else: ?>
              <span class="muted">-</span>
            <?php endif; ?>
          </td>
          <td><?php echo h($u['nickname'] ? $u['nickname'] : $u['username']); ?></td>
          <td><?php echo h($u['phone'] ? $u['phone'] : '-'); ?></td>
          <td><?php echo h($u['student_id'] ? $u['student_id'] : '-'); ?></td>
          <td><?php echo h($u['campus'] ? $u['campus'] : '-'); ?></td>
          <td><?php echo h($u['login_type'] ? $u['login_type'] : '-'); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该用户？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo intval($u['id']); ?>">
              <button class="btn danger sm" type="submit">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
