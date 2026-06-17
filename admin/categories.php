<?php
/**
 * 分类管理
 */
$pageTitle = '分类管理';

// 处理添加/编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    $name = post('name');
    $slug = post('slug');
    $description = post('description');
    $sortOrder = (int)post('sort_order', 0);

    if (!$name) {
        $error = '请输入分类名称';
    } else {
        if (!$slug) $slug = slug($name);

        if ($action === 'edit') {
            $editId = (int)post('id');
            db()->update('categories', [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sortOrder,
            ], 'id = ?', [$editId]);
            set_flash('success', '分类已更新');
        } else {
            db()->insert('categories', [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sortOrder,
            ]);
            set_flash('success', '分类已添加');
        }
        redirect(SITE_URL . '/admin/categories');
    }
}

// 删除
if (isset($_GET['delete']) && is_admin()) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId > 1) {
        db()->delete('categories', 'id = ?', [$deleteId]);
        db()->update('posts', ['category_id' => 1], 'category_id = ?', [$deleteId]);
        set_flash('success', '分类已删除');
    }
    redirect(SITE_URL . '/admin/categories');
}

$categories = db()->fetchAll("
    SELECT c.*, (SELECT COUNT(*) FROM posts WHERE category_id = c.id) as post_count
    FROM categories c ORDER BY sort_order ASC, id ASC
");

// 编辑模式
$editCat = null;
if (isset($_GET['edit'])) {
    $editCat = db()->fetchOne("SELECT * FROM categories WHERE id = ?", [(int)$_GET['edit']]);
}

include __DIR__ . '/../admin/views/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-folder me-2"></i>分类管理</h1>
</div>

<div class="row">
    <!-- 添加/编辑表单 -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5><?php echo $editCat ? '编辑分类' : '添加分类'; ?></h5>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?php echo clean($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editCat ? 'edit' : 'add'; ?>">
                    <?php if ($editCat): ?>
                    <input type="hidden" name="id" value="<?php echo $editCat['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">分类名称 *</label>
                        <input type="text" name="name" class="form-control" required
                            value="<?php echo clean($editCat['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">别名</label>
                        <input type="text" name="slug" class="form-control" placeholder="留空自动生成"
                            value="<?php echo clean($editCat['slug'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo clean($editCat['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">排序</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $editCat['sort_order'] ?? 0; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i><?php echo $editCat ? '更新' : '添加'; ?>
                    </button>
                    <?php if ($editCat): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/categories" class="btn btn-outline-secondary w-100 mt-2">取消编辑</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- 分类列表 -->
    <div class="col-lg-8">
        <?php if (get_flash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo get_flash('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>名称</th>
                                <th>别名</th>
                                <th>文章数</th>
                                <th>排序</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td>
                                    <strong><?php echo clean($cat['name']); ?></strong>
                                    <?php if ($cat['description']): ?>
                                    <br><small class="text-muted"><?php echo clean($cat['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo clean($cat['slug']); ?></code></td>
                                <td><span class="badge bg-primary"><?php echo $cat['post_count']; ?></span></td>
                                <td><?php echo $cat['sort_order']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-outline-primary" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($cat['id'] > 1): ?>
                                        <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-outline-danger" title="删除"
                                            onclick="return confirm('删除分类后，该分类下的文章将移至未分类，确定删除？')">
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
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../admin/views/footer.php'; ?>
