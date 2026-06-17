<?php
/**
 * 后台控制台
 */
$pageTitle = '控制台';

// 统计数据
$totalPosts = db()->count('posts', "status = 'published'");
$totalDrafts = db()->count('posts', "status = 'draft'");
$totalComments = db()->count('comments');
$pendingComments = db()->count('comments', "status = 'pending'");
$totalViews = db()->fetchColumn("SELECT IFNULL(SUM(views), 0) FROM posts");
$totalUsers = db()->count('users');

// 今日数据
$today = date('Y-m-d');
$todayPosts = db()->count('posts', "DATE(created_at) = ?", [$today]);
$todayComments = db()->count('comments', "DATE(created_at) = ?", [$today]);
$todayViews = db()->fetchColumn("SELECT COUNT(*) FROM views WHERE DATE(created_at) = ?", [$today]);

// 最近文章
$recentPosts = db()->fetchAll("SELECT p.*, u.nickname as author_name FROM posts p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");

// 最近评论
$recentComments = db()->fetchAll("SELECT c.*, p.title as post_title FROM comments c LEFT JOIN posts p ON c.post_id = p.id ORDER BY c.created_at DESC LIMIT 5");

include __DIR__ . '/../admin/views/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt me-2"></i>控制台</h1>
    <p class="text-muted">欢迎回来，<?php echo clean($currentUser['nickname'] ?: $currentUser['username']); ?>！</p>
</div>

<!-- 统计卡片 -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
            <div class="stat-info">
                <div class="stat-num"><?php echo $totalPosts; ?></div>
                <div class="stat-label">已发布文章</div>
            </div>
            <div class="stat-extra">
                <small>今日新增: <?php echo $todayPosts; ?></small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-comments"></i></div>
            <div class="stat-info">
                <div class="stat-num"><?php echo $totalComments; ?></div>
                <div class="stat-label">评论总数</div>
            </div>
            <div class="stat-extra">
                <small>待审核: <?php echo $pendingComments; ?></small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-eye"></i></div>
            <div class="stat-info">
                <div class="stat-num"><?php echo format_number($totalViews); ?></div>
                <div class="stat-label">总浏览量</div>
            </div>
            <div class="stat-extra">
                <small>今日: <?php echo format_number($todayViews); ?></small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info">
                <div class="stat-num"><?php echo $totalDrafts; ?></div>
                <div class="stat-label">草稿箱</div>
            </div>
            <div class="stat-extra">
                <small>待审核评论: <?php echo $pendingComments; ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 最近文章 -->
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="fas fa-clock me-2"></i>最近文章</h5>
                <a href="<?php echo SITE_URL; ?>/admin/posts" class="btn btn-sm btn-outline-primary">查看全部</a>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>标题</th>
                                <th>状态</th>
                                <th>浏览</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPosts as $rp): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/admin/post-edit?id=<?php echo $rp['id']; ?>" class="text-decoration-none">
                                        <?php echo clean(cut_str($rp['title'], 40)); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($rp['status'] === 'published'): ?>
                                    <span class="badge bg-success">已发布</span>
                                    <?php elseif ($rp['status'] === 'draft'): ?>
                                    <span class="badge bg-secondary">草稿</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">已删除</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $rp['views']; ?></td>
                                <td><?php echo friendly_date($rp['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 最近评论 -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="fas fa-comments me-2"></i>最近评论</h5>
                <a href="<?php echo SITE_URL; ?>/admin/comments" class="btn btn-sm btn-outline-primary">查看全部</a>
            </div>
            <div class="admin-card-body">
                <?php foreach ($recentComments as $rc): ?>
                <div class="comment-preview">
                    <div class="d-flex justify-content-between">
                        <strong><?php echo clean($rc['nickname']); ?></strong>
                        <small class="text-muted"><?php echo friendly_date($rc['created_at']); ?></small>
                    </div>
                    <p class="mb-0 text-muted small mt-1"><?php echo clean(cut_str($rc['content'], 80)); ?></p>
                    <small class="text-primary">回复: <?php echo clean(cut_str($rc['post_title'], 30)); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../admin/views/footer.php'; ?>
