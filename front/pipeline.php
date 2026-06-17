<?php
/**
 * 闭环创作中心 - 内容×媒体×智能体
 */
require_once __DIR__ . '/../agent.php';
require_once __DIR__ . '/../pipeline.php';

$pageTitle = '闭环创作中心';
$agents = Agent::all();
$categories = get_categories();

$agentColors = [
    'x-ai' => ['#6366f1', '#8b5cf6', 'fas fa-microchip'],
    'muse' => ['#ec4899', '#f472b6', 'fas fa-palette'],
    'davinci' => ['#06b6d4', '#22d3ee', 'fas fa-wand-magic-sparkles'],
    'socrates' => ['#f59e0b', '#fbbf24', 'fas fa-brain'],
];

$catColors = [
    'programming' => ['#6366f1', '#8b5cf6'],
    'writing' => ['#ec4899', '#f472b6'],
    'creative' => ['#06b6d4', '#22d3ee'],
];

include __DIR__ . '/../views/header.php';
?>

<style>
.pipeline-container { max-width: 900px; margin: 0 auto; }

.pipeline-step {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.pipeline-step::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
}

.step-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.step-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.step-desc {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin: 0;
}

.pipeline-input {
    width: 100%;
    background: var(--bg-elevated);
    border: 2px solid var(--border);
    border-radius: 14px;
    padding: 16px 20px;
    color: var(--text);
    font-size: 1rem;
    outline: none;
    transition: border-color 0.2s;
    font-family: var(--font);
}
.pipeline-input:focus { border-color: var(--primary); }
textarea.pipeline-input { min-height: 120px; resize: vertical; line-height: 1.6; }

.agent-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.agent-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: var(--bg-elevated);
    border: 2px solid var(--border);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.25s ease;
    user-select: none;
}
.agent-chip:hover {
    border-color: var(--primary-light);
    background: rgba(99,102,241,0.05);
    transform: translateY(-1px);
}
.agent-chip.selected {
    border-color: var(--primary) !important;
    background: rgba(99,102,241,0.1) !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}

.agent-chip-avatar {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.pipeline-btn {
    padding: 14px 32px;
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}
.pipeline-btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--accent2));
    color: #fff;
    box-shadow: 0 4px 16px rgba(99,102,241,0.3);
}
.pipeline-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.pipeline-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

/* 流程可视化 */
.pipeline-flow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 32px 0;
    flex-wrap: wrap;
}
.flow-node {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 0.82rem;
    color: var(--text-muted);
    transition: all 0.3s;
}
.flow-node.active {
    border-color: var(--primary);
    background: rgba(99,102,241,0.1);
    color: var(--primary);
}
.flow-node.done {
    border-color: var(--success);
    background: rgba(16,185,129,0.1);
    color: var(--success);
}
.flow-arrow { color: var(--text-muted); font-size: 0.8rem; }

/* 结果展示 */
.result-section {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 24px;
}

.result-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.result-body { padding: 24px; }

.agent-comment-card {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: 14px;
    margin-bottom: 12px;
    transition: all 0.2s;
}
.agent-comment-card:hover { border-color: var(--primary-light); }

.media-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.media-item {
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.2s;
}
.media-item:hover { border-color: var(--primary-light); }

.media-item img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    display: block;
}

.media-item-info { padding: 12px; }
.media-item-type { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

.progress-ring {
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
}

@media (max-width: 768px) {
    .agent-selector { grid-template-columns: 1fr; }
    .media-preview { grid-template-columns: 1fr; }
    .pipeline-flow { flex-direction: column; }
}
</style>

<!-- Hero -->
<section style="min-height:45vh;display:flex;align-items:center;justify-content:center;background:var(--bg);position:relative;overflow:hidden;padding-top:var(--nav-h);">
    <div class="hero-particles">
        <?php for ($i = 0; $i < 14; $i++): ?>
        <div class="particle" style="left:<?php echo rand(0, 100); ?>%;animation-duration:<?php echo rand(8, 18); ?>s;animation-delay:<?php echo rand(0, 8); ?>s;width:<?php echo rand(2, 5); ?>px;height:<?php echo rand(2, 5); ?>px;"></div>
        <?php endfor; ?>
    </div>
    <div style="position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(99,102,241,0.1) 0%,transparent 70%);top:-200px;right:-100px;"></div>
    <div class="hero-content" style="position:relative;z-index:1;text-align:center;padding:0 24px;">
        <div class="hero-badge"><span class="pulse"></span> Content × Media × Agent</div>
        <h1 class="hero-title">闭环 <span class="gradient-text">创作中心</span></h1>
        <p class="hero-subtitle" style="max-width:600px;margin:0 auto;">输入一个主题，智能体协作写作 → 自动生成配图/音频/视频 → 多角度评论 → 智能推荐，完成内容创作闭环</p>
    </div>
</section>

<div class="container pipeline-container" style="margin-top:20px;padding-bottom:60px;">

    <!-- 流程可视化 -->
    <div class="pipeline-flow" id="pipelineFlow">
        <div class="flow-node active" id="flow-1"><i class="fas fa-lightbulb"></i> 输入主题</div>
        <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>
        <div class="flow-node" id="flow-2"><i class="fas fa-robot"></i> 智能体协作</div>
        <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>
        <div class="flow-node" id="flow-3"><i class="fas fa-pen"></i> 生成内容</div>
        <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>
        <div class="flow-node" id="flow-4"><i class="fas fa-image"></i> 生成媒体</div>
        <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>
        <div class="flow-node" id="flow-5"><i class="fas fa-comments"></i> 智能评论</div>
        <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>
        <div class="flow-node" id="flow-6"><i class="fas fa-check-circle"></i> 完成</div>
    </div>

    <!-- Step 1: 输入主题 -->
    <div class="pipeline-step" id="step-1" style="--step-color: var(--primary);">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, var(--primary), var(--accent2));"></div>
        <div class="step-header">
            <div class="step-number" style="background:linear-gradient(135deg, var(--primary), var(--accent2));">1</div>
            <div>
                <h3 class="step-title">💡 输入创作主题</h3>
                <p class="step-desc">告诉智能体你想写什么，它们会协作完成</p>
            </div>
        </div>
        <textarea id="topicInput" class="pipeline-input" placeholder="例如：AI时代人类创造力的未来、量子计算将如何改变世界、赛博朋克城市设计..."></textarea>
    </div>

    <!-- Step 2: 选择智能体 -->
    <div class="pipeline-step" id="step-2">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ec4899, #f472b6);"></div>
        <div class="step-header">
            <div class="step-number" style="background:linear-gradient(135deg, #ec4899, #f472b6);">2</div>
            <div>
                <h3 class="step-title">🤖 选择参与智能体</h3>
                <p class="step-desc">每个智能体将从自己的专业角度贡献内容</p>
            </div>
        </div>
        <div class="agent-selector">
            <?php foreach ($agents as $a):
                $c = $agentColors[$a['slug']] ?? ['#6366f1','#8b5cf6','fas fa-robot'];
                $cc = $catColors[$a['category']] ?? ['#6366f1','#8b5cf6'];
            ?>
            <div class="agent-chip selected" data-id="<?php echo $a['id']; ?>">
                <div class="agent-chip-avatar" style="background:linear-gradient(135deg,<?php echo $c[0]; ?>,<?php echo $c[1]; ?>);">
                    <i class="<?php echo $c[2]; ?>"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600;font-size:0.9rem;color:var(--text);"><?php echo clean($a['name']); ?></div>
                    <div style="font-size:0.75rem;color:<?php echo $cc[0]; ?>;"><?php echo clean($a['title']); ?></div>
                </div>
                <div class="agent-check" style="width:24px;height:24px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.7rem;flex-shrink:0;">✓</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 3: 生成选项 -->
    <div class="pipeline-step" id="step-3">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #06b6d4, #22d3ee);"></div>
        <div class="step-header">
            <div class="step-number" style="background:linear-gradient(135deg, #06b6d4, #22d3ee);">3</div>
            <div>
                <h3 class="step-title">⚙️ 生成选项</h3>
                <p class="step-desc">选择要自动生成的媒体类型</p>
            </div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:var(--bg-elevated);border:2px solid var(--primary);border-radius:12px;cursor:pointer;color:var(--text);font-size:0.9rem;">
                <input type="checkbox" checked id="opt-cover" style="accent-color:var(--primary);"> 🖼️ 封面图
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:var(--bg-elevated);border:2px solid var(--primary);border-radius:12px;cursor:pointer;color:var(--text);font-size:0.9rem;">
                <input type="checkbox" checked id="opt-audio" style="accent-color:var(--primary);"> 🎵 音频摘要
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:var(--bg-elevated);border:2px solid var(--border);border-radius:12px;cursor:pointer;color:var(--text);font-size:0.9rem;">
                <input type="checkbox" id="opt-video" style="accent-color:var(--primary);"> 🎬 视频摘要
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:var(--bg-elevated);border:2px solid var(--primary);border-radius:12px;cursor:pointer;color:var(--text);font-size:0.9rem;">
                <input type="checkbox" checked id="opt-comments" style="accent-color:var(--primary);"> 💬 智能评论
            </label>
        </div>
    </div>

    <!-- 开始按钮 -->
    <div style="text-align:center;margin-bottom:40px;">
        <button onclick="startPipeline()" class="pipeline-btn pipeline-btn-primary" id="startBtn" style="padding:18px 48px;font-size:1.1rem;">
            <i class="fas fa-rocket"></i> 启动创作闭环
        </button>
    </div>

    <!-- 结果区域 -->
    <div id="pipelineResult" style="display:none;"></div>
</div>

<?php
$extraJs = '';
include __DIR__ . '/../views/footer.php';
?>
