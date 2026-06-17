<?php
/**
 * 文章详情页 - Boke 2.0
 */
$postId = (int)get('id');
if (!$postId) {
    http_response_code(404);
    include __DIR__ . '/../views/404.php';
    exit;
}

$post = db()->fetchOne("
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.nickname as author_name, u.avatar as author_avatar, u.email as author_email
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = ? AND p.status = 'published'
", [$postId]);

if (!$post) {
    http_response_code(404);
    include __DIR__ . '/../views/404.php';
    exit;
}

// 增加浏览量
db()->query("UPDATE posts SET views = views + 1 WHERE id = ?", [$postId]);
$post['views']++;

// 计算预计阅读时间（中文按每分钟300字）
$wordCount = mb_strlen(strip_tags($post['content']), 'UTF-8');
$readingTime = max(1, ceil($wordCount / 300));

// 获取文章标签
$tags = db()->fetchAll("
    SELECT t.* FROM tags t
    INNER JOIN post_tags pt ON t.id = pt.tag_id
    WHERE pt.post_id = ?
", [$postId]);

// 获取评论
$comments = db()->fetchAll("
    SELECT c.* FROM comments c
    WHERE c.post_id = ? AND c.status = 'approved'
    ORDER BY c.created_at ASC
", [$postId]);

// 构建评论树
function build_comment_tree($comments, $parentId = 0) {
    $tree = [];
    foreach ($comments as $comment) {
        if ((int)$comment['parent_id'] === (int)$parentId) {
            $comment['children'] = build_comment_tree($comments, $comment['id']);
            $tree[] = $comment;
        }
    }
    return $tree;
}
$commentTree = build_comment_tree($comments);

// 上一篇/下一篇
$prevPost = db()->fetchOne("SELECT id, title FROM posts WHERE status = 'published' AND id < ? ORDER BY id DESC LIMIT 1", [$postId]);
$nextPost = db()->fetchOne("SELECT id, title FROM posts WHERE status = 'published' AND id > ? ORDER BY id ASC LIMIT 1", [$postId]);

// 检查是否已收藏
$isFavorited = false;
if (is_member_login()) {
    $isFavorited = db()->fetchColumn("SELECT COUNT(*) FROM favorites WHERE post_id = ? AND user_id = ?", [$postId, (int)$_SESSION['member_id']]);
} else {
    $visitorId = md5(server('REMOTE_ADDR') . server('HTTP_USER_AGENT'));
    $isFavorited = db()->fetchColumn("SELECT COUNT(*) FROM favorites WHERE post_id = ? AND visitor_id = ?", [$postId, $visitorId]);
}
$favCount = db()->fetchColumn("SELECT COUNT(*) FROM favorites WHERE post_id = ?", [$postId]);

$pageTitle = $post['title'];

include __DIR__ . '/../views/header.php';
?>

<!-- 文章头部 -->
<section class="post-hero" <?php if ($post['cover']): ?>style="background-image: url('<?php echo clean($post['cover']); ?>')"<?php endif; ?>>
    <div class="post-hero-overlay"></div>
    <div class="container post-hero-content">
        <div class="post-hero-meta">
            <a href="<?php echo SITE_URL; ?>/category/<?php echo $post['category_slug'] ?: $post['category_id']; ?>" class="post-hero-category">
                <i class="fas fa-folder me-1"></i><?php echo clean($post['category_name'] ?: '未分类'); ?>
            </a>
            <span class="post-hero-date">
                <i class="far fa-calendar-alt me-1"></i><?php echo format_date($post['created_at'], 'Y年m月d日'); ?>
            </span>
            <span class="reading-time">
                <i class="far fa-clock me-1"></i><?php echo $readingTime; ?> 分钟阅读
            </span>
        </div>
        <h1 class="post-hero-title"><?php echo clean($post['title']); ?></h1>
        <div class="post-hero-author">
            <div class="avatar-md">
                <?php if ($post['author_avatar']): ?>
                <img src="<?php echo clean($post['author_avatar']); ?>" alt="">
                <?php else: ?>
                <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div>
                <div class="author-name"><?php echo clean($post['author_name'] ?: '匿名'); ?></div>
                <div class="author-stats">
                    <span><i class="far fa-eye"></i> <?php echo format_number($post['views']); ?> 阅读</span>
                    <span><i class="far fa-heart"></i> <?php echo format_number($post['likes']); ?> 喜欢</span>
                    <span><i class="far fa-bookmark"></i> <?php echo $favCount; ?> 收藏</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- 文章内容 -->
            <article class="post-detail">
                <div class="post-content" id="postContent">
                    <?php echo $post['content']; ?>
                </div>

                <!-- 标签 -->
                <?php if (!empty($tags)): ?>
                <div class="post-tags">
                    <i class="fas fa-tags me-2"></i>
                    <?php foreach ($tags as $tag): ?>
                    <a href="<?php echo SITE_URL; ?>/tag/<?php echo $tag['slug'] ?: $tag['id']; ?>" class="tag-item">
                        #<?php echo clean($tag['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- 分享按钮 -->
                <div class="post-actions">
                    <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                        <button class="btn-like" onclick="likePost(<?php echo $post['id']; ?>)">
                            <i class="far fa-heart"></i>
                            <span id="likeCount"><?php echo $post['likes']; ?></span>
                        </button>
                        <button class="btn-like" id="favBtn" onclick="toggleFavorite(<?php echo $post['id']; ?>)" <?php echo $isFavorited ? 'class="btn-like favorited"' : ''; ?>>
                            <i class="<?php echo $isFavorited ? 'fas' : 'far'; ?> fa-bookmark"></i>
                            <span id="favCount"><?php echo $favCount; ?></span>
                        </button>
                    </div>
                    <div class="share-buttons justify-content-center">
                        <button class="share-btn share-btn-wechat" data-share="wechat">
                            <i class="fab fa-weixin"></i> 微信
                        </button>
                        <button class="share-btn share-btn-weibo" data-share="weibo">
                            <i class="fab fa-weibo"></i> 微博
                        </button>
                        <button class="share-btn share-btn-twitter" data-share="twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                        <button class="share-btn share-btn-copy" data-share="copy">
                            <i class="fas fa-link"></i> 复制链接
                        </button>
                    </div>
                </div>

                <!-- 上下篇 -->
                <div class="post-nav">
                    <div class="row">
                        <div class="col-6">
                            <?php if ($prevPost): ?>
                            <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $prevPost['id']; ?>" class="post-nav-link prev">
                                <span class="post-nav-label"><i class="fas fa-arrow-left me-1"></i>上一篇</span>
                                <span class="post-nav-title"><?php echo clean(cut_str($prevPost['title'], 30)); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-6 text-end">
                            <?php if ($nextPost): ?>
                            <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $nextPost['id']; ?>" class="post-nav-link next">
                                <span class="post-nav-label">下一篇<i class="fas fa-arrow-right ms-1"></i></span>
                                <span class="post-nav-title"><?php echo clean(cut_str($nextPost['title'], 30)); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 评论区 -->
                <div class="comment-section">
                    <h3 class="section-title">
                        <i class="fas fa-comments me-2"></i>评论 (<?php echo count($comments); ?>)
                    </h3>

                    <!-- 评论表单 -->
                    <div class="comment-form-wrapper">
                        <form id="commentForm" class="comment-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="parent_id" value="0" id="parentId">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" name="nickname" class="form-control" placeholder="昵称 *" required
                                        value="<?php echo is_member_login() ? clean(member_nickname()) : (is_login() ? clean(current_user()['nickname']) : ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <input type="email" name="email" class="form-control" placeholder="邮箱 *" required
                                        value="<?php echo is_member_login() ? clean(current_member()['email']) : (is_login() ? clean(current_user()['email']) : ''); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <textarea name="content" class="form-control" rows="4" placeholder="写下你的评论..." required id="commentContent"></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted" id="replyTo" style="display:none;">
                                    回复: <span id="replyName"></span>
                                    <a href="javascript:;" onclick="cancelReply()" class="ms-2">取消</a>
                                </small>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i>发表评论
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 评论列表 -->
                    <div class="comment-list" id="commentList">
                        <?php foreach ($commentTree as $comment): ?>
                        <?php include __DIR__ . '/../views/comment_item.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        </div>

        <!-- 侧边栏 -->
        <div class="col-lg-4">
            <!-- 文章目录 TOC -->
            <div class="post-toc" style="display:none;">
                <div class="toc-card">
                    <div class="toc-title">
                        <i class="fas fa-list"></i> 文章目录
                    </div>
                    <ul class="toc-list" id="tocList"></ul>
                </div>
            </div>

            <?php include __DIR__ . '/../views/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php
$extraJs = "
// 点赞
function likePost(id) {
    fetch('" . SITE_URL . "/api/like', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'post_id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.code === 1) {
            document.getElementById('likeCount').textContent = d.data.likes;
            document.querySelector('.btn-like i').className = 'fas fa-heart text-danger';
        } else {
            alert(d.msg);
        }
    });
}

// 评论提交
document.getElementById('commentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const data = new FormData(form);
    fetch('" . SITE_URL . "/api/comment', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(d => {
        if (d.code === 1) {
            alert(d.msg);
            form.reset();
            document.getElementById('parentId').value = 0;
            cancelReply();
            location.reload();
        } else {
            alert(d.msg);
        }
    });
});

// 回复
function replyTo(id, name) {
    document.getElementById('parentId').value = id;
    document.getElementById('replyTo').style.display = 'block';
    document.getElementById('replyName').textContent = name;
    document.getElementById('commentContent').focus();
}

function cancelReply() {
    document.getElementById('parentId').value = 0;
    document.getElementById('replyTo').style.display = 'none';
}
";
include __DIR__ . '/../views/footer.php';
?>
