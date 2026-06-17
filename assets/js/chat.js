/**
 * Boke 2.0 - AI 智能助手
 */

class AIChat {
    constructor() {
        this.window = document.getElementById('aiChatWindow');
        this.btn = document.getElementById('aiChatBtn');
        this.closeBtn = document.getElementById('aiChatClose');
        this.messages = document.getElementById('aiChatMessages');
        this.input = document.getElementById('aiChatInput');
        this.sendBtn = document.getElementById('aiChatSend');
        this.quickActions = document.getElementById('aiQuickActions');
        this.isOpen = false;
        this.isTyping = false;

        this.init();
    }

    init() {
        if (!this.btn) return;

        this.btn.addEventListener('click', () => this.toggle());
        this.closeBtn.addEventListener('click', () => this.close());
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // 快捷操作
        this.quickActions.querySelectorAll('.ai-quick-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.input.value = btn.dataset.msg;
                this.sendMessage();
            });
        });

        // 首次打开时显示欢迎消息
        this.addMessage('bot', '你好！👋 我是 AI 智能助手，可以帮你：\n\n• 📝 总结文章内容\n• 🔍 推荐相关文章\n• 💡 回答技术问题\n• 🌐 翻译内容\n\n有什么我可以帮你的吗？');
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.window.classList.add('active');
        this.btn.innerHTML = '<i class="fas fa-times"></i>';
        this.input.focus();
    }

    close() {
        this.isOpen = false;
        this.window.classList.remove('active');
        this.btn.innerHTML = '<i class="fas fa-robot"></i>';
    }

    addMessage(role, content) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'ai-message ' + role;

        const avatar = document.createElement('div');
        avatar.className = 'ai-message-avatar';
        avatar.innerHTML = role === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';

        const contentDiv = document.createElement('div');
        contentDiv.className = 'ai-message-content';
        contentDiv.textContent = content;

        msgDiv.appendChild(avatar);
        msgDiv.appendChild(contentDiv);
        this.messages.appendChild(msgDiv);

        this.scrollToBottom();
    }

    showTyping() {
        this.isTyping = true;
        const typingDiv = document.createElement('div');
        typingDiv.className = 'ai-message bot';
        typingDiv.id = 'typingIndicator';

        const avatar = document.createElement('div');
        avatar.className = 'ai-message-avatar';
        avatar.innerHTML = '<i class="fas fa-robot"></i>';

        const typing = document.createElement('div');
        typing.className = 'ai-typing';
        typing.innerHTML = '<span></span><span></span><span></span>';

        var msgDiv = document.createElement('div');
        msgDiv.className = 'ai-message-content';
        msgDiv.appendChild(typing);

        typingDiv.appendChild(avatar);
        typingDiv.appendChild(msgDiv);
        this.messages.appendChild(typingDiv);

        this.scrollToBottom();
    }

    hideTyping() {
        this.isTyping = false;
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.remove();
    }

    async sendMessage() {
        const text = this.input.value.trim();
        if (!text || this.isTyping) return;

        this.addMessage('user', text);
        this.input.value = '';
        this.sendBtn.disabled = true;

        this.showTyping();

        try {
            const response = await fetch('/boke/api/ai', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });

            const data = await response.json();
            this.hideTyping();

            if (data.code === 1) {
                this.addMessage('bot', data.data.reply);
            } else {
                this.addMessage('bot', '抱歉，我暂时无法处理这个请求。请稍后再试。');
            }
        } catch (error) {
            this.hideTyping();
            this.addMessage('bot', '网络连接出现问题，请检查网络后重试。');
        }

        this.sendBtn.disabled = false;
    }

    scrollToBottom() {
        this.messages.scrollTop = this.messages.scrollHeight;
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    new AIChat();
});
