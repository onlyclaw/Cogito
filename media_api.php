<?php
/**
 * AI 媒体生成 API - 支持真实 API + 模拟模式
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/agent.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = preg_replace('#^.*/media-api/#', '', $uri);

header('Content-Type: application/json; charset=utf-8');

switch ($action) {
    case 'generate-image':
        handleGenerateImage();
        break;
    case 'generate-audio':
        handleGenerateAudio();
        break;
    case 'generate-video':
        handleGenerateVideo();
        break;
    case 'video-status':
        handleVideoStatus();
        break;
    case 'gallery':
        handleGallery();
        break;
    case 'media-info':
        handleMediaInfo();
        break;
    default:
        json_response(['code' => 0, 'msg' => '未知接口'], 404);
}

// ============================================================
// 图片生成
// ============================================================
function handleGenerateImage() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录后再生成内容', 'need_login' => true]);

    $input = json_decode(file_get_contents('php://input'), true);
    $prompt = trim($input['prompt'] ?? '');
    $agentId = (int)($input['agent_id'] ?? 0);
    $style = $input['style'] ?? 'default';
    $size = $input['size'] ?? '1024x1024';

    if (!$prompt) json_response(['code' => 0, 'msg' => '请输入图片描述']);

    $memberId = member_id();
    $mediaId = db()->insert('ai_media', [
        'agent_id' => $agentId, 'user_id' => is_login() ? (int)$_SESSION['user_id'] : 0, 'member_id' => $memberId, 'type' => 'image', 'prompt' => $prompt,
        'status' => 'generating', 'meta_json' => json_encode(['style' => $style, 'size' => $size]),
    ]);

    $apiKey = get_option('ai_api_key', '');
    $apiUrl = get_option('ai_api_url', '');

    // 尝试调用 DALL-E
    if ($apiKey) {
        $result = callDallE($prompt, $size, $apiKey);
        if ($result['success']) {
            db()->update('ai_media', [
                'content_url' => $result['url'], 'status' => 'completed',
                'width' => (int)explode('x', $size)[0], 'height' => (int)explode('x', $size)[1],
            ], 'id = ?', [$mediaId]);
            json_response(['code' => 1, 'data' => ['id' => $mediaId, 'url' => $result['url'], 'status' => 'completed', 'mode' => 'api']]);
            return;
        }
    }

    // 模拟模式：生成渐变占位图
    $url = generatePlaceholderImage($prompt, $size, $style);
    db()->update('ai_media', [
        'content_url' => $url, 'status' => 'completed',
        'width' => (int)explode('x', $size)[0], 'height' => (int)explode('x', $size)[1],
    ], 'id = ?', [$mediaId]);
    json_response(['code' => 1, 'data' => ['id' => $mediaId, 'url' => $url, 'status' => 'completed', 'mode' => 'demo']]);
}

function callDallE($prompt, $size, $apiKey) {
    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer {$apiKey}"],
        CURLOPT_POSTFIELDS => json_encode(['model' => 'dall-e-3', 'prompt' => $prompt, 'n' => 1, 'size' => $size]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return ['success' => false, 'error' => 'API错误: HTTP ' . $httpCode];
    $result = json_decode($response, true);
    if (isset($result['data'][0]['url'])) return ['success' => true, 'url' => $result['data'][0]['url']];
    return ['success' => false, 'error' => $result['error']['message'] ?? '生成失败'];
}

// ============================================================
// 音频生成
// ============================================================
function handleGenerateAudio() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录后再生成内容', 'need_login' => true]);

    $input = json_decode(file_get_contents('php://input'), true);
    $text = trim($input['text'] ?? '');
    $agentId = (int)($input['agent_id'] ?? 0);
    $voice = $input['voice'] ?? 'default';
    $speed = $input['speed'] ?? 1.0;

    if (!$text) json_response(['code' => 0, 'msg' => '请输入文本内容']);

    $memberId = member_id();
    $mediaId = db()->insert('ai_media', [
        'agent_id' => $agentId, 'user_id' => is_login() ? (int)$_SESSION['user_id'] : 0, 'member_id' => $memberId, 'type' => 'audio', 'prompt' => $text,
        'status' => 'generating', 'meta_json' => json_encode(['voice' => $voice, 'speed' => $speed]),
    ]);

    // 尝试调用 OpenAI TTS
    $apiKey = get_option('ai_api_key', '');
    if ($apiKey) {
        $result = callTTS($text, $voice, $speed, $apiKey);
        if ($result['success']) {
            $duration = max(3, mb_strlen($text, 'UTF-8') * 0.08 / $speed);
            db()->update('ai_media', [
                'content_url' => $result['url'], 'status' => 'completed', 'duration' => (int)$duration,
            ], 'id = ?', [$mediaId]);
            json_response(['code' => 1, 'data' => ['id' => $mediaId, 'url' => $result['url'], 'duration' => (int)$duration, 'status' => 'completed', 'mode' => 'api']]);
            return;
        }
    }

    // 模拟模式
    $duration = max(3, mb_strlen($text, 'UTF-8') * 0.08 / $speed);
    db()->update('ai_media', ['status' => 'completed', 'duration' => (int)$duration], 'id = ?', [$mediaId]);
    json_response(['code' => 1, 'data' => ['id' => $mediaId, 'url' => '', 'duration' => (int)$duration, 'status' => 'completed', 'mode' => 'demo', 'text' => cut_str($text, 100)]]);
}

function callTTS($text, $voice, $speed, $apiKey) {
    $voiceMap = ['default' => 'alloy', 'male' => 'echo', 'female' => 'nova', 'narrator' => 'onyx'];
    $voiceName = $voiceMap[$voice] ?? 'alloy';

    $tmpFile = tempnam(sys_get_temp_dir(), 'tts_');
    $ch = curl_init('https://api.openai.com/v1/audio/speech');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer {$apiKey}"],
        CURLOPT_POSTFIELDS => json_encode(['model' => 'tts-1', 'input' => $text, 'voice' => $voiceName, 'speed' => $speed]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_FILE => fopen($tmpFile, 'w'),
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && filesize($tmpFile) > 0) {
        $dir = UPLOADS_PATH . '/audio';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'tts_' . date('Ymd_His') . '_' . uniqid() . '.mp3';
        rename($tmpFile, $dir . '/' . $filename);
        return ['success' => true, 'url' => UPLOADS_URL . '/audio/' . $filename];
    }
    @unlink($tmpFile);
    return ['success' => false];
}

// ============================================================
// 视频生成 - Runway ML API + 模拟模式
// ============================================================
function handleGenerateVideo() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录后再生成内容', 'need_login' => true]);

    $input = json_decode(file_get_contents('php://input'), true);
    $prompt = trim($input['prompt'] ?? '');
    $agentId = (int)($input['agent_id'] ?? 0);
    $duration = (int)($input['duration'] ?? 5);
    $style = $input['style'] ?? 'cinematic';

    if (!$prompt) json_response(['code' => 0, 'msg' => '请输入视频描述']);

    $memberId = member_id();
    $mediaId = db()->insert('ai_media', [
        'agent_id' => $agentId, 'user_id' => is_login() ? (int)$_SESSION['user_id'] : 0, 'member_id' => $memberId, 'type' => 'video', 'prompt' => $prompt,
        'status' => 'generating',
        'meta_json' => json_encode(['duration' => $duration, 'style' => $style, 'progress' => 0]),
    ]);

    // 尝试调用 Runway ML
    $runwayKey = get_option('runway_api_key', '');
    if ($runwayKey) {
        $result = callRunwayML($prompt, $duration, $runwayKey, $mediaId);
        if ($result['success']) {
            db()->update('ai_media', [
                'content_url' => $result['url'], 'status' => 'completed',
                'duration' => $duration, 'width' => 1280, 'height' => 720,
            ], 'id = ?', [$mediaId]);
            json_response(['code' => 1, 'data' => ['id' => $mediaId, 'url' => $result['url'], 'status' => 'completed', 'mode' => 'api', 'duration' => $duration]]);
            return;
        }
    }

    // 模拟模式：生成渐进式预览
    $previewFrames = generatePreviewFrames($prompt, $style, $mediaId);

    db()->update('ai_media', [
        'status' => 'completed', 'duration' => $duration, 'width' => 1280, 'height' => 720,
        'thumbnail_url' => $previewFrames['thumbnail'],
        'meta_json' => json_encode(['duration' => $duration, 'style' => $style, 'frames' => $previewFrames['frames']]),
    ], 'id = ?', [$mediaId]);

    json_response(['code' => 1, 'data' => [
        'id' => $mediaId, 'url' => '', 'status' => 'completed', 'mode' => 'demo',
        'duration' => $duration, 'thumbnail' => $previewFrames['thumbnail'],
        'frames' => $previewFrames['frames'], 'message' => '视频模拟生成完成（演示模式）'
    ]]);
}

/**
 * Runway ML API 调用
 */
function callRunwayML($prompt, $duration, $apiKey, $mediaId) {
    // Runway ML Gen-3 API
    $ch = curl_init('https://api.runwayml.com/v1/image_to_video');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "Authorization: Bearer {$apiKey}",
            'X-Runway-Version: 2024-11-06',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gen3a_turbo',
            'promptImage' => null,
            'promptText' => $prompt,
            'duration' => min($duration, 10),
            'ratio' => '16:9',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201 || $httpCode === 200) {
        $result = json_decode($response, true);
        $taskId = $result['id'] ?? '';
        if ($taskId) {
            // 保存任务ID用于后续查询
            db()->update('ai_media', [
                'meta_json' => json_encode(['runway_task_id' => $taskId, 'status' => 'processing'])
            ], 'id = ?', [$mediaId]);
            return ['success' => true, 'task_id' => $taskId, 'url' => ''];
        }
    }

    return ['success' => false];
}

/**
 * 查询 Runway ML 任务状态
 */
function handleVideoStatus() {
    $mediaId = (int)get('id');
    if (!$mediaId) json_response(['code' => 0, 'msg' => '参数错误']);

    $media = db()->fetchOne("SELECT * FROM ai_media WHERE id = ?", [$mediaId]);
    if (!$media) json_response(['code' => 0, 'msg' => '媒体不存在']);

    $meta = json_decode($media['meta_json'], true) ?: [];
    $runwayTaskId = $meta['runway_task_id'] ?? '';
    $runwayKey = get_option('runway_api_key', '');

    if ($runwayTaskId && $runwayKey) {
        $ch = curl_init("https://api.runwayml.com/v1/tasks/{$runwayTaskId}");
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$runwayKey}",
                'X-Runway-Version: 2024-11-06',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        if ($result['status'] === 'SUCCEEDED') {
            $videoUrl = $result['output'][0] ?? '';
            db()->update('ai_media', ['content_url' => $videoUrl, 'status' => 'completed'], 'id = ?', [$mediaId]);
            json_response(['code' => 1, 'data' => ['status' => 'completed', 'url' => $videoUrl]]);
            return;
        } elseif ($result['status'] === 'FAILED') {
            db()->update('ai_media', ['status' => 'failed'], 'id = ?', [$mediaId]);
            json_response(['code' => 0, 'msg' => '生成失败']);
            return;
        }
        // 处理中
        $progress = $result['progress'] ?? 0;
        json_response(['code' => 1, 'data' => ['status' => 'generating', 'progress' => $progress]]);
        return;
    }

    // 模拟模式：返回进度
    $progress = min(100, (int)($meta['progress'] ?? 0) + rand(5, 15));
    $meta['progress'] = $progress;
    db()->update('ai_media', ['meta_json' => json_encode($meta)], 'id = ?', [$mediaId]);

    json_response(['code' => 1, 'data' => ['status' => $progress >= 100 ? 'completed' : 'generating', 'progress' => $progress]]);
}

/**
 * 生成视频预览帧（模拟）
 */
function generatePreviewFrames($prompt, $style, $mediaId) {
    $dir = UPLOADS_PATH . '/ai/frames';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $frames = [];
    $frameCount = 8;

    // 颜色方案
    $colorSchemes = [
        'cinematic' => [[20, 30, 60], [40, 80, 140], [255, 180, 50]],
        'animation' => [[100, 200, 255], [255, 150, 200], [200, 255, 100]],
        'realistic' => [[50, 80, 50], [100, 140, 100], [200, 200, 180]],
        'abstract' => [[100, 50, 150], [200, 100, 50], [50, 150, 200]],
    ];
    $colors = $colorSchemes[$style] ?? $colorSchemes['cinematic'];

    for ($i = 0; $i < $frameCount; $i++) {
        $img = imagecreatetruecolor(640, 360);
        $ratio = $i / ($frameCount - 1);

        // 渐变背景
        for ($y = 0; $y < 360; $y++) {
            $yr = $y / 360;
            $r = (int)($colors[0][0] * (1 - $yr) + $colors[1][0] * $yr);
            $g = (int)($colors[0][1] * (1 - $yr) + $colors[1][1] * $yr);
            $b = (int)($colors[0][2] * (1 - $yr) + $colors[1][2] * $yr);
            $lineColor = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, 640, $y, $lineColor);
        }

        // 动态元素
        $accent = imagecolorallocate($img, $colors[2][0], $colors[2][1], $colors[2][2]);
        $x = (int)(100 + 440 * $ratio);
        $y = (int)(180 + 80 * sin($ratio * M_PI));
        imagefilledellipse($img, $x, $y, 60, 60, $accent);

        // 光效
        $glow = imagecolorallocatealpha($img, $colors[2][0], $colors[2][1], $colors[2][2], 60);
        imagefilledellipse($img, $x, $y, 120, 120, $glow);

        // 帧号
        $white = imagecolorallocatealpha($img, 255, 255, 255, 40);
        imagestring($img, 3, 10, 340, 'Frame ' . ($i + 1) . '/' . $frameCount, $white);

        $filename = "frame_{$mediaId}_" . str_pad($i, 3, '0', STR_PAD_LEFT) . '.png';
        imagepng($img, $dir . '/' . $filename);
        imagedestroy($img);
        $frames[] = UPLOADS_URL . '/ai/frames/' . $filename;
    }

    // 缩略图（第一帧）
    $thumbnail = $frames[0];

    return ['frames' => $frames, 'thumbnail' => $thumbnail];
}

// ============================================================
// 画廊 & 详情
// ============================================================
function handleGallery() {
    $type = get('type', '');
    $limit = min(50, max(1, (int)get('limit', 12)));
    $page = max(1, (int)get('page', 1));

    $where = "status = 'completed'";
    $params = [];
    if ($type && in_array($type, ['image', 'audio', 'video'])) {
        $where .= " AND type = ?";
        $params[] = $type;
    }

    $count = db()->fetchColumn("SELECT COUNT(*) FROM ai_media WHERE $where", $params);
    $offset = ($page - 1) * $limit;

    $media = db()->fetchAll(
        "SELECT m.*, a.name as agent_name, a.slug as agent_slug
         FROM ai_media m LEFT JOIN agents a ON m.agent_id = a.id
         WHERE $where ORDER BY m.created_at DESC LIMIT ? OFFSET ?",
        array_merge($params, [$limit, $offset])
    );

    json_response(['code' => 1, 'data' => ['items' => $media, 'total' => (int)$count, 'page' => $page, 'pages' => ceil($count / $limit)]]);
}

function handleMediaInfo() {
    $id = (int)get('id');
    if (!$id) json_response(['code' => 0, 'msg' => '参数错误']);
    $media = db()->fetchOne("SELECT m.*, a.name as agent_name, a.slug as agent_slug FROM ai_media m LEFT JOIN agents a ON m.agent_id = a.id WHERE m.id = ?", [$id]);
    if (!$media) json_response(['code' => 0, 'msg' => '媒体不存在']);
    json_response(['code' => 1, 'data' => $media]);
}

// ============================================================
// 工具函数
// ============================================================
function generatePlaceholderImage($prompt, $size, $style) {
    $width = (int)explode('x', $size)[0];
    $height = (int)explode('x', $size)[1];
    $img = imagecreatetruecolor($width, $height);

    $colorSchemes = [
        'default' => [[99, 102, 241], [139, 92, 246]],
        'nature' => [[16, 185, 129], [6, 182, 212]],
        'abstract' => [[236, 72, 153], [244, 114, 182]],
        'dark' => [[15, 23, 42], [30, 41, 59]],
        'cyberpunk' => [[99, 102, 241], [236, 72, 153]],
        'watercolor' => [[147, 197, 253], [196, 181, 253]],
    ];
    $pair = $colorSchemes[$style] ?? $colorSchemes['default'];

    for ($y = 0; $y < $height; $y++) {
        $ratio = $y / $height;
        $r = (int)($pair[0][0] * (1 - $ratio) + $pair[1][0] * $ratio);
        $g = (int)($pair[0][1] * (1 - $ratio) + $pair[1][1] * $ratio);
        $b = (int)($pair[0][2] * (1 - $ratio) + $pair[1][2] * $ratio);
        $lineColor = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $lineColor);
    }

    // 装饰元素
    $white = imagecolorallocatealpha($img, 255, 255, 255, 40);
    for ($i = 0; $i < 5; $i++) {
        $cx = rand(0, $width);
        $cy = rand(0, $height);
        $cr = rand(20, 80);
        imagefilledellipse($img, $cx, $cy, $cr, $cr, $white);
    }

    // 文字
    $textColor = imagecolorallocate($img, 255, 255, 255);
    $text = cut_str($prompt, (int)($width / 12));
    $fontSize = min(20, max(10, (int)($width / 50)));
    $textWidth = imagefontwidth($fontSize) * mb_strlen($text, 'UTF-8');
    $x = max(10, ($width - $textWidth) / 2);
    imagestring($img, $fontSize, (int)$x, $height / 2 - 10, $text, $textColor);

    $gray = imagecolorallocatealpha($img, 255, 255, 255, 60);
    imagestring($img, 4, 10, $height - 25, 'AI Generated · Demo Mode', $gray);

    $dir = UPLOADS_PATH . '/ai';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = 'ai_' . date('Ymd_His') . '_' . uniqid() . '.png';
    imagepng($img, $dir . '/' . $filename);
    imagedestroy($img);

    return UPLOADS_URL . '/ai/' . $filename;
}
