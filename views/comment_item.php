<?php
/**
 * 评论项模板（递归）
 */
?>
<div class="comment-item" id="comment-<?php echo $comment['id']; ?>">
    <div class="comment-avatar">
        <div class="avatar-md">
            <i class="fas fa-user"></i>
        </div>
    </div>
    <div class="comment-body">
        <div class="comment-header">
            <span class="comment-name"><?php echo clean($comment['nickname']); ?></span>
            <span class="comment-date"><?php echo friendly_date($comment['created_at']); ?></span>
        </div>
        <div class="comment-content">
            <?php echo nl2br(clean($comment['content'])); ?>
        </div>
        <div class="comment-actions">
            <a href="javascript:;" onclick="replyTo(<?php echo $comment['id']; ?>, '<?php echo clean($comment['nickname']); ?>')">
                <i class="fas fa-reply me-1"></i>回复
            </a>
        </div>
        <?php if (!empty($comment['children'])): ?>
        <div class="comment-children">
            <?php foreach ($comment['children'] as $child): ?>
            <?php include __DIR__ . '/comment_item.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
