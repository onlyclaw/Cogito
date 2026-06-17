<?php
/**
 * 文章管理
 */
$pageTitle = '文章管理';
$page = max(1, (int)get('page', 1));
$status = get('status', '');
$keyword = get('q', '');

// 构建查询条件
$where = "1=1";
$params = [];

if ($status && in_array($status, ['published', 'draft', 'trash'])) {
    $where .= " AND p.status = ?";
    $params[] = $status;
}

if ($keyword) {
    $where .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}

$count = db()->fetchColumn("SELECT COUNT(*) FROM posts p WHERE $where", $params);
$pagination = paginate($count, $page);

$orderBy = $status === 'trash' ? 'p.updated_at DESC' : 'p.created_at DESC';
$posts = db()->fetchAll("
    SELECT p.*, c.name as category_name, u.nickname as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE $where
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
", array_merge($params, [$pagination['page_size'], $pagination['offset']]));

// 删除操作
if (isset($_GET['delete']) && is_admin()) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId) {
        db()->delete('posts', 'id = ?', [$deleteId]);
        db()->delete('post_tags', 'post_id = ?', [$deleteId]);
        set_flash('success', '文章已删除');
        redirect(SITE_URL . '/admin/posts');
    }
}

// 恢复操作
if (isset($_GET['restore']) && is_admin()) {
    $restoreId = (int)$_GET['restore'];
    if ($restoreId) {
        db()->update('posts', ['status' => 'draft'], 'id = ?', [$restoreId]);
        set_flash('success', '文章已恢复到草稿');
        redirect(SITE_URL . '/admin/posts');
    }
}

include __DIR__ . '/../admin/views/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-newspaper me-2"></i>文章管理</h1>
        <p class="text-muted mb-0">共 <?php echo $count; ?> 篇文章</p>
    </div>
    <a href="<?php echo SITE_URL; ?>/admin/post-add" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>写文章
    </a>
</div>

<!-- 筛选栏 -->
<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">搜索</label>
                <input type="text" name="q" class="form-control" placeholder="搜索文章标题或内容..." value="<?php echo clean($keyword); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">状态</label>
                <select name="status" class="form-select">
                    <option value="">全部状态</option>
                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已发布</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                    <option value="trash" <?php echo $status === 'trash' ? 'selected' : ''; ?>>已删除</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>搜索
                </button>
            </div>
            <div class="col-md-2">
                <a href="<?php echo SITE_URL; ?>/admin/posts" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-redo me-1"></i>重置
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (get_flash('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo get_flash('success'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- 文章列表 -->
<div class="admin-card">
    <div class="admin-card-body p-0">
        <?php if (empty($posts)): ?>
        <div class="empty-state p-5">
            <i class="fas fa-inbox"></i>
            <h5>暂无文章</h5>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                        <th>标题</th>
                        <th>分类</th>
                        <th>状态</th>
                        <th>浏览</th>
                        <th>时间</th>
                        <th width="150">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><input type="checkbox" class="form-check-input post-check" value="<?php echo $post['id']; ?>"></td>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/admin/post-edit?id=<?php echo $post['id']; ?>" class="text-decoration-none fw-medium">
                                <?php if ($post['is_top']): ?>
                                <i class="fas fa-thumbtack text-danger me-1" title="置顶"></i>
                                <?php endif; ?>
                                <?php echo clean(cut_str($post['title'], 50)); ?>
                            </a>
                        </td>
                        <td><span class="badge bg-light text-dark"><?php echo clean($post['category_name'] ?: '未分类'); ?></span></td>
                        <td>
                            <?php if ($post['status'] === 'published'): ?>
                            <span class="badge bg-success">已发布</span>
                            <?php elseif ($post['status'] === 'draft'): ?>
                            <span class="badge bg-secondary">草稿</span>
                            <?php else: ?>
                            <span class="badge bg-danger">已删除</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $post['views']; ?></td>
                        <td><?php echo format_date($post['created_at']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo SITE_URL; ?>/admin/post-edit?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary" title="编辑">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $post['id']; ?>" class="btn btn-outline-info" target="_blank" title="查看">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($post['status'] === 'trash'): ?>
                                <a href="?restore=<?php echo $post['id']; ?>" class="btn btn-outline-warning" title="恢复" onclick="return confirm('确定恢复？')">
                                    <i class="fas fa-undo"></i>
                                </a>
                                <?php else: ?>
                                <a href="?delete=<?php echo $post['id']; ?>" class="btn btn-outline-danger" title="删除" onclick="return confirm('确定删除？')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php echo render_pagination($pagination, SITE_URL . '/admin/posts'); ?>

<?php
$extraJs = "
document.getElementById('checkAll')?.addEventListener('change', function() {
    document.querySelectorAll('.post-check').forEach(cb => cb.checked = this.checked);
});
";
include __DIR__ . '/../admin/views/footer.php';
?>
