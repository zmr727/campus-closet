<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM vip_members WHERE id={$id}");
        $msg = '已删除会员记录 #' . $id;
    }
}

$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '1=1';
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (u.nickname LIKE '%{$k}%' OR u.phone LIKE '%{$k}%' OR u.username LIKE '%{$k}%' OR v.plan LIKE '%{$k}%')";
}

$list = array();
$res = mysqli_query($conn, "SELECT v.id, v.user_id, v.plan, v.opened_at, v.expire_at,
    u.nickname, u.phone, u.username, u.avatar
    FROM vip_members v
    LEFT JOIN users u ON u.id = v.user_id
    WHERE {$where}
    ORDER BY v.id DESC LIMIT 200");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

$planMap = array('semester' => '学期会员', 'month' => '月度会员', 'year' => '年度会员');

admin_header('会员管理', 'vip.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
?>
<form class="toolbar" method="get">
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="搜索昵称 / 手机号 / 套餐">
  <button class="btn" type="submit">搜索</button>
  <?php if ($kw !== ''): ?><a class="btn gray" href="vip.php">清空</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>用户</th>
        <th>昵称</th>
        <th>手机号</th>
        <th>套餐</th>
        <th>开通时间</th>
        <th>到期时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="8" class="muted">暂无会员记录</td></tr>
      <?php else: foreach ($list as $v): ?>
        <tr>
          <td><?php echo intval($v['id']); ?></td>
          <td><?php echo intval($v['user_id']); ?></td>
          <td><?php echo h($v['nickname'] ? $v['nickname'] : ($v['username'] ? $v['username'] : '-')); ?></td>
          <td><?php echo h($v['phone'] ? $v['phone'] : '-'); ?></td>
          <td><?php echo h(isset($planMap[$v['plan']]) ? $planMap[$v['plan']] : $v['plan']); ?></td>
          <td><?php echo h($v['opened_at']); ?></td>
          <td><?php echo h($v['expire_at'] ? $v['expire_at'] : '长期'); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该会员记录？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo intval($v['id']); ?>">
              <button class="btn danger sm" type="submit">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
