<?php
/**
 * 博客系统入口文件
 */

// 加载配置
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// 获取请求路径
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 移除项目路径前缀（支持子目录部署）
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

$uri = rtrim($uri, '/');
if ($uri === '') $uri = '/';

// 路由匹配
$uri = ltrim($uri, '/');

// === 安装页面（始终可访问） ===
if ($uri === 'install') {
    include __DIR__ . '/install.php';
    exit;
}

// === 数据库未安装时，显示安装提示 ===
if (!is_installed()) {
    show_install_prompt();
}

// === 前台路由 ===
if ($uri === '' || $uri === 'index') {
    // 首页
    include __DIR__ . '/front/index.php';
}
elseif ($uri === 'post' && isset($_GET['id'])) {
    // 文章详情
    include __DIR__ . '/front/post.php';
}
elseif (preg_match('#^category/(.+)$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    include __DIR__ . '/front/category.php';
}
elseif (preg_match('#^tag/(.+)$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    include __DIR__ . '/front/tag.php';
}
elseif ($uri === 'search') {
    include __DIR__ . '/front/search.php';
}
elseif ($uri === 'rss') {
    include __DIR__ . '/front/rss.php';
}
// === 用户系统路由 ===
elseif ($uri === 'auth') {
    include __DIR__ . '/front/auth.php';
}
elseif ($uri === 'profile') {
    include __DIR__ . '/front/profile.php';
}
// === 智能体路由 ===
elseif ($uri === 'agents') {
    include __DIR__ . '/front/agents.php';
}
elseif (preg_match('#^agent/(.+)$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    include __DIR__ . '/front/agent.php';
}
elseif ($uri === 'agent-collab') {
    include __DIR__ . '/front/agent_collab.php';
}
elseif ($uri === 'media') {
    include __DIR__ . '/front/media.php';
}
elseif ($uri === 'pipeline') {
    include __DIR__ . '/front/pipeline.php';
}
elseif ($uri === 'coding') {
    include __DIR__ . '/front/coding.php';
}
// === 后台路由 ===
elseif (preg_match('#^admin(/.*)?$#', $uri, $m)) {
    $adminPath = isset($m[1]) ? $m[1] : '/';
    $adminPath = rtrim($adminPath, '/');

    // 加载后台
    include __DIR__ . '/admin/index.php';
}
// === API路由 ===
elseif (preg_match('#^api/(.+)$#', $uri, $m)) {
    $apiAction = $m[1];
    include __DIR__ . '/api.php';
}
// === 智能体API路由 ===
elseif (preg_match('#^agent-api/(.+)$#', $uri, $m)) {
    include __DIR__ . '/agent_api.php';
}
// === 媒体生成API路由 ===
elseif (preg_match('#^media-api/(.+)$#', $uri, $m)) {
    include __DIR__ . '/media_api.php';
}
// === 闭环系统API路由 ===
elseif (preg_match('#^pipeline-api/(.+)$#', $uri, $m)) {
    include __DIR__ . '/pipeline_api.php';
}
// === 前台用户API路由 ===
elseif (preg_match('#^member-api/(.+)$#', $uri, $m)) {
    include __DIR__ . '/member_api.php';
}
// === 编程智能体API路由 ===
elseif (preg_match('#^coding-api/(.+)$#', $uri, $m)) {
    include __DIR__ . '/coding_api.php';
}
// === 404 ===
else {
    http_response_code(404);
    include __DIR__ . '/views/404.php';
}
