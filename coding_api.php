<?php
/**
 * 编程智能体 API - 类似 Codex/OpenCode
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/agent.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = preg_replace('#^.*/coding-api/#', '', $uri);

header('Content-Type: application/json; charset=utf-8');

switch ($action) {
    case 'chat':
        handleCodingChat();
        break;
    case 'projects':
        handleProjects();
        break;
    case 'project':
        handleProject();
        break;
    case 'create-project':
        handleCreateProject();
        break;
    case 'save-file':
        handleSaveFile();
        break;
    case 'delete-file':
        handleDeleteFile();
    case 'token-usage':
        handleTokenUsage();
        break;
    case 'stats':
        handleStats();
        break;
    default:
        json_response(['code' => 0, 'msg' => '未知接口'], 404);
}

/**
 * 编程智能体对话
 */
function handleCodingChat() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);

    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    $projectId = (int)($input['project_id'] ?? 0);
    $context = $input['context'] ?? '';

    if (!$message) json_response(['code' => 0, 'msg' => '请输入消息']);

    $memberId = member_id();
    $agent = Agent::find('x-ai');
    if (!$agent) json_response(['code' => 0, 'msg' => '智能体不存在']);

    // 模拟token消耗
    $tokensIn = mb_strlen($message, 'UTF-8');
    $tokensOut = 0;
    $credits = 0;

    // 生成回复
    $apiKey = get_option('ai_api_key', '');
    $apiUrl = get_option('ai_api_url', '');

    if ($apiKey && $apiUrl) {
        $systemPrompt = $agent['system_prompt'] . "\n\n你是一个专业的编程助手，擅长代码生成、调试、优化和项目架构设计。\n用户消息: {$message}";
        if ($context) $systemPrompt .= "\n\n项目上下文:\n{$context}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message]
        ];

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer {$apiKey}"],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $agent['model'],
                'messages' => $messages,
                'max_tokens' => 1500,
                'temperature' => $agent['temperature'],
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $reply = $data['choices'][0]['message']['content'];
            $tokensOut = $data['usage']['completion_tokens'] ?? mb_strlen($reply, 'UTF-8');
        } else {
            $reply = getLocalCodingReply($message, $context);
        }
    } else {
        $reply = getLocalCodingReply($message, $context);
    }

    $credits = round(($tokensIn + $tokensOut) * 0.00002, 4);

    // 记录token使用
    db()->insert('token_usage', [
        'member_id' => $memberId,
        'agent_id' => $agent['id'],
        'agent_slug' => 'x-ai',
        'tokens_in' => $tokensIn,
        'tokens_out' => $tokensOut,
        'credits' => $credits,
        'action' => 'coding_chat',
    ]);

    // 如果有项目，保存到项目历史
    if ($projectId) {
        $project = db()->fetchOne("SELECT * FROM code_projects WHERE id = ? AND member_id = ?", [$projectId, $memberId]);
        if ($project) {
            db()->update('code_projects', [
                'total_tokens' => $project['total_tokens'] + $tokensIn + $tokensOut,
                'total_credits' => $project['total_credits'] + $credits,
            ], 'id = ?', [$projectId]);
        }
    }

    json_response([
        'code' => 1,
        'data' => [
            'reply' => $reply,
            'tokens_in' => $tokensIn,
            'tokens_out' => $tokensOut,
            'credits' => $credits,
        ]
    ]);
}

/**
 * 本地编程回复
 */
function getLocalCodingReply($message, $context) {
    $msg = mb_strtolower($message, 'UTF-8');

    if (mb_strpos($msg, '项目') !== false || mb_strpos($msg, '创建') !== false) {
        return "🚀 项目创建指南\n\n我可以帮你创建一个完整的项目。请告诉我：\n\n1. **项目类型**：Web应用、API、CLI工具、库等\n2. **技术栈**：PHP、Python、JavaScript、Go等\n3. **核心功能**：你需要实现什么功能\n4. **项目规模**：预计文件数量和复杂度\n\n例如：\n- \"创建一个PHP博客系统\"\n- \"开发一个RESTful API\"\n- \"构建一个React组件库\"\n\n请描述你的项目需求，我会为你生成完整的项目结构和代码。";
    }

    if (mb_strpos($msg, '代码') !== false || mb_strpos($msg, '写') !== false || mb_strpos($msg, 'generate') !== false) {
        return "💻 代码生成\n\n请提供以下信息：\n\n```\n1. 编程语言：PHP/Python/JavaScript/Go等\n2. 功能描述：你想实现什么功能\n3. 输入参数：函数/方法需要什么参数\n4. 输出格式：期望的返回值\n```\n\n**示例：**\n```\n语言：PHP\n功能：用户注册验证函数\n参数：$username, $email, $password\n输出：验证结果数组\n```\n\n我会为你生成完整的、可直接使用的代码。";
    }

    if (mb_strpos($msg, '调试') !== false || mb_strpos($msg, 'bug') !== false || mb_strpos($msg, 'error') !== false) {
        return "🔍 代码调试\n\n请提供以下信息：\n\n1. **错误信息**：完整的错误消息\n2. **错误位置**：哪个文件、哪一行\n3. **相关代码**：出错的代码片段\n4. **预期行为**：你期望代码做什么\n5. **实际行为**：代码实际做了什么\n\n我会帮你分析错误原因并提供修复方案。";
    }

    if (mb_strpos($msg, '优化') !== false || mb_strpos($msg, '性能') !== false) {
        return "⚡ 性能优化\n\n我可以帮你优化以下方面：\n\n1. **数据库查询**：SQL优化、索引建议\n2. **代码效率**：算法优化、减少复杂度\n3. **缓存策略**：Redis、Memcached、文件缓存\n4. **前端性能**：懒加载、代码分割、CDN\n5. **服务器配置**：Nginx、PHP-FPM调优\n\n请描述你的性能问题，我会提供具体的优化方案。";
    }

    if (mb_strpos($msg, '架构') !== false || mb_strpos($msg, '设计') !== false) {
        return "🏗️ 架构设计\n\n我可以帮你设计以下架构：\n\n1. **MVC架构**：Model-View-Controller模式\n2. **微服务架构**：服务拆分与通信\n3. **事件驱动架构**：异步处理与消息队列\n4. **CQRS架构**：读写分离模式\n5. **无服务器架构**：Serverless设计\n\n请描述你的系统需求，我会提供详细的架构方案。";
    }

    if (mb_strpos($msg, 'api') !== false || mb_strpos($msg, '接口') !== false) {
        return "🔌 API设计\n\n我可以帮你设计RESTful API：\n\n```\nGET    /api/users        获取用户列表\nGET    /api/users/:id    获取用户详情\nPOST   /api/users        创建用户\nPUT    /api/users/:id    更新用户\nDELETE /api/users/:id    删除用户\n```\n\n包括：\n- 请求/响应格式设计\n- 认证与授权\n- 错误处理\n- 数据验证\n- API文档生成\n\n请告诉我你的API需求。";
    }

    return "💡 作为编程智能体，我可以帮你：\n\n• **代码生成** - 根据需求生成完整代码\n• **代码审查** - 发现潜在问题和改进点\n• **架构设计** - 系统架构和设计模式\n• **性能优化** - 提升代码和系统性能\n• **调试排错** - 快速定位和修复Bug\n• **项目规划** - 项目结构和技术选型\n\n请告诉我你需要什么帮助，比如：\n- \"帮我写一个用户注册接口\"\n- \"分析这段代码的性能问题\"\n- \"设计一个微服务架构\"";
}

/**
 * 获取项目列表
 */
function handleProjects() {
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);
    $memberId = member_id();
    $projects = db()->fetchAll("SELECT * FROM code_projects WHERE member_id = ? ORDER BY updated_at DESC", [$memberId]);
    json_response(['code' => 1, 'data' => $projects]);
}

/**
 * 获取单个项目
 */
function handleProject() {
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);
    $projectId = (int)get('id');
    $memberId = member_id();
    $project = db()->fetchOne("SELECT * FROM code_projects WHERE id = ? AND member_id = ?", [$projectId, $memberId]);
    if (!$project) json_response(['code' => 0, 'msg' => '项目不存在']);
    $files = db()->fetchAll("SELECT * FROM code_files WHERE project_id = ? ORDER BY filename", [$projectId]);
    $project['files'] = $files;
    json_response(['code' => 1, 'data' => $project]);
}

/**
 * 创建项目
 */
function handleCreateProject() {
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);

    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $language = $input['language'] ?? 'php';

    if (!$name) json_response(['code' => 0, 'msg' => '请输入项目名称']);

    $memberId = member_id();
    $projectId = db()->insert('code_projects', [
        'member_id' => $memberId,
        'name' => $name,
        'description' => $desc,
        'language' => $language,
        'status' => 'active',
    ]);

    // 创建默认文件
    $defaultFiles = getDefaultFiles($language);
    foreach ($defaultFiles as $file) {
        db()->insert('code_files', [
            'project_id' => $projectId,
            'filename' => $file['name'],
            'filepath' => $file['path'],
            'content' => $file['content'],
            'language' => $file['lang'],
            'size' => strlen($file['content']),
        ]);
    }

    json_response(['code' => 1, 'msg' => '项目创建成功', 'data' => ['id' => $projectId]]);
}

/**
 * 保存文件
 */
function handleSaveFile() {
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);

    $input = json_decode(file_get_contents('php://input'), true);
    $projectId = (int)($input['project_id'] ?? 0);
    $filename = trim($input['filename'] ?? '');
    $content = $input['content'] ?? '';
    $language = $input['language'] ?? 'php';

    if (!$projectId || !$filename) json_response(['code' => 0, 'msg' => '参数错误']);

    $memberId = member_id();
    $project = db()->fetchOne("SELECT id FROM code_projects WHERE id = ? AND member_id = ?", [$projectId, $memberId]);
    if (!$project) json_response(['code' => 0, 'msg' => '项目不存在']);

    $existing = db()->fetchOne("SELECT id FROM code_files WHERE project_id = ? AND filename = ?", [$projectId, $filename]);
    if ($existing) {
        db()->update('code_files', [
            'content' => $content,
            'language' => $language,
            'size' => strlen($content),
        ], 'id = ?', [$existing['id']]);
    } else {
        db()->insert('code_files', [
            'project_id' => $projectId,
            'filename' => $filename,
            'filepath' => $filename,
            'content' => $content,
            'language' => $language,
            'size' => strlen($content),
        ]);
    }

    db()->update('code_projects', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$projectId]);

    json_response(['code' => 1, 'msg' => '文件保存成功']);
}

/**
 * 删除文件
 */
function handleDeleteFile() {
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);

    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = (int)($input['file_id'] ?? 0);
    $memberId = member_id();

    if (!$fileId) json_response(['code' => 0, 'msg' => '参数错误']);

    $file = db()->fetchOne("SELECT f.* FROM code_files f JOIN code_projects p ON f.project_id = p.id WHERE f.id = ? AND p.member_id = ?", [$fileId, $memberId]);
    if (!$file) json_response(['code' => 0, 'msg' => '文件不存在']);

    db()->delete('code_files', 'id = ?', [$fileId]);
    json_response(['code' => 1, 'msg' => '文件已删除']);
}

/**
 * Token使用统计
 */
function handleTokenUsage() {
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);
    $memberId = member_id();

    $totalTokens = db()->fetchColumn("SELECT IFNULL(SUM(tokens_in + tokens_out), 0) FROM token_usage WHERE member_id = ?", [$memberId]);
    $totalCredits = db()->fetchColumn("SELECT IFNULL(SUM(credits), 0) FROM token_usage WHERE member_id = ?", [$memberId]);
    $todayTokens = db()->fetchColumn("SELECT IFNULL(SUM(tokens_in + tokens_out), 0) FROM token_usage WHERE member_id = ? AND DATE(created_at) = CURDATE()", [$memberId]);
    $recentUsage = db()->fetchAll("SELECT * FROM token_usage WHERE member_id = ? ORDER BY created_at DESC LIMIT 10", [$memberId]);

    json_response(['code' => 1, 'data' => [
        'total_tokens' => (int)$totalTokens,
        'total_credits' => (float)$totalCredits,
        'today_tokens' => (int)$todayTokens,
        'recent' => $recentUsage,
    ]]);
}

/**
 * 统计数据
 */
function handleStats() {
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);
    $memberId = member_id();

    $projectCount = db()->count('code_projects', 'member_id = ?', [$memberId]);
    $fileCount = db()->fetchColumn("SELECT IFNULL(SUM(1), 0) FROM code_files cf JOIN code_projects cp ON cf.project_id = cp.id WHERE cp.member_id = ?", [$memberId]);
    $totalCredits = db()->fetchColumn("SELECT IFNULL(SUM(credits), 0) FROM token_usage WHERE member_id = ?", [$memberId]);

    json_response(['code' => 1, 'data' => [
        'projects' => (int)$projectCount,
        'files' => (int)$fileCount,
        'credits' => (float)$totalCredits,
    ]]);
}

/**
 * 获取默认文件模板
 */
function getDefaultFiles($language) {
    $templates = [
        'php' => [
            ['name' => 'index.php', 'path' => 'index.php', 'lang' => 'php', 'content' => "<?php\n/**\n * 项目入口\n */\n\nrequire_once __DIR__ . '/config.php';\nrequire_once __DIR__ . '/database.php';\nrequire_once __DIR__ . '/functions.php';\n\n// 项目逻辑\n\necho \"项目已启动\";\n"],
            ['name' => 'config.php', 'path' => 'config.php', 'lang' => 'php', 'content' => "<?php\n/**\n * 配置文件\n */\n\ndefine('DB_HOST', '127.0.0.1');\ndefine('DB_NAME', 'mydb');\ndefine('DB_USER', 'root');\ndefine('DB_PASS', '');\n"],
            ['name' => 'functions.php', 'path' => 'functions.php', 'lang' => 'php', 'content' => "<?php\n/**\n * 公共函数\n */\n\nfunction hello() {\n    return \"Hello, World!\";\n}\n"],
        ],
        'javascript' => [
            ['name' => 'index.js', 'path' => 'index.js', 'lang' => 'javascript', 'content' => "/**\n * 项目入口\n */\n\nconsole.log('项目已启动');\n"],
            ['name' => 'package.json', 'path' => 'package.json', 'lang' => 'json', 'content' => "{\n  \"name\": \"my-project\",\n  \"version\": \"1.0.0\",\n  \"main\": \"index.js\"\n}\n"],
        ],
        'python' => [
            ['name' => 'main.py', 'path' => 'main.py', 'lang' => 'python', 'content' => "#!/usr/bin/env python3\n\"\"\"项目入口\"\"\"\n\ndef main():\n    print('项目已启动')\n\nif __name__ == '__main__':\n    main()\n"],
            ['name' => 'requirements.txt', 'path' => 'requirements.txt', 'lang' => 'text', 'content' => "# 依赖列表\n"],
        ],
    ];
    return $templates[$language] ?? $templates['php'];
}
