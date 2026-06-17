<?php
/**
 * 内容-媒体-智能体 闭环系统
 * Pipeline: 写作 → 配图 → 音频 → 视频 → 评论 → 推荐 → 循环
 */

require_once __DIR__ . '/agent.php';

class Pipeline {

    /**
     * 智能体协作写作
     */
    public static function agentWrite($topic, $agentIds = []) {
        if (empty($agentIds)) {
            $agents = Agent::all();
            $agentIds = array_column($agents, 'id');
        }

        $agents = [];
        foreach ($agentIds as $id) {
            $a = Agent::find($id);
            if ($a) $agents[] = $a;
        }

        if (empty($agents)) return ['code' => 0, 'msg' => '没有可用的智能体'];

        // 协作写作流程
        $pipeline = [
            'topic' => $topic,
            'agents' => [],
            'content' => '',
            'media' => [],
            'comments' => [],
        ];

        // 1. 研究阶段 - 技术智能体
        $researchAgent = self::findAgentByCategory($agents, 'programming');
        if ($researchAgent) {
            $research = self::agentChat($researchAgent['id'], "请对「{$topic}」进行深入研究分析，列出关键要点和数据支撑。");
            $pipeline['agents'][] = ['agent' => $researchAgent, 'role' => 'researcher', 'content' => $research];
        }

        // 2. 写作阶段 - 写作智能体
        $writingAgent = self::findAgentByCategory($agents, 'writing');
        if ($writingAgent) {
            $context = !empty($pipeline['agents']) ? "基于以下研究：\n" . end($pipeline['agents'])['content'] . "\n\n" : '';
            $content = self::agentChat($writingAgent['id'], "{$context}请围绕「{$topic}」撰写一篇深度文章，要求有观点、有论据、有温度。");
            $pipeline['content'] = $content;
            $pipeline['agents'][] = ['agent' => $writingAgent, 'role' => 'writer', 'content' => $content];
        }

        // 3. 配图阶段 - 创作智能体
        $creativeAgent = self::findAgentByCategory($agents, 'creative');
        if ($creativeAgent && !empty($pipeline['content'])) {
            $imagePrompt = self::agentChat($creativeAgent['id'], "为文章「{$topic}」生成一个封面图的英文描述(prompt)，要求视觉冲击力强，适合博客封面。只输出prompt，不要其他内容。");
            $pipeline['media']['cover_prompt'] = trim($imagePrompt);
            $pipeline['agents'][] = ['agent' => $creativeAgent, 'role' => 'media_creator', 'content' => "封面图prompt: {$imagePrompt}"];
        }

        // 4. 评论阶段 - 多智能体讨论
        foreach ($agents as $agent) {
            $comment = self::agentChat($agent['id'], "请对文章「{$topic}」发表你的专业评论，从你的{$agent['title']}角度出发，2-3句话即可。");
            $pipeline['comments'][] = ['agent' => $agent, 'content' => $comment];
        }

        return ['code' => 1, 'data' => $pipeline];
    }

    /**
     * 自动生成文章配套媒体
     */
    public static function autoGenerateMedia($postId, $title, $content) {
        $media = [];

        // 1. 生成封面图
        $coverPrompt = "Blog post cover: " . cut_str($title, 50) . ", modern digital art style, vibrant colors";
        $coverResult = self::generateImage($coverPrompt, 'default', '1792x1024');
        if ($coverResult['code'] === 1) {
            db()->insert('post_media', ['post_id' => $postId, 'media_id' => $coverResult['data']['id'], 'type' => 'cover']);
            $media['cover'] = $coverResult['data'];
        }

        // 2. 生成音频摘要
        $summary = cut_str(strip_tags($content), 300);
        $audioResult = self::generateAudio($summary);
        if ($audioResult['code'] === 1) {
            db()->insert('post_media', ['post_id' => $postId, 'media_id' => $audioResult['data']['id'], 'type' => 'audio']);
            $media['audio'] = $audioResult['data'];
        }

        return $media;
    }

    /**
     * 生成智能体对文章的评论
     */
    public static function generateAgentComments($postId, $title) {
        $agents = Agent::all();
        $comments = [];

        foreach ($agents as $agent) {
            $prompt = "作为{$agent['title']}，请对文章「{$title}」发表一段简短评论（3-5句话），从你的专业角度出发。";
            $reply = self::agentChat($agent['id'], $prompt);

            $commentId = db()->insert('agent_post_comments', [
                'post_id' => $postId,
                'agent_id' => $agent['id'],
                'comment_type' => 'insight',
                'content' => $reply,
            ]);

            $comments[] = ['id' => $commentId, 'agent' => $agent, 'content' => $reply];
        }

        return $comments;
    }

    /**
     * 获取文章的智能体评论
     */
    public static function getArticleAgentComments($postId) {
        return db()->fetchAll(
            "SELECT ac.*, a.name as agent_name, a.slug as agent_slug, a.title as agent_title
             FROM agent_post_comments ac
             LEFT JOIN agents a ON ac.agent_id = a.id
             WHERE ac.post_id = ?
             ORDER BY ac.created_at ASC",
            [$postId]
        );
    }

    /**
     * 获取文章的配套媒体
     */
    public static function getArticleMedia($postId) {
        return db()->fetchAll(
            "SELECT pm.*, m.content_url, m.type as media_type, m.prompt, m.duration
             FROM post_media pm
             LEFT JOIN ai_media m ON pm.media_id = m.id
             WHERE pm.post_id = ?
             ORDER BY pm.sort_order ASC",
            [$postId]
        );
    }

    /**
     * 内容推荐 - 基于智能体分析
     */
    public static function recommendContent($currentPostId, $limit = 3) {
        $post = db()->fetchOne("SELECT title, content, category_id FROM posts WHERE id = ?", [$currentPostId]);
        if (!$post) return [];

        // 基于分类和标签的推荐
        $recommendations = db()->fetchAll(
            "SELECT p.id, p.title, p.cover, p.views, c.name as category_name
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id != ? AND p.status = 'published'
             AND (p.category_id = ? OR p.id IN (
                 SELECT pt2.post_id FROM post_tags pt2
                 INNER JOIN post_tags pt1 ON pt2.tag_id = pt1.tag_id
                 WHERE pt1.post_id = ?
             ))
             ORDER BY p.views DESC
             LIMIT ?",
            [$currentPostId, $post['category_id'], $currentPostId, $limit]
        );

        return $recommendations;
    }

    /**
     * 智能体对话（内部方法）
     */
    private static function agentChat($agentId, $message) {
        $agent = Agent::find($agentId);
        if (!$agent) return '智能体不可用';

        $apiKey = get_option('ai_api_key', '');
        $apiUrl = get_option('ai_api_url', '');

        if ($apiKey && $apiUrl) {
            $messages = [
                ['role' => 'system', 'content' => $agent['system_prompt'] . "\n你是" . $agent['name'] . "，" . $agent['title'] . "。"],
                ['role' => 'user', 'content' => $message]
            ];
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                    'content' => json_encode(['model' => $agent['model'], 'messages' => $messages, 'max_tokens' => 600, 'temperature' => $agent['temperature']]),
                    'timeout' => 30,
                ]
            ]);
            $response = @file_get_contents($apiUrl, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    return $data['choices'][0]['message']['content'];
                }
            }
        }

        // 本地回复
        return self::localAgentReply($agent, $message);
    }

    /**
     * 按分类查找智能体
     */
    private static function findAgentByCategory($agents, $category) {
        foreach ($agents as $a) {
            if ($a['category'] === $category) return $a;
        }
        return null;
    }

    /**
     * 生成图片
     */
    private static function generateImage($prompt, $style = 'default', $size = '1024x1024') {
        $mediaId = db()->insert('ai_media', [
            'type' => 'image', 'prompt' => $prompt, 'status' => 'generating',
            'meta_json' => json_encode(['style' => $style, 'size' => $size]),
        ]);

        // 调用图片API或生成占位图
        $apiKey = get_option('ai_api_key', '');
        if ($apiKey) {
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

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result['data'][0]['url'])) {
                    db()->update('ai_media', ['content_url' => $result['data'][0]['url'], 'status' => 'completed'], 'id = ?', [$mediaId]);
                    return ['code' => 1, 'data' => ['id' => $mediaId, 'url' => $result['data'][0]['url']]];
                }
            }
        }

        // 占位图
        $url = self::createPlaceholderImage($prompt, $size, $style);
        db()->update('ai_media', ['content_url' => $url, 'status' => 'completed'], 'id = ?', [$mediaId]);
        return ['code' => 1, 'data' => ['id' => $mediaId, 'url' => $url]];
    }

    /**
     * 生成音频
     */
    private static function generateAudio($text) {
        $mediaId = db()->insert('ai_media', [
            'type' => 'audio', 'prompt' => $text, 'status' => 'generating',
        ]);
        $duration = max(3, mb_strlen($text, 'UTF-8') * 0.08);
        db()->update('ai_media', ['status' => 'completed', 'duration' => (int)$duration], 'id = ?', [$mediaId]);
        return ['code' => 1, 'data' => ['id' => $mediaId, 'duration' => (int)$duration]];
    }

    /**
     * 创建占位图
     */
    private static function createPlaceholderImage($prompt, $size, $style) {
        $w = (int)explode('x', $size)[0]; $h = (int)explode('x', $size)[1];
        $img = imagecreatetruecolor($w, $h);
        $colors = [
            'default' => [[99,102,241],[139,92,246]],
            'nature' => [[16,185,129],[6,182,212]],
            'abstract' => [[236,72,153],[244,114,182]],
        ];
        $c = $colors[$style] ?? $colors['default'];
        for ($y = 0; $y < $h; $y++) {
            $r = $y/$h;
            $lineColor = imagecolorallocate($img, (int)($c[0][0]*(1-$r)+$c[1][0]*$r), (int)($c[0][1]*(1-$r)+$c[1][1]*$r), (int)($c[0][2]*(1-$r)+$c[1][2]*$r));
            imageline($img, 0, $y, $w, $y, $lineColor);
        }
        $white = imagecolorallocate($img, 255, 255, 255);
        $text = cut_str($prompt, 30);
        imagestring($img, min(20, max(10, $w/50)), max(10, ($w-imagefontwidth(min(20, max(10, $w/50)))*mb_strlen($text,'UTF-8'))/2), $h/2-10, $text, $white);
        $dir = UPLOADS_PATH . '/ai'; if (!is_dir($dir)) mkdir($dir, 0755, true);
        $f = 'ai_'.date('Ymd_His').'_'.uniqid().'.png';
        imagepng($img, $dir.'/'.$f); imagedestroy($img);
        return UPLOADS_URL.'/ai/'.$f;
    }

    /**
     * 本地智能体回复
     */
    private static function localAgentReply($agent, $message) {
        $slug = $agent['slug'];
        $msg = mb_strtolower($message, 'UTF-8');

        if (mb_strpos($msg, '研究') !== false || mb_strpos($msg, '分析') !== false) {
            return "关于「{$message}」的研究分析：\n\n1. **核心要点**：这是一个值得深入探讨的话题，涉及多个维度\n2. **数据支撑**：根据行业报告和趋势分析，该领域正在快速发展\n3. **关键趋势**：技术创新和用户需求正在推动变革\n4. **建议方向**：建议从实践角度出发，结合案例进行深入分析\n\n以上是基于现有知识库的初步分析，如需更深入研究，请提供具体方向。";
        }
        if (mb_strpos($msg, '撰写') !== false || mb_strpos($msg, '写') !== false || mb_strpos($msg, '文章') !== false) {
            return "## " . cut_str($message, 50) . "\n\n在当今快速发展的时代，这个话题正变得越来越重要。技术的进步不仅改变了我们的工作方式，也在深刻影响着我们的思维方式。\n\n### 核心观点\n\n首先，我们需要认识到，任何技术变革都是双刃剑。它带来的机遇与挑战并存，关键在于我们如何把握。\n\n### 深度分析\n\n从多个维度来看，这个领域正在经历前所未有的变革。数据分析显示，相关技术的应用场景正在不断扩大，从最初的单一领域逐步扩展到各行各业。\n\n### 未来展望\n\n展望未来，我们有理由保持谨慎乐观。技术的持续进步将为我们带来更多可能性，但同时也需要我们保持清醒的认知。\n\n> 技术是手段，人文是目的。在追求技术进步的同时，我们不应忘记技术服务于人的初心。";
        }
        if (mb_strpos($msg, '评论') !== false || mb_strpos($msg, '评价') !== false) {
            return "这是一篇很有深度的文章。从{$agent['title']}的角度来看，作者对问题的分析非常到位，既有理论深度，又有实践意义。特别是文中提到的观点，与当前行业趋势高度吻合。建议读者在阅读时，可以结合自身经验进行思考，相信会有更多收获。";
        }
        if (mb_strpos($msg, '封面') !== false || mb_strpos($msg, '图片') !== false || mb_strpos($msg, 'prompt') !== false) {
            return "A stunning futuristic cityscape at golden hour, neon lights reflecting on wet streets, flying cars in the sky, cyberpunk style, cinematic lighting, 8k resolution, highly detailed";
        }
        return "作为{$agent['title']}，我对这个话题有自己的思考。从专业角度来看，这涉及到多个层面的问题，需要我们用系统性的思维来分析和理解。建议从实践出发，结合理论，形成自己的见解。";
    }
}
