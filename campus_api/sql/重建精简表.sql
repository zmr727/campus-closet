-- 在 phpMyAdmin 里：先点左侧库 miniprogram，再点「SQL」，粘贴全部执行
-- 注意：会删除旧 users 表

DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL COMMENT '兼容旧登录账号(可与phone/openid同值)',
  phone VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  openid VARCHAR(64) DEFAULT NULL COMMENT '微信openid(演示)',
  login_type VARCHAR(16) DEFAULT NULL COMMENT 'phone|wechat',
  password VARCHAR(255) DEFAULT NULL COMMENT '密码(加密)',
  nickname VARCHAR(64) DEFAULT NULL COMMENT '昵称(展示名)',
  real_name VARCHAR(64) DEFAULT NULL COMMENT '真实姓名',
  avatar VARCHAR(255) DEFAULT NULL COMMENT '头像路径',
  student_id VARCHAR(32) DEFAULT NULL COMMENT '学号',
  address VARCHAR(255) DEFAULT NULL COMMENT '校内交接地址',
  campus VARCHAR(64) DEFAULT NULL COMMENT '当前校区',
  PRIMARY KEY (id),
  UNIQUE KEY uk_username (username),
  KEY idx_phone (phone),
  KEY idx_openid (openid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 演示数据（密码明文是 123456）
INSERT INTO users (username, phone, login_type, password, nickname, real_name, avatar, student_id, address, campus) VALUES
('13800000001', '13800000001', 'phone', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '校园小桃', '张小桃', '/images/mine/avatar.png', '2023001', '梅园3号楼 201宿舍', '大学城校区'),
('13800000002', '13800000002', 'phone', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '测试用户', NULL, '/images/avatar/a2.png', NULL, '兰园5号楼 412宿舍', '大学城校区');
