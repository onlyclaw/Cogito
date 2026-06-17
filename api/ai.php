<?php
/**
 * AI 智能助手接口
 * 支持配置外部 API（OpenAI/Claude等），未配置时使用内置智能回复
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database.php';
require_once dirname(__DIR__) . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

// 获取请求
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
