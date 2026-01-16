<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS - Quick Task</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <a href="/ors/" class="back-btn">&larr;</a>
            <h1>Quick Task</h1>
        </div>
    </header>

    <main class="main-content form-page">
        <form id="quickTaskForm" class="quick-form">
            <?php echo ors_csrf_field(); ?>

            <div class="form-group">
                <label for="title">Task Title *</label>
                <input type="text" id="title" name="title" required
                       placeholder="e.g. Buy M6 bolts"
                       autofocus autocomplete="off">
            </div>

            <div class="form-group">
                <label for="description">Notes (optional)</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Additional details..."></textarea>
            </div>

            <div class="form-group">
                <label for="project_id">Project (optional)</label>
                <select id="project_id" name="project_id">
                    <option value="">-- No Project --</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block btn-large">
                    Save Task
                </button>
            </div>
        </form>
    </main>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
    <script>
        // Load projects
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
                console.error('Failed to load projects:', error);
            }
        }

        document.getElementById('quickTaskForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

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
                    showToast('Task saved!', 'success');
                    // Clear form for next entry
                    document.getElementById('title').value = '';
                    document.getElementById('description').value = '';
                    document.getElementById('title').focus();
                } else {
                    showToast(result.message || 'Save failed', 'error');
                }
            } catch (error) {
                showToast('Network error', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Task';
            }
        });
    </script>
</body>
</html>
