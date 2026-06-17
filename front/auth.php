<?php
/**
 * 前台用户登录/注册页面
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

$pageTitle = '登录 / 注册';
$siteName = get_option('site_name', SITE_NAME);

// 已登录则跳转
if (is_member_login()) {
    redirect(SITE_URL . '/profile');
}

$tab = get('tab', 'login');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($pageTitle); ?> - <?php echo clean($siteName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #050810;
            --card-bg: rgba(15, 20, 35, 0.8);
            --border: rgba(99, 102, 241, 0.12);
            --primary: #6366f1;
            --primary-light: #818cf8;
            --accent: #22d3ee;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --neon: #22d3ee;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-y: auto;
        }
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: float 8s ease-in-out infinite;
        }
        .bg-orb-1 { width: 400px; height: 400px; background: #6366f1; top: -100px; left: -100px; }
        .bg-orb-2 { width: 300px; height: 300px; background: #22d3ee; bottom: -50px; right: -50px; animation-delay: -3s; }
        .bg-orb-3 { width: 200px; height: 200px; background: #ec4899; top: 50%; left: 60%; animation-delay: -5s; }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 20px) scale(0.95); }
        }
        .auth-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        .auth-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            position: relative;
            overflow: hidden;
        }
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--primary));
        }
        .auth-brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .auth-brand .brand-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            margin-bottom: 16px;
        }
        .auth-brand h2 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff, var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .auth-brand p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 6px;
        }
        .auth-tabs {
            display: flex;
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 28px;
        }
        .auth-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-muted);
            border: none;
            background: none;
        }
        .auth-tab.active {
            background: linear-gradient(135deg, var(--primary), rgba(99,102,241,0.8));
            color: #fff;
            box-shadow: 0 4px 15px rgba(99,102,241,0.3);
        }
        .auth-form { display: none; }
        .auth-form.active { display: block; }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.3s;
            outline: none;
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        .form-input::placeholder { color: rgba(148,163,184,0.5); }
        .btn-submit {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), rgba(34,211,238,0.8));
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(99,102,241,0.35);
        }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }
        .form-footer a {
            color: var(--accent);
            text-decoration: none;
        }
        .form-footer a:hover { text-decoration: underline; }
        .form-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.82rem;
            color: #fca5a5;
            margin-bottom: 16px;
            display: none;
        }
        .form-error.show { display: block; }
        .form-success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.82rem;
            color: #86efac;
            margin-bottom: 16px;
            display: none;
        }
        .form-success.show { display: block; }
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .remember-row label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: var(--text-muted);
            cursor: pointer;
        }
        .remember-row input[type="checkbox"] {
            accent-color: var(--primary);
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--text-muted);
            font-size: 0.78rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        .back-home a {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .back-home a:hover { color: var(--accent); }
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="brand-icon"><i class="fas fa-brain"></i></div>
                <h2><?php echo clean($siteName); ?></h2>
                <p>智能体驱动的AI内容创作平台</p>
            </div>

            <div class="auth-tabs">
                <button class="auth-tab <?php echo $tab === 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">
                    <i class="fas fa-sign-in-alt"></i> 登录
                </button>
                <button class="auth-tab <?php echo $tab === 'register' ? 'active' : ''; ?>" onclick="switchTab('register')">
                    <i class="fas fa-user-plus"></i> 注册
                </button>
            </div>

            <div id="errorMsg" class="form-error"></div>
            <div id="successMsg" class="form-success"></div>

            <!-- 登录表单 -->
            <form id="loginForm" class="auth-form <?php echo $tab === 'login' ? 'active' : ''; ?>" onsubmit="return handleLogin(event)">
                <div class="form-group">
                    <label>用户名 / 邮箱</label>
                    <input type="text" class="form-input" name="login" placeholder="输入用户名或邮箱" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" class="form-input" name="password" placeholder="输入密码" required autocomplete="current-password">
                </div>
                <div class="remember-row">
                    <label><input type="checkbox" name="remember" value="1"> 记住我</label>
                </div>
                <button type="submit" class="btn-submit" id="loginBtn">登录</button>
                <div class="form-footer">
                    还没有账号？<a href="javascript:void(0)" onclick="switchTab('register')">立即注册</a>
                </div>
            </form>

            <!-- 注册表单 -->
            <form id="registerForm" class="auth-form <?php echo $tab === 'register' ? 'active' : ''; ?>" onsubmit="return handleRegister(event)">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" class="form-input" name="username" placeholder="3-20个字符" required minlength="3" maxlength="20" autocomplete="username">
                </div>
                <div class="form-group">
                    <label>邮箱</label>
                    <input type="email" class="form-input" name="email" placeholder="your@email.com" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label>昵称（选填）</label>
                    <input type="text" class="form-input" name="nickname" placeholder="显示名称" autocomplete="nickname">
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" class="form-input" name="password" placeholder="至少6个字符" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label>确认密码</label>
                    <input type="password" class="form-input" name="confirm_password" placeholder="再次输入密码" required minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="btn-submit" id="registerBtn">注册</button>
                <div class="form-footer">
                    已有账号？<a href="javascript:void(0)" onclick="switchTab('login')">立即登录</a>
                </div>
            </form>

            <div class="divider">或</div>

            <div class="back-home">
                <a href="<?php echo SITE_URL; ?>/"><i class="fas fa-arrow-left"></i> 返回首页</a>
            </div>
        </div>
    </div>

    <script>
    function switchTab(tab) {
        document.querySelectorAll('.auth-tab').forEach(function(t, i) {
            t.classList.toggle('active', (tab === 'login' && i === 0) || (tab === 'register' && i === 1));
        });
        document.getElementById('loginForm').classList.toggle('active', tab === 'login');
        document.getElementById('registerForm').classList.toggle('active', tab === 'register');
        hideMsg();
    }

    function showError(msg) {
        var el = document.getElementById('errorMsg');
        el.textContent = msg;
        el.classList.add('show');
        document.getElementById('successMsg').classList.remove('show');
    }

    function showSuccess(msg) {
        var el = document.getElementById('successMsg');
        el.textContent = msg;
        el.classList.add('show');
        document.getElementById('errorMsg').classList.remove('show');
    }

    function hideMsg() {
        document.getElementById('errorMsg').classList.remove('show');
        document.getElementById('successMsg').classList.remove('show');
    }

    function handleLogin(e) {
        e.preventDefault();
        var form = e.target;
        var data = new FormData(form);
        var btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span>登录中...';

        fetch('<?php echo SITE_URL; ?>/member-api/login', {
            method: 'POST',
            body: data
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            btn.textContent = '登录';
            if (res.error) {
                showError(res.error);
            } else {
                showSuccess(res.message || '登录成功');
                setTimeout(function() {
                    var from = new URLSearchParams(window.location.search).get('from');
                    window.location.href = from || '<?php echo SITE_URL; ?>/profile';
                }, 800);
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = '登录';
            showError('网络错误，请重试');
        });
        return false;
    }

    function handleRegister(e) {
        e.preventDefault();
        var form = e.target;
        var data = new FormData(form);
        var btn = document.getElementById('registerBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span>注册中...';

        fetch('<?php echo SITE_URL; ?>/member-api/register', {
            method: 'POST',
            body: data
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            btn.textContent = '注册';
            if (res.error) {
                showError(res.error);
            } else {
                showSuccess(res.message || '注册成功');
                setTimeout(function() {
                    window.location.href = '<?php echo SITE_URL; ?>/profile';
                }, 800);
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = '注册';
            showError('网络错误，请重试');
        });
        return false;
    }
    </script>
</body>
</html>
