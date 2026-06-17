<?php
/**
 * 标签页
 */
$slug = get('slug');
$page = max(1, (int)get('page', 1));

$tag = db()->fetchOne("SELECT * FROM tags WHERE slug = ? OR id = ?", [$slug, $slug]);
if (!$tag) {
    http_response_code(404);
    include __DIR__ . '/../views/404.php';
    exit;
}

$count = db()->fetchColumn("
    SELECT COUNT(*) FROM posts p
    INNER JOIN post_tags pt ON p.id = pt.post_id
    WHERE pt.tag_id = ? AND p.status = 'published'
", [$tag['id']]);
$pagination = paginate($count, $page);

$posts = db()->fetchAll("
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.nickname as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    INNER JOIN post_tags pt ON p.id = pt.post_id
    WHERE pt.tag_id = ? AND p.status = 'published'
    ORDER BY p.is_top DESC, p.created_at DESC
    LIMIT ? OFFSET ?
", [$tag['id'], $pagination['page_size'], $pagination['offset']]);

$categories = get_categories();
$tags = get_tags();
$hotPosts = get_hot_posts(5);
$pageTitle = '标签: #' . $tag['name'];

include __DIR__ . '/../views/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-tag me-2"></i>#<?php echo clean($tag['name']); ?></h1>
        <p class="text-muted"><?php echo $count; ?> 篇文章</p>
    </div>
</section>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-8">
            <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>该标签暂无文章</h4>
            </div>
            <?php else: ?>
            <div class="post-list">
                <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <?php if ($post['cover']): ?>
                    <div class="post-cover">
                        <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $post['id']; ?>">
                            <img src="<?php echo clean($post['cover']); ?>" alt="<?php echo clean($post['title']); ?>" loading="lazy">
                        </a>
                    </div>
                    <?php endif; ?>
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
                            <?php echo clean(cut_str(strip_tags($post['content']), 200)); ?>
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
            <?php echo render_pagination($pagination, SITE_URL . '/tag/' . $slug); ?>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <?php include __DIR__ . '/../views/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
