<?php
/**
 * 个人资料
 */
$pageTitle = '个人资料';
$user = current_user();

// 处理更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');

    if ($action === 'profile') {
        $nickname = post('nickname');
        $email = post('email');

        if (!$nickname || !$email) {
            $error = '请填写完整信息';
        } else {
            db()->update('users', [
                'nickname' => $nickname,
                'email' => $email,
            ], 'id = ?', [$user['id']]);
            set_flash('success', '个人资料已更新');
            redirect(SITE_URL . '/admin/profile');
        }
    }

    if ($action === 'password') {
        $oldPass = post('old_password');
        $newPass = post('new_password');
        $confirmPass = post('confirm_password');

        if (!$oldPass || !$newPass || !$confirmPass) {
            $error = '请填写完整密码信息';
        } elseif (!password_verify($oldPass, $user['password'])) {
            $error = '原密码不正确';
        } elseif ($newPass !== $confirmPass) {
            $error = '两次输入的新密码不一致';
        } elseif (strlen($newPass) < 6) {
            $error = '新密码长度不能少于6位';
        } else {
            db()->update('users', [
                'password' => password_hash($newPass, PASSWORD_DEFAULT),
            ], 'id = ?', [$user['id']]);
            set_flash('success', '密码已修改');
            redirect(SITE_URL . '/admin/profile');
        }
    }

    if ($action === 'avatar' && !empty($_FILES['avatar'])) {
        $result = upload_file($_FILES['avatar'], 'avatars');
        if ($result['code'] === 1) {
            db()->update('users', ['avatar' => $result['url']], 'id = ?', [$user['id']]);
            set_flash('success', '头像已更新');
        } else {
            $error = $result['msg'];
        }
        redirect(SITE_URL . '/admin/profile');
    }
}

include __DIR__ . '/../admin/views/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user me-2"></i>个人资料</h1>
</div>

<?php if (get_flash('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo get_flash('success'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo clean($error); ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- 头像 -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5>头像</h5>
            </div>
            <div class="admin-card-body text-center">
                <div class="profile-avatar mb-3">
                    <?php if ($user['avatar']): ?>
                    <img src="<?php echo clean($user['avatar']); ?>" alt="头像" style="width:120px;height:120px;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                    <div class="avatar-placeholder-lg">
                        <i class="fas fa-user"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="avatar">
                    <input type="file" name="avatar" accept="image/*" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload me-1"></i>上传头像
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 基本信息 -->
    <div class="col-lg-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5>基本信息</h5>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="profile">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">用户名</label>
                            <input type="text" class="form-control" value="<?php echo clean($user['username']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">角色</label>
                            <input type="text" class="form-control" value="<?php echo $user['role']; ?>" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">昵称</label>
                            <input type="text" name="nickname" class="form-control" value="<?php echo clean($user['nickname']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">邮箱</label>
                            <input type="email" name="email" class="form-control" value="<?php echo clean($user['email']); ?>" required>
                        </div>
                    </div>
                    <div class="text-muted small mb-3">
                        <i class="fas fa-info-circle me-1"></i>注册时间: <?php echo format_date($user['created_at'], 'Y年m月d日 H:i'); ?>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>保存信息
                    </button>
                </form>
            </div>
        </div>

        <!-- 修改密码 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5>修改密码</h5>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="password">
                    <div class="mb-3">
                        <label class="form-label">原密码</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">新密码</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">确认新密码</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>修改密码
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../admin/views/footer.php'; ?>
