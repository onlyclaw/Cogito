<?php
/**
 * AI 媒体工坊 - 图片/音频/视频生成
 */
require_once __DIR__ . '/../agent.php';
$pageTitle = 'AI 媒体工坊';

$agents = Agent::all();
$categories = get_categories();
$tags = get_tags();
$hotPosts = get_hot_posts(5);

$agentColors = [
    'x-ai' => ['#6366f1', '#8b5cf6', 'fas fa-microchip'],
    'muse' => ['#ec4899', '#f472b6', 'fas fa-palette'],
    'davinci' => ['#06b6d4', '#22d3ee', 'fas fa-wand-magic-sparkles'],
    'socrates' => ['#f59e0b', '#fbbf24', 'fas fa-brain'],
];

include __DIR__ . '/../views/header.php';
?>

<style>
.media-tabs {
    display: flex;
    gap: 4px;
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 4px;
    margin-bottom: 28px;
}
.media-tab {
    flex: 1;
    padding: 14px 20px;
    border: none;
    background: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.media-tab:hover { color: var(--text); background: rgba(99,102,241,0.05); }
.media-tab.active { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }

.media-panel { display: none; }
.media-panel.active { display: block; }

.gen-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    overflow: hidden;
}

.gen-card-body { padding: 32px; }

.gen-input-group { margin-bottom: 20px; }

.gen-label {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text);
    margin-bottom: 8px;
}

.gen-textarea {
    width: 100%;
    min-height: 120px;
    background: var(--bg-elevated);
    border: 2px solid var(--border);
    border-radius: 14px;
    padding: 16px 20px;
    color: var(--text);
    font-size: 0.95rem;
    line-height: 1.6;
    resize: vertical;
    outline: none;
    transition: border-color 0.2s;
    font-family: var(--font);
}
.gen-textarea:focus { border-color: var(--primary); }
.gen-textarea::placeholder { color: var(--text-muted); }

.gen-input {
    width: 100%;
    background: var(--bg-elevated);
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 14px 18px;
    color: var(--text);
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s;
}
.gen-input:focus { border-color: var(--primary); }

.gen-select-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.gen-select-item {
    padding: 8px 16px;
    border: 2px solid var(--border);
    border-radius: 10px;
    background: var(--bg-elevated);
    color: var(--text-secondary);
    font-size: 0.88rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}
.gen-select-item:hover { border-color: var(--primary-light); }
.gen-select-item.active { border-color: var(--primary); background: rgba(99,102,241,0.1); color: var(--primary); }

.gen-btn {
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.gen-btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--accent2));
    color: #fff;
    box-shadow: 0 4px 16px rgba(99,102,241,0.3);
}
.gen-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.gen-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.gen-result {
    margin-top: 24px;
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
}

.gen-result img, .gen-result video {
    max-width: 100%;
    border-radius: 12px;
    margin-bottom: 16px;
}

.gen-result-empty {
    padding: 48px 20px;
    color: var(--text-muted);
}
.gen-result-empty i { font-size: 48px; margin-bottom: 12px; opacity: 0.3; display: block; }

/* 画廊 */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    margin-top: 24px;
}

.gallery-item {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
}
.gallery-item:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-light);
}

.gallery-item-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
}

.gallery-item-info { padding: 16px; }
.gallery-item-title { font-weight: 600; font-size: 0.9rem; color: var(--text); margin-bottom: 6px; }
.gallery-item-meta { font-size: 0.78rem; color: var(--text-muted); display: flex; justify-content: space-between; }

/* 粒子 */
.particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: var(--primary-light);
    border-radius: 50%;
    opacity: 0.3;
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    10% { opacity: 0.3; }
    90% { opacity: 0.3; }
    100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
}

@media (max-width: 768px) {
    .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
}
</style>

<!-- Hero -->
<section style="min-height:40vh;display:flex;align-items:center;justify-content:center;background:var(--bg);position:relative;overflow:hidden;padding-top:var(--nav-h);">
    <div class="hero-particles">
        <?php for ($i = 0; $i < 12; $i++): ?>
        <div class="particle" style="left:<?php echo rand(0, 100); ?>%;animation-duration:<?php echo rand(8, 18); ?>s;animation-delay:<?php echo rand(0, 8); ?>s;width:<?php echo rand(2, 5); ?>px;height:<?php echo rand(2, 5); ?>px;"></div>
        <?php endfor; ?>
    </div>
    <div style="position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(99,102,241,0.1) 0%,transparent 70%);top:-150px;right:-100px;"></div>
    <div class="hero-content" style="position:relative;z-index:1;text-align:center;padding:0 24px;">
        <div class="hero-badge"><span class="pulse"></span> AI Media Studio</div>
        <h1 class="hero-title">AI <span class="gradient-text">媒体工坊</span></h1>
        <p class="hero-subtitle" style="max-width:520px;margin:0 auto;">输入文字，AI 为你生成图片、音频、视频 —— 让想象力变成现实</p>
    </div>
</section>

<div class="container" style="margin-top:20px;padding-bottom:60px;">
    <!-- 媒体类型标签 -->
    <div class="media-tabs">
        <button class="media-tab active" onclick="switchTab('image')">
            <i class="fas fa-image"></i> 图片生成
        </button>
        <button class="media-tab" onclick="switchTab('audio')">
            <i class="fas fa-headphones"></i> 音频生成
        </button>
        <button class="media-tab" onclick="switchTab('video')">
            <i class="fas fa-video"></i> 视频生成
        </button>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- 图片生成 -->
            <div class="media-panel active" id="panel-image">
                <div class="gen-card">
                    <div class="gen-card-body">
                        <div class="gen-input-group">
                            <label class="gen-label">📝 图片描述</label>
                            <textarea id="imgPrompt" class="gen-textarea" placeholder="描述你想生成的图片，例如：一只在星空下飞翔的机械蝴蝶，赛博朋克风格，霓虹灯光效果"></textarea>
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">🎨 风格</label>
                            <div class="gen-select-group" id="imgStyleGroup">
                                <div class="gen-select-item active" data-value="default" onclick="selectStyle(this,'imgStyle')">✨ 默认</div>
                                <div class="gen-select-item" data-value="nature" onclick="selectStyle(this,'imgStyle')">🌿 自然</div>
                                <div class="gen-select-item" data-value="abstract" onclick="selectStyle(this,'imgStyle')">🎨 抽象</div>
                                <div class="gen-select-item" data-value="dark" onclick="selectStyle(this,'imgStyle')">🌙 暗黑</div>
                                <div class="gen-select-item" data-value="cyberpunk" onclick="selectStyle(this,'imgStyle')">🤖 赛博朋克</div>
                                <div class="gen-select-item" data-value="watercolor" onclick="selectStyle(this,'imgStyle')">🎨 水彩</div>
                            </div>
                            <input type="hidden" id="imgStyle" value="default">
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">📐 尺寸</label>
                            <div class="gen-select-group" id="imgSizeGroup">
                                <div class="gen-select-item active" data-value="1024x1024" onclick="selectStyle(this,'imgSize')">1:1 正方形</div>
                                <div class="gen-select-item" data-value="1792x1024" onclick="selectStyle(this,'imgSize')">16:9 横版</div>
                                <div class="gen-select-item" data-value="1024x1792" onclick="selectStyle(this,'imgSize')">9:16 竖版</div>
                            </div>
                            <input type="hidden" id="imgSize" value="1024x1024">
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">🤖 选择智能体</label>
                            <div class="gen-select-group">
                                <?php foreach ($agents as $a):
                                    $c = $agentColors[$a['slug']] ?? ['#6366f1','#8b5cf6','fas fa-robot'];
                                ?>
                                <div class="gen-select-item agent-select" data-value="<?php echo $a['id']; ?>" onclick="selectAgent(this)" style="display:flex;align-items:center;gap:6px;">
                                    <div style="width:20px;height:20px;border-radius:5px;background:linear-gradient(135deg,<?php echo $c[0]; ?>,<?php echo $c[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.55rem;"><i class="<?php echo $c[2]; ?>"></i></div>
                                    <?php echo clean($a['name']); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="imgAgent" value="3">
                        </div>

                        <button onclick="generateImage()" class="gen-btn gen-btn-primary" id="imgGenBtn">
                            <i class="fas fa-wand-magic-sparkles"></i> 生成图片
                        </button>

                        <div class="gen-result" id="imgResult">
                            <div class="gen-result-empty"><i class="fas fa-image"></i>输入描述，AI 为你生成图片</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 音频生成 -->
            <div class="media-panel" id="panel-audio">
                <div class="gen-card">
                    <div class="gen-card-body">
                        <div class="gen-input-group">
                            <label class="gen-label">📝 文本内容</label>
                            <textarea id="audioText" class="gen-textarea" placeholder="输入你想转换为音频的文本，例如：欢迎来到AI智能博客，这里是技术与创意的交汇点..."></textarea>
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">🎤 声音风格</label>
                            <div class="gen-select-group" id="audioVoiceGroup">
                                <div class="gen-select-item active" data-value="default" onclick="selectStyle(this,'audioVoice')">🎙️ 默认</div>
                                <div class="gen-select-item" data-value="male" onclick="selectStyle(this,'audioVoice')">👨 男声</div>
                                <div class="gen-select-item" data-value="female" onclick="selectStyle(this,'audioVoice')">👩 女声</div>
                                <div class="gen-select-item" data-value="narrator" onclick="selectStyle(this,'audioVoice')">📖 旁白</div>
                            </div>
                            <input type="hidden" id="audioVoice" value="default">
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">⚡ 语速</label>
                            <div class="gen-select-group" id="audioSpeedGroup">
                                <div class="gen-select-item" data-value="0.8" onclick="selectStyle(this,'audioSpeed')">🐢 慢速</div>
                                <div class="gen-select-item active" data-value="1.0" onclick="selectStyle(this,'audioSpeed')">🏃 正常</div>
                                <div class="gen-select-item" data-value="1.2" onclick="selectStyle(this,'audioSpeed')">🚀 快速</div>
                            </div>
                            <input type="hidden" id="audioSpeed" value="1.0">
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">🤖 选择智能体</label>
                            <div class="gen-select-group">
                                <?php foreach ($agents as $a):
                                    $c = $agentColors[$a['slug']] ?? ['#6366f1','#8b5cf6','fas fa-robot'];
                                ?>
                                <div class="gen-select-item agent-select" data-value="<?php echo $a['id']; ?>" onclick="selectAgent(this)" style="display:flex;align-items:center;gap:6px;">
                                    <div style="width:20px;height:20px;border-radius:5px;background:linear-gradient(135deg,<?php echo $c[0]; ?>,<?php echo $c[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.55rem;"><i class="<?php echo $c[2]; ?>"></i></div>
                                    <?php echo clean($a['name']); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="audioAgent" value="1">
                        </div>

                        <button onclick="generateAudio()" class="gen-btn gen-btn-primary" id="audioGenBtn">
                            <i class="fas fa-headphones"></i> 生成音频
                        </button>

                        <div class="gen-result" id="audioResult">
                            <div class="gen-result-empty"><i class="fas fa-headphones"></i>输入文本，AI 为你生成语音</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 视频生成 -->
            <div class="media-panel" id="panel-video">
                <div class="gen-card">
                    <div class="gen-card-body">
                        <div class="gen-input-group">
                            <label class="gen-label">📝 视频描述</label>
                            <textarea id="videoPrompt" class="gen-textarea" placeholder="描述你想生成的视频，例如：一个未来城市的日出，摩天大楼反射着金色阳光，飞行汽车穿梭其间..."></textarea>
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">🎬 视频风格</label>
                            <div class="gen-select-group" id="videoStyleGroup">
                                <div class="gen-select-item active" data-value="cinematic" onclick="selectStyle(this,'videoStyle')">🎬 电影感</div>
                                <div class="gen-select-item" data-value="animation" onclick="selectStyle(this,'videoStyle')">🎨 动画</div>
                                <div class="gen-select-item" data-value="realistic" onclick="selectStyle(this,'videoStyle')">📷 写实</div>
                                <div class="gen-select-item" data-value="abstract" onclick="selectStyle(this,'videoStyle')">🎭 抽象</div>
                            </div>
                            <input type="hidden" id="videoStyle" value="cinematic">
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">⏱️ 时长（秒）</label>
                            <div class="gen-select-group" id="videoDurGroup">
                                <div class="gen-select-item" data-value="3" onclick="selectStyle(this,'videoDur')">3秒</div>
                                <div class="gen-select-item active" data-value="5" onclick="selectStyle(this,'videoDur')">5秒</div>
                                <div class="gen-select-item" data-value="10" onclick="selectStyle(this,'videoDur')">10秒</div>
                            </div>
                            <input type="hidden" id="videoDur" value="5">
                        </div>

                        <div class="gen-input-group">
                            <label class="gen-label">🤖 选择智能体</label>
                            <div class="gen-select-group">
                                <?php foreach ($agents as $a):
                                    $c = $agentColors[$a['slug']] ?? ['#6366f1','#8b5cf6','fas fa-robot'];
                                ?>
                                <div class="gen-select-item agent-select" data-value="<?php echo $a['id']; ?>" onclick="selectAgent(this)" style="display:flex;align-items:center;gap:6px;">
                                    <div style="width:20px;height:20px;border-radius:5px;background:linear-gradient(135deg,<?php echo $c[0]; ?>,<?php echo $c[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.55rem;"><i class="<?php echo $c[2]; ?>"></i></div>
                                    <?php echo clean($a['name']); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="videoAgent" value="3">
                        </div>

                        <button onclick="generateVideo()" class="gen-btn gen-btn-primary" id="videoGenBtn">
                            <i class="fas fa-video"></i> 生成视频
                        </button>

                        <div class="gen-result" id="videoResult">
                            <div class="gen-result-empty"><i class="fas fa-video"></i>输入描述，AI 为你生成视频</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 侧栏：最近生成 -->
        <div class="col-lg-4">
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;padding:24px;">
                <div style="font-weight:700;font-size:0.95rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-clock-rotate-left" style="color:var(--neon);"></i> 最近生成
                </div>
                <div id="recentMedia" style="min-height:200px;">
                    <div class="gen-result-empty" style="padding:40px 10px;"><i class="fas fa-sparkles"></i>还没有生成内容</div>
                </div>
            </div>

            <?php include __DIR__ . '/../views/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php
$extraJs = '';
include __DIR__ . '/../views/footer.php';
?>
