/**
 * 后台管理脚本
 */

document.addEventListener('DOMContentLoaded', function() {

    // ============ 侧边栏切换 ============
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });

        // 点击内容区域关闭侧边栏
        document.querySelector('.admin-main')?.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('show');
            }
        });
    }

    // ============ 自动关闭提示框 ============
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });

    // ============ 确认删除操作 ============
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || '确定执行此操作？')) {
                e.preventDefault();
            }
        });
    });

    // ============ 表格全选 ============
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.form-check-input:not(#checkAll)');
            checkboxes.forEach(function(cb) {
                cb.checked = checkAll.checked;
            });
        });
    }

});
