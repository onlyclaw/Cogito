<?php
/**
 * 前台用户个人中心
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!is_member_login()) {
    redirect(SITE_URL . '/auth?tab=login');
}

$pageTitle = '个人中心';
$member = current_member();
if (!$member) {
    unset($_SESSION['member_id']);
    redirect(SITE_URL . '/auth?tab=login');
}

// 统计
$mediaCount = db()->count('ai_media', 'member_id = ?', [$member['id']]);
$favCount = db()->count('favorites', 'user_id = ?', [$member['id']]);
$commentCount = db()->count('comments', 'email = ?', [$member['email']]);

// 获取最近媒体
$recentMedia = db()->fetchAll("SELECT * FROM ai_media WHERE member_id = ? ORDER BY created_at DESC LIMIT 6", [$member['id']]);

// 获取收藏
$favorites = db()->fetchAll("SELECT f.*, p.title, p.summary FROM favorites f LEFT JOIN posts p ON f.post_id = p.id WHERE f.user_id = ? ORDER BY f.created_at DESC LIMIT 10", [$member['id']]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($pageTitle); ?> - <?php echo clean(get_option('site_name', SITE_NAME)); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #050810;
            --card-bg: rgba(15, 20, 35, 0.8);
            --card-bg-solid: #0f1423;
            --border: rgba(99, 102, 241, 0.12);
            --primary: #6366f1;
            --primary-light: #818cf8;
            --accent: #22d3ee;
            --accent2: #ec4899;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --neon: #22d3ee;
            --radius: 16px;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            position: relative;
        }
        .bg-orb { position: fixed; border-radius: 50%; filter: blur(80px); opacity: 0.2; animation: float 8s ease-in-out infinite; pointer-events: none; }
        .bg-orb-1 { width: 400px; height: 400px; background: #6366f1; top: -100px; left: -100px; }
        .bg-orb-2 { width: 300px; height: 300px; background: #22d3ee; bottom: -50px; right: -50px; animation-delay: -3s; }
        @keyframes float { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -20px); } }

        /* 导航 */
        .top-nav {
            background: rgba(5, 8, 16, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .top-nav .container { display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none; font-weight: 700; font-size: 1.1rem; }
        .nav-brand .icon { width: 32px; height: 32px; border-radius: 10px; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 0.85rem; transition: color 0.3s; }
        .nav-links a:hover { color: var(--accent); }

        /* 主内容 */
        .profile-container { max-width: 900px; margin: 0 auto; padding: 32px 20px; position: relative; z-index: 1; }

        /* 用户卡片 */
        .profile-header {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
            margin-bottom: 24px;
            backdrop-filter: blur(24px);
            position: relative;
            overflow: hidden;
        }
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--accent2));
        }
        .profile-info { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .profile-avatar {
            width: 80px; height: 80px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: #fff;
            flex-shrink: 0;
        }
        .profile-avatar img {
            width: 100%; height: 100%;
            border-radius: 20px;
            object-fit: cover;
        }
        .profile-detail h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 4px; }
        .profile-detail .bio { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 8px; }
        .profile-detail .meta { display: flex; gap: 16px; font-size: 0.8rem; color: var(--text-muted); }
        .profile-detail .meta i { color: var(--primary-light); margin-right: 4px; }
        .profile-actions { margin-left: auto; display: flex; gap: 10px; }
        .btn-edit, .btn-logout {
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            font-family: inherit;
            transition: all 0.3s;
        }
        .btn-edit {
            background: rgba(99,102,241,0.15);
            color: var(--primary-light);
            border: 1px solid rgba(99,102,241,0.2);
        }
        .btn-edit:hover { background: rgba(99,102,241,0.25); }
        .btn-logout {
            background: rgba(239,68,68,0.1);
            color: #fca5a5;
            border: 1px solid rgba(239,68,68,0.15);
        }
        .btn-logout:hover { background: rgba(239,68,68,0.2); }

        /* 统计 */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(24px);
        }
        .stat-card .stat-num {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-card .stat-label { font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; }

        /* 标签页 */
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: rgba(255,255,255,0.03); border-radius: 12px; padding: 4px; }
        .tab-btn {
            flex: 1; padding: 10px; text-align: center; border-radius: 10px; background: none; border: none;
            color: var(--text-muted); font-size: 0.85rem; font-weight: 500; cursor: pointer; font-family: inherit; transition: all 0.3s;
        }
        .tab-btn.active { background: linear-gradient(135deg, var(--primary), rgba(99,102,241,0.7)); color: #fff; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* 媒体网格 */
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
        .media-item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .media-item:hover { transform: translateY(-3px); }
        .media-thumb {
            width: 100%; aspect-ratio: 1;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
        }
        .media-thumb.img-type { background: linear-gradient(135deg, rgba(99,102,241,0.2), rgba(34,211,238,0.2)); }
        .media-thumb.audio-type { background: linear-gradient(135deg, rgba(236,72,153,0.2), rgba(244,114,182,0.2)); }
        .media-thumb.video-type { background: linear-gradient(135deg, rgba(34,211,238,0.2), rgba(6,182,212,0.2)); }
        .media-info { padding: 8px 10px; }
        .media-info .type-badge { font-size: 0.7rem; color: var(--accent); }
        .media-info .media-date { font-size: 0.7rem; color: var(--text-muted); }

        /* 收藏列表 */
        .fav-item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform 0.3s;
            text-decoration: none;
            color: var(--text);
        }
        .fav-item:hover { transform: translateX(4px); border-color: rgba(99,102,241,0.3); }
        .fav-icon { color: #f87171; font-size: 1.1rem; }
        .fav-info { flex: 1; }
        .fav-title { font-size: 0.9rem; font-weight: 500; }
        .fav-date { font-size: 0.75rem; color: var(--text-muted); }

        /* 编辑表单 */
        .edit-form {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            backdrop-filter: blur(24px);
        }
        .edit-form .form-group { margin-bottom: 16px; }
        .edit-form label { display: block; font-size: 0.82rem; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
        .edit-form .form-input {
            width: 100%; padding: 11px 14px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s;
        }
        .edit-form .form-input:focus { border-color: var(--primary); }
        .edit-form textarea { min-height: 80px; resize: vertical; }
        .btn-save {
            padding: 10px 24px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-save:hover { opacity: 0.9; }

        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.3; }
        .empty-state p { font-size: 0.85rem; }

        @media (max-width: 576px) {
            .profile-info { flex-direction: column; text-align: center; }
            .profile-actions { margin-left: 0; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>

    <nav class="top-nav">
        <div class="container">
            <a href="<?php echo SITE_URL; ?>/" class="nav-brand">
                <span class="icon"><i class="fas fa-brain"></i></span>
                <?php echo clean(get_option('site_name', SITE_NAME)); ?>
            </a>
            <div class="nav-links">
                <a href="<?php echo SITE_URL; ?>/">首页</a>
                <a href="<?php echo SITE_URL; ?>/pipeline">闭环创作</a>
                <a href="<?php echo SITE_URL; ?>/agents">智能体</a>
                <a href="<?php echo SITE_URL; ?>/media">媒体工坊</a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <!-- 用户信息 -->
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-avatar">
                    <?php if (!empty($member['avatar'])): ?>
                        <img src="<?php echo clean($member['avatar']); ?>" alt="">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="profile-detail">
                    <h2><?php echo clean($member['nickname'] ?: $member['username']); ?></h2>
                    <?php if (!empty($member['bio'])): ?>
                        <div class="bio"><?php echo clean($member['bio']); ?></div>
                    <?php endif; ?>
                    <div class="meta">
                        <span><i class="fas fa-at"></i><?php echo clean($member['username']); ?></span>
                        <span><i class="fas fa-envelope"></i><?php echo clean($member['email']); ?></span>
                        <span><i class="fas fa-calendar"></i><?php echo format_date($member['created_at'], 'Y-m-d'); ?> 加入</span>
                    </div>
                </div>
                <div class="profile-actions">
                    <button class="btn-edit" onclick="switchTab('edit')"><i class="fas fa-pen"></i> 编辑资料</button>
                    <button class="btn-logout" onclick="handleLogout()"><i class="fas fa-sign-out-alt"></i> 退出</button>
                </div>
            </div>
        </div>

        <!-- 统计 -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-num"><?php echo $mediaCount; ?></div>
                <div class="stat-label">AI 创作</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?php echo $favCount; ?></div>
                <div class="stat-label">收藏文章</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?php echo $commentCount; ?></div>
                <div class="stat-label">评论</div>
            </div>
        </div>

        <!-- 标签页 -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('media')"><i class="fas fa-palette"></i> 我的创作</button>
            <button class="tab-btn" onclick="switchTab('favs')"><i class="fas fa-heart"></i> 我的收藏</button>
            <button class="tab-btn" onclick="switchTab('edit')"><i class="fas fa-cog"></i> 账号设置</button>
        </div>

        <!-- 我的创作 -->
        <div id="panel-media" class="tab-panel active">
            <?php if (empty($recentMedia)): ?>
                <div class="empty-state">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <p>还没有AI创作，去<a href="<?php echo SITE_URL; ?>/media" style="color:var(--accent)">媒体工坊</a>开始创作吧</p>
                </div>
            <?php else: ?>
                <div class="media-grid">
                    <?php foreach ($recentMedia as $item): ?>
                    <div class="media-item">
                        <div class="media-thumb <?php echo $item['type']; ?>-type">
                            <?php
                                $icons = ['image' => 'fa-image', 'audio' => 'fa-music', 'video' => 'fa-film'];
                                echo '<i class="fas ' . ($icons[$item['type']] ?? 'fa-file') . '"></i>';
                            ?>
                        </div>
                        <div class="media-info">
                            <span class="type-badge"><?php echo $item['type']; ?></span>
                            <div class="media-date"><?php echo friendly_date($item['created_at']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 我的收藏 -->
        <div id="panel-favs" class="tab-panel">
            <?php if (empty($favorites)): ?>
                <div class="empty-state">
                    <i class="fas fa-heart"></i>
                    <p>还没有收藏文章，浏览<a href="<?php echo SITE_URL; ?>/" style="color:var(--accent)">文章</a>时点击收藏吧</p>
                </div>
            <?php else: ?>
                <?php foreach ($favorites as $fav): ?>
                <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $fav['post_id']; ?>" class="fav-item">
                    <span class="fav-icon"><i class="fas fa-heart"></i></span>
                    <div class="fav-info">
                        <div class="fav-title"><?php echo clean($fav['title'] ?? '已删除的文章'); ?></div>
                        <div class="fav-date"><?php echo friendly_date($fav['created_at']); ?></div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:0.8rem;"></i>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 账号设置 -->
        <div id="panel-edit" class="tab-panel">
            <div class="edit-form">
                <h4 style="margin-bottom:20px;font-weight:600;"><i class="fas fa-user-edit" style="color:var(--primary-light);margin-right:8px;"></i>编辑资料</h4>
                <form id="profileForm" onsubmit="return saveProfile(event)">
                    <div class="form-group">
                        <label>昵称</label>
                        <input type="text" class="form-input" name="nickname" value="<?php echo clean($member['nickname']); ?>" placeholder="你的显示名称">
                    </div>
                    <div class="form-group">
                        <label>个人简介</label>
                        <textarea class="form-input" name="bio" placeholder="介绍一下自己..."><?php echo clean($member['bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>头像</label>
                        <input type="file" class="form-input" name="avatar" accept="image/*" style="padding:8px;">
                    </div>
                    <button type="submit" class="btn-save"><i class="fas fa-check"></i> 保存修改</button>
                </form>

                <hr style="border-color:var(--border);margin:28px 0;">

                <h4 style="margin-bottom:20px;font-weight:600;"><i class="fas fa-lock" style="color:var(--accent2);margin-right:8px;"></i>修改密码</h4>
                <form id="passwordForm" onsubmit="return changePassword(event)">
                    <div class="form-group">
                        <label>原密码</label>
                        <input type="password" class="form-input" name="old_password" placeholder="输入当前密码" required>
                    </div>
                    <div class="form-group">
                        <label>新密码</label>
                        <input type="password" class="form-input" name="new_password" placeholder="至少6个字符" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>确认新密码</label>
                        <input type="password" class="form-input" name="confirm_password" placeholder="再次输入新密码" required>
                    </div>
                    <button type="submit" class="btn-save"><i class="fas fa-key"></i> 修改密码</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function switchTab(name) {
        document.querySelectorAll('.tab-btn').forEach(function(btn, i) {
            var tabs = ['media', 'favs', 'edit'];
            btn.classList.toggle('active', tabs[i] === name);
        });
        document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
        var panel = document.getElementById('panel-' + name);
        if (panel) panel.classList.add('active');
    }

    function handleLogout() {
        if (!confirm('确定退出登录？')) return;
        fetch('<?php echo SITE_URL; ?>/member-api/logout')
        .then(function() { window.location.href = '<?php echo SITE_URL; ?>/'; })
        .catch(function() { window.location.href = '<?php echo SITE_URL; ?>/'; });
    }

    function saveProfile(e) {
        e.preventDefault();
        var form = e.target;
        var data = new FormData(form);
        fetch('<?php echo SITE_URL; ?>/member-api/profile', { method: 'POST', body: data })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) { alert('资料已更新'); location.reload(); }
            else { alert(res.error || '更新失败'); }
        });
        return false;
    }

    function changePassword(e) {
        e.preventDefault();
        var form = e.target;
        var data = new FormData(form);
        fetch('<?php echo SITE_URL; ?>/member-api/change-password', { method: 'POST', body: data })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) { alert('密码修改成功'); form.reset(); }
            else { alert(res.error || '修改失败'); }
        });
        return false;
    }
    </script>
</body>
</html>
