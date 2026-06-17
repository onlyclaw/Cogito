-- 博客系统数据库初始化脚本
-- MySQL 5.7

CREATE DATABASE IF NOT EXISTS `boke` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `boke`;

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `nickname` VARCHAR(50) DEFAULT '',
    `avatar` VARCHAR(255) DEFAULT '',
    `role` ENUM('admin','author','editor') DEFAULT 'author',
    `status` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 文章表
CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) DEFAULT '',
    `content` LONGTEXT NOT NULL,
    `summary` TEXT,
    `cover` VARCHAR(255) DEFAULT '',
    `category_id` INT UNSIGNED DEFAULT 0,
    `user_id` INT UNSIGNED NOT NULL,
    `status` ENUM('published','draft','trash') DEFAULT 'draft',
    `is_top` TINYINT(1) DEFAULT 0,
    `is_recommend` TINYINT(1) DEFAULT 0,
    `views` INT UNSIGNED DEFAULT 0,
    `likes` INT UNSIGNED DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_category` (`category_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- 分类表
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) DEFAULT '',
    `description` VARCHAR(200) DEFAULT '',
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 标签表
CREATE TABLE IF NOT EXISTS `tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) DEFAULT '',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 文章标签关联表
CREATE TABLE IF NOT EXISTS `post_tags` (
    `post_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`post_id`, `tag_id`)
) ENGINE=InnoDB;

-- 评论表
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT 0,
    `user_id` INT UNSIGNED DEFAULT 0,
    `nickname` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('approved','pending','spam') DEFAULT 'pending',
    `ip` VARCHAR(50) DEFAULT '',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_post` (`post_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB;

-- 站点配置表
CREATE TABLE IF NOT EXISTS `options` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `option_key` VARCHAR(100) NOT NULL UNIQUE,
    `option_value` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 访问统计表
CREATE TABLE IF NOT EXISTS `views` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT UNSIGNED NOT NULL,
    `ip` VARCHAR(50) DEFAULT '',
    `user_agent` VARCHAR(255) DEFAULT '',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_post` (`post_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- 收藏表
CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED DEFAULT 0,
    `visitor_id` VARCHAR(100) DEFAULT '',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_post` (`post_id`),
    KEY `idx_visitor` (`visitor_id`)
) ENGINE=InnoDB;

-- 插入默认管理员账号 (密码: admin123)
INSERT INTO `users` (`username`, `password`, `email`, `nickname`, `role`) VALUES
('admin', '$2y$10$4iqofuDm1fpY78vWtk1xu.C55xh6zgnP2FWUYZGVirycyVztIXdCC', 'admin@example.com', '管理员', 'admin');

-- 插入默认分类
INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('未分类', 'uncategorized', '默认分类', 0),
('技术分享', 'tech', '技术相关文章', 1),
('生活随笔', 'life', '生活感悟', 2),
('学习笔记', 'notes', '学习过程中的记录', 3);

-- 插入默认标签
INSERT INTO `tags` (`name`, `slug`) VALUES
('PHP', 'php'),
('MySQL', 'mysql'),
('JavaScript', 'javascript'),
('前端', 'frontend'),
('后端', 'backend'),
('随笔', 'essay');

-- 插入默认配置
INSERT INTO `options` (`option_key`, `option_value`) VALUES
('site_name', '我的博客'),
('site_desc', '一个简洁优雅的个人博客'),
('site_keywords', '博客,技术,分享'),
('site_url', 'http://localhost/boke'),
('admin_email', 'admin@example.com'),
('comment_check', '1'),
('posts_per_page', '8'),
('ai_api_key', ''),
('ai_api_url', ''),
('ai_model', 'gpt-3.5-turbo'),
('ai_enabled', '1');
