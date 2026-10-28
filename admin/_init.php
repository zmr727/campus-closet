<?php
/**
 * 管理后台公共初始化：会话、数据库、登录校验
 */
session_start();

function admin_db() {
    static $conn = null;
    if ($conn) return $conn;
    $conn = mysqli_connect('127.0.0.1', 'root', '', 'miniprogram');
    if (!$conn) {
        die('数据库连接失败，请确认 XAMPP 的 MySQL 已启动');
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

function admin_host() {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
    return 'http://' . $host;
}

/**
 * 图片路径 → 可访问 URL
 * 订单里常存 .png，压缩后实际多为 .jpg，自动回退
 */
function admin_media($path) {
    if ($path === null || $path === '') return '';
    $path = trim($path);

    if (preg_match('#^https?://#i', $path)) {
        if (preg_match('#/campus_api/static/(images/.+)$#i', $path, $m)) {
            $fs = dirname(__DIR__) . '/campus_api/static/' . str_replace('/', DIRECTORY_SEPARATOR, $m[1]);
            if (!is_file($fs) && preg_match('/\.png$/i', $fs)) {
                $fsJpg = preg_replace('/\.png$/i', '.jpg', $fs);
                if (is_file($fsJpg)) {
                    return preg_replace('/\.png(\?|$)/i', '.jpg$1', $path);
                }
            }
        }
        return $path;
    }

    if (strpos($path, '/miniprogram/') === 0) {
        return admin_host() . $path;
    }

    if (strpos($path, '/images/') === 0) {
        $fs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'campus_api' . DIRECTORY_SEPARATOR . 'static' . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $url = admin_host() . '/miniprogram/campus_api/static' . $path;
        if (!is_file($fs) && preg_match('/\.png$/i', $fs)) {
            $fsJpg = preg_replace('/\.png$/i', '.jpg', $fs);
            if (is_file($fsJpg)) {
                $url = preg_replace('/\.png(\?|$)/i', '.jpg$1', $url);
            }
        } elseif (!is_file($fs) && preg_match('/\.jpg$/i', $fs)) {
            $fsPng = preg_replace('/\.jpg$/i', '.png', $fs);
            if (is_file($fsPng)) {
                $url = preg_replace('/\.jpg(\?|$)/i', '.png$1', $url);
            }
        }
        return $url;
    }

    if (strpos($path, '/uploads/') === 0 || strpos($path, 'uploads/') === 0) {
        $p = ltrim($path, '/');
        if (strpos($p, 'uploads/') === 0) {
            return admin_host() . '/miniprogram/campus_api/' . $p;
        }
    }

    return $path;
}

function admin_logged_in() {
    return !empty($_SESSION['admin_ok']);
}

function admin_require_login() {
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** 课设演示账号（可改） */
function admin_check_login($user, $pass) {
    return $user === 'admin' && $pass === 'admin123';
}

function admin_nav($active) {
    $groups = array(
        array(
            'label' => '总览',
            'items' => array(
                'index.php' => '数据概览',
            ),
        ),
        array(
            'label' => '用户与会员',
            'items' => array(
                'users.php' => '用户管理',
                'vip.php' => '会员管理',
            ),
        ),
        array(
            'label' => '商品与交易',
            'items' => array(
                'clothes.php' => '服装管理',
                'orders.php' => '订单管理',
                'reviews.php' => '评价管理',
                'favorites.php' => '收藏管理',
            ),
        ),
        array(
            'label' => '内容与互动',
            'items' => array(
                'buyershow.php' => '买家秀',
                'show_comments.php' => '秀评论',
                'messages.php' => '消息会话',
                'chats.php' => '聊天记录',
            ),
        ),
    );
    echo '<aside class="side">';
    echo '<div class="brand">CAMPUS<span>Admin</span></div>';
    echo '<nav>';
    foreach ($groups as $g) {
        echo '<div class="nav-group">';
        echo '<div class="nav-group-label">' . h($g['label']) . '</div>';
        foreach ($g['items'] as $href => $label) {
            $cls = ($active === $href) ? ' class="on"' : '';
            echo '<a href="' . $href . '"' . $cls . '>' . h($label) . '</a>';
        }
        echo '</div>';
    }
    echo '</nav>';
    echo '<a class="logout" href="logout.php">退出登录</a>';
    echo '</aside>';
}

function admin_header($title, $active, $opts = array()) {
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . ' - CAMPUS 管理后台</title>';
    echo '<link rel="stylesheet" href="assets/admin.css?v=9">';
    echo '</head><body><div class="layout">';
    admin_nav($active);

    $hour = intval(date('G'));
    if ($hour < 11) $greet = '早上好';
    elseif ($hour < 14) $greet = '中午好';
    elseif ($hour < 18) $greet = '下午好';
    else $greet = '晚上好';

    if (!empty($opts['welcome'])) {
        echo '<main class="main"><header class="top top-hero">';
        echo '<div class="hero-inner">';
        echo '<p class="hero-eyebrow">Campus Wardrobe · Admin</p>';
        echo '<h1 class="hero-hello">Hello, Admin</h1>';
        echo '<p class="hero-sub">' . h($greet) . '，今天也一起看看校园衣橱的运营数据吧</p>';
        echo '</div>';
        echo '<div class="hero-meta"><span class="hero-date">' . h(date('Y.m.d')) . '</span><span class="hero-page">' . h($title) . '</span></div>';
        echo '</header><div class="content">';
    } else {
        echo '<main class="main"><header class="top"><div><p class="eyebrow">Campus Wardrobe</p><h1>' . h($title) . '</h1></div></header><div class="content">';
    }
}

function admin_footer() {
    echo '</div></main></div></body></html>';
}
