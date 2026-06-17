<?php
/**
 * 公共函数库
 */

// 检查数据库是否已安装
function is_installed() {
    return db()->isConnected();
}

// 安全获取GET参数
function get($key, $default = '') {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// 安全获取POST参数
function post($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// 获取服务器变量
function server($key, $default = '') {
    return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
}

// XSS过滤
function clean($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 截取字符串
function cut_str($str, $length = 100, $suffix = '...') {
    if (mb_strlen($str, 'UTF-8') <= $length) return $str;
    return mb_substr($str, 0, $length, 'UTF-8') . $suffix;
}

// 生成URL友好的slug
function slug($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

// 格式化时间
function format_date($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

// 格式化友好的时间
function friendly_date($date) {
    $now = time();
    $time = strtotime($date);
    $diff = $now - $time;

    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    if ($diff < 31536000) return floor($diff / 2592000) . '个月前';
    return floor($diff / 31536000) . '年前';
}

// 格式化数字
function format_number($num) {
    if ($num >= 10000) {
        return round($num / 10000, 1) . '万';
    }
    return $num;
}

// 重定向
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// JSON响应
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// CSRF Token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    $token = post('_token');
    return $token && hash_equals(csrf_token(), $token);
}

// 检查登录状态
function is_login() {
    return !empty($_SESSION['user_id']);
}

// 获取当前登录用户
function current_user() {
    if (!is_login()) return null;
    if (!is_installed()) return null;
    static $user = null;
    if ($user === null) {
        $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    return $user;
}

// 检查是否管理员
function is_admin() {
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

// ========== 前台用户（会员）系统 ==========

// 检查前台用户是否登录
function is_member_login() {
    return !empty($_SESSION['member_id']);
}

// 获取当前登录的前台用户
function current_member() {
    if (!is_member_login()) return null;
    if (!is_installed()) return null;
    static $member = null;
    if ($member === null) {
        $member = db()->fetchOne("SELECT * FROM members WHERE id = ? AND status = 1", [$_SESSION['member_id']]);
    }
    return $member;
}

// 获取前台用户昵称
function member_nickname() {
    return $_SESSION['member_nickname'] ?? $_SESSION['member_username'] ?? '用户';
}

// 获取前台用户ID
function member_id() {
    return $_SESSION['member_id'] ?? 0;
}
// 生成分页
function paginate($total, $page, $pageSize = PAGE_SIZE) {
    $totalPages = max(1, ceil($total / $pageSize));
    $page = max(1, min($page, $totalPages));

    return [
        'total' => (int)$total,
        'page' => (int)$page,
        'page_size' => (int)$pageSize,
        'total_pages' => (int)$totalPages,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
        'offset' => ($page - 1) * $pageSize,
    ];
}

// 渲染分页HTML
function render_pagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav class="pagination-wrap"><ul class="pagination">';

    // 上一页
    if ($pagination['has_prev']) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($pagination['page'] - 1) . '" class="page-link"><i class="fas fa-chevron-left"></i></a></li>';
    }

    // 页码
    $start = max(1, $pagination['page'] - 2);
    $end = min($pagination['total_pages'], $pagination['page'] + 2);

    if ($start > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=1" class="page-link">1</a></li>';
        if ($start > 2) $html .= '<li class="page-dots">...</li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $pagination['page'] ? ' active' : '';
        $html .= '<li class="' . $active . '"><a href="' . $baseUrl . '?page=' . $i . '" class="page-link">' . $i . '</a></li>';
    }

    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) $html .= '<li class="page-dots">...</li>';
        $html .= '<li><a href="' . $baseUrl . '?page=' . $pagination['total_pages'] . '" class="page-link">' . $pagination['total_pages'] . '</a></li>';
    }

    // 下一页
    if ($pagination['has_next']) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($pagination['page'] + 1) . '" class="page-link"><i class="fas fa-chevron-right"></i></a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

// 上传文件
function upload_file($file, $dir = 'images') {
    $uploadDir = UPLOADS_PATH . '/' . $dir;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
    if (!in_array($ext, $allowExts)) {
        return ['code' => 0, 'msg' => '不支持的文件格式'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['code' => 0, 'msg' => '文件大小不能超过5MB'];
    }

    $newName = date('Ymd') . '_' . uniqid() . '.' . $ext;
    $filePath = $uploadDir . '/' . $newName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $url = UPLOADS_URL . '/' . $dir . '/' . $newName;
        return ['code' => 1, 'msg' => '上传成功', 'url' => $url, 'path' => $dir . '/' . $newName];
    }

    return ['code' => 0, 'msg' => '上传失败'];
}

// 获取站点配置
function get_option($key, $default = '') {
    // 默认配置（数据库未安装时使用）
    $defaults = [
        'site_name' => '我的博客',
        'site_desc' => '一个简洁优雅的个人博客',
        'site_keywords' => '博客,技术,分享',
        'site_url' => 'http://localhost/boke',
        'admin_email' => 'admin@example.com',
        'comment_check' => '1',
        'posts_per_page' => '8',
    ];

    if (!is_installed()) {
        return isset($defaults[$key]) ? $defaults[$key] : $default;
    }

    static $options = null;
    if ($options === null) {
        $rows = db()->fetchAll("SELECT option_key, option_value FROM options");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['option_key']] = $row['option_value'];
        }
    }
    return isset($options[$key]) ? $options[$key] : (isset($defaults[$key]) ? $defaults[$key] : $default);
}

// 设置站点配置
function set_option($key, $value) {
    if (!is_installed()) return false;
    $exists = db()->fetchColumn("SELECT COUNT(*) FROM options WHERE option_key = ?", [$key]);
    if ($exists) {
        db()->update('options', ['option_value' => $value], 'option_key = ?', [$key]);
    } else {
        db()->insert('options', ['option_key' => $key, 'option_value' => $value]);
    }
}

// 获取分类列表
function get_categories() {
    if (!is_installed()) return [];
    return db()->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM posts WHERE category_id = c.id AND status = 'published') as post_count FROM categories c ORDER BY sort_order ASC, id ASC");
}

// 获取标签列表
function get_tags() {
    if (!is_installed()) return [];
    return db()->fetchAll("SELECT t.*, (SELECT COUNT(*) FROM post_tags WHERE tag_id = t.id) as post_count FROM tags t ORDER BY post_count DESC");
}

// 获取热门文章
function get_hot_posts($limit = 5) {
    if (!is_installed()) return [];
    return db()->fetchAll("SELECT id, title, views, created_at FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT ?", [$limit]);
}

// 获取最新评论
function get_recent_comments($limit = 5) {
    if (!is_installed()) return [];
    return db()->fetchAll("SELECT c.*, p.title as post_title FROM comments c LEFT JOIN posts p ON c.post_id = p.id WHERE c.status = 'approved' ORDER BY c.created_at DESC LIMIT ?", [$limit]);
}

// 渲染视图
function view($name, $data = []) {
    extract($data);
    $viewPath = ROOT_PATH . '/views/' . str_replace('.', '/', $name) . '.php';
    if (file_exists($viewPath)) {
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
    die("视图文件不存在: $name");
}

// 消息提示
function set_flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return '';
}

// 生成缩略图URL（简单版本，实际可对接图片处理库）
function thumbnail($url, $width = 300, $height = 200) {
    // 简单返回原图，实际项目可使用图片处理库
    return $url;
}

// 获取文章摘要
function get_summary($content, $length = 200) {
    $text = strip_tags($content);
    return cut_str($text, $length);
}

// 安装提示页面
function show_install_prompt() {
    $siteName = get_option('site_name', SITE_NAME);
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>系统未安装 - <?php echo clean($siteName); ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .install-prompt { max-width: 500px; background: #fff; border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
            .install-prompt i { font-size: 60px; color: #667eea; margin-bottom: 20px; }
            .btn-install { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px 30px; color: #fff; border-radius: 10px; font-size: 16px; text-decoration: none; display: inline-block; }
            .btn-install:hover { opacity: 0.9; color: #fff; }
        </style>
    </head>
    <body>
        <div class="install-prompt">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>系统尚未安装</h3>
            <p class="text-muted mb-4">检测到数据库尚未初始化，请先运行安装向导</p>
            <a href="<?php echo SITE_URL; ?>/install.php" class="btn-install">
                <i class="fas fa-rocket me-2"></i>开始安装
            </a>
            <p class="text-muted mt-3 small">安装将自动创建数据库和默认管理员账号</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
