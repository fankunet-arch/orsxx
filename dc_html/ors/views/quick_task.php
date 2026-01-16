<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS - 快速任务</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <a href="/ors/" class="back-btn">&larr;</a>
            <h1>快速任务</h1>
        </div>
    </header>

    <main class="main-content form-page">
        <form id="quickTaskForm" class="quick-form">
            <?php echo ors_csrf_field(); ?>

            <div class="form-group">
                <label for="title">任务标题 *</label>
                <input type="text" id="title" name="title" required
                       placeholder="例如：买M6膨胀螺丝"
                       autofocus autocomplete="off">
            </div>

            <div class="form-group">
                <label for="description">备注（可选）</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="补充说明..."></textarea>
            </div>

            <div class="form-group">
                <label for="project_id">所属项目（可选）</label>
                <select id="project_id" name="project_id">
                    <option value="">-- 不选择项目 --</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block btn-large">
                    保存任务
                </button>
            </div>
        </form>
    </main>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
    <script>
        // 加载项目列表
        loadProjects();

        async function loadProjects() {
            try {
                const response = await fetch('/ors/api/projects.php?action=list');
                const result = await response.json();
                if (result.success && result.data.projects) {
                    const select = document.getElementById('project_id');
                    result.data.projects.forEach(project => {
                        const option = document.createElement('option');
                        option.value = project.id;
                        option.textContent = project.project_name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('加载项目失败:', error);
            }
        }

        document.getElementById('quickTaskForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '保存中...';

            const formData = new FormData(this);
            const data = {
                title: formData.get('title'),
                description: formData.get('description') || null,
                project_id: formData.get('project_id') || null
            };

            try {
                const response = await fetch('/ors/api/tasks.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('任务已保存！', 'success');
                    // 清空表单以便继续录入
                    document.getElementById('title').value = '';
                    document.getElementById('description').value = '';
                    document.getElementById('title').focus();
                } else {
                    showToast(result.message || '保存失败', 'error');
                }
            } catch (error) {
                showToast('网络错误', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '保存任务';
            }
        });
    </script>
</body>
</html>
