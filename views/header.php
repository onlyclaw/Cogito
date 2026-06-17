<?php
$siteName = get_option('site_name', SITE_NAME);
$siteDesc = get_option('site_desc', SITE_DESC);
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// 去掉项目路径前缀
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath && strpos($currentUri, $basePath) === 0) {
    $currentUri = substr($currentUri, strlen($basePath));
}
$currentUri = '/' . ltrim($currentUri, '/');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo clean($siteDesc); ?>">
    <title><?php echo isset($pageTitle) ? clean($pageTitle) . ' - ' . clean($siteName) : clean($siteName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="icon" href="data:,">
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>/">
            <span class="brand-icon"><i class="fas fa-brain"></i></span>
            <?php echo clean($siteName); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link <?php echo $currentUri === '/' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/">首页</a></li>
                <li class="nav-item"><a class="nav-link highlight <?php echo $currentUri === '/pipeline' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/pipeline">闭环创作</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $currentUri === '/coding' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/coding"><i class="fas fa-code me-1"></i>编程</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $currentUri === '/agents' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/agents">智能体</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $currentUri === '/media' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/media">媒体工坊</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/search"><i class="fas fa-search"></i></a></li>
                <?php if (is_member_login()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/profile" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-user-circle"></i> <?php echo clean($_SESSION['member_nickname'] ?? $_SESSION['member_username'] ?? '用户'); ?>
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-auth-btn" href="<?php echo SITE_URL; ?>/auth?tab=login"><i class="fas fa-sign-in-alt"></i> 登录</a></li>
                <li class="nav-item"><a class="nav-register-btn" href="<?php echo SITE_URL; ?>/auth?tab=register"><i class="fas fa-user-plus"></i> 注册</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="main-content">
