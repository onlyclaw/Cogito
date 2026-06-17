<?php $siteName = get_option('site_name', SITE_NAME); ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="footer-title"><i class="fas fa-brain"></i> <?php echo clean($siteName); ?></h5>
                <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.7;"><?php echo clean(get_option('site_desc', SITE_DESC)); ?></p>
                <div class="social-links" style="margin-top:14px;">
                    <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <h5 class="footer-title"><i class="fas fa-rocket me-1"></i> 快速入口</h5>
                <ul class="friend-links">
                    <li><a href="<?php echo SITE_URL; ?>/pipeline">🔄 闭环创作</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/agents">🤖 智能体</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/media">🎨 媒体工坊</a></li>
                </ul>
            </div>
            <div class="col-lg-4 mb-4">
                <h5 class="footer-title"><i class="fas fa-sparkles me-1"></i> AI 能力</h5>
                <ul class="info-list">
                    <li>✦ 智能体协作写作</li>
                    <li>✦ AI 图片/音频/视频</li>
                    <li>✦ 多角度智能评论</li>
                </ul>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo clean($siteName); ?> · Powered by <span style="color:var(--neon);">AI</span></p>
        </div>
    </div>
</footer>

<button id="backToTop" class="back-to-top"><i class="fas fa-arrow-up"></i></button>

<script>var LOGGED_IN = <?php echo is_member_login() ? 'true' : 'false'; ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?php echo SITE_URL; ?>/assets/js/main.js" defer></script>
<script src="<?php echo SITE_URL; ?>/assets/js/chat.js" defer></script>
<script src="<?php echo SITE_URL; ?>/assets/js/pipeline.js" defer></script>
<script src="<?php echo SITE_URL; ?>/assets/js/media.js" defer></script>
<script src="<?php echo SITE_URL; ?>/assets/js/agent-chat.js" defer></script>
<?php if (!empty($extraJs)): ?><script><?php echo $extraJs; ?></script><?php endif; ?>
</body>
</html>
