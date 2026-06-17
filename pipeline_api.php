<?php
/**
 * 闭环系统 API
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/pipeline.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = preg_replace('#^.*/pipeline-api/#', '', $uri);

header('Content-Type: application/json; charset=utf-8');

switch ($action) {
    case 'create':
        handleCreate();
        break;
    case 'publish':
        handlePublish();
        break;
    case 'article-media':
        handleArticleMedia();
        break;
    case 'article-comments':
        handleArticleComments();
        break;
    case 'recommend':
        handleRecommend();
        break;
    case 'full-pipeline':
        handleFullPipeline();
        break;
    default:
        json_response(['code' => 0, 'msg' => '未知接口'], 404);
}

/**
 * 智能体协作创作
 */
function handleCreate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);

    $input = json_decode(file_get_contents('php://input'), true);
    $topic = trim($input['topic'] ?? '');
    $agentIds = $input['agent_ids'] ?? [];

    if (!$topic) json_response(['code' => 0, 'msg' => '请输入创作主题']);

    $result = Pipeline::agentWrite($topic, $agentIds);
    json_response($result);
}

/**
 * 发布文章并自动生成媒体
 */
function handlePublish() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
    $input = json_decode(file_get_contents('php://input'), true);
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');
    $categoryId = (int)($input['category_id'] ?? 1);
    $agentIds = $input['agent_ids'] ?? [];

    if (!$title || !$content) json_response(['code' => 0, 'msg' => '请填写标题和内容']);

    // 1. 创建文章
    $summary = cut_str(strip_tags($content), 200);
    $postId = db()->insert('posts', [
        'title' => $title, 'content' => $content, 'summary' => $summary,
        'category_id' => $categoryId, 'user_id' => is_login() ? (int)$_SESSION['user_id'] : 1,
        'status' => 'published',
    ]);

    // 2. 自动生成媒体
    $media = Pipeline::autoGenerateMedia($postId, $title, $content);

    // 3. 智能体评论
    $comments = Pipeline::generateAgentComments($postId, $title);

    // 4. 记录协作智能体
    foreach ($agentIds as $agentId) {
        db()->insert('agent_article_collab', [
            'post_id' => $postId, 'agent_id' => (int)$agentId,
            'role' => 'writer', 'contribution' => '参与创作',
        ]);
    }

    json_response(['code' => 1, 'msg' => '文章发布成功', 'data' => [
        'post_id' => $postId, 'media' => $media, 'agent_comments' => $comments,
    ]]);
}

/**
 * 获取文章配套媒体
 */
function handleArticleMedia() {
    $postId = (int)get('post_id');
    if (!$postId) json_response(['code' => 0, 'msg' => '参数错误']);
    $media = Pipeline::getArticleMedia($postId);
    json_response(['code' => 1, 'data' => $media]);
}

/**
 * 获取文章智能体评论
 */
function handleArticleComments() {
    $postId = (int)get('post_id');
    if (!$postId) json_response(['code' => 0, 'msg' => '参数错误']);
    $comments = Pipeline::getArticleAgentComments($postId);
    json_response(['code' => 1, 'data' => $comments]);
}

/**
 * 内容推荐
 */
function handleRecommend() {
    $postId = (int)get('post_id');
    $limit = (int)get('limit', 3);
    if (!$postId) json_response(['code' => 0, 'msg' => '参数错误']);
    $recs = Pipeline::recommendContent($postId, $limit);
    json_response(['code' => 1, 'data' => $recs]);
}

/**
 * 完整闭环流程
 */
function handleFullPipeline() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
    if (!is_member_login()) json_response(['code' => 0, 'msg' => '请先登录', 'need_login' => true]);

    $input = json_decode(file_get_contents('php://input'), true);
    $topic = trim($input['topic'] ?? '');
    $agentIds = $input['agent_ids'] ?? [];

    if (!$topic) json_response(['code' => 0, 'msg' => '请输入主题']);

    // 1. 智能体协作创作
    $pipeline = Pipeline::agentWrite($topic, $agentIds);
    if ($pipeline['code'] !== 1) json_response($pipeline);

    // 2. 保存为文章
    $content = $pipeline['data']['content'] ?? $topic;
    $summary = cut_str(strip_tags($content), 200);
    $postId = db()->insert('posts', [
        'title' => $topic, 'content' => $content, 'summary' => $summary,
        'category_id' => 1, 'user_id' => is_login() ? (int)$_SESSION['user_id'] : 1,
        'status' => 'published',
    ]);

    // 3. 生成媒体
    $media = Pipeline::autoGenerateMedia($postId, $topic, $content);

    // 4. 获取智能体评论
    $comments = Pipeline::getArticleAgentComments($postId);
    if (empty($comments)) {
        $comments = Pipeline::generateAgentComments($postId, $topic);
    }

    // 5. 推荐
    $recommendations = Pipeline::recommendContent($postId, 3);

    json_response(['code' => 1, 'data' => [
        'post_id' => $postId,
        'title' => $topic,
        'content' => $content,
        'agents' => $pipeline['data']['agents'],
        'media' => $media,
        'comments' => $comments,
        'recommendations' => $recommendations,
    ]]);
}
