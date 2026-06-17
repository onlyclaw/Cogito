/**
 * Agent Chat - 独立JS
 */
(function() {
    var app = document.getElementById('agentApp');
    if (!app) return;
    var AGENT_ID = parseInt(app.dataset.agentId);
    var SESSION_ID = app.dataset.sessionId;
    var COLOR1 = app.dataset.color1;
    var COLOR2 = app.dataset.color2;

    var style = document.createElement('style');
    style.textContent = '.agent-quick-btn{background:rgba(99,102,241,0.08);border:1px solid var(--border);color:var(--text-secondary);padding:6px 12px;border-radius:8px;font-size:0.8rem;cursor:pointer;transition:all 0.2s}.agent-quick-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}.agent-msg{display:flex;gap:10px;max-width:85%}.agent-msg.bot{align-self:flex-start}.agent-msg.user{align-self:flex-end;flex-direction:row-reverse}.agent-typing{display:flex;gap:4px;padding:14px 18px}.agent-typing span{width:6px;height:6px;border-radius:50%;background:var(--text-muted);animation:typing 1.4s ease-in-out infinite}.agent-typing span:nth-child(2){animation-delay:0.2s}.agent-typing span:nth-child(3){animation-delay:0.4s}@keyframes typing{0%,60%,100%{transform:translateY(0);opacity:0.4}30%{transform:translateY(-6px);opacity:1}}';
    document.head.appendChild(style);

    function esc(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function addMsg(role, content) {
        var c = document.getElementById('agentMessages');
        var isBot = role === 'assistant';
        var avatarBg = isBot ? 'linear-gradient(135deg,' + COLOR1 + ',' + COLOR2 + ')' : 'var(--bg-elevated)';
        var avatarColor = isBot ? '#fff' : 'var(--text-muted)';
        var msgBg = isBot ? 'var(--bg-elevated)' : 'var(--primary)';
        var msgBorder = isBot ? 'var(--border)' : 'var(--primary)';
        var msgRadius = isBot ? 'border-top-left-radius:4px;' : 'border-top-right-radius:4px;';
        var msgColor = isBot ? 'var(--text-secondary)' : '#fff';

        var div = document.createElement('div');
        div.className = 'agent-msg ' + (isBot ? 'bot' : 'user');
        div.innerHTML = '<div style="width:32px;height:32px;border-radius:8px;background:' + avatarBg + ';display:flex;align-items:center;justify-content:center;color:' + avatarColor + ';font-size:0.75rem;flex-shrink:0;border:1px solid var(--border);"><i class="fas fa-' + (isBot ? 'robot' : 'user') + '"></i></div>' +
            '<div style="background:' + msgBg + ';border:1px solid ' + msgBorder + ';border-radius:12px;' + msgRadius + 'padding:14px 18px;font-size:0.9rem;line-height:1.7;color:' + msgColor + ';white-space:pre-wrap;max-width:80%;">' + esc(content) + '</div>';
        c.appendChild(div);
        c.scrollTop = c.scrollHeight;
    }

    function showTyping() {
        var c = document.getElementById('agentMessages');
        var div = document.createElement('div');
        div.id = 'typingIndicator';
        div.className = 'agent-msg bot';
        div.innerHTML = '<div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,' + COLOR1 + ',' + COLOR2 + ');display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem;flex-shrink:0;"><i class="fas fa-robot"></i></div><div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:12px;border-top-left-radius:4px;padding:14px 18px;"><div class="agent-typing"><span></span><span></span><span></span></div></div>';
        c.appendChild(div);
        c.scrollTop = c.scrollHeight;
    }

    function hideTyping() {
        var e = document.getElementById('typingIndicator');
        if (e) e.remove();
    }

    window.sendAgentMsg = function(msg) {
        document.getElementById('agentInput').value = msg;
        window.sendAgentMessage();
    };

    window.sendAgentMessage = function() {
        var input = document.getElementById('agentInput');
        var msg = input.value.trim();
        if (!msg) return;
        addMsg('user', msg);
        input.value = '';
        showTyping();
        fetch('/boke/agent-api/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ agent_id: AGENT_ID, message: msg, session_id: SESSION_ID })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            hideTyping();
            addMsg('assistant', d.code === 1 ? d.data.reply : '抱歉，我暂时无法回应。');
        })
        .catch(function() {
            hideTyping();
            addMsg('assistant', '网络错误，请重试。');
        });
    };

    document.getElementById('agentInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') window.sendAgentMessage();
    });
})();
