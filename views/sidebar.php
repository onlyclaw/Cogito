<?php
if (!isset($categories)) $categories = get_categories();
if (!isset($tags)) $tags = get_tags();
if (!isset($hotPosts)) $hotPosts = get_hot_posts(5);
?>

<!-- 搜索 -->
<div class="sidebar-widget">
    <form action="<?php echo SITE_URL; ?>/search" method="GET" class="sidebar-search">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="搜索..." value="<?php echo clean(get('q')); ?>">
            <button class="btn" type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<!-- 关于博主 -->
<div class="sidebar-widget">
    <div class="sidebar-title"><i class="fas fa-user-circle"></i> 关于博主</div>
    <div class="about-content">
        <div class="about-avatar"><i class="fas fa-user"></i></div>
        <p class="about-name"><?php echo clean(get_option('site_name', '博主')); ?></p>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:0;"><?php echo clean(get_option('site_desc', '')); ?></p>
        <div class="about-stats">
            <div class="stat-item"><div class="stat-num"><?php echo is_installed() ? db()->fetchColumn("SELECT COUNT(*) FROM posts WHERE status='published'") : 0; ?></div><div class="stat-label">文章</div></div>
            <div class="stat-item"><div class="stat-num"><?php echo is_installed() ? db()->fetchColumn("SELECT COUNT(*) FROM categories") : 0; ?></div><div class="stat-label">分类</div></div>
            <div class="stat-item"><div class="stat-num"><?php echo is_installed() ? db()->fetchColumn("SELECT COUNT(*) FROM comments WHERE status='approved'") : 0; ?></div><div class="stat-label">评论</div></div>
        </div>
    </div>
</div>

<!-- 热门文章 -->
<div class="sidebar-widget">
    <div class="sidebar-title"><i class="fas fa-fire"></i> 热门文章</div>
    <ul class="hot-posts">
        <?php foreach ($hotPosts as $i=>$hp): ?>
        <li>
            <span class="hot-index"><?php echo $i+1; ?></span>
            <a href="<?php echo SITE_URL; ?>/post?id=<?php echo $hp['id']; ?>"><?php echo clean(cut_str($hp['title'],35)); ?></a>
            <span class="hot-views"><i class="far fa-eye"></i> <?php echo format_number($hp['views']); ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- 分类 -->
<div class="sidebar-widget">
    <div class="sidebar-title"><i class="fas fa-folder"></i> 分类</div>
    <ul class="category-list">
        <?php foreach ($categories as $cat): ?>
        <li>
            <a href="<?php echo SITE_URL; ?>/category/<?php echo $cat['slug'] ?: $cat['id']; ?>"><i class="fas fa-chevron-right" style="font-size:0.6rem;"></i> <?php echo clean($cat['name']); ?></a>
            <span class="category-count"><?php echo $cat['post_count']; ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- 标签云 -->
<div class="sidebar-widget">
    <div class="sidebar-title"><i class="fas fa-tags"></i> 标签</div>
    <div class="tag-cloud">
        <?php foreach (array_slice($tags,0,15) as $tag): ?>
        <a href="<?php echo SITE_URL; ?>/tag/<?php echo $tag['slug'] ?: $tag['id']; ?>" class="tag-item">#<?php echo clean($tag['name']); ?></a>
        <?php endforeach; ?>
    </div>
</div>
