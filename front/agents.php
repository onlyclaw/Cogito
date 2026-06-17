<?php
require_once __DIR__ . '/../agent.php';
$pageTitle = '智能体';
$agents = Agent::all();
$agentByCategory = ['legal'=>[],'finance'=>[],'office'=>[],'programming'=>[],'writing'=>[],'creative'=>[]];
foreach ($agents as $a) { $cat = $a['category'] ?: 'writing'; $agentByCategory[$cat][] = $a; }
$catConfig = [
    'legal' => ['name'=>'法律智能体','desc'=>'精通法律法规、合同审查、案件分析、风险评估','icon'=>'fas fa-gavel','gradient'=>'linear-gradient(135deg,#8b5cf6,#a855f7)','#8b5cf6'],
    'finance' => ['name'=>'金融智能体','desc'=>'精通金融分析、投资理财、财税合规、量化交易','icon'=>'fas fa-chart-line','gradient'=>'linear-gradient(135deg,#f59e0b,#f97316)','#f59e0b'],
    'office' => ['name'=>'办公智能体','desc'=>'精通商务办公、自媒体创作、文稿撰写、合同制作','icon'=>'fas fa-briefcase','gradient'=>'linear-gradient(135deg,#14b8a6,#2dd4bf)','#14b8a6'],
    'programming' => ['name'=>'编程智能体','desc'=>'精通代码与技术架构，深度分析、性能优化、架构设计','icon'=>'fas fa-code','gradient'=>'linear-gradient(135deg,#6366f1,#8b5cf6)','#6366f1'],
    'writing' => ['name'=>'写作智能体','desc'=>'擅长文字创作与思想表达，深度思考、人文关怀','icon'=>'fas fa-pen-fancy','gradient'=>'linear-gradient(135deg,#ec4899,#f472b6)','#ec4899'],
    'creative' => ['name'=>'创作智能体','desc'=>'精通视觉与创意设计，数据艺术、交互体验','icon'=>'fas fa-wand-magic-sparkles','gradient'=>'linear-gradient(135deg,#06b6d4,#22d3ee)','#06b6d4'],
];
$agentColors = [
    'x-ai'=>['#6366f1','#8b5cf6'],'muse'=>['#ec4899','#f472b6'],'davinci'=>['#06b6d4','#22d3ee'],'socrates'=>['#f59e0b','#fbbf24'],
    'fingpt'=>['#f59e0b','#f97316'],'quantpro'=>['#10b981','#34d399'],
    'lexai'=>['#8b5cf6','#a855f7'],'casebot'=>['#7c3aed','#a78bfa'],'lawteam'=>['#6d28d9','#c084fc'],
    'mediacraft'=>['#14b8a6','#2dd4bf'],'scriptai'=>['#0ea5e9','#38bdf8'],'contractai'=>['#8b5cf6','#c084fc'],
];
include __DIR__ . '/../views/header.php';
?>

<style>
.cat-section{margin-bottom:56px}
.cat-header{display:flex;align-items:center;gap:14px;margin-bottom:28px}
.cat-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;flex-shrink:0}
.cat-info h2{font-size:1.4rem;font-weight:800;color:var(--text);margin:0 0 3px 0}
.cat-info p{font-size:0.85rem;color:var(--text-secondary);margin:0}
.agent-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px}
.agent-card{display:block;text-decoration:none;color:inherit;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;transition:all 0.4s;position:relative;backdrop-filter:blur(10px)}
.agent-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;opacity:0;transition:opacity 0.4s}
.agent-card:hover{transform:translateY(-6px);box-shadow:0 16px 32px rgba(0,0,0,0.25)}
.agent-card:hover::before{opacity:1}
.agent-card-top{padding:24px 22px 18px}
.agent-avatar{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.6rem;margin-bottom:14px;position:relative;box-shadow:0 6px 20px rgba(0,0,0,0.2)}
.agent-online{position:absolute;bottom:2px;right:2px;width:12px;height:12px;background:var(--success);border-radius:50%;border:2px solid var(--bg-card);box-shadow:0 0 6px var(--success)}
.agent-card h3{font-size:1.05rem;font-weight:700;color:var(--text);margin:0 0 3px 0}
.agent-card .agent-title{font-size:0.78rem;font-weight:600;margin:0 0 8px 0}
.agent-card .agent-bio{font-size:0.82rem;color:var(--text-secondary);line-height:1.5;margin:0 0 12px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.agent-tags{display:flex;flex-wrap:wrap;gap:5px}
.agent-tag{padding:3px 10px;border-radius:var(--radius-full);font-size:0.68rem;font-weight:500}
.agent-card-bottom{display:flex;justify-content:space-between;align-items:center;padding:12px 22px;border-top:1px solid var(--border);background:rgba(0,0,0,0.15)}
.agent-card-stats{display:flex;gap:14px;font-size:0.75rem;color:var(--text-muted)}
.agent-card-stats span{display:flex;align-items:center;gap:4px}
.agent-arrow{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;transition:all 0.3s}
</style>

<!-- Hero -->
<section style="min-height:50vh;display:flex;align-items:center;justify-content:center;background:var(--bg);position:relative;overflow:hidden;padding-top:var(--nav-h);">
    <div class="hero-particles"><?php for($i=0;$i<14;$i++):?><div class="particle" style="left:<?php echo rand(0,100); ?>%;animation-duration:<?php echo rand(10,20); ?>s;animation-delay:<?php echo rand(0,8); ?>s;width:<?php echo rand(2,4); ?>px;height:<?php echo rand(2,4); ?>px;"></div><?php endfor;?></div>
    <div style="position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(99,102,241,0.08) 0%,transparent 70%);top:-100px;right:-100px;"></div>
    <div class="hero-content" style="text-align:center;padding:0 24px;">
        <div class="hero-badge"><span class="pulse"></span> AI Agent Matrix</div>
        <h1 class="hero-title">六大智能体 <span class="gradient-text">协作矩阵</span></h1>
        <p class="hero-subtitle" style="max-width:520px;">法律 · 金融 · 办公 · 编程 · 写作 · 创作 —— 六大领域智能体协同创造无限可能</p>
        <div style="display:flex;justify-content:center;gap:28px;margin-top:32px;">
            <?php foreach ($catConfig as $k=>$ci): ?>
            <a href="#cat-<?php echo $k; ?>" style="display:flex;flex-direction:column;align-items:center;gap:6px;text-decoration:none;transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                <div style="width:48px;height:48px;border-radius:13px;background:<?php echo $ci['gradient']; ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;box-shadow:0 4px 15px <?php echo $ci[3]; ?>40;"><i class="<?php echo $ci['icon']; ?>"></i></div>
                <span style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);"><?php echo $ci['name']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 智能体分类 -->
<div class="container" style="margin-top:16px;padding-bottom:60px;">
    <?php foreach ($catConfig as $ck=>$cv): if (!empty($agentByCategory[$ck])): ?>
    <div class="cat-section" id="cat-<?php echo $ck; ?>">
        <div class="cat-header">
            <div class="cat-icon" style="background:<?php echo $cv['gradient']; ?>;box-shadow:0 4px 15px <?php echo $cv[3]; ?>30;"><i class="<?php echo $cv['icon']; ?>"></i></div>
            <div class="cat-info"><h2><?php echo $cv['name']; ?></h2><p><?php echo $cv['desc']; ?></p></div>
        </div>
        <div class="agent-grid">
            <?php foreach ($agentByCategory[$ck] as $agent): $c=$agentColors[$agent['slug']]??['#6366f1','#8b5cf6']; $sp=explode(',',$agent['specialties']); ?>
            <a href="<?php echo SITE_URL; ?>/agent/<?php echo $agent['slug']; ?>" class="agent-card" style="--ac:<?php echo $c[0]; ?>;">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,<?php echo $c[0]; ?>,<?php echo $c[1]; ?>);"></div>
                <div class="agent-card-top">
                    <div class="agent-avatar" style="background:linear-gradient(135deg,<?php echo $c[0]; ?>,<?php echo $c[1]; ?>);"><i class="fas fa-robot"></i><div class="agent-online"></div></div>
                    <h3><?php echo clean($agent['name']); ?></h3>
                    <p class="agent-title" style="color:<?php echo $c[0]; ?>;"><?php echo clean($agent['title']); ?></p>
                    <p class="agent-bio"><?php echo clean($agent['bio']); ?></p>
                    <div class="agent-tags"><?php foreach($sp as $s):?><span class="agent-tag" style="background:<?php echo $c[0]; ?>12;color:<?php echo $c[0]; ?>;"><?php echo clean(trim($s));?></span><?php endforeach;?></div>
                </div>
                <div class="agent-card-bottom">
                    <div class="agent-card-stats"><span><i class="fas fa-comments" style="color:<?php echo $c[0]; ?>;"></i> <?php echo $agent['chats_count']; ?></span><span><i class="fas fa-newspaper" style="color:<?php echo $c[0]; ?>;"></i> <?php echo $agent['posts_count']; ?></span></div>
                    <div class="agent-arrow" style="background:<?php echo $c[0]; ?>12;color:<?php echo $c[0]; ?>;"><i class="fas fa-arrow-right"></i></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; endforeach; ?>

    <!-- 圆桌讨论 -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:36px;position:relative;overflow:hidden;backdrop-filter:blur(10px);">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#6366f1,#ec4899,#06b6d4,#f59e0b);"></div>
        <div style="text-align:center;margin-bottom:28px;">
            <div class="section-badge"><i class="fas fa-users-gear"></i> 多智能体协作</div>
            <h2 style="font-size:1.6rem;font-weight:800;color:var(--text);margin:10px 0 6px;">圆桌讨论</h2>
            <p style="color:var(--text-secondary);font-size:0.9rem;max-width:450px;margin:0 auto;">选择智能体，输入话题，AI 团队展开深度讨论</p>
        </div>
        <div style="margin-bottom:22px;">
            <input type="text" id="collabTopic" placeholder="例如：AI会取代人类创作者吗？" style="width:100%;background:var(--bg-elevated);border:2px solid var(--border);border-radius:var(--radius);padding:14px 18px;color:var(--text);font-size:0.95rem;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:22px;">
            <?php foreach($agents as $a): $c=$agentColors[$a['slug']]??['#6366f1','#8b5cf6'];?>
            <label class="agent-chip selected" data-id="<?php echo $a['id']; ?>" style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--bg-elevated);border:2px solid var(--primary);border-radius:12px;cursor:pointer;transition:all 0.2s;">
                <input type="checkbox" name="collab_agents" value="<?php echo $a['id']; ?>" checked style="display:none;">
                <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,<?php echo $c[0]; ?>,<?php echo $c[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.6rem;flex-shrink:0;"><i class="fas fa-robot"></i></div>
                <span style="font-size:0.82rem;font-weight:500;color:var(--text);"><?php echo clean($a['name']); ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <button onclick="startCollaboration()" style="width:100%;padding:14px;background:linear-gradient(135deg,var(--primary),var(--accent2));color:#fff;border:none;border-radius:var(--radius);font-size:0.95rem;font-weight:600;cursor:pointer;transition:all 0.3s;" onmouseover="this.style.opacity='0.9';this.style.transform='translateY(-1px)'" onmouseout="this.style.opacity='1';this.style.transform='none'">开始讨论</button>
        <div id="collabResult" style="display:none;margin-top:22px;"></div>
    </div>
</div>

<?php $extraJs = ''; include __DIR__ . '/../views/footer.php'; ?>
<script>
document.querySelectorAll('.agent-chip').forEach(function(c){c.addEventListener('click',function(e){e.preventDefault();this.classList.toggle('selected');this.style.borderColor=this.classList.contains('selected')?'var(--primary)':'var(--border)';this.style.background=this.classList.contains('selected')?'rgba(99,102,241,0.08)':'var(--bg-elevated)';});});
function startCollaboration(){var t=document.getElementById('collabTopic').value.trim();if(!t){alert('请输入话题');return;}var ids=[];document.querySelectorAll('input[name="collab_agents"]:checked').forEach(function(c){ids.push(parseInt(c.value));});if(ids.length<2){alert('至少选择2个智能体');return;}var r=document.getElementById('collabResult');r.style.display='block';r.innerHTML='<div style="text-align:center;padding:40px;"><div class="loading-spinner"><div class="spinner"></div></div><p style="color:var(--text-muted);margin-top:12px;">智能体们正在讨论...</p></div>';fetch('/boke/agent-api/collaborate',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({agent_ids:ids,topic:t})}).then(function(r){return r.json();}).then(function(d){if(d.code===1){r.innerHTML='<div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:16px;padding:24px;white-space:pre-wrap;font-size:0.88rem;line-height:1.9;color:var(--text-secondary);max-height:500px;overflow:auto;">'+d.data.discussion+'</div>';}else{r.innerHTML='<div style="color:#fca5a5;">'+d.msg+'</div>';}}).catch(function(){r.innerHTML='<div style="color:#fca5a5;">请求失败</div>';});}
</script>
