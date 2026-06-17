<?php
/**
 * RSS输出
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$siteName = get_option('site_name', SITE_NAME);
$siteDesc = get_option('site_desc', SITE_DESC);

$posts = db()->fetchAll("
    SELECT p.*, c.name as category_name, u.nickname as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.status = 'published'
    ORDER BY p.created_at DESC
    LIMIT 20
");

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0">
    <channel>
        <title><?php echo clean($siteName); ?></title>
        <link><?php echo SITE_URL; ?></link>
        <description><?php echo clean($siteDesc); ?></description>
        <language>zh-CN</language>
        <pubDate><?php echo date('r'); ?></pubDate>
        <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
        <?php foreach ($posts as $post): ?>
        <item>
            <title><?php echo clean($post['title']); ?></title>
            <link><?php echo SITE_URL; ?>/post?id=<?php echo $post['id']; ?></link>
            <guid><?php echo SITE_URL; ?>/post?id=<?php echo $post['id']; ?></guid>
            <description><?php echo clean(cut_str(strip_tags($post['content']), 500)); ?></description>
            <category><?php echo clean($post['category_name'] ?: '未分类'); ?></category>
            <author><?php echo clean($post['author_name'] ?: '匿名'); ?></author>
            <pubDate><?php echo date('r', strtotime($post['created_at'])); ?></pubDate>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>
