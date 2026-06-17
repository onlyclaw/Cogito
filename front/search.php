<?php
/**
 * 搜索页
 */
$keyword = get('q');
$page = max(1, (int)get('page', 1));

$posts = [];
$count = 0;
$pagination = paginate(0, $page);

if ($keyword) {
    $likeKeyword = "%{$keyword}%";
    $count = db()->fetchColumn(
        "SELECT COUNT(*) FROM posts WHERE status = 'published' AND (title LIKE ? OR content LIKE ?)",
        [$likeKeyword, $likeKeyword]
    );
    $pagination = paginate($count, $page);

    $posts = db()->fetchAll("
        SELECT p.*, c.name as category_name, c.slug as category_slug, u.nickname as author_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.status = 'published' AND (p.title LIKE ? OR p.content LIKE ?)
        ORDER BY p.is_top DESC, p.created_at DESC
        LIMIT ? OFFSET ?
", [$likeKeyword, $likeKeyword, $pagination['page_size'], $pagination['offset']]);
}

$categories = get_categories();
$tags = get_tags();
$hotPosts = get_hot_posts(5);
$pageTitle = $keyword ? '搜索: ' . $keyword : '搜索';

include __DIR__ . '/../views/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-search me-2"></i>搜索</h1>
        <form action="<?php echo SITE_URL; ?>/search" method="GET" class="search-header-form mt-3">
            <div class="input-group input-group-lg">
                <input type="text" name="q" class="form-control" placeholder="输入关键词搜索..." value="<?php echo clean($keyword); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</section>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-8">
            <?php if ($keyword): ?>
            <p class="text-muted mb-4">找到 <strong><?php echo $count; ?></strong> 篇相关文章</p>
            <?php endif; ?>

            <?php if (empty($posts) && $keyword): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h4>没有找到相关文章</h4>
                <p>换个关键词试试吧</p>
            </div>
            <?php else: ?>
            <div class="post-list">
                <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <div class="post-body">
                        <div class="post-meta">
                            <span class="post-category">
                                <a href="<?php echo SITE_URL; ?>/category/<?php echo $post['category_slug'] ?: $post['category_id']; ?>">
                                    <?php echo clean($post['category_name'] ?: '未分类'); ?>
                                </a>
                            </span>
                            <span class="post-date">
                                <i class="far fa-clock me-1"></i><?php echo friendly_date($post['created_at']); ?>
                            </span>
                        </div>
                        <h2 class="post-title">
                            <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $post['id']; ?>">
                                <?php echo clean($post['title']); ?>
                            </a>
                        </h2>
                        <p class="post-summary">
                            <?php
                            $summary = strip_tags($post['content']);
                            if ($keyword) {
                                $summary = str_ireplace($keyword, '<mark>' . $keyword . '</mark>', cut_str($summary, 200));
                                echo $summary;
                            } else {
                                echo clean(cut_str($summary, 200));
                            }
                            ?>
                        </p>
                        <div class="post-footer">
                            <div class="post-stats">
                                <span><i class="far fa-eye"></i> <?php echo format_number($post['views']); ?></span>
                                <span><i class="far fa-heart"></i> <?php echo format_number($post['likes']); ?></span>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php echo render_pagination($pagination, SITE_URL . '/search?q=' . urlencode($keyword)); ?>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <?php include __DIR__ . '/../views/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
