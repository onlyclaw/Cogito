<?php
/**
 * API接口
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// 获取API动作
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = preg_replace('#^.*/api/#', '', $uri);

header('Content-Type: application/json; charset=utf-8');

switch ($action) {
    case 'comment':
        handle_comment();
        break;
    case 'like':
        handle_like();
        break;
    case 'favorite':
        handle_favorite();
        break;
    case 'upload':
        handle_upload();
        break;
    case 'login':
        handle_login();
        break;
    case 'ai':
        handle_ai();
        break;
    default:
        json_response(['code' => 0, 'msg' => '未知接口'], 404);
}

/**
 * 处理评论提交
 */
function handle_comment() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['code' => 0, 'msg' => '请求方式错误']);
    }

    $postId = (int)post('post_id');
    $parentId = (int)post('parent_id', 0);
    $nickname = post('nickname');
    $email = post('email');
    $content = post('content');

    if (!$postId || !$nickname || !$email || !$content) {
        json_response(['code' => 0, 'msg' => '请填写完整信息']);
    }

    if (strlen($content) < 2) {
        json_response(['code' => 0, 'msg' => '评论内容太短']);
    }

    // 检查文章是否存在
    $post = db()->fetchOne("SELECT id FROM posts WHERE id = ? AND status = 'published'", [$postId]);
    if (!$post) {
        json_response(['code' => 0, 'msg' => '文章不存在']);
    }

    // 默认审核通过（可在后台设置）
    $status = get_option('comment_check', '1') === '1' ? 'pending' : 'approved';

    // 如果用户已登录，自动审核通过
    if (is_login() || is_member_login()) {
        $status = 'approved';
    }

    $commentId = db()->insert('comments', [
        'post_id' => $postId,
        'parent_id' => $parentId,
        'user_id' => is_login() ? (int)$_SESSION['user_id'] : 0,
        'nickname' => $nickname,
        'email' => $email,
        'content' => $content,
        'status' => $status,
        'ip' => server('REMOTE_ADDR'),
    ]);

    json_response([
        'code' => 1,
        'msg' => $status === 'pending' ? '评论已提交，等待审核' : '评论成功',
        'data' => ['id' => $commentId]
    ]);
}

/**
 * 处理文章点赞
 */
function handle_like() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['code' => 0, 'msg' => '请求方式错误']);
    }

    $postId = (int)post('post_id');
    if (!$postId) {
        json_response(['code' => 0, 'msg' => '参数错误']);
    }

    // 检查是否已点赞（使用session记录）
    $likedKey = 'liked_' . $postId;
    if (isset($_SESSION[$likedKey])) {
        json_response(['code' => 0, 'msg' => '您已经点过赞了']);
    }

    db()->query("UPDATE posts SET likes = likes + 1 WHERE id = ?", [$postId]);
    $_SESSION[$likedKey] = true;

    $likes = db()->fetchColumn("SELECT likes FROM posts WHERE id = ?", [$postId]);

    json_response(['code' => 1, 'msg' => '点赞成功', 'data' => ['likes' => $likes]]);
}

/**
 * 处理文件上传
 */
function handle_upload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['code' => 0, 'msg' => '请求方式错误']);
    }

    if (!is_login()) {
        json_response(['code' => 0, 'msg' => '请先登录'], 401);
    }

    if (empty($_FILES['file'])) {
        json_response(['code' => 0, 'msg' => '请选择文件']);
    }

    $result = upload_file($_FILES['file']);
    json_response($result);
}

/**
 * 处理登录
 */
function handle_login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['code' => 0, 'msg' => '请求方式错误']);
    }

    $username = post('username');
    $password = post('password');

    if (!$username || !$password) {
        json_response(['code' => 0, 'msg' => '请输入用户名和密码']);
    }

    $user = db()->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
    if (!$user || !password_verify($password, $user['password'])) {
        json_response(['code' => 0, 'msg' => '用户名或密码错误']);
    }

    if ($user['status'] != 1) {
        json_response(['code' => 0, 'msg' => '账号已被禁用']);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    json_response(['code' => 1, 'msg' => '登录成功', 'data' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'nickname' => $user['nickname'],
        'role' => $user['role'],
    ]]);
}

/**
 * 处理文章收藏
 */
function handle_favorite() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['code' => 0, 'msg' => '请求方式错误']);
    }

    $postId = (int)post('post_id');
    if (!$postId) {
        json_response(['code' => 0, 'msg' => '参数错误']);
    }

    // 会员收藏使用会员ID
    if (is_member_login()) {
        $memberId = (int)$_SESSION['member_id'];
        $exists = db()->fetchColumn(
            "SELECT COUNT(*) FROM favorites WHERE post_id = ? AND user_id = ?",
            [$postId, $memberId]
        );
        if ($exists) {
            db()->delete('favorites', 'post_id = ? AND user_id = ?', [$postId, $memberId]);
            $favorited = false;
        } else {
            db()->insert('favorites', [
                'post_id' => $postId,
                'visitor_id' => md5(server('REMOTE_ADDR') . server('HTTP_USER_AGENT')),
                'user_id' => $memberId,
            ]);
            $favorited = true;
        }
    } else {
        // 访客收藏
        $visitorId = md5(server('REMOTE_ADDR') . server('HTTP_USER_AGENT'));
        $exists = db()->fetchColumn(
            "SELECT COUNT(*) FROM favorites WHERE post_id = ? AND visitor_id = ?",
            [$postId, $visitorId]
        );
        if ($exists) {
            db()->delete('favorites', 'post_id = ? AND visitor_id = ?', [$postId, $visitorId]);
            $favorited = false;
        } else {
            db()->insert('favorites', [
                'post_id' => $postId,
                'visitor_id' => $visitorId,
                'user_id' => 0,
            ]);
            $favorited = true;
        }
    }

    $count = db()->fetchColumn("SELECT COUNT(*) FROM favorites WHERE post_id = ?", [$postId]);

    json_response([
        'code' => 1,
        'msg' => $favorited ? '收藏成功' : '已取消收藏',
        'data' => ['favorited' => $favorited, 'count' => $count]
    ]);
}

/**
 * AI 智能助手
 */
function handle_ai() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['code' => 0, 'msg' => '请求方式错误']);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    if (!$message) {
        json_response(['code' => 0, 'msg' => '请输入消息']);
    }

    // 检查是否配置了外部 AI API
    $apiKey = get_option('ai_api_key', '');
    $apiUrl = get_option('ai_api_url', '');
    $aiModel = get_option('ai_model', 'gpt-3.5-turbo');

    // 如果配置了外部 API，调用外部 API
    if ($apiKey && $apiUrl) {
        $reply = callExternalAI($message, $apiKey, $apiUrl, $aiModel);
        json_response(['code' => 1, 'data' => ['reply' => $reply]]);
    }

    // 否则使用内置智能回复
    $reply = getSmartReply($message);
    json_response(['code' => 1, 'data' => ['reply' => $reply]]);
}

/**
 * 调用外部 AI API
 */
function callExternalAI($message, $apiKey, $apiUrl, $model) {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                        "Authorization: Bearer {$apiKey}\r\n",
            'content' => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => '你是一个智能博客助手，可以帮助用户总结文章、回答问题、推荐内容。请用简洁友好的方式回答。'],
                    ['role' => 'user', 'content' => $message]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]),
            'timeout' => 30,
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);
    if ($response === false) {
        return 'AI 服务暂时不可用，请稍后再试。';
    }

    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }

    return '无法获取 AI 响应，请检查 API 配置。';
}

/**
 * 内置智能回复
 */
function getSmartReply($message) {
    $message = mb_strtolower($message, 'UTF-8');

    // 获取站点信息
    $siteName = get_option('site_name', '博客');
    $postCount = is_installed() ? db()->fetchColumn("SELECT COUNT(*) FROM posts WHERE status = 'published'") : 0;
    $catCount = is_installed() ? db()->fetchColumn("SELECT COUNT(*) FROM categories") : 0;
    $tagCount = is_installed() ? db()->fetchColumn("SELECT COUNT(*) FROM tags") : 0;

    // 总结文章
    if (mb_strpos($message, '总结') !== false || mb_strpos($message, '摘要') !== false) {
        $postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
        if ($postId && is_installed()) {
            $post = db()->fetchOne("SELECT title, content, summary FROM posts WHERE id = ?", [$postId]);
            if ($post) {
                $summary = $post['summary'] ?: mb_substr(strip_tags($post['content']), 0, 200, 'UTF-8') . '...';
                return "📄 文章摘要：\n\n【{$post['title']}】\n\n{$summary}\n\n💡 这篇文章共有 " . mb_strlen(strip_tags($post['content']), 'UTF-8') . " 字。";
            }
        }
        return "📝 我可以帮你总结文章内容。请在文章详情页使用此功能，或者告诉我你想总结哪篇文章。";
    }

    // 推荐文章
    if (mb_strpos($message, '推荐') !== false || mb_strpos($message, '相关') !== false) {
        if (is_installed()) {
            $posts = db()->fetchAll("SELECT id, title, views FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT 3");
            if (!empty($posts)) {
                $list = "📚 热门文章推荐：\n\n";
                foreach ($posts as $i => $p) {
                    $list .= ($i + 1) . ". {$p['title']} ({$p['views']}次浏览)\n";
                }
                $list .= "\n点击导航栏的「分类」可以发现更多内容！";
                return $list;
            }
        }
        return "📚 暂时没有推荐文章，博主还在努力创作中！";
    }

    // 解释代码
    if (mb_strpos($message, '代码') !== false || mb_strpos($message, '编程') !== false) {
        return "💻 我可以帮你解释代码片段。你可以：\n\n1. 在文章中查看代码示例\n2. 把代码片段发给我，我来解释\n3. 询问特定编程语言的问题\n\n支持的语言：PHP、JavaScript、Python、SQL 等";
    }

    // 翻译
    if (mb_strpos($message, '翻译') !== false) {
        return "🌐 我可以帮你翻译内容。请把需要翻译的文本发给我，支持中英互译。";
    }

    // 问候
    if (mb_strpos($message, '你好') !== false || mb_strpos($message, 'hi') !== false ||
        mb_strpos($message, 'hello') !== false || mb_strpos($message, '嗨') !== false) {
        return "你好！👋 欢迎来到 {$siteName}！\n\n我是你的 AI 助手，可以帮你：\n• 总结文章内容\n• 推荐热门文章\n• 解释代码片段\n• 翻译文本\n\n有什么我可以帮你的吗？";
    }

    // 关于站点
    if (mb_strpos($message, '关于') !== false || mb_strpos($message, '站点') !== false ||
        mb_strpos($message, '博客') !== false) {
        return "ℹ️ 关于 {$siteName}：\n\n• 📝 共有 {$postCount} 篇文章\n• 📁 {$catCount} 个分类\n• 🏷️ {$tagCount} 个标签\n\n这是一个使用 PHP 7.4 + MySQL 5.7 构建的现代化博客系统，支持 AI 智能助手功能。";
    }

    // 帮助
    if (mb_strpos($message, '帮助') !== false || mb_strpos($message, 'help') !== false ||
        mb_strpos($message, '功能') !== false) {
        return "🤖 AI 助手使用指南：\n\n• 「总结」- 总结当前文章\n• 「推荐」- 获取热门文章推荐\n• 「代码」- 代码相关帮助\n• 「翻译」- 文本翻译\n• 「关于」- 了解站点信息\n\n你也可以直接问我任何问题！";
    }

    // 时间
    if (mb_strpos($message, '时间') !== false || mb_strpos($message, '日期') !== false) {
        return "🕐 当前时间：" . date('Y年m月d日 H:i:s') . "\n\n今天是" . ['日', '一', '二', '三', '四', '五', '六'][date('w')] . "。";
    }

    // 默认回复
    $defaults = [
        "🤔 这个问题很有趣！虽然我的能力有限，但我会尽力帮助你。你可以试试问我：\n\n• 总结文章\n• 推荐内容\n• 代码问题\n• 翻译文本",
        "💡 好问题！让我想想...\n\n你可以使用快捷操作按钮，或者直接描述你的需求。我会尽我所能帮助你！",
        "😊 谢谢你的提问！作为 AI 助手，我可以帮你：\n\n1. 总结文章要点\n2. 推荐热门内容\n3. 解释代码逻辑\n4. 翻译多语言内容\n\n请告诉我你需要什么帮助？",
        "🚀 收到！虽然我可能无法完美回答所有问题，但我会尽力而为。\n\n你可以试试这些指令：\n• 总结\n• 推荐\n• 代码\n• 翻译\n• 关于",
    ];

    return $defaults[array_rand($defaults)];
}
