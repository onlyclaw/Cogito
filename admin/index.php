<?php
/**
 * 后台管理入口
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

// 获取后台路径
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$adminPath = preg_replace('#^.*/admin#', '', $uri);
$adminPath = rtrim($adminPath, '/');
if ($adminPath === '') $adminPath = '/';

// === 登录处理（优先处理，不检查登录状态） ===
if ($adminPath === '/login' || $adminPath === '/') {
    // 如果是登录页面或首页POST请求
    if ($adminPath === '/login' || ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminPath === '/')) {
        $loginError = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = post('username');
            $password = post('password');

            if (!is_installed()) {
                $loginError = '系统尚未安装，请先 <a href="' . SITE_URL . '/install.php">安装</a>';
            } elseif (!$username || !$password) {
                $loginError = '请输入用户名和密码';
            } else {
                $user = db()->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
                if ($user && password_verify($password, $user['password']) && $user['status'] == 1) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    redirect(SITE_URL . '/admin/dashboard');
                } else {
                    $loginError = '用户名或密码错误';
                }
            }
        }

        // 如果是首页且已登录，跳转到控制台
        if ($adminPath === '/' && is_login()) {
            redirect(SITE_URL . '/admin/dashboard');
        }

        include __DIR__ . '/views/login.php';
        exit;
    }
}

// === 退出登录 ===
if ($adminPath === '/logout') {
    session_destroy();
    redirect(SITE_URL . '/admin/login');
}

// === 检查登录状态（登录页面已处理，这里检查其他页面） ===
if (!is_login()) {
    redirect(SITE_URL . '/admin/login');
}

// === 数据库未安装提示 ===
if (!is_installed()) {
    show_install_prompt();
}

// === 分发到各个管理页面 ===
switch ($adminPath) {
    case '/dashboard':
        include __DIR__ . '/dashboard.php';
        break;
    case '/posts':
        include __DIR__ . '/posts.php';
        break;
    case '/post-add':
    case '/post-edit':
        include __DIR__ . '/post_edit.php';
        break;
    case '/categories':
        include __DIR__ . '/categories.php';
        break;
    case '/tags':
        include __DIR__ . '/tags.php';
        break;
    case '/comments':
        include __DIR__ . '/comments.php';
        break;
    case '/settings':
        include __DIR__ . '/settings.php';
        break;
    case '/profile':
        include __DIR__ . '/profile.php';
        break;
    case '/upload':
        handle_admin_upload();
        break;
    default:
        include __DIR__ . '/dashboard.php';
}

/**
 * 后台文件上传处理
 */
function handle_admin_upload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_login()) {
        json_response(['code' => 0, 'msg' => '无权操作'], 403);
    }

    if (empty($_FILES['file'])) {
        json_response(['code' => 0, 'msg' => '请选择文件']);
    }

    $result = upload_file($_FILES['file']);
    json_response($result);
}
