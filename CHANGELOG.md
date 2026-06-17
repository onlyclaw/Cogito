<img width="1920" height="910" alt="image" src="https://github.com/user-attachments/assets/fe3e7445-6876-42e7-b7c9-1ea009e8356a" />

# 更新日志

## 2026-06-16 Boke 2.0 - AI时代内容平台升级

### 升级概览
将传统博客系统全面升级为符合 AI 新时代的内容平台，包含视觉重设计、深色模式、AI 智能助手、代码高亮、阅读体验优化等。

### 新增功能

#### 1. 全新视觉系统
- 玻璃拟态（Glassmorphism）设计风格
- 渐变背景 + 动态动画效果
- 更大的圆角（16-24px）
- 浮动装饰球动画
- 品牌图标设计

#### 2. 深色/浅色模式
- 一键切换深色/浅色主题
- localStorage 记忆用户选择
- 跟随系统偏好（prefers-color-scheme）
- 平滑过渡动画

#### 3. AI 智能助手
- 浮动聊天按钮（脉冲动画）
- AI 对话窗口（打字指示器）
- 快捷操作按钮（总结/推荐/代码/翻译）
- 支持配置外部 API（OpenAI/Claude）
- 内置智能回复（无需 API Key）

#### 4. 代码高亮
- Prism.js 代码语法高亮
- 支持 PHP、JavaScript、Python、SQL、JSON、CSS、HTML
- 仿 macOS 终端窗口样式（红黄绿三点）

#### 5. 阅读体验优化
- 阅读进度条（顶部细线）
- 预计阅读时间显示
- 文章目录导航（TOC）
- 文章收藏功能

#### 6. 交互升级
- 首页无限滚动加载
- 社交分享按钮（微信/微博/Twitter/复制链接）
- 文章收藏/取消收藏
- 打字机效果的 Hero 标题

### 数据库变更
- 新增 `favorites` 收藏表
- 新增 AI 配置项（ai_api_key, ai_api_url, ai_model, ai_enabled）

### 修改的文件
| 文件 | 变更 |
|------|------|
| `assets/css/style.css` | 全新视觉系统，深色模式支持 |
| `assets/css/chat.css` | AI 聊天窗口样式 |
| `assets/js/main.js` | 深色模式、无限滚动、阅读进度 |
| `assets/js/chat.js` | AI 聊天交互逻辑 |
| `views/header.php` | 主题切换按钮、新依赖引入 |
| `views/footer.php` | AI 聊天组件、Prism.js |
| `front/index.php` | 无限滚动支持、新 Hero 设计 |
| `front/post.php` | 阅读进度、TOC、分享、收藏 |
| `api.php` | 收藏 API、AI 接口 |
| `admin/settings.php` | AI 配置面板 |
| `init.sql` | 收藏表、AI 配置项 |

---

## 2026-06-16 修复系统问题

### 问题描述
系统安装后前后台均无法正常使用，出现 403 Forbidden 和 500 Internal Server Error 错误。

### 修复的问题

#### 1. Nginx 重定向循环（500 错误）
**问题**：nginx 的 `location /admin/` 规则匹配了 `/boke/admin/` 路径，导致重定向循环

**错误日志**：
```
rewrite or internal redirection cycle while redirect to named location "@php"
```

**解决方案**：在 nginx 虚拟主机配置 (`vhosts/0localhost_80.conf`) 中添加博客专用路由：
```nginx
# 博客系统 - 前台和后台路由（必须在通用 /admin/ 规则之前）
location /boke/ {
    try_files $uri $uri/ /boke/index.php?$query_string;
}
```

**修改文件**：`D:\phpstudy_pro\Extensions\Nginx1.15.11\conf\vhosts\0localhost_80.conf`

---

#### 2. URI 解析错误（404 错误）
**问题**：nginx 传递的 URI 是 `/boke/`，但路由代码期望的是根路径 `/`

**原因**：`index.php` 直接使用 `parse_url($_SERVER['REQUEST_URI'])` 获取路径，没有移除项目前缀

**解决方案**：修改 `index.php`，自动检测并移除项目路径前缀：
```php
// 移除项目路径前缀（支持子目录部署）
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
```

**修改文件**：`index.php`

---

#### 3. 数据库密码不匹配（登录失败）
**问题**：`init.sql` 中的密码 hash 与 `admin123` 不匹配

**错误表现**：登录时提示"用户名或密码错误"

**解决方案**：
- 更新 `init.sql` 中的密码 hash
- 直接更新数据库中的密码

**修改文件**：`init.sql`

---

#### 4. SQL 查询错误（后台控制台崩溃）
**问题**：`dashboard.php` 查询 `views` 表的 `views` 列，但该列不存在

**错误信息**：
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'views' in 'field list'
```

**原因**：`views` 表是访问记录表，只有 `id`、`post_id`、`ip`、`user_agent`、`created_at` 列，没有 `views` 列

**解决方案**：将 `SUM(views)` 改为 `COUNT(*)`：
```php
// 修改前
$todayViews = db()->fetchColumn("SELECT IFNULL(SUM(views), 0) FROM views WHERE DATE(created_at) = ?", [$today]);

// 修改后
$todayViews = db()->fetchColumn("SELECT COUNT(*) FROM views WHERE DATE(created_at) = ?", [$today]);
```

**修改文件**：`admin/dashboard.php`

---

#### 5. 数据库连接失败处理不当（页面崩溃）
**问题**：数据库未初始化时，`Database` 类直接 `die()` 终止程序

**解决方案**：
- `Database` 类改为静默失败，设置 `$connected = false`
- 添加 `is_installed()` 函数检查数据库状态
- `get_option()` 等函数在数据库不可用时返回默认值
- 未安装时显示安装提示页面

**修改文件**：
- `database.php`
- `functions.php`

---

#### 6. 后台登录逻辑错误
**问题**：登录检查在 POST 处理之前，导致登录表单提交时被重定向

**解决方案**：重新组织 `admin/index.php` 逻辑，优先处理登录请求

**修改文件**：`admin/index.php`

---

### 修改的文件清单

| 文件 | 修改内容 |
|------|----------|
| `index.php` | 修复 URI 解析，支持子目录部署 |
| `database.php` | 数据库连接失败时静默处理 |
| `functions.php` | 添加 `is_installed()` 函数，函数降级处理 |
| `admin/index.php` | 修复登录逻辑 |
| `admin/dashboard.php` | 修复 SQL 查询错误 |
| `init.sql` | 修复管理员密码 hash |
| `README.md` | 添加 Nginx 配置说明 |

---

### Nginx 配置说明

如果使用 Nginx 作为 Web 服务器，需要在虚拟主机配置中添加以下内容：

```nginx
# 博客系统路由（必须在其他 location 规则之前）
location /boke/ {
    try_files $uri $uri/ /boke/index.php?$query_string;
}
```

### 验证结果

所有页面测试通过：
- 前台首页：200 ✓
- 搜索页：200 ✓
- RSS：200 ✓
- 安装页：200 ✓
- 后台登录：200 ✓
- 控制台：200 ✓
- 文章管理：200 ✓
- 分类管理：200 ✓
- 标签管理：200 ✓
- 评论管理：200 ✓
- 系统设置：200 ✓
- 个人资料：200 ✓
