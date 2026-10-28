<?php
require_once __DIR__ . '/_init.php';
admin_require_login();
$conn = admin_db();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        mysqli_query($conn, "UPDATE orders SET review_score=NULL, review_content=NULL, review_tags=NULL, reviewed_at=NULL WHERE id={$id}");
        $msg = '已清除订单 #' . $id . ' 的评价';
    }
}

$kw = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = 'review_score IS NOT NULL';
if ($kw !== '') {
    $k = mysqli_real_escape_string($conn, $kw);
    $where .= " AND (order_no LIKE '%{$k}%' OR title LIKE '%{$k}%' OR review_content LIKE '%{$k}%' OR buyer_name LIKE '%{$k}%' OR seller_name LIKE '%{$k}%')";
}

$list = array();
$res = mysqli_query($conn, "SELECT id, order_no, title, cover, side, status, buyer_name, seller_name,
    review_score, review_content, review_tags, reviewed_at, created_at
    FROM orders WHERE {$where}
    ORDER BY reviewed_at DESC, id DESC LIMIT 300");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $list[] = $row;
}

admin_header('评价管理', 'reviews.php');
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
?>
<form class="toolbar" method="get">
  <input type="text" name="q" value="<?php echo h($kw); ?>" placeholder="订单号 / 商品 / 评价内容 / 姓名">
  <button class="btn" type="submit">搜索</button>
  <?php if ($kw !== ''): ?><a class="btn gray" href="reviews.php">清空</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>封面</th>
        <th>订单号</th>
        <th>商品</th>
        <th>评分</th>
        <th>评价内容</th>
        <th>标签</th>
        <th>买家</th>
        <th>卖家</th>
        <th>评价时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="11" class="muted">暂无评价记录</td></tr>
      <?php else: foreach ($list as $r): ?>
        <tr>
          <td><?php echo intval($r['id']); ?></td>
          <td>
            <?php $cover = admin_media($r['cover']); ?>
            <?php if ($cover): ?>
              <img class="thumb" src="<?php echo h($cover); ?>" alt=""
                onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'thumb-fallback',textContent:'无'}))">
            <?php else: ?>
              <span class="thumb-fallback">无</span>
            <?php endif; ?>
          </td>
          <td><?php echo h($r['order_no']); ?></td>
          <td><?php echo h($r['title']); ?></td>
          <td><strong><?php echo intval($r['review_score']); ?></strong> 星</td>
          <td class="cell-ellipsis"><?php echo h($r['review_content'] ? $r['review_content'] : '-'); ?></td>
          <td><?php echo h($r['review_tags'] ? $r['review_tags'] : '-'); ?></td>
          <td><?php echo h($r['buyer_name'] ? $r['buyer_name'] : '-'); ?></td>
          <td><?php echo h($r['seller_name'] ? $r['seller_name'] : '-'); ?></td>
          <td><?php echo h($r['reviewed_at'] ? $r['reviewed_at'] : '-'); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('确定清除该评价？');">
              <input type="hidden" name="action" value="clear">
              <input type="hidden" name="id" value="<?php echo intval($r['id']); ?>">
              <button class="btn danger sm" type="submit">清除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
