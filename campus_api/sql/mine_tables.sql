-- 我的页相关表（推荐浏览器打开 seed_mine.php 一键重建并灌演示数据）
-- 库：miniprogram
-- 说明：订单/收藏/会员均按 user_id 隔离；一笔交易买卖双方各有一条 orders（同 order_no）

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_no VARCHAR(32) NOT NULL COMMENT '同一笔交易买卖双方共享',
  user_id INT UNSIGNED NOT NULL COMMENT '订单所属用户（各自视角）',
  peer_id INT UNSIGNED DEFAULT NULL COMMENT '对方用户id',
  clothes_id INT UNSIGNED NOT NULL,
  title VARCHAR(100) NOT NULL,
  cover VARCHAR(255) DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  size_info VARCHAR(32) DEFAULT NULL,
  days INT UNSIGNED NOT NULL DEFAULT 1,
  total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  side VARCHAR(10) NOT NULL COMMENT 'rent|lend',
  status VARCHAR(20) NOT NULL,
  buyer_name VARCHAR(64) DEFAULT NULL,
  seller_name VARCHAR(64) DEFAULT NULL,
  pickup_code VARCHAR(16) DEFAULT NULL,
  return_code VARCHAR(16) DEFAULT NULL COMMENT '归还核销码',
  station VARCHAR(64) DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  picked_at DATETIME DEFAULT NULL COMMENT '取衣时间',
  returned_at DATETIME DEFAULT NULL COMMENT '归还时间',
  finished_at DATETIME DEFAULT NULL COMMENT '本侧完成时间',
  review_score TINYINT UNSIGNED DEFAULT NULL COMMENT '我对对方的评分1-5',
  review_content VARCHAR(255) DEFAULT NULL COMMENT '评价内容',
  review_tags VARCHAR(255) DEFAULT NULL COMMENT '评价标签',
  reviewed_at DATETIME DEFAULT NULL COMMENT '我方评价时间',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_side_status (user_id, side, status),
  KEY idx_order_no (order_no),
  KEY idx_clothes (clothes_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS favorites (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  clothes_id INT UNSIGNED NOT NULL,
  title VARCHAR(100) DEFAULT NULL,
  cover VARCHAR(255) DEFAULT NULL,
  price DECIMAL(10,2) DEFAULT 0,
  category VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_clothes (user_id, clothes_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS vip_members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  plan VARCHAR(32) NOT NULL DEFAULT 'semester',
  opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expire_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- clothes 补充发布者（若已有可忽略）
-- ALTER TABLE clothes ADD COLUMN user_id INT UNSIGNED DEFAULT NULL COMMENT '发布者用户id' AFTER seller;
