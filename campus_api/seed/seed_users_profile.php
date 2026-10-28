<?php
/**
 * 完善演示用户表字段（phone/openid/login_type/real_name/address/campus/avatar）
 * 打开一次：http://127.0.0.1/miniprogram/campus_api/seed/seed_users_profile.php
 * 建议先打开 migrate_users.php
 */
require_once __DIR__ . '/../config.php';

$conn = db_connect();

$cols = array('phone', 'openid', 'login_type', 'real_name', 'address', 'campus', 'avatar', 'student_id', 'nickname');
foreach ($cols as $c) {
    $q = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE '{$c}'");
    if (!$q || mysqli_num_rows($q) === 0) {
        mysqli_close($conn);
        json_err('缺少字段 ' . $c . '，请先打开 migrate_users.php');
    }
}

/**
 * 按手机号完善一条演示账号
 */
function fill_demo($conn, $phone, $nick, $real, $sid, $addr, $campus, $avatar) {
    $p = mysqli_real_escape_string($conn, $phone);
    $n = mysqli_real_escape_string($conn, $nick);
    $r = mysqli_real_escape_string($conn, $real);
    $s = $sid === null ? 'NULL' : ("'" . mysqli_real_escape_string($conn, $sid) . "'");
    $a = mysqli_real_escape_string($conn, $addr);
    $c = mysqli_real_escape_string($conn, $campus);
    $av = mysqli_real_escape_string($conn, $avatar);

    $res = mysqli_query($conn, "SELECT id FROM users WHERE phone='{$p}' OR username='{$p}' LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $id = intval($row['id']);
        mysqli_query($conn, "UPDATE users SET
            username='{$p}',
            phone='{$p}',
            login_type='phone',
            nickname='{$n}',
            real_name='{$r}',
            student_id={$s},
            address='{$a}',
            campus='{$c}',
            avatar='{$av}'
            WHERE id={$id}");
        return $id;
    }

    $pwd = mysqli_real_escape_string($conn, password_hash('123456', PASSWORD_DEFAULT));
    mysqli_query($conn, "INSERT INTO users (username, phone, login_type, password, nickname, real_name, student_id, address, campus, avatar)
                         VALUES ('{$p}', '{$p}', 'phone', '{$pwd}', '{$n}', '{$r}', {$s}, '{$a}', '{$c}', '{$av}')");
    return intval(mysqli_insert_id($conn));
}

$ids = array();
$ids['校园小桃'] = fill_demo($conn, '13800000001', '校园小桃', '张小桃', '2023001', '梅园3号楼 201宿舍', '大学城校区', '/images/mine/avatar.png');
$ids['测试用户'] = fill_demo($conn, '13800000002', '测试用户', '王测试', null, '兰园5号楼 412宿舍', '大学城校区', '/images/avatar/a8.png');
$ids['阿月'] = fill_demo($conn, '13800000003', '阿月', '李阿月', '2023003', '竹园2号楼 108宿舍', '大学城校区', '/images/avatar/a2.png');
$ids['玲玲'] = fill_demo($conn, '13800000004', '玲玲', '陈玲玲', '2023004', '梅园1号楼 305宿舍', '五山校区', '/images/avatar/a3.png');
$ids['阿白'] = fill_demo($conn, '13800000005', '阿白', '白小白', null, '兰园3号楼 210宿舍', '大学城校区', '/images/avatar/a4.png');
$ids['橙子'] = fill_demo($conn, '13800000006', '橙子', '黄橙子', null, '菊园6号楼 401宿舍', '南校区', '/images/avatar/a5.png');
$ids['米米'] = fill_demo($conn, '13800000007', '米米', '周米米', null, '梅园4号楼 112宿舍', '大学城校区', '/images/avatar/a6.png');
$ids['小鱼'] = fill_demo($conn, '13800000008', '小鱼', '刘小鱼', null, '兰园8号楼 509宿舍', '国际校区', '/images/avatar/a1.png');
$ids['夜子溪'] = fill_demo($conn, '13800000009', '夜子溪', '赵子溪', null, '竹园5号楼 220宿舍', '大学城校区', '/images/avatar/a9.png');
$ids['阿宁同学'] = fill_demo($conn, '13800000011', '阿宁同学', '宁阿宁', '2023011', '梅园2号楼 318宿舍', '大学城校区', '/images/avatar/a8.png');
$ids['学长衣柜'] = fill_demo($conn, '13800000012', '学长衣柜', '孙学长', null, '兰园1号楼 101宿舍', '五山校区', '/images/avatar/a7.png');
$ids['毕业季补给站'] = fill_demo($conn, '13800000013', '毕业季补给站', '郑补给', null, '西门驿站旁', '大学城校区', '/images/avatar/a10.png');
$ids['汉服铺子'] = fill_demo($conn, '13800000015', '汉服铺子', '吴汉服', null, '东门商业街', '大学城校区', '/images/avatar/a9.png');

// 微信演示号：补 login_type / openid（不覆盖已有）
mysqli_query($conn, "UPDATE users SET login_type='wechat' WHERE (username LIKE 'wx_%' OR openid LIKE 'wx_%') AND (login_type IS NULL OR login_type='')");
mysqli_query($conn, "UPDATE users SET openid=username WHERE username LIKE 'wx_%' AND (openid IS NULL OR openid='')");

$list = array();
$res = mysqli_query($conn, "SELECT id, username, phone, openid, login_type, nickname, real_name, student_id, address, campus FROM users ORDER BY id ASC");
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $list[] = $row;
}
mysqli_close($conn);

json_ok(array(
    'demo_ids' => $ids,
    'users' => $list,
), '演示用户资料已完善（含真实姓名）');
