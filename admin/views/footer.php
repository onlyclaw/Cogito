<?php
/**
 * 后台管理底部模板
 */
?>
    </div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
<?php if (isset($extraJs)): ?>
<script><?php echo $extraJs; ?></script>
<?php endif; ?>
</body>
</html>
