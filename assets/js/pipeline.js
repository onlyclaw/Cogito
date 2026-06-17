/**
 * 闭环创作中心 - 脚本
 */
document.addEventListener("DOMContentLoaded", function() {
    // 智能体选择
    document.querySelectorAll(".agent-chip").forEach(function(chip) {
        chip.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.toggle("selected");
            var check = this.querySelector(".agent-check");
            if (this.classList.contains("selected")) {
                check.style.background = "var(--primary)";
                check.textContent = "✓";
                this.style.borderColor = "var(--primary)";
                this.style.background = "rgba(99,102,241,0.1)";
                this.style.boxShadow = "0 0 0 3px rgba(99,102,241,0.15)";
            } else {
                check.style.background = "var(--border)";
                check.textContent = "";
                this.style.borderColor = "var(--border)";
                this.style.background = "var(--bg-elevated)";
                this.style.boxShadow = "none";
            }
        });
    });
});

function updateFlow(step) {
    for (var i = 1; i <= 6; i++) {
        var node = document.getElementById("flow-" + i);
        node.classList.remove("active", "done");
        if (i < step) node.classList.add("done");
        else if (i === step) node.classList.add("active");
    }
}

function startPipeline() {
    if (typeof LOGGED_IN === 'undefined' || !LOGGED_IN) {
        showLoginModal();
        return;
    }
    var topic = document.getElementById("topicInput").value.trim();
    if (!topic) { alert("请输入创作主题"); return; }

    var selectedAgents = [];
    document.querySelectorAll(".agent-chip.selected").forEach(function(el) {
        selectedAgents.push(parseInt(el.dataset.id));
    });
    if (selectedAgents.length < 2) { alert("请至少选择2个智能体"); return; }

    var btn = document.getElementById("startBtn");
    btn.disabled = true;
    btn.textContent = "⏳ 创作中...";

    var resultDiv = document.getElementById("pipelineResult");
    resultDiv.style.display = "block";

    updateFlow(2);
    resultDiv.innerHTML = buildProgressHTML("智能体正在协作创作...", "分析主题、研究资料、撰写文章、生成配图描述");

    setTimeout(function() { updateFlow(3); }, 2000);
    setTimeout(function() { updateFlow(4); }, 4000);

    fetch("/boke/pipeline-api/full-pipeline", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ topic: topic, agent_ids: selectedAgents })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        updateFlow(5);
        setTimeout(function() { updateFlow(6); }, 1000);
        btn.disabled = false;
        btn.textContent = "🚀 启动创作闭环";
        if (d.code === 1) {
            renderResult(d.data, topic);
        } else {
            resultDiv.innerHTML = '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;padding:20px;border-radius:16px;text-align:center;">' + d.msg + '</div>';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = "🚀 启动创作闭环";
        resultDiv.innerHTML = '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;padding:20px;border-radius:16px;text-align:center;">请求失败，请重试</div>';
    });
}

function buildProgressHTML(title, desc) {
    return '<div style="text-align:center;padding:60px 20px;background:var(--bg-card);border:1px solid var(--border);border-radius:20px;">' +
        '<div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent2));display:flex;align-items:center;justify-content:center;margin:0 auto 20px;animation:pulse 2s infinite;font-size:2rem;color:#fff;">🤖</div>' +
        '<h3 style="font-weight:700;color:var(--text);margin-bottom:8px;">' + title + '</h3>' +
        '<p style="color:var(--text-muted);font-size:0.9rem;">' + desc + '</p>' +
        '<div style="margin-top:20px;"><div class="loading-spinner"><div class="spinner"></div></div></div>' +
        '</div>';
}

function renderResult(data, topic) {
    var agentColors = {"x-ai":["#6366f1","#8b5cf6"],"muse":["#ec4899","#f472b6"],"davinci":["#06b6d4","#22d3ee"],"socrates":["#f59e0b","#fbbf24"]};
    var html = "";

    // 文章
    html += '<div class="result-section">';
    html += '<div class="result-header"><div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--accent2));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;">✏️</div><div><h4 style="font-weight:700;margin:0;color:var(--text);">' + topic + '</h4><p style="font-size:0.82rem;color:var(--text-muted);margin:0;">智能体协作生成</p></div></div>';
    html += '<div class="result-body"><div style="font-size:0.95rem;line-height:2;color:var(--text-secondary);white-space:pre-wrap;font-family:var(--font-serif);">' + (data.content || '内容生成中...') + '</div>';
    html += '<div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">';
    html += '<a href="' + SITE_URL + '/post?id=' + data.post_id + '" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:var(--primary);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:0.9rem;">👁 查看文章</a>';
    html += '<a href="' + SITE_URL + '/media" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:var(--bg-elevated);border:1px solid var(--border);color:var(--text);border-radius:10px;text-decoration:none;font-weight:500;font-size:0.9rem;">✨ 生成更多媒体</a>';
    html += '</div></div></div>';

    // 协作过程
    if (data.agents && data.agents.length > 0) {
        html += '<div class="result-section">';
        html += '<div class="result-header"><div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#ec4899,#f472b6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;">👥</div><div><h4 style="font-weight:700;margin:0;color:var(--text);">智能体协作过程</h4><p style="font-size:0.82rem;color:var(--text-muted);margin:0;">' + data.agents.length + ' 个智能体参与</p></div></div>';
        html += '<div class="result-body">';
        data.agents.forEach(function(a) {
            var c = agentColors[a.agent.slug] || ['#6366f1','#8b5cf6'];
            html += '<div class="agent-comment-card">';
            html += '<div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,' + c[0] + ',' + c[1] + ');display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.9rem;flex-shrink:0;">🤖</div>';
            html += '<div style="flex:1;"><div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;"><span style="font-weight:600;font-size:0.9rem;color:var(--text);">' + a.agent.name + '</span><span style="font-size:0.72rem;padding:2px 8px;border-radius:6px;background:' + c[0] + '15;color:' + c[0] + ';">' + a.role + '</span></div>';
            html += '<div style="font-size:0.88rem;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap;">' + a.content.substring(0, 500) + (a.content.length > 500 ? '...' : '') + '</div></div></div>';
        });
        html += '</div></div>';
    }

    // 媒体
    if (data.media && (data.media.cover || data.media.audio)) {
        html += '<div class="result-section">';
        html += '<div class="result-header"><div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#06b6d4,#22d3ee);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;">🎨</div><div><h4 style="font-weight:700;margin:0;color:var(--text);">AI 生成媒体</h4><p style="font-size:0.82rem;color:var(--text-muted);margin:0;">自动生成配套内容</p></div></div>';
        html += '<div class="result-body"><div class="media-preview">';
        if (data.media.cover) {
            html += '<div class="media-item"><img src="' + data.media.cover.url + '" alt="cover"><div class="media-item-info"><div class="media-item-type" style="color:#6366f1;">封面图</div></div></div>';
        }
        if (data.media.audio) {
            html += '<div class="media-item"><div style="height:160px;background:linear-gradient(135deg,#ec4899,#f472b6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:3rem;">🎵</div><div class="media-item-info"><div class="media-item-type" style="color:#ec4899;">音频摘要</div><div style="font-size:0.78rem;color:var(--text-muted);">' + (data.media.audio.duration || 0) + '秒</div></div></div>';
        }
        html += '</div></div></div>';
    }

    // 评论
    if (data.comments && data.comments.length > 0) {
        html += '<div class="result-section">';
        html += '<div class="result-header"><div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#fbbf24);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;">💬</div><div><h4 style="font-weight:700;margin:0;color:var(--text);">智能体评论</h4><p style="font-size:0.82rem;color:var(--text-muted);margin:0;">多角度专业点评</p></div></div>';
        html += '<div class="result-body">';
        data.comments.forEach(function(c) {
            var ca = c.agent || {};
            var cc = agentColors[ca.slug] || ['#6366f1','#8b5cf6'];
            html += '<div class="agent-comment-card">';
            html += '<div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,' + cc[0] + ',' + cc[1] + ');display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.9rem;flex-shrink:0;">🤖</div>';
            html += '<div style="flex:1;"><div style="font-weight:600;font-size:0.9rem;color:var(--text);margin-bottom:4px;">' + (ca.name || 'AI') + ' <span style="font-size:0.75rem;color:' + cc[0] + ';">' + (ca.title || '') + '</span></div>';
            html += '<div style="font-size:0.88rem;color:var(--text-secondary);line-height:1.7;">' + c.content + '</div></div></div>';
        });
        html += '</div></div>';
    }

    document.getElementById("pipelineResult").innerHTML = html;
}
