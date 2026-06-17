/**
 * AI 媒体工坊 - 脚本
 */

// 登录检查
function checkLogin() {
    if (typeof LOGGED_IN === 'undefined' || !LOGGED_IN) {
        if (confirm('请先登录后再生成内容\n\n点击确定跳转到登录页面')) {
            window.location.href = '/boke/admin/login';
        }
        return false;
    }
    return true;
}

// 登录提示弹窗
function showLoginModal() {
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:9999;backdrop-filter:blur(8px);';
    overlay.innerHTML = '<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;padding:40px;max-width:400px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.5);">' +
        '<div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent2));display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.5rem;color:#fff;">🔒</div>' +
        '<h3 style="font-weight:700;color:var(--text);margin-bottom:8px;">需要登录</h3>' +
        '<p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:24px;">请先登录后再使用AI生成功能</p>' +
        '<div style="display:flex;gap:10px;justify-content:center;">' +
        '<a href="/boke/admin/login" style="padding:12px 28px;background:var(--primary);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;">去登录</a>' +
        '<button onclick="this.closest(\'div\').parentElement.parentElement.remove()" style="padding:12px 28px;background:var(--bg-elevated);border:1px solid var(--border);color:var(--text);border-radius:10px;cursor:pointer;">取消</button>' +
        '</div></div>';
    overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
}

function switchTab(tab) {
    document.querySelectorAll('.media-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.media-panel').forEach(function(p) { p.classList.remove('active'); });
    event.target.closest('.media-tab').classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}

function selectStyle(el, inputId) {
    el.parentElement.querySelectorAll('.gen-select-item').forEach(function(i) { i.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById(inputId).value = el.dataset.value;
}

function selectAgent(el) {
    el.parentElement.querySelectorAll('.gen-select-item').forEach(function(i) { i.classList.remove('active'); });
    el.classList.add('active');
    var input = el.closest('.media-panel').querySelector('input[type=hidden][id$=Agent]');
    if (input) input.value = el.dataset.value;
}

function generateImage() {
    if (!checkLogin()) return;
    var prompt = document.getElementById('imgPrompt').value.trim();
    if (!prompt) { alert('请输入图片描述'); return; }
    var btn = document.getElementById('imgGenBtn');
    btn.disabled = true;
    btn.textContent = '⏳ 生成中...';
    document.getElementById('imgResult').innerHTML = '<div style="padding:40px;text-align:center;"><div class="loading-spinner"><div class="spinner"></div></div><p style="color:var(--text-muted);margin-top:12px;">🎨 AI 正在创作中...</p></div>';

    fetch('/boke/media-api/generate-image', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            prompt: prompt,
            agent_id: parseInt(document.getElementById('imgAgent').value),
            style: document.getElementById('imgStyle').value,
            size: document.getElementById('imgSize').value
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        btn.disabled = false;
        btn.textContent = '✨ 生成图片';
        if (d.code === 1) {
            document.getElementById('imgResult').innerHTML = '<img src="' + d.data.url + '" style="max-width:100%;border-radius:12px;"><div style="margin-top:12px;font-size:0.85rem;color:var(--text-muted);">' + prompt + '</div>';
            loadRecentMedia();
        } else {
            document.getElementById('imgResult').innerHTML = '<div style="color:#fca5a5;">' + d.msg + '</div>';
        }
    })
    .catch(function() { btn.disabled = false; btn.textContent = '✨ 生成图片'; document.getElementById('imgResult').innerHTML = '<div style="color:#fca5a5;">请求失败</div>'; });
}

function generateAudio() {
    if (!checkLogin()) return;
    var text = document.getElementById('audioText').value.trim();
    if (!text) { alert('请输入文本内容'); return; }
    var btn = document.getElementById('audioGenBtn');
    btn.disabled = true;
    btn.textContent = '⏳ 生成中...';
    document.getElementById('audioResult').innerHTML = '<div style="padding:40px;text-align:center;"><div class="loading-spinner"><div class="spinner"></div></div><p style="color:var(--text-muted);margin-top:12px;">🎤 AI 正在录制中...</p></div>';

    fetch('/boke/media-api/generate-audio', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            text: text,
            agent_id: parseInt(document.getElementById('audioAgent').value),
            voice: document.getElementById('audioVoice').value,
            speed: parseFloat(document.getElementById('audioSpeed').value)
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        btn.disabled = false;
        btn.textContent = '🎵 生成音频';
        if (d.code === 1) {
            document.getElementById('audioResult').innerHTML = '<div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:12px;padding:20px;"><div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;"><div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--accent2));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;">🎵</div><div style="flex:1;"><div style="font-weight:600;color:var(--text);">音频已生成</div><div style="font-size:0.82rem;color:var(--text-muted);">时长: ' + d.data.duration + '秒</div></div></div><div style="font-size:0.85rem;color:var(--text-secondary);white-space:pre-wrap;line-height:1.6;max-height:100px;overflow:auto;">' + text.substring(0, 200) + (text.length > 200 ? '...' : '') + '</div></div>';
            loadRecentMedia();
        } else {
            document.getElementById('audioResult').innerHTML = '<div style="color:#fca5a5;">' + d.msg + '</div>';
        }
    })
    .catch(function() { btn.disabled = false; btn.textContent = '🎵 生成音频'; document.getElementById('audioResult').innerHTML = '<div style="color:#fca5a5;">请求失败</div>'; });
}

function generateVideo() {
    if (!checkLogin()) return;
    var prompt = document.getElementById('videoPrompt').value.trim();
    if (!prompt) { alert('请输入视频描述'); return; }
    var btn = document.getElementById('videoGenBtn');
    btn.disabled = true;
    btn.textContent = '⏳ 提交中...';

    document.getElementById('videoResult').innerHTML =
        '<div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:16px;padding:28px;">' +
        '<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">' +
        '<div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#06b6d4,#22d3ee);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;">🎬</div>' +
        '<div style="flex:1;"><div style="font-weight:600;color:var(--text);">视频生成中</div>' +
        '<div style="font-size:0.82rem;color:var(--text-muted);" id="videoProgressText">正在初始化...</div></div></div>' +
        '<div style="background:var(--bg-card);border-radius:8px;height:8px;overflow:hidden;margin-bottom:16px;">' +
        '<div id="videoProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#06b6d4,#22d3ee);border-radius:8px;transition:width 0.5s ease;"></div></div>' +
        '<div id="videoPreviewFrames" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px;"></div>' +
        '<div style="text-align:center;font-size:0.82rem;color:var(--text-muted);" id="videoEta">预计剩余: 计算中...</div></div>';

    fetch('/boke/media-api/generate-video', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            prompt: prompt,
            agent_id: parseInt(document.getElementById('videoAgent').value),
            duration: parseInt(document.getElementById('videoDur').value),
            style: document.getElementById('videoStyle').value
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        btn.disabled = false;
        btn.textContent = '🎬 生成视频';
        if (d.code === 1) {
            var data = d.data;
            if (data.frames && data.frames.length > 0) {
                var framesHtml = '';
                data.frames.forEach(function(frame) {
                    framesHtml += '<div style="border-radius:8px;overflow:hidden;aspect-ratio:16/9;"><img src="' + frame + '" style="width:100%;height:100%;object-fit:cover;"></div>';
                });
                document.getElementById('videoPreviewFrames').innerHTML = framesHtml;
            }
            simulateProgress(data.id, data.duration || 5, data.mode);
        } else {
            document.getElementById('videoResult').innerHTML = '<div style="color:#fca5a5;padding:20px;">' + d.msg + '</div>';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = '🎬 生成视频';
        document.getElementById('videoResult').innerHTML = '<div style="color:#fca5a5;padding:20px;">请求失败</div>';
    });
}

function simulateProgress(mediaId, duration, mode) {
    var progress = 0;
    var stages = [
        { at: 10, text: '🧠 理解视频描述...' },
        { at: 25, text: '🎨 生成关键帧...' },
        { at: 45, text: '🔄 插值补帧中...' },
        { at: 65, text: '✨ 添加视觉特效...' },
        { at: 80, text: '🎬 渲染视频...' },
        { at: 95, text: '📦 压缩输出...' },
        { at: 100, text: '✅ 生成完成！' }
    ];

    var interval = setInterval(function() {
        progress += Math.random() * 3 + 1;
        if (progress > 100) progress = 100;

        var bar = document.getElementById('videoProgressBar');
        var text = document.getElementById('videoProgressText');
        var eta = document.getElementById('videoEta');

        if (bar) bar.style.width = progress + '%';

        for (var i = stages.length - 1; i >= 0; i--) {
            if (progress >= stages[i].at) {
                if (text) text.textContent = stages[i].text;
                break;
            }
        }

        var remaining = Math.max(0, Math.ceil((100 - progress) / 3));
        if (eta) eta.textContent = '预计剩余: ' + remaining + '秒';

        if (progress >= 100) {
            clearInterval(interval);
            if (eta) eta.textContent = '✅ 视频生成完成！';
            loadRecentMedia();

            setTimeout(function() {
                document.getElementById('videoResult').innerHTML =
                    '<div style="background:var(--bg-elevated);border:1px solid rgba(16,185,129,0.3);border-radius:16px;padding:24px;">' +
                    '<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">' +
                    '<div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;">✓</div>' +
                    '<div><div style="font-weight:600;color:var(--text);">视频生成完成</div>' +
                    '<div style="font-size:0.82rem;color:var(--text-muted);">' + duration + '秒 · 1280x720 · ' + (mode === 'api' ? 'Runway ML' : '演示模式') + '</div></div></div>' +
                    '<div id="videoFinalFrames" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;"></div></div>';

                fetch('/boke/media-api/media-info?id=' + mediaId)
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.code === 1 && d.data.meta_json) {
                            var meta = JSON.parse(d.data.meta_json);
                            if (meta.frames) {
                                var html = '';
                                meta.frames.forEach(function(f) {
                                    html += '<div style="border-radius:8px;overflow:hidden;aspect-ratio:16/9;"><img src="' + f + '" style="width:100%;height:100%;object-fit:cover;"></div>';
                                });
                                document.getElementById('videoFinalFrames').innerHTML = html;
                            }
                        }
                    });
            }, 500);
        }
    }, 200);
}

function loadRecentMedia() {
    fetch('/boke/media-api/gallery?limit=6')
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.code === 1 && d.data.items.length > 0) {
            var icons = { image: '🖼️', audio: '🎵', video: '🎬' };
            var colors = { image: '#6366f1', audio: '#ec4899', video: '#06b6d4' };
            var html = '';
            d.data.items.forEach(function(item) {
                html += '<div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:12px;margin-bottom:8px;">' +
                    '<div style="width:40px;height:40px;border-radius:10px;background:' + colors[item.type] + '20;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">' + icons[item.type] + '</div>' +
                    '<div style="flex:1;overflow:hidden;"><div style="font-size:0.85rem;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + (item.prompt || '').substring(0, 40) + '</div>' +
                    '<div style="font-size:0.72rem;color:var(--text-muted);">' + (item.agent_name || 'AI') + ' · ' + item.type + '</div></div></div>';
            });
            document.getElementById('recentMedia').innerHTML = html;
        }
    });
}

loadRecentMedia();
