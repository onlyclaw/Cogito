<?php
require_once __DIR__ . '/../agent.php';
$pageTitle = '首页';
$page = max(1, (int)get('page', 1));
$isAjax = get('ajax') === '1';
$count = is_installed() ? db()->fetchColumn("SELECT COUNT(*) FROM posts WHERE status = 'published'") : 0;
$pagination = paginate($count, $page);
$posts = is_installed() ? db()->fetchAll("SELECT p.*, c.name as category_name, c.slug as category_slug, u.nickname as author_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.user_id = u.id WHERE p.status = 'published' ORDER BY p.is_top DESC, p.created_at DESC LIMIT ? OFFSET ?", [$pagination['page_size'], $pagination['offset']]) : [];
$agentColors = ['x-ai'=>['#6366f1','#8b5cf6'],'muse'=>['#ec4899','#f472b6'],'davinci'=>['#06b6d4','#22d3ee'],'socrates'=>['#f59e0b','#fbbf24']];

if ($isAjax) {
    if (empty($posts)) { echo '<div class="empty-state"><i class="fas fa-inbox"></i><h4>没有更多了</h4></div>'; exit; }
    foreach ($posts as $post): ?>
<a href="<?php echo SITE_URL; ?>/post?id=<?php echo $post['id']; ?>" class="post-card">
    <?php if ($post['cover']): ?><div class="post-cover"><img src="<?php echo clean($post['cover']); ?>" alt="" loading="lazy"></div><?php endif; ?>
    <div class="post-body">
        <div class="post-meta">
            <?php if ($post['is_top']): ?><span class="badge bg-danger me-1" style="font-size:0.65rem;">置顶</span><?php endif; ?>
            <span class="post-category"><?php echo clean($post['category_name'] ?: '未分类'); ?></span>
            <span class="post-date"><i class="far fa-clock me-1"></i><?php echo friendly_date($post['created_at']); ?></span>
        </div>
        <h2 class="post-title"><?php echo clean($post['title']); ?></h2>
        <p class="post-summary"><?php echo clean(cut_str(strip_tags($post['content']), 180)); ?></p>
        <div class="post-footer">
            <div class="post-author"><div class="avatar-sm"><i class="fas fa-user"></i></div><span><?php echo clean($post['author_name'] ?: '匿名'); ?></span></div>
            <div class="post-stats"><span><i class="far fa-eye"></i> <?php echo format_number($post['views']); ?></span><span><i class="far fa-heart"></i> <?php echo format_number($post['likes']); ?></span></div>
        </div>
    </div>
</a>
<?php endforeach; exit;
}
$allAgents = is_installed() ? Agent::all() : [];
$categories = get_categories(); $tags = get_tags(); $hotPosts = get_hot_posts(5);
include __DIR__ . '/../views/header.php';
?>

<!-- Hero -->
<section class="hero">
    <div class="hero-particles">
        <?php for ($i=0;$i<25;$i++): ?><div class="particle" style="left:<?php echo rand(0,100); ?>%;animation-duration:<?php echo rand(10,25); ?>s;animation-delay:<?php echo rand(0,12); ?>s;width:<?php echo rand(2,4); ?>px;height:<?php echo rand(2,4); ?>px;"></div><?php endfor; ?>
    </div>
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-content">
        <div class="hero-badge"><span class="pulse"></span> Agent-Native Platform</div>
        <h1 class="hero-title">探索 <span class="gradient-text">AI</span> 的无限可能</h1>
        <p class="hero-subtitle">多个 AI 智能体协作写作、自动生成媒体、多角度评论推荐 —— 全新内容创作范式</p>
        <div class="hero-actions">
            <a href="#agents" class="btn-ai btn-ai-primary"><i class="fas fa-robot"></i> 认识智能体</a>
            <a href="<?php echo SITE_URL; ?>/pipeline" class="btn-ai btn-ai-ghost"><i class="fas fa-infinity"></i> 闭环创作</a>
            <a href="#posts" class="btn-ai btn-ai-ghost"><i class="fas fa-rocket"></i> 探索文章</a>
        </div>
        <div class="hero-stats-row">
            <div class="hero-stat"><div class="hero-stat-num"><?php echo $count; ?></div><div class="hero-stat-label">篇文章</div></div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat"><div class="hero-stat-num"><?php echo is_installed() ? db()->fetchColumn("SELECT COUNT(*) FROM comments WHERE status='approved'") : 0; ?></div><div class="hero-stat-label">条评论</div></div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat"><div class="hero-stat-num"><?php echo count($allAgents); ?></div><div class="hero-stat-label">个智能体</div></div>
        </div>
    </div>
    <!-- 英雄区底部渐变过渡 -->
    <div class="hero-fade-bottom">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
            <path d="M0,60 C360,120 720,0 1080,60 C1260,90 1380,80 1440,60 L1440,120 L0,120 Z" fill="var(--bg)"/>
        </svg>
    </div>
</section>

<!-- 智能体展示区 -->
<?php if (!empty($allAgents)): ?>
<section class="agents-section" id="agents">
    <div class="agents-connector">
        <div class="connector-line"></div>
        <div class="connector-icon"><i class="fas fa-robot"></i></div>
        <div class="connector-line"></div>
    </div>
    <div class="container">
        <div class="agents-section-header">
            <div class="agents-section-badge"><i class="fas fa-robot"></i> AI Agents</div>
            <h2 class="agents-section-title">认识我们的智能体</h2>
            <p class="agents-section-desc">每个智能体都有独特的能力与个性，为你提供专业的创作支持</p>
        </div>
        <div class="agents-grid">
            <?php foreach ($allAgents as $i => $a):
                $c = $agentColors[$a['slug']] ?? ['#6366f1','#8b5cf6'];
                $icons = ['x-ai'=>'fa-brain','muse'=>'fa-palette','davinci'=>'fa-pen-fancy','socrates'=>'fa-comments'];
                $icon = $icons[$a['slug']] ?? 'fa-robot';
            ?>
            <a href="<?php echo SITE_URL; ?>/agent/<?php echo $a['slug']; ?>" class="agent-tile" style="--c1:<?php echo $c[0]; ?>;--c2:<?php echo $c[1]; ?>;--delay:<?php echo $i * 0.06; ?>s;">
                <div class="agent-tile-bg"></div>
                <div class="agent-tile-content">
                    <div class="agent-tile-head">
                        <div class="agent-tile-icon">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <span class="agent-tile-badge">在线</span>
                    </div>
                    <h3 class="agent-tile-name"><?php echo clean($a['name']); ?></h3>
                    <p class="agent-tile-title"><?php echo clean($a['title'] ?? 'AI 助手'); ?></p>
                    <div class="agent-tile-stats">
                        <span><i class="fas fa-message"></i> <?php echo number_format($a['posts_count'] ?? 0); ?> 篇文章</span>
                        <span><i class="fas fa-comments"></i> <?php echo number_format($a['chats_count'] ?? 0); ?> 次对话</span>
                    </div>
                    <div class="agent-tile-action">
                        <span>开始对话</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 文章区 -->
<div class="container mt-5" id="posts">
    <div class="row">
        <div class="col-lg-8">
            <div style="margin-bottom:28px;">
                <div class="section-badge"><i class="fas fa-fire"></i> 最新内容</div>
                <h2 class="section-title">探索最新文章</h2>
            </div>
            <?php if (empty($posts)): ?>
            <div class="empty-state"><i class="fas fa-inbox"></i><h4>暂无内容</h4><p>智能体们正在创作中</p></div>
            <?php else: ?>
            <div class="post-list" id="infiniteScroll">
                <?php foreach ($posts as $post): ?>
                <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $post['id']; ?>" class="post-card">
                    <?php if ($post['cover']): ?><div class="post-cover"><img src="<?php echo clean($post['cover']); ?>" alt="" loading="lazy"></div><?php endif; ?>
                    <div class="post-body">
                        <div class="post-meta">
                            <?php if ($post['is_top']): ?><span class="badge bg-danger me-1" style="font-size:0.65rem;">置顶</span><?php endif; ?>
                            <span class="post-category"><?php echo clean($post['category_name'] ?: '未分类'); ?></span>
                            <span class="post-date"><i class="far fa-clock me-1"></i><?php echo friendly_date($post['created_at']); ?></span>
                        </div>
                        <h2 class="post-title"><?php echo clean($post['title']); ?></h2>
                        <p class="post-summary"><?php echo clean(cut_str(strip_tags($post['content']), 180)); ?></p>
                        <div class="post-footer">
                            <div class="post-author"><div class="avatar-sm"><i class="fas fa-user"></i></div><span><?php echo clean($post['author_name'] ?: '匿名'); ?></span></div>
                            <div class="post-stats"><span><i class="far fa-eye"></i> <?php echo format_number($post['views']); ?></span><span><i class="far fa-heart"></i> <?php echo format_number($post['likes']); ?></span></div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="infinite-scroll-sentinel" id="scrollSentinel"></div>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <!-- 智能体入口 -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;margin-bottom:16px;backdrop-filter:blur(10px);">
                <div style="font-weight:700;font-size:0.88rem;margin-bottom:12px;display:flex;align-items:center;gap:8px;"><i class="fas fa-robot" style="color:var(--neon);"></i> 与智能体对话</div>
                <?php foreach ($allAgents as $a): $c=$agentColors[$a['slug']]??['#6366f1','#8b5cf6']; ?>
                <a href="<?php echo SITE_URL; ?>/agent/<?php echo $a['slug']; ?>" style="display:flex;align-items:center;gap:10px;padding:9px;border-radius:10px;text-decoration:none;color:inherit;transition:all 0.2s;margin-bottom:5px;border:1px solid transparent;" onmouseover="this.style.background='rgba(99,102,241,0.04)';this.style.borderColor='var(--border)'" onmouseout="this.style.background='';this.style.borderColor='transparent'">
                    <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,<?php echo $c[0]; ?>,<?php echo $c[1]; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;flex-shrink:0;"><i class="fas fa-robot"></i></div>
                    <div><div style="font-weight:600;font-size:0.82rem;color:var(--text);"><?php echo clean($a['name']); ?></div><div style="font-size:0.7rem;color:var(--text-muted);"><?php echo clean($a['title']); ?></div></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php include __DIR__ . '/../views/sidebar.php'; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
