<?php
require_once __DIR__ . '/_init.php';

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $pass = isset($_POST['password']) ? trim($_POST['password']) : '';
    if (admin_check_login($user, $pass)) {
        $_SESSION['admin_ok'] = 1;
        $_SESSION['admin_name'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = '账号或密码错误';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>登录 - CAMPUS 管理后台</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="login-page">
  <div class="login-box">
    <h1>CAMPUS 管理后台</h1>
    <p>校园衣橱 · 管理员登录</p>
    <?php if ($error): ?>
      <div class="msg err"><?php echo h($error); ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="row">
        <label>账号</label>
        <input type="text" name="username" required autofocus placeholder="admin">
      </div>
      <div class="row">
        <label>密码</label>
        <input type="password" name="password" required placeholder="admin123">
      </div>
      <button class="btn" type="submit">登录</button>
    </form>
    <div class="hint">演示账号：admin / admin123</div>
  </div>
</body>
</html>
