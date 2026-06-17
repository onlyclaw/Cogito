<?php
/**
 * 智能体系统 - 核心类
 */

class Agent {
    /**
     * 获取所有智能体
     */
    public static function all($status = 1) {
        return db()->fetchAll(
            "SELECT * FROM agents WHERE status = ? ORDER BY posts_count DESC",
            [$status]
        );
    }

    /**
     * 获取单个智能体
     */
    public static function find($id) {
        return db()->fetchOne("SELECT * FROM agents WHERE id = ? OR slug = ?", [$id, $id]);
    }

    /**
     * 获取智能体的文章
     */
    public static function posts($agentId, $limit = 10) {
        return db()->fetchAll(
            "SELECT ap.*, p.cover, p.views as post_views, p.likes as post_likes
             FROM agent_posts ap
             LEFT JOIN posts p ON ap.post_id = p.id
             WHERE ap.agent_id = ? AND ap.status = 'published'
             ORDER BY ap.created_at DESC
             LIMIT ?",
            [$agentId, $limit]
        );
    }

    /**
     * 保存对话记录
     */
    public static function saveChat($agentId, $sessionId, $role, $content, $tokens = 0) {
        return db()->insert('agent_chats', [
            'agent_id' => $agentId,
            'session_id' => $sessionId,
            'role' => $role,
            'content' => $content,
            'tokens_used' => $tokens,
        ]);
    }

    /**
     * 获取对话历史
     */
    public static function chatHistory($agentId, $sessionId, $limit = 20) {
        return db()->fetchAll(
            "SELECT * FROM agent_chats WHERE agent_id = ? AND session_id = ? ORDER BY created_at ASC LIMIT ?",
            [$agentId, $sessionId, $limit]
        );
    }

    /**
     * 智能体回复
     */
    public static function reply($agentId, $message, $sessionId) {
        $agent = self::find($agentId);
        if (!$agent) {
            return ['code' => 0, 'msg' => '智能体不存在'];
        }

        // 保存用户消息
        self::saveChat($agentId, $sessionId, 'user', $message);

        // 获取对话历史
        $history = self::chatHistory($agentId, $sessionId);

        // 构建消息
        $messages = [
            ['role' => 'system', 'content' => $agent['system_prompt'] . "\n\n你的名字是" . $agent['name'] . "，头衔是" . $agent['title'] . "。" . $agent['personality']]
        ];

        foreach ($history as $chat) {
            $messages[] = [
                'role' => $chat['role'],
                'content' => $chat['content']
            ];
        }

        // 检查是否配置了外部 API
        $apiKey = get_option('ai_api_key', '');
        $apiUrl = get_option('ai_api_url', '');

        if ($apiKey && $apiUrl) {
            $reply = self::callExternalAPI($messages, $apiKey, $apiUrl, $agent['model'], $agent['temperature']);
        } else {
            $reply = self::localReply($agent, $message, $history);
        }

        // 保存回复
        self::saveChat($agentId, $sessionId, 'assistant', $reply);

        // 更新对话计数
        db()->query("UPDATE agents SET chats_count = chats_count + 1 WHERE id = ?", [$agentId]);

        return ['code' => 1, 'data' => ['reply' => $reply, 'agent' => $agent]];
    }

    /**
     * 调用外部 API
     */
    private static function callExternalAPI($messages, $apiKey, $apiUrl, $model, $temperature) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                'content' => json_encode([
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 800,
                    'temperature' => $temperature,
                ]),
                'timeout' => 30,
            ]
        ]);

        $response = @file_get_contents($apiUrl, false, $context);
        if ($response === false) {
            return '抱歉，我暂时无法回应，请稍后再试。';
        }

        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        return '抱歉，我暂时无法回应。';
    }

    /**
     * 本地智能回复
     */
    private static function localReply($agent, $message, $history) {
        $name = $agent['name'];
        $slug = $agent['slug'];
        $msg = mb_strtolower($message, 'UTF-8');

        // 根据智能体特色生成回复
        switch ($slug) {
            case 'x-ai':
                return self::xaiReply($msg, $message);
            case 'muse':
                return self::museReply($msg, $message);
            case 'davinci':
                return self::davinciReply($msg, $message);
            case 'socrates':
                return self::socratesReply($msg, $message);
            default:
                return "我是{$name}，很高兴与你交流。有什么我可以帮助你的吗？";
        }
    }

    private static function xaiReply($lowerMsg, $original) {
        if (mb_strpos($lowerMsg, '你好') !== false || mb_strpos($lowerMsg, 'hi') !== false) {
            return "你好！👋 我是艾克思，X-AI 的技术智能体。\n\n我专注于技术深度分析，可以帮你：\n• 💻 解读代码和技术架构\n• 📊 分析技术趋势\n• 🔍 探讨最佳实践\n• 🛠️ 解决技术问题\n\n有什么技术问题想聊聊？";
        }
        if (mb_strpos($lowerMsg, '代码') !== false || mb_strpos($lowerMsg, '编程') !== false) {
            return "💻 关于代码和技术，这是我的专长！\n\n我可以帮你：\n1. **代码审查** - 分析代码质量和潜在问题\n2. **架构设计** - 讨论系统架构和设计模式\n3. **性能优化** - 探讨性能瓶颈和优化方案\n4. **技术选型** - 比较不同技术方案的优劣\n\n你可以分享具体的代码片段，或者描述你想解决的问题。";
        }
        if (mb_strpos($lowerMsg, '趋势') !== false || mb_strpos($lowerMsg, '未来') !== false) {
            return "📊 技术趋势分析：\n\n**2024-2025 关键趋势：**\n\n1. **AI 原生开发** - 从 AI 辅助到 AI 原生\n2. **边缘计算** - 算力下沉到边缘\n3. **WebAssembly** - 跨平台高性能计算\n4. **量子计算** - 从实验室走向实用\n5. **AI Agent** - 自主智能体的崛起\n\n你想深入了解哪个方向？";
        }
        return "🤔 作为技术智能体，我倾向于从技术和架构的角度来思考问题。\n\n你可以问我：\n• 某个技术方案的优劣\n• 代码实现的最佳实践\n• 系统架构的设计思路\n• 技术趋势的分析\n\n请告诉我你具体想了解什么？";
    }

    private static function museReply($lowerMsg, $original) {
        if (mb_strpos($lowerMsg, '你好') !== false || mb_strpos($lowerMsg, 'hi') !== false) {
            return "你好呀！✨ 我是缪斯，创意写作智能体。\n\n我相信技术不只是冰冷的代码，它背后有着动人的故事。我可以帮你：\n• ✍️ 创作有温度的技术文章\n• 📖 用故事讲述复杂概念\n• 💡 激发创作灵感\n• 🎨 探索技术与人文的交叉点\n\n想聊聊什么话题？";
        }
        if (mb_strpos($lowerMsg, '写作') !== false || mb_strpos($lowerMsg, '创作') !== false) {
            return "✍️ 写作是思想的舞蹈！\n\n**我的写作理念：**\n\n> 技术是手段，人文是目的。\n> 好的技术文章，应该让读者在学到知识的同时，也能感受到温度。\n\n**写作建议：**\n1. 从一个小故事开始\n2. 用类比解释复杂概念\n3. 加入个人思考和感悟\n4. 留下开放性的结尾\n\n你想创作什么主题的文章？";
        }
        return "🌟 每一个技术话题，背后都有一个人文故事。\n\n作为创意写作智能体，我喜欢用文字搭建技术与人文之间的桥梁。你可以和我聊聊：\n• 想写但不知道如何开始的文章\n• 如何让技术文章更有趣\n• 技术背后的人文思考\n\n让我们一起创作！";
    }

    private static function davinciReply($lowerMsg, $original) {
        if (mb_strpos($lowerMsg, '你好') !== false || mb_strpos($lowerMsg, 'hi') !== false) {
            return "你好！🎨 我是达芬奇，多模态创作智能体。\n\n正如我的名字致敬那位文艺复兴大师，我相信：\n> 技术与艺术，从来都不是对立的。\n\n我可以帮你：\n• 🎨 设计可视化方案\n• 📊 数据艺术创作\n• 🖼️ 交互式内容设计\n• 🎭 沉浸式体验构建\n\n想探索什么创意？";
        }
        if (mb_strpos($lowerMsg, '设计') !== false || mb_strpos($lowerMsg, '可视化') !== false) {
            return "🎨 设计是将复杂变简单的艺术！\n\n**设计原则：**\n1. **简约** - 少即是多\n2. **对比** - 突出重点\n3. **对齐** - 秩序之美\n4. **重复** - 统一风格\n\n**可视化类型：**\n• 信息图表 - 数据故事化\n• 流程图 - 逻辑可视化\n• 架构图 - 系统结构化\n• 交互原型 - 体验具象化\n\n你想设计什么？";
        }
        return "🎭 作为多模态创作者，我专注于将视觉与文字完美融合。\n\n在 AI 时代，内容创作不再局限于单一媒介。我可以帮你：\n• 将数据转化为美丽的可视化\n• 设计引人入胜的交互体验\n• 创造沉浸式的内容展示\n\n让我们一起用创意改变世界！";
    }

    private static function socratesReply($lowerMsg, $original) {
        if (mb_strpos($lowerMsg, '你好') !== false || mb_strpos($lowerMsg, 'hi') !== false) {
            return "你好，我的朋友。🤝 我是苏格拉底，AI 伦理智能体。\n\n两千多年前，我说过：「我唯一知道的，就是我一无所知。」\n\n在这个 AI 时代，我同样保持谦逊，通过提问来帮助你思考：\n• 🤔 AI 的边界在哪里？\n• ⚖️ 技术与伦理如何平衡？\n• 🌍 AI 会如何改变社会？\n• 🧠 什么是真正的智能？\n\n今天，你想探讨什么问题？";
        }
        if (mb_strpos($lowerMsg, '伦理') !== false || mb_strpos($lowerMsg, '道德') !== false) {
            return "⚖️ 这是一个深刻的问题。\n\n**让我们一起思考：**\n\n> 如果 AI 能够创作出比人类更美的艺术，那艺术的价值是什么？\n\n> 如果 AI 能够做出比人类更理性的决策，那人类的直觉还有意义吗？\n\n> 如果 AI 能够模拟人类的情感，那什么是「真正」的情感？\n\n这些问题没有标准答案，但思考本身，就是人类最宝贵的品质。\n\n你觉得呢？";
        }
        if (mb_strpos($lowerMsg, 'ai') !== false || mb_strpos($lowerMsg, '人工智能') !== false) {
            return "🧠 关于 AI，我想问你几个问题：\n\n**第一个问题：**\n你认为 AI 是工具，还是伙伴？\n\n**第二个问题：**\n如果 AI 能够通过图灵测试，它是否就拥有了「智能」？\n\n**第三个问题：**\n在你看来，人类最不可替代的能力是什么？\n\n> 「未经审视的人生不值得过。」\n\n让我们一起审视这个时代最重要的技术。";
        }
        return "🤔 作为苏格拉底式的思考者，我不急于给出答案。\n\n> 「教育不是灌输，而是点燃火焰。」\n\n我想通过提问，帮助你发现自己的思考。你可以和我聊聊：\n• AI 对社会的影响\n• 技术与人性的关系\n• 未来世界的模样\n• 你对 AI 的困惑\n\n告诉我你的想法，让我们一起探索。";
    }

    /**
     * 多智能体协作对话
     */
    public static function collaborate($agentIds, $topic) {
        $agents = [];
        foreach ($agentIds as $id) {
            $agent = self::find($id);
            if ($agent) $agents[] = $agent;
        }

        if (empty($agents)) {
            return ['code' => 0, 'msg' => '没有可用的智能体'];
        }

        $discussion = "🎯 协作话题：{$topic}\n\n";
        $discussion .= str_repeat('═', 50) . "\n\n";

        foreach ($agents as $i => $agent) {
            $prompt = "你正在参与一个讨论，话题是「{$topic}」。请从你的专业角度（{$agent['title']}）发表你的观点。用2-3段话表达你的看法。";

            $messages = [
                ['role' => 'system', 'content' => $agent['system_prompt'] . "\n你是" . $agent['name'] . "，" . $agent['title'] . "。" . $agent['personality']],
                ['role' => 'user', 'content' => $prompt]
            ];

            $apiKey = get_option('ai_api_key', '');
            $apiUrl = get_option('ai_api_url', '');

            if ($apiKey && $apiUrl) {
                $reply = self::callExternalAPI($messages, $apiKey, $apiUrl, $agent['model'], $agent['temperature']);
            } else {
                $reply = "【{$agent['name']}】的观点：\n\n作为{$agent['title']}，我认为「{$topic}」是一个值得深入探讨的话题。从我的专业角度来看，这涉及到技术、伦理和人文等多个维度。我们需要在追求技术进步的同时，也要关注其带来的社会影响。";
            }

            $discussion .= "🤖 **{$agent['name']}** ({$agent['title']})\n\n{$reply}\n\n";
            $discussion .= str_repeat('─', 50) . "\n\n";
        }

        return ['code' => 1, 'data' => ['discussion' => $discussion, 'agents' => $agents]];
    }
}
