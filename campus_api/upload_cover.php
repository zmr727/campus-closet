<?php
/**
 * 上传封面图到 uploads/，返回可给小程序用的相对路径
 * POST multipart: file
 */
require_once __DIR__ . '/config.php';

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

if (!isset($_FILES['file'])) {
    json_err('请选择图片');
}

$file = $_FILES['file'];
if (!empty($file['error'])) {
    json_err('上传失败，错误码 ' . $file['error']);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allow = array('jpg', 'jpeg', 'png', 'webp', 'gif');
if (!in_array($ext, $allow)) {
    json_err('仅支持 jpg/png/webp/gif');
}

$name = 'c_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
$dest = $dir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_err('保存文件失败');
}

$path = '/miniprogram/campus_api/uploads/' . $name;
json_ok(array(
    'path' => $path,
    'url' => media_url($path),
));

// **图片上传专用后端接口**：小程序上传图片文件，PHP 接收、校验、保存图片到服务器文件夹，
// 最后返回图片访问路径 / URL 给小程序，用于发布商品时上传封面图
