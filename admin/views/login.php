<?php
/**
 * 后台登录页 - Boke 2.0
 */
$siteName = get_option('site_name', SITE_NAME);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - <?php echo clean($siteName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
            top: -200px;
            right: -200px;
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(139,92,246,0.1) 0%, transparent 70%);
            bottom: -100px;
            left: -100px;
        }
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }
        .login-header {
            text-align: center;
            padding: 40px 30px 30px;
        }
        .login-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px rgba(99,102,241,0.4);
        }
        .login-logo i { font-size: 28px; color: #fff; }
        .login-header h3 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .login-header p {
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
        }
        .login-body {
            padding: 0 30px 30px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .input-group {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s;
        }
        .input-group:focus-within {
            border-color: #6366f1;
            background: rgba(99,102,241,0.05);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
        }
        .input-group-text {
            background: none;
            border: none;
            color: rgba(255,255,255,0.4);
            padding: 12px 16px;
        }
        .input-group .form-control {
            background: none;
            border: none;
            color: #fff;
            padding: 12px 16px 12px 0;
            font-size: 0.95rem;
        }
        .input-group .form-control::placeholder { color: rgba(255,255,255,0.3); }
        .input-group .form-control:focus { box-shadow: none; }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(99,102,241,0.4);
        }
        .login-footer {
            text-align: center;
            padding-top: 16px;
        }
        .login-footer a {
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        .login-footer a:hover { color: rgba(255,255,255,0.7); }
        .alert {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.2);
            color: #fca5a5;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 0.88rem;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>后台管理</h3>
                <p><?php echo clean($siteName); ?> · Boke 2.0</p>
            </div>
            <div class="login-body">
                <?php if (!empty($loginError)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $loginError; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo SITE_URL; ?>/admin/">
                    <div class="form-group">
                        <label>用户名</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="请输入用户名" required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>密码</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="请输入密码" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>登录
                    </button>
                </form>
                <div class="login-footer">
                    <a href="<?php echo SITE_URL; ?>/">
                        <i class="fas fa-arrow-left me-1"></i>返回首页
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
