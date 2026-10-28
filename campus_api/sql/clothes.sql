-- 服装表（也可浏览器打开 seed_clothes.php 一键建表灌数据）
USE miniprogram;

DROP TABLE IF EXISTS clothes;
CREATE TABLE clothes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(100) NOT NULL,
  cover VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  category VARCHAR(50) DEFAULT NULL COMMENT '所属类型/分类',
  size_info VARCHAR(100) DEFAULT NULL,
  tag VARCHAR(30) NOT NULL DEFAULT 'recommend',
  date_label VARCHAR(20) DEFAULT NULL,
  intro VARCHAR(255) DEFAULT NULL,
  seller VARCHAR(64) DEFAULT NULL COMMENT '卖家昵称',
  fav_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数',
  available_from DATE DEFAULT NULL COMMENT '可租开始日期',
  available_to DATE DEFAULT NULL COMMENT '可租结束日期',
  PRIMARY KEY (id),
  KEY idx_tag (tag),
  KEY idx_category (category),
  KEY idx_available (available_from, available_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
