<?php
// 连数据库 + 统一成功/失败格式 + 读参数 + 用户信息格式化。
/**
 * 精简版公共连接 - 库 miniprogram
 */
// 返回的是 JSON，编码 UTF-8（中文正常）
header('Content-Type: application/json; charset=utf-8');
// 允许任意来源跨域访问（开发时方便）
header('Access-Control-Allow-Origin: *');
// 允许请求里带 Content-Type 头
header('Access-Control-Allow-Headers: Content-Type');
// 允许的方法：GET、POST，以及预检 OPTIONS
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// 有时浏览器跨域发 POST/JSON 前，会先发一个 OPTIONS 问服务器：「能不能这样请求？」
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

/** 当前请求的主机（真机用局域网 IP 访问时，图片 URL 也会跟着变） */
function api_host() {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
    return 'http://' . $host;
}

/**
 * 把本地路径 / 旧数据转成可给小程序加载的完整图片 URL
 * 数据库可只存 /images/... ，大图实际放在 campus_api/static/images/
 */
function media_url($path) {
    if ($path === null || $path === '') {
        return '';
    }
    $path = trim($path);
    if (preg_match('#^https?://#i', $path)) {
        if (preg_match('#^https?://(127\.0\.0\.1|192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+)#i', $path)) {
            $path = preg_replace('#^https?://[^/]+#i', api_host(), $path);
        }
    } elseif (strpos($path, '/miniprogram/') === 0) {
        $path = api_host() . $path;
    } elseif (strpos($path, '/images/') === 0) {
        $path = api_host() . '/miniprogram/campus_api/static' . $path;
    } else {
        return $path;
    }
    // 日历抠图优先用透明 png；其它若 png 不存在则回退 jpg
    if (preg_match('#/campus_api/static/(images/[^?]+)#i', $path, $m)) {
        $rel = str_replace('/', DIRECTORY_SEPARATOR, $m[1]);
        $fs = __DIR__ . '/static/' . $rel;
        if (preg_match('#images/home/(cal\d+|new_hanfu|new_mamianskirt)\.(png|jpg)$#i', $m[1])) {
            $fsPng = preg_replace('/\.(png|jpg)$/i', '.png', $fs);
            if (is_file($fsPng)) {
                $path = preg_replace('/\.(png|jpg)(\?|$)/i', '.png$2', $path);
                return $path;
            }
        }
        if (!is_file($fs) && preg_match('/\.png$/i', $fs)) {
            $fsJpg = preg_replace('/\.png$/i', '.jpg', $fs);
            if (is_file($fsJpg)) {
                $path = preg_replace('/\.png(\?|$)/i', '.jpg$1', $path);
            }
        }
    }
    return $path;
}

// 连接数据库的函数
function db_connect() {
    $conn = mysqli_connect('127.0.0.1', 'root', '', 'miniprogram');
    if (!$conn) {
        echo json_encode(array('code' => 1, 'msg' => '数据库连接失败', 'data' => null), JSON_UNESCAPED_UNICODE);
        exit;
    }
    mysqli_set_charset($conn, 'utf8');
    return $conn;
}
// 接口成功时统一返回的函数
// $data:成功时要交给小程序的业务数据（比如用户信息）。不传就是 null
// 成功时的提示文字，默认 'ok'
function json_ok($data = null, $msg = 'ok') {
    echo json_encode(array('code' => 0, 'msg' => $msg, 'data' => $data), JSON_UNESCAPED_UNICODE);
    exit;
}
// 接口失败时统一返回的函数
function json_err($msg = 'error', $code = 1) {
    echo json_encode(array('code' => $code, 'msg' => $msg, 'data' => null), JSON_UNESCAPED_UNICODE);
    exit;
}
// 用来读取小程序 POST 过来的参数
function read_input() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        return $json;
    }
    return $_POST;
}

/** 只返回必要字段 */
//数据库用户行 → 前端能用的标准用户对象
function user_public($row) {
    if (!$row) {
        return null;
    }
    $phone = isset($row['phone']) ? $row['phone'] : '';
    $openid = isset($row['openid']) ? $row['openid'] : '';
    $loginType = isset($row['login_type']) ? $row['login_type'] : '';
    // 兼容旧数据：尚未拆字段时从 username 推断
    $username = isset($row['username']) ? $row['username'] : '';
    if ($phone === '' && $username !== '' && strpos($username, 'wx_') !== 0) {
        $phone = $username;
    }
    if ($openid === '' && strpos($username, 'wx_') === 0) {
        $openid = $username;
    }
    if ($loginType === '') {
        $loginType = strpos($username, 'wx_') === 0 ? 'wechat' : ($phone !== '' ? 'phone' : '');
    }
    return array(
        'id' => intval($row['id']),
        'username' => $username,
        'phone' => $phone,
        'openid' => $openid,
        'login_type' => $loginType,
        'nickname' => isset($row['nickname']) ? $row['nickname'] : '',
        'real_name' => isset($row['real_name']) ? $row['real_name'] : '',
        'avatar' => media_url(isset($row['avatar']) ? $row['avatar'] : ''),
        'student_id' => isset($row['student_id']) ? $row['student_id'] : '',
        'address' => isset($row['address']) ? $row['address'] : '',
        'campus' => isset($row['campus']) ? $row['campus'] : '',
    );
}
/*新用户（库里还没有这个手机号）

前端把 phone、code 发给 PHP
PHP 写入 users（INSERT）
再把刚写入的那条用户信息 返回 给前端
老用户（库里已有）

前端同样发 phone、code
PHP 只查询，一般不新建
把查到的用户信息 返回 给前端*/
