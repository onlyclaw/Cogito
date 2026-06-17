<?php
/**
 * 系统设置 - Boke 2.0
 */
$pageTitle = '系统设置';

// 处理保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    $fields = ['site_name', 'site_desc', 'site_keywords', 'admin_email', 'posts_per_page', 'comment_check',
               'ai_api_key', 'ai_api_url', 'ai_model', 'ai_enabled', 'runway_api_key'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            set_option($field, post($field));
        }
    }
    set_flash('success', '设置已保存');
    redirect(SITE_URL . '/admin/settings');
}

include __DIR__ . '/../admin/views/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cog me-2"></i>系统设置</h1>
</div>

<?php if (get_flash('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo get_flash('success'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <form method="POST">
            <!-- 站点设置 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5><i class="fas fa-globe me-2"></i>站点设置</h5>
                </div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">站点名称</label>
                        <input type="text" name="site_name" class="form-control" value="<?php echo clean(get_option('site_name')); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">站点描述</label>
                        <textarea name="site_desc" class="form-control" rows="2"><?php echo clean(get_option('site_desc')); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">站点关键词</label>
                        <input type="text" name="site_keywords" class="form-control" value="<?php echo clean(get_option('site_keywords')); ?>"
                            placeholder="多个关键词用逗号分隔">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">管理员邮箱</label>
                        <input type="email" name="admin_email" class="form-control" value="<?php echo clean(get_option('admin_email')); ?>">
                    </div>
                </div>
            </div>

            <!-- AI 设置 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5><i class="fas fa-robot me-2"></i>AI 智能助手设置</h5>
                </div>
                <div class="admin-card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="ai_enabled" value="1" id="aiEnabled"
                            <?php echo get_option('ai_enabled', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="aiEnabled">启用 AI 助手</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="password" name="ai_api_key" class="form-control" value="<?php echo clean(get_option('ai_api_key')); ?>"
                            placeholder="留空使用内置智能回复">
                        <small class="text-muted">支持 OpenAI、Claude 等 API，留空则使用内置智能回复</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API URL</label>
                        <input type="text" name="ai_api_url" class="form-control" value="<?php echo clean(get_option('ai_api_url')); ?>"
                            placeholder="https://api.openai.com/v1/chat/completions">
                        <small class="text-muted">OpenAI: https://api.openai.com/v1/chat/completions</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">模型</label>
                        <select name="ai_model" class="form-select">
                            <option value="gpt-3.5-turbo" <?php echo get_option('ai_model') === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo</option>
                            <option value="gpt-4" <?php echo get_option('ai_model') === 'gpt-4' ? 'selected' : ''; ?>>GPT-4</option>
                            <option value="claude-3-haiku" <?php echo get_option('ai_model') === 'claude-3-haiku' ? 'selected' : ''; ?>>Claude 3 Haiku</option>
                            <option value="claude-3-sonnet" <?php echo get_option('ai_model') === 'claude-3-sonnet' ? 'selected' : ''; ?>>Claude 3 Sonnet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Runway ML API Key（视频生成）</label>
                        <input type="password" name="runway_api_key" class="form-control" value="<?php echo clean(get_option('runway_api_key')); ?>"
                            placeholder="留空使用演示模式">
                        <small class="text-muted">配置后可使用 Runway ML Gen-3 生成真实视频。获取地址：<a href="https://runwayml.com" target="_blank">runwayml.com</a></small>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        未配置 API 时，AI 助手将使用内置智能回复功能，支持文章总结、推荐、代码解释等基础功能。
                    </div>
                </div>
            </div>

            <!-- 阅读设置 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5><i class="fas fa-book-reader me-2"></i>阅读设置</h5>
                </div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">每页显示文章数</label>
                        <input type="number" name="posts_per_page" class="form-control" style="width:150px;"
                            value="<?php echo get_option('posts_per_page', '8'); ?>">
                    </div>
                </div>
            </div>

            <!-- 评论设置 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5><i class="fas fa-comments me-2"></i>评论设置</h5>
                </div>
                <div class="admin-card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="comment_check" value="1" id="commentCheck"
                            <?php echo get_option('comment_check', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="commentCheck">开启评论审核（新评论需要审核后才显示）</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save me-2"></i>保存设置
            </button>
        </form>
    </div>

    <!-- 系统信息 -->
    <div class="col-lg-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5><i class="fas fa-info-circle me-2"></i>系统信息</h5>
            </div>
            <div class="admin-card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">PHP版本</td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">MySQL版本</td>
                        <td><?php echo db()->fetchColumn("SELECT VERSION()"); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">服务器软件</td>
                        <td><?php echo server('SERVER_SOFTWARE'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">上传限制</td>
                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">内存限制</td>
                        <td><?php echo ini_get('memory_limit'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">博客版本</td>
                        <td><span class="badge bg-primary">2.0.0</span> <small class="text-muted">AI Edition</small></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="fas fa-database me-2"></i>数据统计</h5>
            </div>
            <div class="admin-card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">文章数</td>
                        <td><?php echo db()->count('posts'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">评论数</td>
                        <td><?php echo db()->count('comments'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">分类数</td>
                        <td><?php echo db()->count('categories'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">标签数</td>
                        <td><?php echo db()->count('tags'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">收藏数</td>
                        <td><?php echo db()->count('favorites'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../admin/views/footer.php'; ?>
