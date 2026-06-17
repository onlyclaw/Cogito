/**
 * Cogito - 主脚本
 */
document.addEventListener('DOMContentLoaded', function() {

    // 导航栏滚动
    var nav = document.getElementById('mainNav');
    if (nav) {
        window.addEventListener('scroll', function() {
            nav.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // 回到顶部
    var backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            backToTop.classList.toggle('visible', window.scrollY > 400);
        });
        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // 阅读进度条
    var progressBar = document.getElementById('readingProgress');
    var postContent = document.getElementById('postContent');
    if (progressBar && postContent) {
        window.addEventListener('scroll', function() {
            var rect = postContent.getBoundingClientRect();
            var total = postContent.offsetHeight - window.innerHeight;
            var progress = Math.min(100, Math.max(0, ((-rect.top) / total) * 100));
            progressBar.style.width = progress + '%';
        });
    }

    // TOC 目录
    var tocList = document.getElementById('tocList');
    if (tocList && postContent) {
        var headings = postContent.querySelectorAll('h2, h3');
        if (headings.length > 2) {
            headings.forEach(function(h, i) {
                h.id = 'heading-' + i;
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.href = '#heading-' + i;
                a.textContent = h.textContent;
                a.style.paddingLeft = (h.tagName === 'H3' ? 16 : 0) + 'px';
                li.appendChild(a);
                tocList.appendChild(li);
            });
            var tocWidget = document.querySelector('.post-toc');
            if (tocWidget) tocWidget.style.display = 'block';
            window.addEventListener('scroll', function() {
                var current = '';
                headings.forEach(function(h) {
                    if (h.getBoundingClientRect().top <= 100) current = h.id;
                });
                tocList.querySelectorAll('a').forEach(function(a) {
                    a.classList.toggle('active', a.getAttribute('href') === '#' + current);
                });
            });
        }
    }

    // 图片点击放大
    var contentArea = document.querySelector('.post-content');
    if (contentArea) {
        contentArea.querySelectorAll('img').forEach(function(img) {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function() {
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;z-index:9999;cursor:pointer;padding:20px;';
                var bigImg = document.createElement('img');
                bigImg.src = this.src;
                bigImg.style.cssText = 'max-width:90%;max-height:90vh;border-radius:8px;';
                overlay.appendChild(bigImg);
                overlay.addEventListener('click', function() { this.remove(); });
                document.body.appendChild(overlay);
            });
        });
    }

    // 平滑锚点
    document.querySelectorAll('a[href^="#"]').forEach(function(a) {
        a.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href === '#') return;
            e.preventDefault();
            var target = document.querySelector(href);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // 无限滚动
    var infiniteContainer = document.getElementById('infiniteScroll');
    var sentinel = document.getElementById('scrollSentinel');
    if (infiniteContainer && sentinel) {
        var currentPage = 1, isLoading = false, hasMore = true;
        var infObs = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting && !isLoading && hasMore) loadMore();
        }, { rootMargin: '200px' });
        infObs.observe(sentinel);

        function loadMore() {
            isLoading = true;
            currentPage++;
            var spinner = document.createElement('div');
            spinner.className = 'loading-spinner';
            spinner.innerHTML = '<div class="spinner"></div>';
            infiniteContainer.appendChild(spinner);
            fetch(window.location.pathname + '?page=' + currentPage + '&ajax=1')
                .then(function(r) { return r.text(); })
                .then(function(html) {
                    spinner.remove();
                    if (!html.trim() || html.indexOf('empty-state') > -1) {
                        hasMore = false;
                        sentinel.innerHTML = '<p style="text-align:center;color:var(--text-muted);font-size:0.85rem;padding:16px;">没有更多了</p>';
                    } else {
                        infiniteContainer.insertAdjacentHTML('beforeend', html);
                    }
                    isLoading = false;
                })
                .catch(function() { spinner.remove(); isLoading = false; });
        }
    }

    // 社交分享
    document.querySelectorAll('.share-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var type = this.dataset.share;
            var url = encodeURIComponent(window.location.href);
            var title = encodeURIComponent(document.title);
            if (type === 'copy') {
                navigator.clipboard.writeText(window.location.href).then(function() {
                    btn.innerHTML = '<i class="fas fa-check"></i> 已复制';
                    setTimeout(function() { btn.innerHTML = '<i class="fas fa-link"></i> 复制'; }, 2000);
                });
            } else if (type === 'wechat') {
                alert('请截图或复制链接分享到微信');
            } else if (type === 'weibo') {
                window.open('https://service.weibo.com/share/share.php?url=' + url + '&title=' + title, '_blank');
            } else if (type === 'twitter') {
                window.open('https://twitter.com/intent/tweet?url=' + url + '&title=' + title, '_blank');
            }
        });
    });

    // 文章收藏
    window.toggleFavorite = function(postId) {
        fetch('/boke/api/favorite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'post_id=' + postId
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.code === 1) {
                var icon = document.querySelector('#favBtn i');
                if (icon) icon.className = d.data.favorited ? 'fas fa-bookmark' : 'far fa-bookmark';
                var count = document.getElementById('favCount');
                if (count) count.textContent = d.data.count;
            } else { alert(d.msg); }
        });
    };

});
