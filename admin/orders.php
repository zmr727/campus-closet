<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$statusLabels = array(
    'pay' => '待付款',
    'pickup' => '待取衣',
    'deliver' => '待交衣',
    'renting' => '租赁中',
    'lending' => '出租中',
    'return' => '待归还',
    'reclaim' => '待收回',
    'review' => '评价',
    'rated' => '待评价',
    'done' => '已完成',
    'cancel' => '已取消',
);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0 && $action === 'delete') {
        mysqli_query($conn, "DELETE FROM orders WHERE id={$id}");
        $msg = '已删除订单 #' . $id;
    } elseif ($id > 0 && $action === 'status') {
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        if ($status !== '') {
            $st = mysqli_real_escape_string($conn, $status);
            mysqli_query($conn, "UPDATE orders SET status='{$st}' WHERE id={$id}");
            $msg = '已更新订单 #' . $id . ' 状态';
        }
    }
}

$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$side = isset($_GET['side']) ? trim($_GET['side']) : '';

$where = '1=1';
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (order_no LIKE '%{$k}%' OR title LIKE '%{$k}%' OR buyer_name LIKE '%{$k}%' OR seller_name LIKE '%{$k}%')";
}
if ($status !== '') {
    $where .= " AND status='" . mysqli_real_escape_string($conn, $status) . "'";
}
if ($side === 'rent' || $side === 'lend') {
    $where .= " AND side='" . mysqli_real_escape_string($conn, $side) . "'";
}

$list = array();
$res = mysqli_query($conn, "SELECT * FROM orders WHERE {$where} ORDER BY id DESC LIMIT 300");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

admin_header('订单管理', 'orders.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
if ($err) echo '<div class="msg err">' . h($err) . '</div>';
?>
<form class="toolbar" method="get">
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="订单号 / 商品 / 姓名">
  <select name="side">
    <option value="">全部类型</option>
    <option value="rent" <?php echo $side === 'rent' ? 'selected' : ''; ?>>求租</option>
    <option value="lend" <?php echo $side === 'lend' ? 'selected' : ''; ?>>出租</option>
  </select>
  <select name="status">
    <option value="">全部状态</option>
    <?php foreach ($statusLabels as $k => $label): ?>
      <option value="<?php echo h($k); ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo h($label); ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">筛选</button>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>封面</th>
        <th>订单号</th>
        <th>商品</th>
        <th>类型</th>
        <th>状态</th>
        <th>金额</th>
        <th>买家</th>
        <th>时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="10" class="muted">暂无订单</td></tr>
      <?php else: foreach ($list as $o): ?>
        <tr>
          <td><?php echo intval($o['id']); ?></td>
          <td>
            <?php $cover = admin_media($o['cover']); ?>
            <?php if ($cover): ?>
              <img class="thumb" src="<?php echo h($cover); ?>" alt=""
                onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'thumb-fallback',textContent:'无'}))">
            <?php else: ?>
              <span class="thumb-fallback">无</span>
            <?php endif; ?>
          </td>
          <td><?php echo h($o['order_no']); ?></td>
          <td><?php echo h($o['title']); ?></td>
          <td><?php echo $o['side'] === 'lend' ? '出租' : '求租'; ?></td>
          <td>
            <form method="post" style="display:flex;gap:6px;align-items:center">
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="id" value="<?php echo intval($o['id']); ?>">
              <select name="status">
                <?php foreach ($statusLabels as $k => $label): ?>
                  <option value="<?php echo h($k); ?>" <?php echo $o['status'] === $k ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                <?php endforeach; ?>
                <?php if (!isset($statusLabels[$o['status']])): ?>
                  <option value="<?php echo h($o['status']); ?>" selected><?php echo h($o['status']); ?></option>
                <?php endif; ?>
              </select>
              <button class="btn gray sm" type="submit">改</button>
            </form>
          </td>
          <td>￥<?php echo number_format(floatval($o['total_price']), 2); ?></td>
          <td><?php echo h($o['buyer_name'] ? $o['buyer_name'] : '-'); ?></td>
          <td><?php echo h($o['created_at']); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('确定删除该订单？');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo intval($o['id']); ?>">
              <button class="btn danger sm" type="submit">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
