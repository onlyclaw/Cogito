<?php
/**
 * 前台用户API - 登录/注册/个人中心
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

// 解析路由参数
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = ltrim($uri, '/');
$uri_parts = explode('/', $uri);
$action = $uri_parts[1] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleMemberLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'profile':
        handleProfile();
        break;
    case 'change-password':
        handleChangePassword();
        break;
    case 'check-login':
        json_response(['logged_in' => is_member_login(), 'user' => current_member()]);
        break;
    case 'my-media':
        handleMyMedia();
        break;
    case 'my-favorites':
        handleMyFavorites();
        break;
    default:
        json_response(['error' => '未知操作'], 400);
}

function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['error' => '请求方法错误'], 405);
    }
    
    $username = trim(post('username'));
    $email = trim(post('email'));
    $password = post('password');
    $confirm = post('confirm_password');
    $nickname = trim(post('nickname')) ?: $username;
    
    // 验证
    if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
        json_response(['error' => '用户名长度需在3-20个字符之间'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['error' => '邮箱格式不正确'], 400);
    }
    if (mb_strlen($password) < 6) {
        json_response(['error' => '密码长度不能少于6个字符'], 400);
    }
    if ($password !== $confirm) {
        json_response(['error' => '两次输入的密码不一致'], 400);
    }
    
    // 检查重复
    $exists = db()->fetchOne("SELECT id FROM members WHERE username = ? OR email = ?", [$username, $email]);
    if ($exists) {
        json_response(['error' => '用户名或邮箱已被注册'], 400);
    }
    
    // 创建用户
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $id = db()->insert('members', [
        'username' => $username,
        'email' => $email,
        'password' => $hash,
        'nickname' => $nickname,
        'status' => 1
    ]);
    
    if ($id) {
        $_SESSION['member_id'] = $id;
        $_SESSION['member_username'] = $username;
        $_SESSION['member_nickname'] = $nickname;
        json_response(['success' => true, 'message' => '注册成功', 'user' => ['id' => $id, 'username' => $username, 'nickname' => $nickname]]);
    } else {
        json_response(['error' => '注册失败，请重试'], 500);
    }
}

function handleMemberLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['error' => '请求方法错误'], 405);
    }
    
    $login = trim(post('login'));
    $password = post('password');
    $remember = post('remember') === '1';
    
    if (empty($login) || empty($password)) {
        json_response(['error' => '请填写用户名和密码'], 400);
    }
    
    // 查找用户（支持用户名或邮箱登录）
    $member = db()->fetchOne("SELECT * FROM members WHERE (username = ? OR email = ?) AND status = 1", [$login, $login]);
    
    if (!$member || !password_verify($password, $member['password'])) {
        json_response(['error' => '用户名或密码错误'], 400);
    }
    
    $_SESSION['member_id'] = $member['id'];
    $_SESSION['member_username'] = $member['username'];
    $_SESSION['member_nickname'] = $member['nickname'] ?: $member['username'];
    
    if ($remember) {
        setcookie('member_token', md5($member['id'] . $member['password'] . $_SERVER['HTTP_USER_AGENT']), time() + 86400 * 30, '/');
    }
    
    json_response(['success' => true, 'message' => '登录成功', 'user' => ['id' => $member['id'], 'username' => $member['username'], 'nickname' => $member['nickname'] ?: $member['username'], 'avatar' => $member['avatar']]]);
}

function handleLogout() {
    unset($_SESSION['member_id'], $_SESSION['member_username'], $_SESSION['member_nickname']);
    setcookie('member_token', '', time() - 3600, '/');
    json_response(['success' => true, 'message' => '已退出登录']);
}

function handleProfile() {
    if (!is_member_login()) {
        json_response(['error' => '请先登录'], 401);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $member = current_member();
        unset($member['password']);
        json_response(['user' => $member]);
        return;
    }
    
    // 更新资料
    $nickname = trim(post('nickname'));
    $bio = trim(post('bio'));
    
    $data = [];
    if ($nickname) $data['nickname'] = $nickname;
    if ($bio !== null) $data['bio'] = $bio;
    
    // 头像上传
    if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = upload_file($_FILES['avatar'], 'avatars');
        if ($file) {
            $data['avatar'] = $file['url'];
        }
    }
    
    if (!empty($data)) {
        db()->update('members', $data, 'id = ?', [$_SESSION['member_id']]);
        if (isset($data['nickname'])) {
            $_SESSION['member_nickname'] = $data['nickname'];
        }
    }
    
    $member = current_member();
    unset($member['password']);
    json_response(['success' => true, 'message' => '资料已更新', 'user' => $member]);
}

function handleChangePassword() {
    if (!is_member_login()) {
        json_response(['error' => '请先登录'], 401);
    }
    
    $oldPassword = post('old_password');
    $newPassword = post('new_password');
    $confirmPassword = post('confirm_password');
    
    if (mb_strlen($newPassword) < 6) {
        json_response(['error' => '新密码长度不能少于6个字符'], 400);
    }
    if ($newPassword !== $confirmPassword) {
        json_response(['error' => '两次输入的密码不一致'], 400);
    }
    
    $member = db()->fetchOne("SELECT password FROM members WHERE id = ?", [$_SESSION['member_id']]);
    if (!password_verify($oldPassword, $member['password'])) {
        json_response(['error' => '原密码错误'], 400);
    }
    
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    db()->update('members', ['password' => $hash], 'id = ?', [$_SESSION['member_id']]);
    
    json_response(['success' => true, 'message' => '密码修改成功']);
}

function handleMyMedia() {
    if (!is_member_login()) {
        json_response(['error' => '请先登录'], 401);
    }
    $type = get('type', '');
    $page = max(1, intval(get('page', 1)));
    $pageSize = 12;
    $offset = ($page - 1) * $pageSize;
    
    $where = 'member_id = ?';
    $params = [$_SESSION['member_id']];
    if ($type && in_array($type, ['image', 'audio', 'video'])) {
        $where .= ' AND type = ?';
        $params[] = $type;
    }
    
    $total = db()->count('ai_media', $where, $params);
    $items = db()->fetchAll("SELECT * FROM ai_media WHERE $where ORDER BY created_at DESC LIMIT $offset, $pageSize", $params);
    
    json_response(['items' => $items, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}

function handleMyFavorites() {
    if (!is_member_login()) {
        json_response(['error' => '请先登录'], 401);
    }
    $page = max(1, intval(get('page', 1)));
    $pageSize = 10;
    $offset = ($page - 1) * $pageSize;
    
    $total = db()->count('favorites', 'user_id = ?', [$_SESSION['member_id']]);
    $items = db()->fetchAll(
        "SELECT f.*, p.title, p.summary, p.cover FROM favorites f LEFT JOIN posts p ON f.post_id = p.id WHERE f.user_id = ? ORDER BY f.created_at DESC LIMIT $offset, $pageSize",
        [$_SESSION['member_id']]
    );
    
    json_response(['items' => $items, 'total' => $total, 'page' => $page]);
}
