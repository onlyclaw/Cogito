<?php
/**
 * 404页面
 */
$siteName = get_option('site_name', SITE_NAME);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - 页面不存在 - <?php echo clean($siteName); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-code">404</div>
        <h2>页面不存在</h2>
        <p class="text-muted">抱歉，您访问的页面不存在或已被删除</p>
        <a href="<?php echo SITE_URL; ?>/" class="btn btn-primary mt-3">
            <i class="fas fa-home me-2"></i>返回首页
        </a>
    </div>
</body>
</html>
