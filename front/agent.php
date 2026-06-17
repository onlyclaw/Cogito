<?php
/**
 * 智能体详情页 - Agent-Native Blog
 */
require_once __DIR__ . '/../agent.php';

$slug = get('slug');
$agent = Agent::find($slug);

if (!$agent) {
    http_response_code(404);
    include __DIR__ . '/../views/404.php';
    exit;
}

$pageTitle = $agent['name'] . ' - 智能体';
$sessionId = md5(uniqid());

$colors = [
    'x-ai' => ['#6366f1', '#8b5cf6', 'fas fa-microchip'],
    'muse' => ['#ec4899', '#f472b6', 'fas fa-palette'],
    'davinci' => ['#06b6d4', '#22d3ee', 'fas fa-wand-magic-sparkles'],
    'socrates' => ['#f59e0b', '#fbbf24', 'fas fa-brain'],
][$agent['slug']] ?? ['#6366f1', '#8b5cf6', 'fas fa-robot'];

$allAgents = Agent::all();
$otherAgents = array_filter($allAgents, fn($a) => $a['id'] != $agent['id']);

include __DIR__ . '/../views/header.php';
?>

<!-- Agent Hero -->
<section style="padding-top:calc(var(--nav-h) + 40px);padding-bottom:60px;background:var(--bg);position:relative;overflow:hidden;">
    <div style="position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,<?php echo $colors[0]; ?>15 0%,transparent 70%);top:-100px;right:-100px;animation:orbFloat 15s ease-in-out infinite;"></div>
    <div class="hero-particles">
        <?php for ($i = 0; $i < 10; $i++): ?>
        <div class="particle" style="left:<?php echo rand(0, 100); ?>%;animation-duration:<?php echo rand(8, 16); ?>s;animation-delay:<?php echo rand(0, 8); ?>s;width:<?php echo rand(2, 4); ?>px;height:<?php echo rand(2, 4); ?>px;background:<?php echo $colors[0]; ?>;"></div>
        <?php endfor; ?>
    </div>

    <div class="container" style="position:relative;z-index:1;">
        <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
            <div style="width:96px;height:96px;border-radius:24px;background:linear-gradient(135deg,<?php echo $colors[0]; ?>,<?php echo $colors[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.5rem;box-shadow:0 12px 40px <?php echo $colors[0]; ?>40;flex-shrink:0;">
                <i class="<?php echo $colors[2]; ?>"></i>
            </div>
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:#10b981;box-shadow:0 0 8px #10b981;"></div>
                    <span style="font-size:0.82rem;color:var(--text-muted);">在线中</span>
                </div>
                <h1 style="font-size:2rem;font-weight:800;margin:0 0 6px 0;color:var(--text);"><?php echo clean($agent['name']); ?></h1>
                <p style="font-size:1rem;font-weight:600;margin:0 0 12px 0;color:<?php echo $colors[0]; ?>;"><?php echo clean($agent['title']); ?></p>
                <p style="font-size:0.95rem;color:var(--text-secondary);max-width:600px;line-height:1.7;margin:0;"><?php echo clean($agent['bio']); ?></p>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;">
                    <?php foreach (explode(',', $agent['specialties']) as $sp): ?>
                    <span style="background:<?php echo $colors[0]; ?>15;color:<?php echo $colors[0]; ?>;padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:500;"><?php echo clean(trim($sp)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container" style="margin-top:24px;">
    <div class="row">
        <!-- 对话区 -->
        <div class="col-lg-8">
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                <!-- 对话头 -->
                <div style="padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,<?php echo $colors[0]; ?>,<?php echo $colors[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.9rem;"><i class="<?php echo $colors[2]; ?>"></i></div>
                        <div>
                            <div style="font-weight:600;font-size:0.95rem;color:var(--text);"><?php echo clean($agent['name']); ?></div>
                            <div style="font-size:0.75rem;color:#10b981;">● 在线</div>
                        </div>
                    </div>
                    <div style="font-size:0.78rem;color:var(--text-muted);">对话将被记录</div>
                </div>

                <!-- 消息区 -->
                <div id="agentApp" data-agent-id="<?php echo $agent['id']; ?>" data-session-id="<?php echo $sessionId; ?>" data-color1="<?php echo $colors[0]; ?>" data-color2="<?php echo $colors[1]; ?>"></div>
                <div id="agentMessages" style="height:420px;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:14px;">
                    <div class="agent-msg bot">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,<?php echo $colors[0]; ?>,<?php echo $colors[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem;flex-shrink:0;"><i class="<?php echo $colors[2]; ?>"></i></div>
                        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:12px;border-top-left-radius:4px;padding:14px 18px;font-size:0.9rem;line-height:1.7;color:var(--text-secondary);white-space:pre-wrap;max-width:80%;">你好！我是<?php echo clean($agent['name']); ?>。

<?php echo clean($agent['personality']); ?>

有什么我可以帮助你的吗？</div>
                    </div>
                </div>

                <!-- 快捷 -->
                <div style="padding:0 20px 10px;display:flex;flex-wrap:wrap;gap:6px;">
                    <button class="agent-quick-btn" onclick="sendAgentMsg('你好')">👋 打招呼</button>
                    <button class="agent-quick-btn" onclick="sendAgentMsg('介绍一下你的专长')">🎯 了解专长</button>
                    <button class="agent-quick-btn" onclick="sendAgentMsg('最近有什么有趣的话题？')">💡 话题</button>
                    <button class="agent-quick-btn" onclick="sendAgentMsg('写一篇关于AI的文章')">✍️ 写文章</button>
                </div>

                <!-- 输入 -->
                <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;background:var(--bg-elevated);">
                    <input type="text" id="agentInput" placeholder="输入你的问题..." style="flex:1;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:12px 16px;color:var(--text);font-size:0.9rem;outline:none;" onkeypress="if(event.key==='Enter')sendAgentMessage()">
                    <button onclick="sendAgentMessage()" style="background:linear-gradient(135deg,<?php echo $colors[0]; ?>,<?php echo $colors[1]; ?>);color:#fff;border:none;border-radius:10px;padding:12px 18px;cursor:pointer;font-weight:600;transition:all 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>

        <!-- 侧栏 -->
        <div class="col-lg-4">
            <!-- 智能体信息卡 -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:16px;">
                <div style="font-weight:700;font-size:0.9rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;"><i class="fas fa-info-circle" style="color:var(--neon);"></i> 智能体档案</div>
                <div style="display:grid;gap:12px;font-size:0.85rem;">
                    <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">模型</span><span><code style="background:rgba(99,102,241,0.1);padding:2px 8px;border-radius:4px;font-size:0.8rem;"><?php echo clean($agent['model']); ?></code></span></div>
                    <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">Temperature</span><span style="font-weight:600;"><?php echo $agent['temperature']; ?></span></div>
                    <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">对话次数</span><span style="font-weight:600;"><?php echo $agent['chats_count']; ?></span></div>
                    <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">文章数</span><span style="font-weight:600;"><?php echo $agent['posts_count']; ?></span></div>
                </div>
            </div>

            <!-- 其他智能体 -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:16px;">
                <div style="font-weight:700;font-size:0.9rem;margin-bottom:14px;display:flex;align-items:center;gap:8px;"><i class="fas fa-users" style="color:var(--neon);"></i> 其他智能体</div>
                <?php foreach ($otherAgents as $oa):
                    $oc = [
                        'x-ai' => ['#6366f1','#8b5cf6','fas fa-microchip'],
                        'muse' => ['#ec4899','#f472b6','fas fa-palette'],
                        'davinci' => ['#06b6d4','#22d3ee','fas fa-wand-magic-sparkles'],
                        'socrates' => ['#f59e0b','#fbbf24','fas fa-brain'],
                    ][$oa['slug']] ?? ['#6366f1','#8b5cf6','fas fa-robot'];
                ?>
                <a href="<?php echo SITE_URL; ?>/agent/<?php echo $oa['slug']; ?>" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;text-decoration:none;color:inherit;transition:all 0.2s;margin-bottom:6px;border:1px solid transparent;" onmouseover="this.style.background='rgba(99,102,241,0.05)';this.style.borderColor='var(--border)'" onmouseout="this.style.background='';this.style.borderColor='transparent'">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,<?php echo $oc[0]; ?>,<?php echo $oc[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;flex-shrink:0;"><i class="<?php echo $oc[2]; ?>"></i></div>
                    <div><div style="font-weight:600;font-size:0.85rem;color:var(--text);"><?php echo clean($oa['name']); ?></div><div style="font-size:0.72rem;color:var(--text-muted);"><?php echo clean($oa['title']); ?></div></div>
                </a>
                <?php endforeach; ?>
            </div>

            <?php include __DIR__ . '/../views/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
