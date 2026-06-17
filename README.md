<img width="1920" height="910" alt="image" src="https://github.com/user-attachments/assets/0513f8f0-3456-44b1-bb54-9ca5cab3c367" />
# Cogito - 智能体驱动的AI内容创作平台

> "我思故创造" - Cogito, ergo creo

一个由 AI 智能体驱动的新型内容创作平台，融合智能体协作写作、AI 媒体生成、闭环创作系统。

> 📋 更新记录请查看 [CHANGELOG.md](CHANGELOG.md)

## 项目结构

```
boke/
├── config.php          # 配置文件（数据库、站点信息）
├── database.php        # PDO数据库类
├── functions.php       # 公共函数库
├── index.php           # 入口路由
├── install.php         # 安装页面
├── api.php             # 前台API（评论、点赞、上传）
├── .htaccess           # Apache伪静态
├── web.config          # IIS伪静态
├── init.sql            # 数据库初始化SQL
├── README.md           # 项目说明文档
│
├── front/              # 前台页面
│   ├── index.php       # 首页
│   ├── post.php        # 文章详情
│   ├── category.php    # 分类列表
│   ├── tag.php         # 标签列表
│   ├── search.php      # 搜索页
│   └── rss.php         # RSS订阅
│
├── admin/              # 后台管理
│   ├── index.php       # 后台路由+登录
│   ├── dashboard.php   # 控制台
│   ├── posts.php       # 文章管理
│   ├── post_edit.php   # 文章编辑（富文本编辑器）
│   ├── categories.php  # 分类管理
│   ├── tags.php        # 标签管理
│   ├── comments.php    # 评论管理
│   ├── settings.php    # 系统设置
│   └── profile.php     # 个人资料
│
├── views/              # 前台模板
│   ├── header.php      # 头部导航
│   ├── footer.php      # 底部页脚
│   ├── sidebar.php     # 侧边栏
│   ├── comment_item.php # 评论项（递归）
│   └── 404.php         # 404页面
│
├── assets/             # 前端资源
│   ├── css/
│   │   ├── style.css   # 前台样式
│   │   └── admin.css   # 后台样式
│   └── js/
│       ├── main.js     # 前台脚本
│       └── admin.js    # 后台脚本
│
└── uploads/            # 上传文件目录
    ├── images/
    └── avatars/
```

## 功能特性

### 前台功能
- 响应式博客首页（置顶/推荐文章）
- 文章详情页（上下篇导航、文章点赞）
- 分类浏览、标签浏览
- 全文搜索（关键词高亮）
- 嵌套评论系统（支持回复）
- RSS订阅
- 侧边栏（关于博主、热门文章、分类、标签云）
- 图片点击放大、回到顶部
- 404错误页面

### 后台功能
- 控制台仪表盘（文章/评论/浏览量统计）
- 文章管理（富文本编辑器、封面图、标签、分类）
- 分类管理（增删改、排序）
- 标签管理
- 评论管理（审核、批量操作、垃圾过滤）
- 系统设置（站点信息、阅读设置、评论设置）
- 个人资料（头像上传、密码修改）

## 使用方法

### 环境要求
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx

### 安装步骤

1. 确保 phpStudy 已启动（Apache + MySQL）
2. 打开浏览器访问安装页面：

```
http://localhost/boke/install.php
```

3. 填写数据库信息（默认 root / root），点击安装
4. 安装完成后访问：

- 前台：`http://localhost/boke/`
- 后台：`http://localhost/boke/admin/`
- 默认账号：**admin / admin123**

### Nginx 配置（如使用 Nginx）

如果使用 Nginx，需要在虚拟主机配置中添加以下内容：

```nginx
# 博客系统路由
location /boke/ {
    try_files $uri $uri/ /boke/index.php?$query_string;
}
```

### 注意事项
- 安装完成后建议删除 `install.php` 文件
- 上传文件保存在 `uploads/` 目录，请确保该目录可写
- 数据库配置在 `config.php` 中修改
- 默认管理员密码：admin123

## 技术栈

- 后端：PHP 7.4（PDO）
- 数据库：MySQL 5.7
- 前端：Bootstrap 5、Font Awesome 6、Google Fonts
- 编辑器：自定义 contenteditable 富文本编辑器
- 架构：轻量级 MVC
