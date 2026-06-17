<?php
/**
 * 智能体 API 接口
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/agent.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = preg_replace('#^.*/agent-api/#', '', $uri);

header('Content-Type: application/json; charset=utf-8');

switch ($action) {
    case 'list':
        $agents = Agent::all();
        json_response(['code' => 1, 'data' => $agents]);
        break;

    case 'info':
        $id = get('id') ?: get('slug');
        $agent = Agent::find($id);
        if (!$agent) json_response(['code' => 0, 'msg' => '智能体不存在']);
        $posts = Agent::posts($agent['id']);
        json_response(['code' => 1, 'data' => ['agent' => $agent, 'posts' => $posts]]);
        break;

    case 'chat':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
        $input = json_decode(file_get_contents('php://input'), true);
        $agentId = (int)($input['agent_id'] ?? 0);
        $message = trim($input['message'] ?? '');
        $sessionId = $input['session_id'] ?? md5(uniqid());

        if (!$agentId || !$message) json_response(['code' => 0, 'msg' => '参数错误']);

        $result = Agent::reply($agentId, $message, $sessionId);
        $result['session_id'] = $sessionId;
        json_response($result);
        break;

    case 'collaborate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['code' => 0, 'msg' => '请求方式错误']);
        $input = json_decode(file_get_contents('php://input'), true);
        $agentIds = $input['agent_ids'] ?? [];
        $topic = trim($input['topic'] ?? '');

        if (empty($agentIds) || !$topic) json_response(['code' => 0, 'msg' => '请选择智能体和话题']);

        $result = Agent::collaborate($agentIds, $topic);
        json_response($result);
        break;

    case 'history':
        $agentId = get('agent_id');
        $sessionId = get('session_id');
        if (!$agentId || !$sessionId) json_response(['code' => 0, 'msg' => '参数错误']);

        $history = Agent::chatHistory($agentId, $sessionId);
        json_response(['code' => 1, 'data' => $history]);
        break;

    default:
        json_response(['code' => 0, 'msg' => '未知接口'], 404);
}
