<?php
/**
 * 博客系统配置文件
 */

// 数据库配置
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'boke');
define('DB_USER', 'boke');
define('DB_PASS', '123456');
define('DB_CHARSET', 'utf8mb4');

// 站点配置
define('SITE_NAME', 'Cogito');
define('SITE_URL', 'http://localhost/boke');
define('SITE_DESC', '智能体驱动的AI内容创作平台');
define('SITE_KEYWORDS', '博客,技术,分享');
define('SITE_ICO', '');

// 分页配置
define('PAGE_SIZE', 8);

// 路径配置
define('ROOT_PATH', dirname(__FILE__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');

// 时区
date_default_timezone_set('Asia/Shanghai');

// 会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 开启错误报告（开发阶段）
error_reporting(E_ALL);
ini_set('display_errors', 1);
