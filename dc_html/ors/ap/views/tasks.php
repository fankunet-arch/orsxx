<div class="page-header">
    <h2>任务列表</h2>
    <div class="page-actions">
        <select id="projectFilter" onchange="loadTasks()">
            <option value="">全部项目</option>
        </select>
        <button class="btn btn-primary" onclick="showBulkActions()">批量更新</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="tasksTable">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                <th>标题</th>
                <th>状态</th>
                <th>阶段</th>
                <th>优先级</th>
                <th>模板</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="tasksBody">
            <tr><td colspan="8" class="loading">加载中...</td></tr>
        </tbody>
    </table>
</div>

<!-- 批量更新弹窗 -->
<div id="bulkModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>批量更新任务</h3>
            <button class="modal-close" onclick="closeBulkModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="bulkCount">已选择 0 个任务</p>
            <div class="form-group">
                <label>阶段</label>
                <select id="bulkPhase">
                    <option value="">-- 不修改 --</option>
                </select>
            </div>
            <div class="form-group">
                <label>标记为模板</label>
                <select id="bulkTemplate">
                    <option value="">-- 不修改 --</option>
                    <option value="1">是</option>
                    <option value="0">否</option>
                </select>
            </div>
            <div class="form-group">
                <label>模板标签（逗号分隔）</label>
                <input type="text" id="bulkTags" placeholder="例如：must_buy, it">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeBulkModal()">取消</button>
            <button class="btn btn-primary" onclick="executeBulkUpdate()">应用更改</button>
        </div>
    </div>
</div>

<script>
let phases = [];

const statusNames = {
    'todo': '待办',
    'doing': '进行中',
    'blocked': '阻塞',
    'done': '已完成'
};

const priorityNames = {
    'low': '低',
    'medium': '中',
    'high': '高',
    'urgent': '紧急'
};

document.addEventListener('DOMContentLoaded', function() {
    loadProjects();
    loadPhases();
    loadTasks();
});

async function loadProjects() {
    try {
        const response = await fetch('/ors/api/projects.php?action=list');
        const result = await response.json();
        if (result.success) {
            const select = document.getElementById('projectFilter');
            result.data.projects.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = p.project_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('加载项目失败:', error);
    }
}

async function loadPhases() {
    try {
        const response = await fetch('/ors/api/phases.php?action=list');
        const result = await response.json();
        if (result.success) {
            phases = result.data.phases;
            const select = document.getElementById('bulkPhase');
            phases.forEach(p => {
                const option = document.createElement('option');
                option.value = p.phase_code;
                option.textContent = p.phase_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('加载阶段失败:', error);
    }
}

async function loadTasks() {
    const projectId = document.getElementById('projectFilter').value;
    const url = '/ors/api/tasks.php?action=list' + (projectId ? '&project_id=' + projectId : '');

    const tbody = document.getElementById('tasksBody');

    try {
        const response = await fetch(url);
        const result = await response.json();

        if (result.success && result.data.tasks.length > 0) {
            tbody.innerHTML = result.data.tasks.map(task => {
                const phase = phases.find(p => p.phase_code === task.phase_code);
                return `
                <tr>
                    <td><input type="checkbox" class="task-checkbox" value="${task.id}"></td>
                    <td>${escapeHtml(task.title)}</td>
                    <td><span class="status-badge status-${task.status}">${statusNames[task.status] || task.status}</span></td>
                    <td>${phase ? escapeHtml(phase.phase_name) : '-'}</td>
                    <td>${priorityNames[task.priority] || '-'}</td>
                    <td>${task.template_flag ? '<span class="badge badge-success">是</span>' : '-'}</td>
                    <td>${formatDate(task.created_at)}</td>
                    <td>
                        <button class="btn btn-xs" onclick="editTask(${task.id})">编辑</button>
                    </td>
                </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state">暂无任务</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="8" class="error-state">加载失败</td></tr>';
    }
}

function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.task-checkbox').forEach(cb => cb.checked = checked);
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.task-checkbox:checked')).map(cb => parseInt(cb.value));
}

function showBulkActions() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        showToast('请至少选择一个任务', 'warning');
        return;
    }
    document.getElementById('bulkCount').textContent = '已选择 ' + ids.length + ' 个任务';
    document.getElementById('bulkModal').style.display = 'flex';
}

function closeBulkModal() {
    document.getElementById('bulkModal').style.display = 'none';
}

async function executeBulkUpdate() {
    const ids = getSelectedIds();
    const phase = document.getElementById('bulkPhase').value;
    const template = document.getElementById('bulkTemplate').value;
    const tags = document.getElementById('bulkTags').value;

    const data = { ids };
    if (phase) data.phase_code = phase;
    if (template !== '') data.template_flag = template === '1';
    if (tags) data.template_tags = tags;

    try {
        const response = await fetch('/ors/api/tasks.php?action=bulkUpdate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('批量更新成功！', 'success');
            closeBulkModal();
            loadTasks();
        } else {
            showToast(result.message || '批量更新失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}

function editTask(id) {
    showToast('编辑功能 - 待实现', 'info');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('zh-CN', { month: 'short', day: 'numeric' });
}
</script>
