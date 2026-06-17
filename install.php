<?php
/**
 * 安装页面
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = post('db_host', '127.0.0.1');
    $port = post('db_port', '3306');
    $name = post('db_name', 'boke');
    $user = post('db_user', 'root');
    $pass = post('db_pass', 'root');
    $siteName = post('site_name', '我的博客');
    $adminUser = post('admin_user', 'admin');
    $adminPass = post('admin_pass', 'admin123');
    $adminEmail = post('admin_email', 'admin@example.com');

    try {
        // 连接数据库
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");

        // 执行SQL文件
        $sql = file_get_contents(__DIR__ . '/init.sql');
        // 移除CREATE DATABASE和USE语句（已执行）
        $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
        $sql = preg_replace('/USE `.*?`;/i', '', $sql);

        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }

        // 更新管理员密码
        $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ?, email = ?, nickname = ? WHERE username = ?")
            ->execute([$hashedPass, $adminEmail, '管理员', $adminUser]);

        // 更新站点配置
        $pdo->prepare("UPDATE options SET option_value = ? WHERE option_key = 'site_name'")
            ->execute([$siteName]);
        $pdo->prepare("UPDATE options SET option_value = ? WHERE option_key = 'site_url'")
            ->execute(['http://' . $_SERVER['HTTP_HOST'] . '/boke']);

        // 更新config.php中的数据库配置
        $configContent = file_get_contents(__DIR__ . '/config.php');
        $configContent = preg_replace("/define\('DB_HOST',.*?\)/", "define('DB_HOST', '$host')", $configContent);
        $configContent = preg_replace("/define\('DB_PORT',.*?\)/", "define('DB_PORT', '$port')", $configContent);
        $configContent = preg_replace("/define\('DB_NAME',.*?\)/", "define('DB_NAME', '$name')", $configContent);
        $configContent = preg_replace("/define\('DB_USER',.*?\)/", "define('DB_USER', '$user')", $configContent);
        $configContent = preg_replace("/define\('DB_PASS',.*?\)/", "define('DB_PASS', '$pass')", $configContent);
        $configContent = preg_replace("/define\('SITE_NAME',.*?\)/", "define('SITE_NAME', '$siteName')", $configContent);
        file_put_contents(__DIR__ . '/config.php', $configContent);

        $success = true;
        $message = '安装成功！';
    } catch (PDOException $e) {
        $message = '安装失败: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装博客系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .install-card { max-width: 500px; margin: 0 auto; border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .install-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px; border-radius: 16px 16px 0 0; text-align: center; }
        .install-header i { font-size: 48px; margin-bottom: 15px; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .btn-install { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px; font-size: 16px; color: #fff; }
        .btn-install:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card install-card">
            <div class="install-header">
                <i class="fas fa-blog"></i>
                <h3>博客系统安装</h3>
                <p class="mb-0">请填写以下信息完成安装</p>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                        <?php echo clean($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 60px;"></i>
                        <h4 class="mt-3">安装完成！</h4>
                        <p class="text-muted">默认账号: admin / admin123</p>
                        <div class="mt-3">
                            <a href="<?php echo SITE_URL; ?>/" class="btn btn-primary me-2">访问前台</a>
                            <a href="<?php echo SITE_URL; ?>/admin/" class="btn btn-outline-primary">进入后台</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <h6 class="text-muted mb-3"><i class="fas fa-database me-2"></i>数据库配置</h6>
                        <div class="row mb-3">
                            <div class="col-8">
                                <label class="form-label">数据库主机</label>
                                <input type="text" name="db_host" class="form-control" value="127.0.0.1">
                            </div>
                            <div class="col-4">
                                <label class="form-label">端口</label>
                                <input type="text" name="db_port" class="form-control" value="3306">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">数据库名称</label>
                            <input type="text" name="db_name" class="form-control" value="boke">
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">数据库用户名</label>
                                <input type="text" name="db_user" class="form-control" value="root">
                            </div>
                            <div class="col-6">
                                <label class="form-label">数据库密码</label>
                                <input type="password" name="db_pass" class="form-control" value="root">
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3"><i class="fas fa-cog me-2"></i>站点配置</h6>
                        <div class="mb-3">
                            <label class="form-label">站点名称</label>
                            <input type="text" name="site_name" class="form-control" value="我的博客">
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3"><i class="fas fa-user-shield me-2"></i>管理员账号</h6>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">用户名</label>
                                <input type="text" name="admin_user" class="form-control" value="admin">
                            </div>
                            <div class="col-6">
                                <label class="form-label">密码</label>
                                <input type="password" name="admin_pass" class="form-control" value="admin123">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">邮箱</label>
                            <input type="email" name="admin_email" class="form-control" value="admin@example.com">
                        </div>

                        <button type="submit" class="btn btn-install text-white w-100">
                            <i class="fas fa-rocket me-2"></i>开始安装
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
