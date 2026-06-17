<?php
/**
 * 后台管理头部模板 - Boke 2.0
 */
$siteName = get_option('site_name', SITE_NAME);
$currentUser = current_user();
$currentPage = basename($_SERVER['REQUEST_URI'], '.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? clean($pageTitle) . ' - 后台管理' : '后台管理 - ' . clean($siteName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
</head>
<body>

<!-- 侧边栏 -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <a href="<?php echo SITE_URL; ?>/admin/">
            <span class="brand-icon"><i class="fas fa-feather-alt"></i></span>
            <?php echo clean($siteName); ?>
        </a>
    </div>
    <div class="sidebar-menu">
        <div class="menu-label">主要</div>
        <a href="<?php echo SITE_URL; ?>/admin/dashboard" class="menu-item <?php echo $currentPage === 'index' || $currentPage === 'dashboard' || $currentPage === '' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i><span>控制台</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/posts" class="menu-item <?php echo in_array($currentPage, ['posts', 'post-edit', 'post-add']) ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i><span>文章管理</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/post-add" class="menu-item <?php echo $currentPage === 'post-add' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i><span>写文章</span>
        </a>

        <div class="menu-label">内容</div>
        <a href="<?php echo SITE_URL; ?>/admin/categories" class="menu-item <?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
            <i class="fas fa-folder"></i><span>分类管理</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/tags" class="menu-item <?php echo $currentPage === 'tags' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i><span>标签管理</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/comments" class="menu-item <?php echo $currentPage === 'comments' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i><span>评论管理</span>
            <?php
            $pendingCount = is_installed() ? db()->count('comments', "status = 'pending'") : 0;
            if ($pendingCount > 0): ?>
            <span class="badge"><?php echo $pendingCount; ?></span>
            <?php endif; ?>
        </a>

        <div class="menu-label">系统</div>
        <a href="<?php echo SITE_URL; ?>/admin/settings" class="menu-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i><span>系统设置</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/profile" class="menu-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i><span>个人资料</span>
        </a>
    </div>
    <div class="sidebar-footer">
        <a href="<?php echo SITE_URL; ?>/" target="_blank" class="menu-item">
            <i class="fas fa-external-link-alt"></i><span>访问前台</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/logout" class="menu-item">
            <i class="fas fa-sign-out-alt"></i><span>退出登录</span>
        </a>
    </div>
</aside>

<!-- 顶部导航 -->
<nav class="admin-nav">
    <div class="admin-nav-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="<?php echo SITE_URL; ?>/admin/" class="admin-logo">
            <span class="brand-icon"><i class="fas fa-feather-alt"></i></span>
            <?php echo clean($siteName); ?>
            <span style="font-size:0.7rem;background:var(--admin-primary);color:#fff;padding:2px 8px;border-radius:6px;font-weight:600;">2.0</span>
        </a>
    </div>
    <div class="admin-nav-right">
        <a href="<?php echo SITE_URL; ?>/" target="_blank" class="nav-link-icon" title="访问前台">
            <i class="fas fa-external-link-alt"></i>
        </a>
        <div class="nav-user dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--admin-primary),#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;">
                    <i class="fas fa-user"></i>
                </div>
                <?php echo clean($currentUser['nickname'] ?: $currentUser['username']); ?>
                <i class="fas fa-chevron-down" style="font-size:0.7rem;"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" style="border:1px solid var(--admin-border);border-radius:12px;padding:8px;">
                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/profile" style="border-radius:8px;padding:8px 12px;"><i class="fas fa-user me-2"></i>个人资料</a></li>
                <li><hr class="dropdown-divider" style="margin:4px 0;border-color:var(--admin-border);"></li>
                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/admin/logout" style="border-radius:8px;padding:8px 12px;"><i class="fas fa-sign-out-alt me-2"></i>退出登录</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- 主内容区 -->
<div class="admin-main">
    <div class="admin-content">
