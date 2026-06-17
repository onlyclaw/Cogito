<?php
/**
 * 文章编辑页
 */
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $postId > 0;
$pageTitle = $isEdit ? '编辑文章' : '写文章';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post('title');
    $content = post('content');
    $summary = post('summary');
    $categoryId = (int)post('category_id', 0);
    $tagIds = post('tags') ?: [];
    $status = post('status', 'draft');
    $isTop = (int)post('is_top', 0);
    $isRecommend = (int)post('is_recommend', 0);
    $cover = post('cover');

    if (!$title) {
        $error = '请输入文章标题';
    } elseif (!$content) {
        $error = '请输入文章内容';
    } else {
        // 生成摘要
        if (!$summary) {
            $summary = cut_str(strip_tags($content), 200);
        }

        $data = [
            'title' => $title,
            'content' => $content,
            'summary' => $summary,
            'category_id' => $categoryId,
            'user_id' => $_SESSION['user_id'],
            'status' => $status,
            'is_top' => $isTop,
            'is_recommend' => $isRecommend,
            'cover' => $cover,
        ];

        if ($isEdit) {
            db()->update('posts', $data, 'id = ?', [$postId]);
            $id = $postId;
        } else {
            $id = db()->insert('posts', $data);
        }

        // 保存标签
        db()->delete('post_tags', 'post_id = ?', [$id]);
        if (!empty($tagIds)) {
            foreach ($tagIds as $tagId) {
                $tagId = (int)$tagId;
                if ($tagId > 0) {
                    db()->insert('post_tags', ['post_id' => $id, 'tag_id' => $tagId]);
                }
            }
        }

        set_flash('success', '文章保存成功');
        redirect(SITE_URL . '/admin/posts');
    }
}

// 加载文章数据
$post = [];
$postTags = [];
if ($isEdit) {
    $post = db()->fetchOne("SELECT * FROM posts WHERE id = ?", [$postId]);
    if (!$post) {
        set_flash('error', '文章不存在');
        redirect(SITE_URL . '/admin/posts');
    }
    $postTags = db()->fetchOne("SELECT GROUP_CONCAT(tag_id) as ids FROM post_tags WHERE post_id = ?", [$postId]);
    $postTags = $postTags ? explode(',', $postTags['ids']) : [];
}

// 获取分类和标签
$categories = db()->fetchAll("SELECT * FROM categories ORDER BY sort_order ASC");
$allTags = db()->fetchAll("SELECT * FROM tags ORDER BY name ASC");

include __DIR__ . '/../admin/views/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-edit me-2"></i><?php echo $pageTitle; ?></h1>
    </div>
    <div>
        <a href="<?php echo SITE_URL; ?>/admin/posts" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>返回列表
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo clean($error); ?>
</div>
<?php endif; ?>

<form method="POST" id="postForm">
    <div class="row">
        <!-- 左侧主内容 -->
        <div class="col-lg-9">
            <div class="admin-card mb-4">
                <div class="admin-card-body">
                    <div class="mb-3">
                        <input type="text" name="title" class="form-control form-control-lg" placeholder="请输入文章标题"
                            value="<?php echo clean($post['title'] ?? ''); ?>" required>
                    </div>

                    <!-- 富文本编辑器 -->
                    <div class="editor-wrapper">
                        <div class="editor-toolbar">
                            <button type="button" class="editor-btn" onclick="execCmd('bold')" title="加粗"><i class="fas fa-bold"></i></button>
                            <button type="button" class="editor-btn" onclick="execCmd('italic')" title="斜体"><i class="fas fa-italic"></i></button>
                            <button type="button" class="editor-btn" onclick="execCmd('underline')" title="下划线"><i class="fas fa-underline"></i></button>
                            <button type="button" class="editor-btn" onclick="execCmd('strikeThrough')" title="删除线"><i class="fas fa-strikethrough"></i></button>
                            <span class="editor-divider"></span>
                            <button type="button" class="editor-btn" onclick="execCmd('formatBlock', 'h1')" title="标题1">H1</button>
                            <button type="button" class="editor-btn" onclick="execCmd('formatBlock', 'h2')" title="标题2">H2</button>
                            <button type="button" class="editor-btn" onclick="execCmd('formatBlock', 'h3')" title="标题3">H3</button>
                            <button type="button" class="editor-btn" onclick="execCmd('formatBlock', 'p')" title="正文">P</button>
                            <span class="editor-divider"></span>
                            <button type="button" class="editor-btn" onclick="execCmd('insertUnorderedList')" title="无序列表"><i class="fas fa-list-ul"></i></button>
                            <button type="button" class="editor-btn" onclick="execCmd('insertOrderedList')" title="有序列表"><i class="fas fa-list-ol"></i></button>
                            <span class="editor-divider"></span>
                            <button type="button" class="editor-btn" onclick="execCmd('justifyLeft')" title="左对齐"><i class="fas fa-align-left"></i></button>
                            <button type="button" class="editor-btn" onclick="execCmd('justifyCenter')" title="居中"><i class="fas fa-align-center"></i></button>
                            <button type="button" class="editor-btn" onclick="execCmd('justifyRight')" title="右对齐"><i class="fas fa-align-right"></i></button>
                            <span class="editor-divider"></span>
                            <button type="button" class="editor-btn" onclick="insertLink()" title="插入链接"><i class="fas fa-link"></i></button>
                            <button type="button" class="editor-btn" onclick="insertImage()" title="插入图片"><i class="fas fa-image"></i></button>
                            <button type="button" class="editor-btn" onclick="insertCode()" title="插入代码"><i class="fas fa-code"></i></button>
                            <button type="button" class="editor-btn" onclick="insertQuote()" title="插入引用"><i class="fas fa-quote-right"></i></button>
                            <span class="editor-divider"></span>
                            <button type="button" class="editor-btn" onclick="execCmd('removeFormat')" title="清除格式"><i class="fas fa-eraser"></i></button>
                            <button type="button" class="editor-btn" onclick="toggleSource()" title="源码模式"><i class="fas fa-code"></i> 源码</button>
                        </div>
                        <div id="editor" class="editor-content" contenteditable="true"><?php echo $post['content'] ?? ''; ?></div>
                        <textarea name="content" id="editorSource" style="display:none;"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- 摘要 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5>文章摘要</h5>
                </div>
                <div class="admin-card-body">
                    <textarea name="summary" class="form-control" rows="3" placeholder="留空将自动截取正文内容作为摘要"><?php echo clean($post['summary'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 右侧设置 -->
        <div class="col-lg-3">
            <!-- 发布 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5>发布</h5>
                </div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">状态</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?php echo ($post['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>草稿</option>
                            <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>发布</option>
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_top" value="1" id="isTop"
                            <?php echo ($post['is_top'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isTop">置顶文章</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_recommend" value="1" id="isRecommend"
                            <?php echo ($post['is_recommend'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isRecommend">推荐文章</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i>保存文章
                    </button>
                </div>
            </div>

            <!-- 分类 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5>分类</h5>
                </div>
                <div class="admin-card-body">
                    <select name="category_id" class="form-select">
                        <option value="0">-- 选择分类 --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($post['category_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo clean($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 标签 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5>标签</h5>
                </div>
                <div class="admin-card-body">
                    <div class="tag-select">
                        <?php foreach ($allTags as $tag): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>"
                                id="tag<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $postTags) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tag<?php echo $tag['id']; ?>">
                                #<?php echo clean($tag['name']); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 封面图 -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h5>封面图</h5>
                </div>
                <div class="admin-card-body">
                    <input type="hidden" name="cover" id="coverInput" value="<?php echo clean($post['cover'] ?? ''); ?>">
                    <div class="cover-preview" id="coverPreview">
                        <?php if (!empty($post['cover'])): ?>
                        <img src="<?php echo clean($post['cover']); ?>" alt="" style="max-width:100%;border-radius:8px;">
                        <?php else: ?>
                        <div class="cover-placeholder">
                            <i class="fas fa-image"></i>
                            <p>点击上传封面图</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="coverFile" accept="image/*" class="d-none">
                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-2" onclick="document.getElementById('coverFile').click()">
                        <i class="fas fa-upload me-1"></i>上传封面
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 mt-2" onclick="clearCover()" id="clearCoverBtn"
                        <?php echo empty($post['cover']) ? 'style="display:none"' : ''; ?>>
                        <i class="fas fa-times me-1"></i>移除封面
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$extraJs = "
const SITE_URL = '" . SITE_URL . "';

// 编辑器命令
function execCmd(cmd, val) {
    document.execCommand(cmd, false, val || null);
    document.getElementById('editor').focus();
}

// 插入链接
function insertLink() {
    const url = prompt('请输入链接地址:', 'https://');
    if (url) execCmd('createLink', url);
}

// 插入图片
function insertImage() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function() {
        const file = this.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        fetch(SITE_URL + '/admin/upload', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.code === 1) {
                execCmd('insertImage', d.url);
            } else {
                alert(d.msg);
            }
        });
    };
    input.click();
}

// 插入代码块
function insertCode() {
    const code = prompt('请输入代码:');
    if (code) execCmd('insertHTML', '<pre><code>' + code + '</code></pre><p></p>');
}

// 插入引用
function insertQuote() {
    execCmd('formatBlock', 'blockquote');
}

// 源码模式切换
let sourceMode = false;
function toggleSource() {
    const editor = document.getElementById('editor');
    const source = document.getElementById('editorSource');
    sourceMode = !sourceMode;
    if (sourceMode) {
        source.value = editor.innerHTML;
        editor.style.display = 'none';
        source.style.display = 'block';
    } else {
        editor.innerHTML = source.value;
        source.style.display = 'none';
        editor.style.display = 'block';
    }
}

// 表单提交前同步内容
document.getElementById('postForm').addEventListener('submit', function() {
    const editor = document.getElementById('editor');
    const source = document.getElementById('editorSource');
    if (sourceMode) {
        source.value = editor.innerHTML;
    }
    editor.innerHTML = sourceMode ? source.value : editor.innerHTML;
});

// 封面上传
document.getElementById('coverFile').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('file', file);
    fetch(SITE_URL + '/admin/upload', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(d => {
        if (d.code === 1) {
            document.getElementById('coverInput').value = d.url;
            document.getElementById('coverPreview').innerHTML = '<img src=\"' + d.url + '\" style=\"max-width:100%;border-radius:8px;\">';
            document.getElementById('clearCoverBtn').style.display = 'block';
        } else {
            alert(d.msg);
        }
    });
});

function clearCover() {
    document.getElementById('coverInput').value = '';
    document.getElementById('coverPreview').innerHTML = '<div class=\"cover-placeholder\"><i class=\"fas fa-image\"></i><p>点击上传封面图</p></div>';
    document.getElementById('clearCoverBtn').style.display = 'none';
}
";
include __DIR__ . '/../admin/views/footer.php';
?>
