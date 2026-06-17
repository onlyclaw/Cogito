<?php
/**
 * 标签管理
 */
$pageTitle = '标签管理';

// 处理添加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'add') {
    $name = post('name');
    $slug = post('slug');

    if (!$name) {
        $error = '请输入标签名称';
    } else {
        if (!$slug) $slug = slug($name);
        db()->insert('tags', ['name' => $name, 'slug' => $slug]);
        set_flash('success', '标签已添加');
        redirect(SITE_URL . '/admin/tags');
    }
}

// 删除
if (isset($_GET['delete']) && is_admin()) {
    $deleteId = (int)$_GET['delete'];
    db()->delete('tags', 'id = ?', [$deleteId]);
    db()->delete('post_tags', 'tag_id = ?', [$deleteId]);
    set_flash('success', '标签已删除');
    redirect(SITE_URL . '/admin/tags');
}

$tags = db()->fetchAll("
    SELECT t.*, (SELECT COUNT(*) FROM post_tags WHERE tag_id = t.id) as post_count
    FROM tags t ORDER BY post_count DESC, name ASC
");

include __DIR__ . '/../admin/views/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tags me-2"></i>标签管理</h1>
</div>

<div class="row">
    <!-- 添加标签 -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5>添加标签</h5>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?php echo clean($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">标签名称 *</label>
                        <input type="text" name="name" class="form-control" required placeholder="如: PHP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">别名</label>
                        <input type="text" name="slug" class="form-control" placeholder="留空自动生成">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-1"></i>添加标签
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 标签列表 -->
    <div class="col-lg-8">
        <?php if (get_flash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo get_flash('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-body">
                <?php if (empty($tags)): ?>
                <div class="empty-state p-4">
                    <i class="fas fa-tags"></i>
                    <h5>暂无标签</h5>
                </div>
                <?php else: ?>
                <div class="tag-cloud-admin">
                    <?php foreach ($tags as $tag): ?>
                    <div class="tag-item-admin">
                        <span class="tag-name">#<?php echo clean($tag['name']); ?></span>
                        <span class="tag-count"><?php echo $tag['post_count']; ?>篇</span>
                        <a href="?delete=<?php echo $tag['id']; ?>" class="tag-delete" title="删除"
                            onclick="return confirm('确定删除标签 #<?php echo clean($tag['name']); ?>？')">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../admin/views/footer.php'; ?>
