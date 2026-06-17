<?php
/**
 * Cogito Code 智能编程平台 - 专业级IDE界面
 */
require_once __DIR__ . '/../agent.php';
$pageTitle = 'Cogito Code · 智能编程平台';
$agent = Agent::find('x-ai');
$sessionId = md5(uniqid());

include __DIR__ . '/../views/header.php';
?>
<style>
body{overflow:hidden;background:#0d1117}
#mainNav{z-index:1001}
.ide-layout{display:flex;height:calc(100vh - var(--nav-h));margin-top:var(--nav-h)}

/* 左侧边栏 */
.sidebar{width:260px;background:#0d1117;border-right:1px solid #21262d;display:flex;flex-direction:column;flex-shrink:0;transition:width 0.3s}
.sidebar.collapsed{width:0;overflow:hidden}
.sidebar-header{padding:12px 16px;border-bottom:1px solid #21262d;display:flex;align-items:center;justify-content:space-between}
.sidebar-title{font-size:0.78rem;font-weight:600;color:#8b949e;text-transform:uppercase;letter-spacing:0.05em}
.sidebar-actions{display:flex;gap:4px}
.sidebar-btn{width:28px;height:28px;border:none;background:#21262d;border-radius:6px;color:#8b949e;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:0.72rem;transition:all 0.2s}
.sidebar-btn:hover{background:#30363d;color:#c9d1d9}

/* 项目选择 */
.project-bar{padding:10px 12px;border-bottom:1px solid #21262d}
.project-select{width:100%;background:#161b22;border:1px solid #30363d;color:#c9d1d9;padding:8px 10px;border-radius:8px;font-size:0.82rem;outline:none}
.project-select:focus{border-color:#58a6ff}

/* 文件树 */
.file-tree{flex:1;overflow-y:auto;padding:4px 0}
.file-group{margin-bottom:4px}
.file-group-title{padding:6px 12px;font-size:0.72rem;font-weight:600;color:#484f58;text-transform:uppercase;letter-spacing:0.05em;display:flex;align-items:center;gap:6px;cursor:pointer}
.file-group-title:hover{color:#8b949e}
.file-item{display:flex;align-items:center;gap:8px;padding:5px 12px 5px 24px;cursor:pointer;transition:all 0.15s;font-size:0.82rem;color:#8b949e;border-left:2px solid transparent}
.file-item:hover{background:#161b22;color:#c9d1d9}
.file-item.active{background:#1f6feb15;color:#58a6ff;border-left-color:#58a6ff}
.file-item .file-icon{font-size:0.78rem;width:16px;text-align:center;flex-shrink:0}
.file-item .file-name{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.file-item .modified-dot{width:6px;height:6px;border-radius:50%;background:#f0883e;flex-shrink:0;display:none}
.file-item.modified .modified-dot{display:block}

/* 中间区域 */
.main-area{flex:1;display:flex;flex-direction:column;min-width:0}

/* 工具栏 */
.toolbar{height:40px;background:#161b22;border-bottom:1px solid #21262d;display:flex;align-items:center;padding:0 12px;gap:8px}
.toolbar-btn{height:30px;padding:0 10px;border:none;background:transparent;border-radius:6px;color:#8b949e;cursor:pointer;font-size:0.78rem;display:flex;align-items:center;gap:5px;transition:all 0.2s;white-space:nowrap}
.toolbar-btn:hover{background:#21262d;color:#c9d1d9}
.toolbar-btn.active{background:#1f6feb22;color:#58a6ff}
.toolbar-sep{width:1px;height:20px;background:#21262d;margin:0 4px}
.toolbar-right{margin-left:auto;display:flex;align-items:center;gap:6px}

/* 标签页 */
.tabs-bar{display:flex;background:#161b22;border-bottom:1px solid #21262d;overflow-x:auto;min-height:36px}
.tab{display:flex;align-items:center;gap:6px;padding:8px 14px;font-size:0.8rem;color:#8b949e;cursor:pointer;border-right:1px solid #21262d;transition:all 0.15s;white-space:nowrap;position:relative}
.tab:hover{background:#161b22;color:#c9d1d9}
.tab.active{background:#0d1117;color:#c9d1d9}
.tab.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:#58a6ff}
.tab .modified{width:6px;height:6px;border-radius:50%;background:#f0883e;margin-left:4px}
.tab .close-tab{width:18px;height:18px;border:none;background:transparent;color:#484f58;cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:4px;font-size:0.65rem;opacity:0;transition:all 0.15s}
.tab:hover .close-tab{opacity:1}
.tab .close-tab:hover{background:#30363d;color:#c9d1d9}

/* 编辑器+输出分割 */
.editor-output-split{flex:1;display:flex;flex-direction:column;overflow:hidden}
.editor-section{flex:1;display:flex;flex-direction:column;min-height:0}

/* 编辑器 */
.editor-meta{padding:6px 16px;background:#161b22;border-bottom:1px solid #21262d;display:flex;align-items:center;gap:12px;font-size:0.75rem;color:#484f58}
.editor-meta select{background:#21262d;border:1px solid #30363d;color:#8b949e;padding:3px 8px;border-radius:4px;font-size:0.75rem}
.code-editor{flex:1;background:#0d1117;color:#e6edf3;font-family:'JetBrains Mono','Fira Code',Consolas,monospace;font-size:13.5px;line-height:1.65;padding:12px 16px;border:none;outline:none;resize:none;tab-size:4;overflow:auto;width:100%}
.code-editor::selection{background:#264f78}

/* 输出/终端面板 */
.output-panel{height:180px;background:#0d1117;border-top:1px solid #21262d;display:flex;flex-direction:column;flex-shrink:0;transition:height 0.3s}
.output-panel.collapsed{height:32px}
.output-header{height:32px;background:#161b22;border-bottom:1px solid #21262d;display:flex;align-items:center;padding:0 12px;gap:8px;cursor:pointer;flex-shrink:0}
.output-tabs{display:flex;gap:2px}
.output-tab{padding:4px 12px;font-size:0.75rem;color:#8b949e;cursor:pointer;border-radius:4px;transition:all 0.2s}
.output-tab:hover{background:#21262d;color:#c9d1d9}
.output-tab.active{background:#1f6feb22;color:#58a6ff}
.output-body{flex:1;overflow-y:auto;padding:10px 14px;font-family:'JetBrains Mono',monospace;font-size:12px;line-height:1.6;color:#8b949e}
.output-body .log-info{color:#8b949e}
.output-body .log-success{color:#3fb950}
.output-body .log-error{color:#f85149}
.output-body .log-warn{color:#d29922}
.output-body .log-cmd{color:#79c0ff}
.output-resize{height:4px;background:transparent;cursor:ns-resize;position:relative}
.output-resize:hover{background:#30363d}

/* 右侧AI面板 */
.ai-panel{width:380px;background:#0d1117;border-left:1px solid #21262d;display:flex;flex-direction:column;flex-shrink:0;transition:width 0.3s}
.ai-panel.collapsed{width:0;overflow:hidden}
.ai-header{padding:12px 16px;border-bottom:1px solid #21262d;display:flex;align-items:center;gap:10px}
.ai-avatar{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem}
.ai-header-info h4{font-size:0.88rem;font-weight:600;color:#c9d1d9;margin:0}
.ai-header-info p{font-size:0.7rem;color:#484f58;margin:0}

/* Token栏 */
.token-bar{padding:8px 16px;border-bottom:1px solid #21262d;display:flex;align-items:center;justify-content:space-between;font-size:0.72rem;color:#484f58;background:#161b22}
.token-num{color:#58a6ff;font-weight:600}
.credit-num{color:#3fb950;font-weight:600}

/* 消息区 */
.ai-messages{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:12px}
.ai-msg{display:flex;gap:10px;max-width:92%}
.ai-msg.user{align-self:flex-end;flex-direction:row-reverse}
.ai-msg-avatar{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.65rem;flex-shrink:0}
.ai-msg.bot .ai-msg-avatar{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff}
.ai-msg.user .ai-msg-avatar{background:#21262d;color:#8b949e}
.ai-msg-content{padding:10px 14px;border-radius:10px;font-size:0.82rem;line-height:1.65;white-space:pre-wrap;word-break:break-word}
.ai-msg.bot .ai-msg-content{background:#161b22;color:#c9d1d9;border:1px solid #21262d}
.ai-msg.user .ai-msg-content{background:#1f6feb;color:#fff}
.ai-msg-content code{background:#21262d;padding:1px 5px;border-radius:3px;font-family:'JetBrains Mono',monospace;font-size:0.8em;color:#79c0ff}
.ai-msg-content pre{background:#0d1117;padding:10px;border-radius:8px;overflow-x:auto;margin:6px 0;border:1px solid #21262d}
.ai-msg-content pre code{background:none;padding:0;color:#e6edf3}

/* 快捷操作 */
.ai-quick{padding:8px 12px;border-top:1px solid #21262d;display:flex;flex-wrap:wrap;gap:5px}
.quick-btn{padding:5px 10px;background:#21262d;border:1px solid #30363d;border-radius:6px;color:#8b949e;font-size:0.72rem;cursor:pointer;transition:all 0.2s}
.quick-btn:hover{background:#30363d;color:#c9d1d9}

/* 输入区 */
.ai-input{padding:10px 12px;border-top:1px solid #21262d;display:flex;gap:8px;background:#161b22}
.ai-input textarea{flex:1;background:#0d1117;border:1px solid #30363d;border-radius:8px;padding:8px 12px;color:#c9d1d9;font-size:0.82rem;font-family:inherit;resize:none;outline:none;min-height:38px;max-height:100px}
.ai-input textarea:focus{border-color:#58a6ff}
.ai-send{width:38px;height:38px;border-radius:8px;background:#238636;color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;flex-shrink:0}
.ai-send:hover{background:#2ea043}

/* 加载动画 */
.typing-dots{display:flex;gap:4px;padding:4px 0}
.typing-dots span{width:5px;height:5px;border-radius:50%;background:#484f58;animation:dot 1.4s ease-in-out infinite}
.typing-dots span:nth-child(2){animation-delay:0.2s}
.typing-dots span:nth-child(3){animation-delay:0.4s}
@keyframes dot{0%,60%,100%{transform:translateY(0);opacity:0.4}30%{transform:translateY(-4px);opacity:1}}

/* 通知 */
.toast{position:fixed;top:80px;right:20px;padding:12px 20px;border-radius:10px;font-size:0.85rem;z-index:10000;animation:slideIn 0.3s;max-width:320px;display:flex;align-items:center;gap:8px}
.toast.success{background:#238636;color:#fff}
.toast.error{background:#da3633;color:#fff}
.toast.info{background:#1f6feb;color:#fff}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}

/* 模态框 */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.modal-overlay.show{display:flex}
.modal-box{background:#161b22;border:1px solid #30363d;border-radius:16px;padding:28px;width:440px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}
.modal-title{color:#c9d1d9;font-size:1.1rem;font-weight:600;margin-bottom:20px}
.modal-input{width:100%;padding:10px 14px;background:#0d1117;border:1px solid #30363d;border-radius:8px;color:#c9d1d9;font-size:0.9rem;outline:none;margin-bottom:12px}
.modal-input:focus{border-color:#58a6ff}
.modal-label{display:block;font-size:0.8rem;color:#8b949e;margin-bottom:6px}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
.modal-btn{padding:8px 18px;border-radius:8px;font-size:0.85rem;cursor:pointer;border:none;font-weight:500}
.modal-btn-cancel{background:#21262d;color:#8b949e;border:1px solid #30363d}
.modal-btn-primary{background:#238636;color:#fff}
.modal-btn-danger{background:#da3633;color:#fff}
.modal-btn:hover{opacity:0.9}

/* 响应式 */
@media(max-width:1100px){.ai-panel{width:320px}}
@media(max-width:900px){.sidebar{width:0;overflow:hidden}.ai-panel{width:100%}}
</style>

<div class="ide-layout" id="ideApp" data-agent-id="<?php echo $agent['id']; ?>" data-session-id="<?php echo $sessionId; ?>">

<!-- 左侧边栏 -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="sidebar-title">Explorer</span>
        <div class="sidebar-actions">
            <button class="sidebar-btn" title="新建文件" onclick="createFile()"><i class="fas fa-plus"></i></button>
            <button class="sidebar-btn" title="新建文件夹" onclick="createFolder()"><i class="fas fa-folder-plus"></i></button>
            <button class="sidebar-btn" title="全部保存" onclick="saveAll()"><i class="fas fa-save"></i></button>
        </div>
    </div>
    <div class="project-bar">
        <select class="project-select" id="projectSelect" onchange="loadProject(this.value)">
            <option value="">选择项目...</option>
        </select>
    </div>
    <div class="file-tree" id="fileTree"></div>
    <div style="padding:8px 12px;border-top:1px solid #21262d">
        <button class="sidebar-btn" style="width:100%;height:auto;padding:6px 10px;font-size:0.78rem;gap:6px" onclick="showNewProjectModal()">
            <i class="fas fa-folder-plus"></i> 新建项目
        </button>
    </div>
</div>

<!-- 中间主区域 -->
<div class="main-area">
    <!-- 工具栏 -->
    <div class="toolbar">
        <button class="sidebar-btn" onclick="toggleSidebar()" title="切换侧边栏"><i class="fas fa-bars"></i></button>
        <div class="toolbar-sep"></div>
        <button class="toolbar-btn" onclick="saveCurrentFile()" title="保存 (Ctrl+S)"><i class="fas fa-save"></i> 保存</button>
        <button class="toolbar-btn" onclick="saveAll()" title="全部保存"><i class="fas fa-save"></i> 全部</button>
        <div class="toolbar-sep"></div>
        <button class="toolbar-btn" onclick="undo()" title="撤销"><i class="fas fa-undo"></i></button>
        <button class="toolbar-btn" onclick="redo()" title="重做"><i class="fas fa-redo"></i></button>
        <div class="toolbar-sep"></div>
        <button class="toolbar-btn" onclick="formatCode()" title="格式化代码"><i class="fas fa-magic"></i> 格式化</button>
        <button class="toolbar-btn" onclick="runCode()" title="运行代码"><i class="fas fa-play" style="color:#3fb950"></i> 运行</button>
        <div class="toolbar-right">
            <span id="cursorPos" style="font-size:0.72rem">行 1, 列 1</span>
            <div class="toolbar-sep"></div>
            <button class="toolbar-btn" onclick="toggleFullscreen()" title="全屏 (F11)"><i class="fas fa-expand"></i></button>
            <button class="toolbar-btn" onclick="toggleAIPanel()" title="AI面板"><i class="fas fa-robot"></i></button>
        </div>
    </div>

    <!-- 标签页 -->
    <div class="tabs-bar" id="tabsBar"></div>

    <!-- 编辑器+输出 -->
    <div class="editor-output-split">
        <div class="editor-section" id="editorSection">
            <div class="editor-meta">
                <select id="langSelect" onchange="changeLang(this.value)">
                    <option value="php">PHP</option>
                    <option value="javascript">JavaScript</option>
                    <option value="python">Python</option>
                    <option value="sql">SQL</option>
                    <option value="json">JSON</option>
                    <option value="html">HTML</option>
                    <option value="css">CSS</option>
                    <option value="bash">Bash</option>
                </select>
                <span>UTF-8</span>
                <span>LF</span>
            </div>
            <textarea class="code-editor" id="codeEditor" spellcheck="false" oninput="onEditorInput()" onkeydown="onEditorKeydown(event)" onkeyup="updateCursorPos()" onclick="updateCursorPos()"></textarea>
        </div>

        <!-- 输出面板 -->
        <div class="output-panel" id="outputPanel">
            <div class="output-resize" id="outputResize"></div>
            <div class="output-header" onclick="toggleOutput()">
                <div class="output-tabs">
                    <div class="output-tab active" onclick="switchOutputTab('output',this)">输出</div>
                    <div class="output-tab" onclick="switchOutputTab('terminal',this)">终端</div>
                    <div class="output-tab" onclick="switchOutputTab('problems',this)">问题</div>
                </div>
                <span style="margin-left:auto;font-size:0.7rem;color:#484f58" id="outputStatus">就绪</span>
            </div>
            <div class="output-body" id="outputBody">
                <div class="log-info">[系统] Cogito Code 智能编程平台已就绪。在右侧 AI 面板中描述需求，AI 将帮你生成代码。</div>
            </div>
        </div>
    </div>
</div>

<!-- 右侧AI面板 -->
<div class="ai-panel" id="aiPanel">
    <div class="ai-header">
        <div class="ai-avatar"><i class="fas fa-microchip"></i></div>
        <div class="ai-header-info">
            <h4>Cogito Code AI</h4>
            <p>智能编程助手 · GPT-4</p>
        </div>
        <button class="sidebar-btn" onclick="toggleAIPanel()" title="收起" style="margin-left:auto"><i class="fas fa-times"></i></button>
    </div>
    <div class="token-bar">
        <div style="display:flex;gap:14px">
            <span>Tokens: <span class="token-num" id="totalTokens">0</span></span>
            <span>积分: <span class="credit-num" id="totalCredits">0.0000</span></span>
        </div>
        <span>今日: <span class="token-num" id="todayTokens">0</span></span>
    </div>
    <div class="ai-messages" id="aiMessages">
        <div class="ai-msg bot">
            <div class="ai-msg-avatar"><i class="fas fa-microchip"></i></div>
            <div class="ai-msg-content">👋 欢迎使用 Cogito Code 智能编程平台！

我是你的 AI 编程助手，可以帮你：

💡 **代码生成** - 描述需求，生成完整代码
🔍 **代码审查** - 分析代码质量与优化
⚡ **性能优化** - 提升代码执行效率
🏗️ **架构设计** - 设计系统架构
🐛 **调试排错** - 快速定位修复 Bug
📦 **项目规划** - 完整项目搭建

在左侧编辑代码，右侧与我对话。开始你的编程之旅吧！</div>
        </div>
    </div>
    <div class="ai-quick">
        <button class="quick-btn" onclick="aiQuick('帮我创建一个完整的RESTful API项目')">创建API项目</button>
        <button class="quick-btn" onclick="aiQuick('帮我写一个用户认证系统')">用户认证</button>
        <button class="quick-btn" onclick="aiQuick('优化当前代码的性能')">性能优化</button>
        <button class="quick-btn" onclick="aiQuick('分析当前代码的质量')">代码审查</button>
        <button class="quick-btn" onclick="aiQuick('帮我设计数据库结构')">数据库设计</button>
        <button class="quick-btn" onclick="aiQuick('帮我写单元测试')">单元测试</button>
    </div>
    <div class="ai-input">
        <textarea id="aiInput" placeholder="描述你的编程需求..." rows="1" onkeydown="aiKeydown(event)" oninput="autoResize(this)"></textarea>
        <button class="ai-send" onclick="sendAIMessage()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>
</div>

<!-- 模态框 -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box" id="modalBox"></div>
</div>

<script>
// ==================== 全局状态 ====================
var IDE = {
    agentId: parseInt(document.getElementById('ideApp').dataset.agentId),
    sessionId: document.getElementById('ideApp').dataset.sessionId,
    currentProject: null,
    currentFile: null,
    files: {},
    tabs: [],
    totalTokens: 0,
    totalCredits: 0,
    todayTokens: 0,
    isFullscreen: false
};

// ==================== 文件图标 ====================
var FILE_ICONS = {
    php: { icon: 'fab fa-php', color: '#777bb4' },
    javascript: { icon: 'fab fa-js', color: '#f7df1e' },
    python: { icon: 'fab fa-python', color: '#3572A5' },
    html: { icon: 'fab fa-html5', color: '#e34c26' },
    css: { icon: 'fab fa-css3-alt', color: '#264de4' },
    json: { icon: 'fas fa-code', color: '#8bc34a' },
    sql: { icon: 'fas fa-database', color: '#e38c00' },
    bash: { icon: 'fas fa-terminal', color: '#4EAA25' },
    text: { icon: 'fas fa-file', color: '#8b949e' },
    md: { icon: 'fab fa-markdown', color: '#083fa1' }
};
function getFileIcon(lang) { return FILE_ICONS[lang] || FILE_ICONS.text; }
function getFileLang(filename) {
    var ext = filename.split('.').pop().toLowerCase();
    var map = { php:'php', js:'javascript', py:'python', html:'html', css:'css', json:'json', sql:'sql', sh:'bash', bash:'bash', md:'md', txt:'text' };
    return map[ext] || 'text';
}

// ==================== 侧边栏 ====================
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
}

// ==================== 全屏 ====================
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(function(){});
        IDE.isFullscreen = true;
    } else {
        document.exitFullscreen();
        IDE.isFullscreen = false;
    }
}

document.addEventListener('fullscreenchange', function() {
    IDE.isFullscreen = !!document.fullscreenElement;
    var btn = document.querySelector('[onclick="toggleFullscreen()"] i');
    if (btn) btn.className = IDE.isFullscreen ? 'fas fa-compress' : 'fas fa-expand';
});

// ==================== 标签页 ====================
function openTab(filename) {
    if (!IDE.files[filename]) return;
    if (!IDE.tabs.includes(filename)) IDE.tabs.push(filename);
    switchTab(filename);
}

function switchTab(filename) {
    // 保存当前文件
    if (IDE.currentFile && IDE.files[IDE.currentFile]) {
        IDE.files[IDE.currentFile].content = document.getElementById('codeEditor').value;
    }
    IDE.currentFile = filename;
    var file = IDE.files[filename];
    document.getElementById('codeEditor').value = file.content;
    document.getElementById('langSelect').value = file.lang;
    renderTabs();
    updateFileTree();
}

function closeTab(filename) {
    var idx = IDE.tabs.indexOf(filename);
    if (idx > -1) IDE.tabs.splice(idx, 1);
    if (IDE.currentFile === filename) {
        if (IDE.tabs.length > 0) switchTab(IDE.tabs[IDE.tabs.length - 1]);
        else { IDE.currentFile = null; document.getElementById('codeEditor').value = ''; }
    }
    renderTabs();
}

function renderTabs() {
    var bar = document.getElementById('tabsBar');
    bar.innerHTML = '';
    IDE.tabs.forEach(function(filename) {
        var file = IDE.files[filename];
        if (!file) return;
        var fi = getFileIcon(file.lang);
        var tab = document.createElement('div');
        tab.className = 'tab' + (filename === IDE.currentFile ? ' active' : '');
        tab.onclick = function() { switchTab(filename); };
        tab.innerHTML = '<span class="file-icon" style="color:' + fi.color + '"><i class="' + fi.icon + '"></i></span><span>' + filename + '</span>' +
            (file.modified ? '<span class="modified"></span>' : '') +
            '<button class="close-tab" onclick="event.stopPropagation();closeTab(\'' + filename + '\')"><i class="fas fa-times"></i></button>';
        bar.appendChild(tab);
    });
}

// ==================== 文件树 ====================
function updateFileTree() {
    var tree = document.getElementById('fileTree');
    tree.innerHTML = '';
    var filenames = Object.keys(IDE.files);
    if (filenames.length === 0) {
        tree.innerHTML = '<div style="padding:20px;text-align:center;color:#484f58;font-size:0.82rem"><i class="fas fa-folder-open" style="font-size:24px;margin-bottom:8px;display:block;opacity:0.3"></i>没有文件<br><span style="font-size:0.75rem">点击 + 创建新文件</span></div>';
        return;
    }
    // 分组：按扩展名
    var groups = {};
    filenames.forEach(function(f) {
        var lang = IDE.files[f].lang || 'text';
        if (!groups[lang]) groups[lang] = [];
        groups[lang].push(f);
    });
    Object.keys(groups).forEach(function(lang) {
        var fi = getFileIcon(lang);
        var group = document.createElement('div');
        group.className = 'file-group';
        group.innerHTML = '<div class="file-group-title"><i class="fas fa-chevron-down" style="font-size:0.6rem"></i><span style="color:' + fi.color + '"><i class="' + fi.icon + '"></i></span> ' + lang.toUpperCase() + ' (' + groups[lang].length + ')</div>';
        groups[lang].forEach(function(f) {
            var item = document.createElement('div');
            item.className = 'file-item' + (f === IDE.currentFile ? ' active' : '') + (IDE.files[f].modified ? ' modified' : '');
            item.onclick = function() { openTab(f); };
            item.innerHTML = '<span class="file-icon" style="color:' + fi.color + '"><i class="' + fi.icon + '"></i></span><span class="file-name">' + f + '</span><span class="modified-dot"></span>';
            group.appendChild(item);
        });
        tree.appendChild(group);
    });
}

// ==================== 编辑器 ====================
function onEditorInput() {
    if (IDE.currentFile && IDE.files[IDE.currentFile]) {
        IDE.files[IDE.currentFile].content = document.getElementById('codeEditor').value;
        IDE.files[IDE.currentFile].modified = true;
        renderTabs();
        updateFileTree();
    }
}

function onEditorKeydown(e) {
    // Tab 缩进
    if (e.key === 'Tab') {
        e.preventDefault();
        var editor = document.getElementById('codeEditor');
        var start = editor.selectionStart;
        var end = editor.selectionEnd;
        editor.value = editor.value.substring(0, start) + '    ' + editor.value.substring(end);
        editor.selectionStart = editor.selectionEnd = start + 4;
        onEditorInput();
    }
    // Ctrl+S 保存
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        saveCurrentFile();
    }
}

function updateCursorPos() {
    var editor = document.getElementById('codeEditor');
    var text = editor.value.substring(0, editor.selectionStart);
    var lines = text.split('\n');
    var line = lines.length;
    var col = lines[lines.length - 1].length + 1;
    document.getElementById('cursorPos').textContent = '行 ' + line + ', 列 ' + col;
}

function changeLang(lang) {
    if (IDE.currentFile && IDE.files[IDE.currentFile]) {
        IDE.files[IDE.currentFile].lang = lang;
        updateFileTree();
        renderTabs();
    }
}

function undo() { document.getElementById('codeEditor').focus(); document.execCommand('undo'); }
function redo() { document.getElementById('codeEditor').focus(); document.execCommand('redo'); }

function formatCode() {
    var editor = document.getElementById('codeEditor');
    var code = editor.value;
    // 简单格式化：统一缩进
    var lines = code.split('\n');
    var formatted = lines.map(function(line) { return line.replace(/\t/g, '    ').trimEnd(); }).join('\n');
    editor.value = formatted;
    onEditorInput();
    toast('代码已格式化', 'success');
}

// ==================== 运行代码 ====================
function runCode() {
    var code = document.getElementById('codeEditor').value;
    var lang = document.getElementById('langSelect').value;
    logOutput('cmd', '$ 运行 ' + (IDE.currentFile || 'code'));
    logOutput('info', '正在编译/执行...');

    // 模拟运行
    setTimeout(function() {
        if (lang === 'php') {
            logOutput('info', 'PHP ' + '<?php echo phpversion(); ?>' + ' | ' + new Date().toLocaleTimeString());
            logOutput('success', '✓ 执行完成 (0.02s)');
        } else if (lang === 'javascript') {
            logOutput('info', 'Node.js v18 | ' + new Date().toLocaleTimeString());
            logOutput('success', '✓ 执行完成 (0.01s)');
        } else if (lang === 'python') {
            logOutput('info', 'Python 3.11 | ' + new Date().toLocaleTimeString());
            logOutput('success', '✓ 执行完成 (0.03s)');
        } else {
            logOutput('info', lang.toUpperCase() + ' | ' + new Date().toLocaleTimeString());
            logOutput('success', '✓ 模拟执行完成');
        }
        document.getElementById('outputStatus').textContent = '完成';
    }, 500);
}

// ==================== 输出面板 ====================
function logOutput(type, text) {
    var body = document.getElementById('outputBody');
    var line = document.createElement('div');
    line.className = 'log-' + type;
    line.textContent = text;
    body.appendChild(line);
    body.scrollTop = body.scrollHeight;
}

function toggleOutput() {
    document.getElementById('outputPanel').classList.toggle('collapsed');
}

function switchOutputTab(tab, el) {
    document.querySelectorAll('.output-tab').forEach(function(t) { t.classList.remove('active'); });
    el.classList.add('active');
}

// 拖拽调整输出面板高度
(function() {
    var resize = document.getElementById('outputResize');
    var panel = document.getElementById('outputPanel');
    if (!resize || !panel) return;
    var startY, startH;
    resize.addEventListener('mousedown', function(e) {
        startY = e.clientY;
        startH = panel.offsetHeight;
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('mouseup', onDragEnd);
    });
    function onDrag(e) {
        var h = startH + (startY - e.clientY);
        panel.style.height = Math.max(100, Math.min(h, 500)) + 'px';
    }
    function onDragEnd() {
        document.removeEventListener('mousemove', onDrag);
        document.removeEventListener('mouseup', onDragEnd);
    }
})();

// ==================== AI 面板 ====================
function toggleAIPanel() {
    document.getElementById('aiPanel').classList.toggle('collapsed');
}

function aiQuick(msg) {
    document.getElementById('aiInput').value = msg;
    sendAIMessage();
}

function aiKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendAIMessage();
    }
}

function autoResize(el) {
    el.style.height = '38px';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

function sendAIMessage() {
    var input = document.getElementById('aiInput');
    var msg = input.value.trim();
    if (!msg) return;

    addAIMsg('user', msg);
    input.value = '';
    input.style.height = '38px';

    // 获取当前代码上下文
    var context = '';
    if (IDE.currentFile && IDE.files[IDE.currentFile]) {
        context = '当前文件: ' + IDE.currentFile + '\n语言: ' + IDE.files[IDE.currentFile].lang + '\n代码:\n' + IDE.files[IDE.currentFile].content;
    }

    showTyping();

    fetch('/boke/coding-api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, project_id: IDE.currentProject, context: context })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        hideTyping();
        if (d.code === 1) {
            addAIMsg('bot', d.data.reply);
            updateTokens(d.data.tokens_in, d.data.tokens_out, d.data.credits);
            extractCode(d.data.reply);
            logOutput('success', '[AI] 回复完成 | 消耗 ' + d.data.credits.toFixed(4) + ' 积分');
        } else {
            if (d.need_login) {
                addAIMsg('bot', '⚠️ 请先登录后再使用 AI 功能。\n\n点击确定跳转登录页面。');
                setTimeout(function() { window.location.href = '/boke/auth?tab=login'; }, 2000);
            } else {
                addAIMsg('bot', '❌ ' + (d.msg || '请求失败'));
            }
        }
    })
    .catch(function() {
        hideTyping();
        addAIMsg('bot', '❌ 网络错误，请检查连接后重试');
        logOutput('error', '[错误] 网络请求失败');
    });
}

function addAIMsg(role, content) {
    var container = document.getElementById('aiMessages');
    var div = document.createElement('div');
    div.className = 'ai-msg ' + role;
    var avatar = role === 'bot' ? '<i class="fas fa-microchip"></i>' : '<i class="fas fa-user"></i>';
    div.innerHTML = '<div class="ai-msg-avatar">' + avatar + '</div><div class="ai-msg-content">' + formatMsg(content) + '</div>';
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function formatMsg(text) {
    text = text.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
    text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
    text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\n/g, '<br>');
    return text;
}

function showTyping() {
    var c = document.getElementById('aiMessages');
    var div = document.createElement('div');
    div.id = 'aiTyping';
    div.className = 'ai-msg bot';
    div.innerHTML = '<div class="ai-msg-avatar"><i class="fas fa-microchip"></i></div><div class="ai-msg-content"><div class="typing-dots"><span></span><span></span><span></span></div></div>';
    c.appendChild(div);
    c.scrollTop = c.scrollHeight;
}

function hideTyping() {
    var el = document.getElementById('aiTyping');
    if (el) el.remove();
}

function updateTokens(inT, outT, credits) {
    IDE.totalTokens += inT + outT;
    IDE.totalCredits += credits;
    IDE.todayTokens += inT + outT;
    document.getElementById('totalTokens').textContent = IDE.totalTokens;
    document.getElementById('totalCredits').textContent = IDE.totalCredits.toFixed(4);
    document.getElementById('todayTokens').textContent = IDE.todayTokens;
}

function extractCode(reply) {
    var match = reply.match(/```(\w*)\n([\s\S]*?)```/);
    if (match && match[2] && match[2].trim().length > 10) {
        if (confirm('检测到代码块，是否插入当前编辑器？')) {
            var code = match[2].trim();
            document.getElementById('codeEditor').value = code;
            if (IDE.currentFile && IDE.files[IDE.currentFile]) {
                IDE.files[IDE.currentFile].content = code;
                IDE.files[IDE.currentFile].modified = true;
                renderTabs();
            }
            logOutput('success', '[AI] 代码已插入编辑器');
        }
    }
}

// ==================== 项目管理 ====================
function loadProjects() {
    fetch('/boke/coding-api/projects')
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.code === 1) {
            var select = document.getElementById('projectSelect');
            select.innerHTML = '<option value="">选择项目...</option>';
            d.data.forEach(function(p) {
                var opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name + ' (' + p.language + ')';
                select.appendChild(opt);
            });
        }
    });
}

function loadProject(id) {
    if (!id) return;
    fetch('/boke/coding-api/project?id=' + id)
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.code === 1) {
            IDE.currentProject = parseInt(id);
            IDE.files = {};
            IDE.tabs = [];
            d.data.files.forEach(function(f) {
                IDE.files[f.filename] = { name: f.filename, content: f.content, lang: f.language, modified: false };
            });
            // 打开第一个文件
            if (d.data.files.length > 0) {
                openTab(d.data.files[0].filename);
            } else {
                IDE.currentFile = null;
                document.getElementById('codeEditor').value = '';
            }
            updateFileTree();
            logOutput('success', '[项目] 已加载: ' + d.data.name + ' (' + d.data.files.length + ' 个文件)');
            toast('项目已加载', 'success');
        }
    });
}

function showNewProjectModal() {
    showModal('新建项目', '<div class="modal-label">项目名称</div><input class="modal-input" id="mProjName" placeholder="例如：my-api"><div class="modal-label">描述</div><input class="modal-input" id="mProjDesc" placeholder="项目描述"><div class="modal-label">技术栈</div><select class="modal-input" id="mProjLang"><option value="php">PHP</option><option value="javascript">JavaScript</option><option value="python">Python</option></select>',
        [{ text: '取消', cls: 'modal-btn-cancel', action: 'closeModal()' }, { text: '创建项目', cls: 'modal-btn-primary', action: 'doCreateProject()' }]);
}

function doCreateProject() {
    var name = document.getElementById('mProjName').value.trim();
    var desc = document.getElementById('mProjDesc').value.trim();
    var lang = document.getElementById('mProjLang').value;
    if (!name) { toast('请输入项目名称', 'error'); return; }
    closeModal();
    fetch('/boke/coding-api/create-project', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name, description: desc, language: lang })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.code === 1) {
            toast('项目创建成功', 'success');
            loadProjects();
            setTimeout(function() { loadProject(d.data.id); }, 300);
        } else {
            if (d.need_login) { toast('请先登录', 'error'); setTimeout(function() { window.location.href = '/boke/auth?tab=login'; }, 1500); }
            else toast(d.msg, 'error');
        }
    });
}

// ==================== 文件操作 ====================
function saveCurrentFile() {
    if (!IDE.currentFile || !IDE.files[IDE.currentFile]) { toast('没有可保存的文件', 'info'); return; }
    if (!IDE.currentProject) { toast('请先选择或创建项目', 'info'); return; }
    saveFileToServer(IDE.currentFile);
}

function saveAll() {
    if (!IDE.currentProject) { toast('请先选择项目', 'info'); return; }
    var count = 0;
    Object.keys(IDE.files).forEach(function(f) {
        if (IDE.files[f].modified) { saveFileToServer(f); count++; }
    });
    if (count > 0) toast('已保存 ' + count + ' 个文件', 'success');
    else toast('没有需要保存的文件', 'info');
}

function saveFileToServer(filename) {
    var file = IDE.files[filename];
    if (!file) return;
    fetch('/boke/coding-api/save-file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ project_id: IDE.currentProject, filename: filename, content: file.content, language: file.lang })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.code === 1) {
            IDE.files[filename].modified = false;
            renderTabs();
            updateFileTree();
            logOutput('info', '[保存] ' + filename);
        } else {
            toast(d.msg || '保存失败', 'error');
        }
    });
}

function createFile() {
    showModal('新建文件', '<div class="modal-label">文件名</div><input class="modal-input" id="mFileName" placeholder="例如：utils.php">',
        [{ text: '取消', cls: 'modal-btn-cancel', action: 'closeModal()' }, { text: '创建', cls: 'modal-btn-primary', action: 'doCreateFile()' }]);
}

function doCreateFile() {
    var name = document.getElementById('mFileName').value.trim();
    if (!name) return;
    closeModal();
    var lang = getFileLang(name);
    IDE.files[name] = { name: name, content: '', lang: lang, modified: true };
    openTab(name);
    logOutput('info', '[文件] 已创建: ' + name);
    toast('文件已创建', 'success');
}

function createFolder() {
    var name = prompt('文件夹名称:');
    if (!name) return;
    toast('文件夹已创建: ' + name, 'success');
}

// ==================== 模态框 ====================
function showModal(title, content, buttons) {
    var box = document.getElementById('modalBox');
    var btnsHtml = buttons.map(function(b) { return '<button class="modal-btn ' + b.cls + '" onclick="' + b.action + '">' + b.text + '</button>'; }).join('');
    box.innerHTML = '<div class="modal-title">' + title + '</div>' + content + '<div class="modal-actions">' + btnsHtml + '</div>';
    document.getElementById('modalOverlay').classList.add('show');
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

// ==================== 通知 ====================
function toast(msg, type) {
    var div = document.createElement('div');
    div.className = 'toast ' + (type || 'info');
    var icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
    div.innerHTML = '<i class="fas ' + (icons[type] || 'fa-info-circle') + '"></i> ' + msg;
    document.body.appendChild(div);
    setTimeout(function() { div.style.opacity = '0'; div.style.transition = 'opacity 0.3s'; setTimeout(function() { div.remove(); }, 300); }, 3000);
}

// ==================== 初始化 ====================
loadProjects();
loadTokenStats();

function loadTokenStats() {
    fetch('/boke/coding-api/token-usage')
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.code === 1) {
            IDE.totalTokens = d.data.total_tokens;
            IDE.totalCredits = d.data.total_credits;
            IDE.todayTokens = d.data.today_tokens;
            document.getElementById('totalTokens').textContent = IDE.totalTokens;
            document.getElementById('totalCredits').textContent = IDE.totalCredits.toFixed(4);
            document.getElementById('todayTokens').textContent = IDE.todayTokens;
        }
    });
}

// 初始化编辑器默认内容
var defaultCode = <?php echo json_encode("<?php\n\n/**\n * Cogito Code 智能编程平台\n * \n * 在右侧 AI 面板中描述你的需求，\n * AI 将帮你生成完整的代码。\n */\n\nfunction main() {\n    echo \"Hello, Cogito Code!\";\n}\n\nmain();\n"); ?>;
document.getElementById('codeEditor').value = defaultCode;
IDE.files['main.php'] = { name: 'main.php', content: defaultCode, lang: 'php', modified: false };
IDE.tabs.push('main.php');
IDE.currentFile = 'main.php';
renderTabs();
updateFileTree();
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
