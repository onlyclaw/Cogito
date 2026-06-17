<?php
/**
 * 评论管理
 */
$pageTitle = '评论管理';
$page = max(1, (int)get('page', 1));
$status = get('status', '');

// 构建查询
$where = "1=1";
$params = [];
if ($status && in_array($status, ['approved', 'pending', 'spam'])) {
    $where .= " AND c.status = ?";
    $params[] = $status;
}

$count = db()->fetchColumn("SELECT COUNT(*) FROM comments c WHERE $where", $params);
$pagination = paginate($count, $page);

$comments = db()->fetchAll("
    SELECT c.*, p.title as post_title
    FROM comments c
    LEFT JOIN posts p ON c.post_id = p.id
    WHERE $where
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
", array_merge($params, [$pagination['page_size'], $pagination['offset']]));

// 批量操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('batch_action');
    $ids = post('ids') ?: [];

    if ($action && !empty($ids)) {
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        switch ($action) {
            case 'approve':
                db()->query("UPDATE comments SET status = 'approved' WHERE id IN ($placeholders)", $ids);
                set_flash('success', '已审核通过 ' . count($ids) . ' 条评论');
                break;
            case 'pending':
                db()->query("UPDATE comments SET status = 'pending' WHERE id IN ($placeholders)", $ids);
                set_flash('success', '已设为待审核 ' . count($ids) . ' 条评论');
                break;
            case 'spam':
                db()->query("UPDATE comments SET status = 'spam' WHERE id IN ($placeholders)", $ids);
                set_flash('success', '已标记为垃圾评论 ' . count($ids) . ' 条');
                break;
            case 'delete':
                db()->query("DELETE FROM comments WHERE id IN ($placeholders)", $ids);
                set_flash('success', '已删除 ' . count($ids) . ' 条评论');
                break;
        }
        redirect(SITE_URL . '/admin/comments' . ($status ? '?status=' . $status : ''));
    }
}

// 单条操作
if (isset($_GET['approve'])) {
    db()->update('comments', ['status' => 'approved'], 'id = ?', [(int)$_GET['approve']]);
    set_flash('success', '评论已审核通过');
    redirect(SITE_URL . '/admin/comments');
}
if (isset($_GET['spam'])) {
    db()->update('comments', ['status' => 'spam'], 'id = ?', [(int)$_GET['spam']]);
    set_flash('success', '已标记为垃圾评论');
    redirect(SITE_URL . '/admin/comments');
}
if (isset($_GET['delete']) && is_admin()) {
    db()->delete('comments', 'id = ?', [(int)$_GET['delete']]);
    set_flash('success', '评论已删除');
    redirect(SITE_URL . '/admin/comments');
}

include __DIR__ . '/../admin/views/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-comments me-2"></i>评论管理</h1>
</div>

<!-- 状态筛选 -->
<div class="mb-4">
    <div class="btn-group">
        <a href="<?php echo SITE_URL; ?>/admin/comments" class="btn btn-<?php echo !$status ? 'primary' : 'outline-primary'; ?>">
            全部 <span class="badge bg-white text-primary"><?php echo db()->count('comments'); ?></span>
        </a>
        <a href="?status=pending" class="btn btn-<?php echo $status === 'pending' ? 'warning' : 'outline-warning'; ?>">
            待审核 <span class="badge bg-white text-warning"><?php echo db()->count('comments', "status = 'pending'"); ?></span>
        </a>
        <a href="?status=approved" class="btn btn-<?php echo $status === 'approved' ? 'success' : 'outline-success'; ?>">
            已通过 <span class="badge bg-white text-success"><?php echo db()->count('comments', "status = 'approved'"); ?></span>
        </a>
        <a href="?status=spam" class="btn btn-<?php echo $status === 'spam' ? 'danger' : 'outline-danger'; ?>">
            垃圾 <span class="badge bg-white text-danger"><?php echo db()->count('comments', "status = 'spam'"); ?></span>
        </a>
    </div>
</div>

<?php if (get_flash('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo get_flash('success'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- 批量操作表单 -->
<form method="POST" id="commentForm">
    <input type="hidden" name="batch_action" id="batchAction">

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="d-flex align-items-center">
                <input type="checkbox" class="form-check-input me-2" id="checkAll">
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="batchSelect">
                    <option value="">批量操作</option>
                    <option value="approve">审核通过</option>
                    <option value="pending">设为待审核</option>
                    <option value="spam">标记为垃圾</option>
                    <option value="delete">删除</option>
                </select>
                <button type="button" class="btn btn-sm btn-primary" onclick="doBatch()">执行</button>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <?php if (empty($comments)): ?>
            <div class="empty-state p-5">
                <i class="fas fa-comments"></i>
                <h5>暂无评论</h5>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" class="form-check-input"></th>
                            <th>评论者</th>
                            <th>内容</th>
                            <th>文章</th>
                            <th>状态</th>
                            <th>时间</th>
                            <th width="120">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input comment-check" value="<?php echo $comment['id']; ?>"></td>
                            <td>
                                <strong><?php echo clean($comment['nickname']); ?></strong>
                                <br><small class="text-muted"><?php echo clean($comment['email']); ?></small>
                            </td>
                            <td><?php echo clean(cut_str($comment['content'], 80)); ?></td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $comment['post_id']; ?>" target="_blank">
                                    <?php echo clean(cut_str($comment['post_title'] ?? '已删除', 30)); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($comment['status'] === 'approved'): ?>
                                <span class="badge bg-success">已通过</span>
                                <?php elseif ($comment['status'] === 'pending'): ?>
                                <span class="badge bg-warning text-dark">待审核</span>
                                <?php else: ?>
                                <span class="badge bg-danger">垃圾</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo friendly_date($comment['created_at']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($comment['status'] !== 'approved'): ?>
                                    <a href="?approve=<?php echo $comment['id']; ?>" class="btn btn-outline-success" title="通过">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($comment['status'] !== 'spam'): ?>
                                    <a href="?spam=<?php echo $comment['id']; ?>" class="btn btn-outline-warning" title="垃圾">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $comment['id']; ?>" class="btn btn-outline-danger" title="删除"
                                        onclick="return confirm('确定删除？')">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
</form>

<?php echo render_pagination($pagination, SITE_URL . '/admin/comments'); ?>

<?php
$extraJs = "
document.getElementById('checkAll')?.addEventListener('change', function() {
    document.querySelectorAll('.comment-check').forEach(cb => cb.checked = this.checked);
});

function doBatch() {
    const action = document.getElementById('batchSelect').value;
    if (!action) return alert('请选择操作');
    const ids = [];
    document.querySelectorAll('.comment-check:checked').forEach(cb => ids.push(cb.value));
    if (!ids.length) return alert('请选择要操作的评论');
    document.getElementById('batchAction').value = action;
    const form = document.getElementById('commentForm');
    // 创建隐藏input传递ids
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    if (action === 'delete' && !confirm('确定删除选中的评论？')) return;
    form.submit();
}
";
include __DIR__ . '/../admin/views/footer.php';
?>
