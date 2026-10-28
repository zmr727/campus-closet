<?php
require_once __DIR__ . '/_init.php';
admin_require_login();

$conn = admin_db();

function count_one($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    if ($r && ($row = mysqli_fetch_assoc($r))) return intval($row['c']);
    return 0;
}

$users = count_one($conn, 'SELECT COUNT(*) AS c FROM users');
$clothes = count_one($conn, 'SELECT COUNT(*) AS c FROM clothes');
$orders = count_one($conn, 'SELECT COUNT(*) AS c FROM orders');
$vip = count_one($conn, 'SELECT COUNT(*) AS c FROM vip_members');
$favorites = count_one($conn, 'SELECT COUNT(*) AS c FROM favorites');
$buyerShows = count_one($conn, 'SELECT COUNT(*) AS c FROM buyer_shows');
$showComments = count_one($conn, 'SELECT COUNT(*) AS c FROM buyer_show_comments');
$messages = count_one($conn, 'SELECT COUNT(*) AS c FROM messages');
$chats = count_one($conn, 'SELECT COUNT(*) AS c FROM message_chats');
$reviews = count_one($conn, 'SELECT COUNT(*) AS c FROM orders WHERE review_score IS NOT NULL');
$orderAmount = 0;
$r = mysqli_query($conn, 'SELECT IFNULL(SUM(total_price),0) AS s FROM orders');
if ($r && ($row = mysqli_fetch_assoc($r))) $orderAmount = floatval($row['s']);

$statusMap = array(
    'pay' => '待付款', 'pickup' => '待取衣', 'deliver' => '待交衣',
    'renting' => '租赁中', 'lending' => '出租中', 'return' => '待归还',
    'reclaim' => '待收回', 'review' => '评价', 'rated' => '待评价',
    'done' => '已完成', 'cancel' => '已取消'
);
$tagMap = array(
    'calendar' => '上新日历', 'hot' => '热租', 'recommend' => '推荐',
    'cate' => '分类', 'buyer' => '买家秀'
);

$statusLabels = array();
$statusCounts = array();
$r = mysqli_query($conn, 'SELECT status, COUNT(*) AS c FROM orders GROUP BY status ORDER BY c DESC');
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $st = $row['status'];
        $statusLabels[] = isset($statusMap[$st]) ? $statusMap[$st] : $st;
        $statusCounts[] = intval($row['c']);
    }
}

$tagLabels = array();
$tagCounts = array();
$r = mysqli_query($conn, 'SELECT tag, COUNT(*) AS c FROM clothes GROUP BY tag ORDER BY c DESC');
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $tg = $row['tag'];
        $tagLabels[] = isset($tagMap[$tg]) ? $tagMap[$tg] : $tg;
        $tagCounts[] = intval($row['c']);
    }
}

$campusLabels = array();
$campusCounts = array();
$r = mysqli_query($conn, "SELECT IFNULL(NULLIF(campus,''), '未设置') AS campus, COUNT(*) AS c FROM users GROUP BY campus ORDER BY c DESC LIMIT 8");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $campusLabels[] = $row['campus'];
        $campusCounts[] = intval($row['c']);
    }
}

$dayMap = array();
$amountMap = array();
$r = mysqli_query($conn, "SELECT DATE(created_at) AS d, COUNT(*) AS c, IFNULL(SUM(total_price),0) AS s FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $dayMap[$row['d']] = intval($row['c']);
        $amountMap[$row['d']] = floatval($row['s']);
    }
}
$dayLabels = array();
$dayCounts = array();
$dayAmounts = array();
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} day"));
    $dayLabels[] = date('n/j', strtotime($d));
    $dayCounts[] = isset($dayMap[$d]) ? $dayMap[$d] : 0;
    $dayAmounts[] = isset($amountMap[$d]) ? $amountMap[$d] : 0;
}

$recent = array();
$r = mysqli_query($conn, 'SELECT id, order_no, title, cover, status, side, total_price, created_at FROM orders ORDER BY id DESC LIMIT 8');
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) $recent[] = $row;
}

/** 迷你折线 SVG */
function kpi_sparkline($values, $stroke = '#e11d48', $fill = 'rgba(225,29,72,0.12)') {
    $values = array_values($values);
    $n = count($values);
    if ($n < 2) $values = array(0, 0);
    $n = count($values);
    $max = max($values);
    $min = min($values);
    if ($max <= $min) { $max = $min + 1; }
    $w = 120; $h = 42; $pad = 3;
    $pts = array();
    for ($i = 0; $i < $n; $i++) {
        $x = $pad + ($n === 1 ? 0 : $i * ($w - $pad * 2) / ($n - 1));
        $y = $h - $pad - (($values[$i] - $min) / ($max - $min)) * ($h - $pad * 2);
        $pts[] = round($x, 1) . ',' . round($y, 1);
    }
    $poly = implode(' ', $pts);
    $area = $pts[0] . ' ' . $poly . ' ' . $pts[$n - 1];
    $first = explode(',', $pts[0]);
    $last = explode(',', $pts[$n - 1]);
    $areaPath = 'M' . $first[0] . ',' . ($h - $pad) . ' L' . str_replace(' ', ' L', $poly) . ' L' . $last[0] . ',' . ($h - $pad) . ' Z';
    return '<svg viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none">'
        . '<path d="' . $areaPath . '" fill="' . $fill . '"></path>'
        . '<polyline fill="none" stroke="' . $stroke . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="' . $poly . '"></polyline>'
        . '</svg>';
}

/** 迷你柱状 */
function kpi_bars($values, $tone = '') {
    $values = array_values($values);
    if (!$values) $values = array(1);
    $max = max($values);
    if ($max <= 0) $max = 1;
    $html = '<div class="mini-bars' . ($tone ? ' ' . $tone : '') . '">';
    foreach ($values as $v) {
        $pct = max(8, round(($v / $max) * 100));
        $html .= '<span style="height:' . $pct . '%"></span>';
    }
    $html .= '</div>';
    return $html;
}

/** VIP 占比环 */
function kpi_ring($vip, $users) {
    $pct = $users > 0 ? round($vip / $users * 100) : 0;
    $r = 16; $c = 2 * M_PI * $r;
    $dash = round($c * $pct / 100, 1);
    $gap = round($c - $dash, 1);
    return '<div class="mini-ring">'
        . '<svg viewBox="0 0 42 42">'
        . '<circle cx="21" cy="21" r="' . $r . '" fill="none" stroke="#e5e7eb" stroke-width="5"></circle>'
        . '<circle cx="21" cy="21" r="' . $r . '" fill="none" stroke="#e11d48" stroke-width="5" stroke-linecap="round"'
        . ' stroke-dasharray="' . $dash . ' ' . $gap . '" transform="rotate(-90 21 21)"></circle>'
        . '</svg>'
        . '<div class="mini-ring-text">占用户<br><strong>' . $pct . '%</strong></div>'
        . '</div>';
}

$chart = array(
    'statusLabels' => $statusLabels,
    'statusCounts' => $statusCounts,
    'tagLabels' => $tagLabels,
    'tagCounts' => $tagCounts,
    'campusLabels' => $campusLabels,
    'campusCounts' => $campusCounts,
    'dayLabels' => $dayLabels,
    'dayCounts' => $dayCounts,
);

admin_header('数据概览', 'index.php', array('welcome' => true));

$campusBars = array_slice($campusCounts, 0, 6);
if (count($campusBars) < 2) $campusBars = array($users, max(1, intval($users * 0.6)), max(1, intval($users * 0.4)));
$tagBars = array_slice($tagCounts, 0, 6);
if (count($tagBars) < 2) $tagBars = array($clothes, max(1, intval($clothes * 0.5)));
?>
<div class="cards">
  <div class="card card-accent">
    <div class="card-head">
      <div class="label">用户数</div>
      <div class="card-icon rose">U</div>
    </div>
    <div class="num"><?php echo $users; ?></div>
    <div class="card-hint">按校区分布</div>
    <div class="card-spark"><?php echo kpi_bars($campusBars); ?></div>
  </div>
  <div class="card">
    <div class="card-head">
      <div class="label">服装数</div>
      <div class="card-icon gray">C</div>
    </div>
    <div class="num"><?php echo $clothes; ?></div>
    <div class="card-hint">按标签分布</div>
    <div class="card-spark"><?php echo kpi_bars($tagBars, 'gray'); ?></div>
  </div>
  <div class="card">
    <div class="card-head">
      <div class="label">订单数</div>
      <div class="card-icon rose">O</div>
    </div>
    <div class="num"><?php echo $orders; ?></div>
    <div class="card-hint">近 7 日趋势</div>
    <div class="card-spark"><?php echo kpi_sparkline($dayCounts); ?></div>
  </div>
  <div class="card">
    <div class="card-head">
      <div class="label">VIP 会员</div>
      <div class="card-icon ink">V</div>
    </div>
    <div class="num"><?php echo $vip; ?></div>
    <div class="card-hint">会员渗透率</div>
    <div class="card-spark"><?php echo kpi_ring($vip, $users); ?></div>
  </div>
  <div class="card">
    <div class="card-head">
      <div class="label">订单总额</div>
      <div class="card-icon rose">￥</div>
    </div>
    <div class="num money">￥<?php echo number_format($orderAmount, 0); ?></div>
    <div class="card-hint">近 7 日成交额</div>
    <div class="card-spark"><?php echo kpi_sparkline($dayAmounts, '#e11d48', 'rgba(225,29,72,0.10)'); ?></div>
  </div>
</div>

<section class="table-map">
  <div class="table-map-head">
    <h3>全库数据一览</h3>
    <p class="chart-sub">左侧导航与 MySQL 表一一对应，点击可进入管理</p>
  </div>
  <div class="table-map-grid">
    <a class="table-map-item" href="users.php">
      <span class="table-map-name">users</span>
      <span class="table-map-label">用户</span>
      <strong><?php echo $users; ?></strong>
    </a>
    <a class="table-map-item" href="vip.php">
      <span class="table-map-name">vip_members</span>
      <span class="table-map-label">会员</span>
      <strong><?php echo $vip; ?></strong>
    </a>
    <a class="table-map-item" href="clothes.php">
      <span class="table-map-name">clothes</span>
      <span class="table-map-label">服装</span>
      <strong><?php echo $clothes; ?></strong>
    </a>
    <a class="table-map-item" href="orders.php">
      <span class="table-map-name">orders</span>
      <span class="table-map-label">订单</span>
      <strong><?php echo $orders; ?></strong>
    </a>
    <a class="table-map-item" href="reviews.php">
      <span class="table-map-name">orders.review</span>
      <span class="table-map-label">评价</span>
      <strong><?php echo $reviews; ?></strong>
    </a>
    <a class="table-map-item" href="favorites.php">
      <span class="table-map-name">favorites</span>
      <span class="table-map-label">收藏</span>
      <strong><?php echo $favorites; ?></strong>
    </a>
    <a class="table-map-item" href="buyershow.php">
      <span class="table-map-name">buyer_shows</span>
      <span class="table-map-label">买家秀</span>
      <strong><?php echo $buyerShows; ?></strong>
    </a>
    <a class="table-map-item" href="show_comments.php">
      <span class="table-map-name">buyer_show_comments</span>
      <span class="table-map-label">秀评论</span>
      <strong><?php echo $showComments; ?></strong>
    </a>
    <a class="table-map-item" href="messages.php">
      <span class="table-map-name">messages</span>
      <span class="table-map-label">消息会话</span>
      <strong><?php echo $messages; ?></strong>
    </a>
    <a class="table-map-item" href="chats.php">
      <span class="table-map-name">message_chats</span>
      <span class="table-map-label">聊天记录</span>
      <strong><?php echo $chats; ?></strong>
    </a>
  </div>
</section>

<div class="bento">
  <section class="bento-main">
    <h3>近 7 天订单趋势</h3>
    <p class="chart-sub">主视觉：按日汇总订单量</p>
    <div class="chart-box"><canvas id="chartDays"></canvas></div>
  </section>
  <section class="bento-side">
    <h3>订单状态</h3>
    <p class="chart-sub">当前各状态占比</p>
    <div class="chart-box"><canvas id="chartStatus"></canvas></div>
  </section>
</div>

<div class="secondary-row">
  <section class="panel-block">
    <h3>服装标签</h3>
    <p class="chart-sub">首页分区与库存结构</p>
    <div class="chart-box"><canvas id="chartTags"></canvas></div>
  </section>
  <section class="panel-block">
    <h3>用户校区</h3>
    <p class="chart-sub">注册用户校区分布</p>
    <div class="chart-box"><canvas id="chartCampus"></canvas></div>
  </section>
</div>

<section class="recent-block">
  <div class="recent-head">
    <h3>最近订单</h3>
    <a href="orders.php">查看全部 ›</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th></th>
          <th>商品</th>
          <th>状态</th>
          <th>金额</th>
          <th>时间</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="5" class="muted">暂无订单</td></tr>
        <?php else: foreach ($recent as $o): ?>
          <tr>
            <td>
              <?php $cover = admin_media($o['cover']); ?>
              <?php if ($cover): ?>
                <img class="thumb" src="<?php echo h($cover); ?>" alt=""
                  onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'thumb-fallback',textContent:'无'}))">
              <?php else: ?>
                <span class="thumb-fallback">无</span>
              <?php endif; ?>
            </td>
            <td><?php echo h($o['title']); ?></td>
            <td><span class="status-pill"><?php
              $st = $o['status'];
              echo h(isset($statusMap[$st]) ? $statusMap[$st] : $st);
            ?></span></td>
            <td>￥<?php echo number_format(floatval($o['total_price']), 2); ?></td>
            <td class="muted"><?php echo h($o['created_at']); ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  var data = <?php echo json_encode($chart, JSON_UNESCAPED_UNICODE); ?>;
  var palette = ['#e11d48', '#be123c', '#fb7185', '#9ca3af', '#6b7280', '#374151', '#f43f5e', '#d1d5db', '#111827'];

  Chart.defaults.font.family = '"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif';
  Chart.defaults.font.size = 14;
  Chart.defaults.color = '#6b7280';

  function fillGrad(ctx, top, bottom) {
    var g = ctx.createLinearGradient(0, 0, 0, 300);
    g.addColorStop(0, top);
    g.addColorStop(1, bottom);
    return g;
  }

  if (document.getElementById('chartDays')) {
    var ctx = document.getElementById('chartDays').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.dayLabels,
        datasets: [{
          label: '订单数',
          data: data.dayCounts,
          borderColor: '#e11d48',
          backgroundColor: fillGrad(ctx, 'rgba(225,29,72,0.18)', 'rgba(225,29,72,0.01)'),
          fill: true,
          tension: 0.4,
          borderWidth: 2.5,
          pointRadius: 3,
          pointHoverRadius: 5,
          pointBackgroundColor: '#e11d48',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(229,231,235,0.9)' }, border: { display: false } },
          x: { grid: { display: false }, border: { display: false } }
        }
      }
    });
  }

  if (document.getElementById('chartStatus')) {
    new Chart(document.getElementById('chartStatus'), {
      type: 'doughnut',
      data: {
        labels: data.statusLabels,
        datasets: [{
          data: data.statusCounts,
          backgroundColor: palette,
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, boxHeight: 10, padding: 12, usePointStyle: true, pointStyle: 'circle' }
          }
        },
        cutout: '70%'
      }
    });
  }

  if (document.getElementById('chartTags')) {
    new Chart(document.getElementById('chartTags'), {
      type: 'bar',
      data: {
        labels: data.tagLabels,
        datasets: [{
          data: data.tagCounts,
          backgroundColor: '#e11d48',
          borderRadius: 8,
          maxBarThickness: 26
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(229,231,235,0.9)' }, border: { display: false } },
          x: { grid: { display: false }, border: { display: false } }
        }
      }
    });
  }

  if (document.getElementById('chartCampus')) {
    new Chart(document.getElementById('chartCampus'), {
      type: 'bar',
      data: {
        labels: data.campusLabels,
        datasets: [{
          data: data.campusCounts,
          backgroundColor: '#374151',
          borderRadius: 8,
          maxBarThickness: 20
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(229,231,235,0.9)' }, border: { display: false } },
          y: { grid: { display: false }, border: { display: false } }
        }
      }
    });
  }
})();
</script>
<?php admin_footer(); ?>
