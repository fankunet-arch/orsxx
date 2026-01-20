<div class="page-header">
    <h2>任务模板</h2>
    <div class="page-actions">
        <select id="projectFilter" onchange="loadTasks()">
            <option value="">全部项目</option>
        </select>
        <button class="btn btn-primary" onclick="showBulkActions()">批量更新</button>
    </div>
</div>

<p class="page-description" style="color: var(--text-light); margin-bottom: 24px;">
    管理可复用的任务模板。在项目执行过程中发现新的任务类型时，可以在这里沉淀为模板，供下次开业使用。
</p>

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

<!-- 编辑任务弹窗 -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>编辑任务</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editTaskId">
            <div class="form-group">
                <label>标题 <span class="text-danger">*</span></label>
                <input type="text" id="editTitle" placeholder="任务标题">
            </div>
            <div class="form-group">
                <label>描述</label>
                <textarea id="editDescription" rows="3" placeholder="任务描述（可选）"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>所属项目</label>
                    <select id="editProject">
                        <option value="">-- 无 --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>阶段</label>
                    <select id="editPhase">
                        <option value="">-- 选择阶段 --</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>状态</label>
                    <select id="editStatus">
                        <option value="todo">待办</option>
                        <option value="doing">进行中</option>
                        <option value="blocked">阻塞</option>
                        <option value="done">已完成</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>优先级</label>
                    <select id="editPriority">
                        <option value="low">低</option>
                        <option value="medium">中</option>
                        <option value="high">高</option>
                        <option value="urgent">紧急</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="editTemplate">
                        标记为模板
                    </label>
                </div>
                <div class="form-group">
                    <label>模板标签（逗号分隔）</label>
                    <input type="text" id="editTags" placeholder="例如：must_buy, it">
                </div>
            </div>
            <div class="form-group">
                <label>适用店铺类型（留空表示全部适用）</label>
                <div class="checkbox-grid">
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="cafeteria"> 咖啡厅</label>
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="restaurant"> 餐厅</label>
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="retail"> 零售店</label>
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="bubble_tea"> 奶茶店</label>
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="ice_cream"> 冰淇淋店</label>
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="dessert"> 甜品店</label>
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="fried_chicken"> 炸鸡店</label>
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="poke"> POKE店</label>
                    <label class="checkbox-label"><input type="checkbox" name="taskProjectTypes" value="sushi"> 寿司店</label>
                </div>
            </div>
            <div class="form-group" id="blockReasonGroup" style="display:none;">
                <label>阻塞原因</label>
                <select id="editBlockReason">
                    <option value="">-- 选择原因 --</option>
                    <option value="waiting_info">等待信息</option>
                    <option value="waiting_resource">等待资源</option>
                    <option value="waiting_approval">等待审批</option>
                    <option value="dependency">依赖阻塞</option>
                    <option value="other">其他</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="deleteTask()" style="margin-right:auto;">删除</button>
            <button class="btn btn-outline" onclick="closeEditModal()">取消</button>
            <button class="btn btn-primary" onclick="saveTask()">保存</button>
        </div>
    </div>
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
            <div class="form-group">
                <label>适用店铺类型</label>
                <select id="bulkProjectTypesAction">
                    <option value="">-- 不修改 --</option>
                    <option value="all">设为全部适用（清空选择）</option>
                    <option value="set">设为指定类型</option>
                </select>
            </div>
            <div class="form-group" id="bulkProjectTypesGroup" style="display:none;">
                <label>选择适用的店铺类型</label>
                <div class="checkbox-grid">
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="cafeteria"> 咖啡厅</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="restaurant"> 餐厅</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="retail"> 零售店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="bubble_tea"> 奶茶店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="ice_cream"> 冰淇淋店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="dessert"> 甜品店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="fried_chicken"> 炸鸡店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="poke"> POKE店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="sushi"> 寿司店</label>
                </div>
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
    // 重置项目类型选择
    document.getElementById('bulkProjectTypesAction').value = '';
    document.getElementById('bulkProjectTypesGroup').style.display = 'none';
    document.querySelectorAll('input[name="bulkProjectTypes"]').forEach(cb => cb.checked = false);
    document.getElementById('bulkModal').style.display = 'flex';
}

// 监听项目类型操作选择变化
document.addEventListener('DOMContentLoaded', function() {
    const actionSelect = document.getElementById('bulkProjectTypesAction');
    if (actionSelect) {
        actionSelect.addEventListener('change', function() {
            const group = document.getElementById('bulkProjectTypesGroup');
            group.style.display = this.value === 'set' ? 'block' : 'none';
        });
    }
});

function closeBulkModal() {
    document.getElementById('bulkModal').style.display = 'none';
}

async function executeBulkUpdate() {
    const ids = getSelectedIds();
    const phase = document.getElementById('bulkPhase').value;
    const template = document.getElementById('bulkTemplate').value;
    const tags = document.getElementById('bulkTags').value;
    const projectTypesAction = document.getElementById('bulkProjectTypesAction').value;

    const data = { ids };
    if (phase) data.phase_code = phase;
    if (template !== '') data.template_flag = template === '1';
    if (tags) data.template_tags = tags;

    // 处理项目类型
    if (projectTypesAction === 'all') {
        data.project_types = null; // 设为全部适用
    } else if (projectTypesAction === 'set') {
        const selectedTypes = Array.from(document.querySelectorAll('input[name="bulkProjectTypes"]:checked'))
            .map(cb => cb.value);
        data.project_types = selectedTypes.length > 0 ? selectedTypes.join(',') : null;
    }

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

async function editTask(id) {
    try {
        const response = await fetch('/ors/api/tasks.php?action=get&id=' + id);
        const result = await response.json();

        if (result.success && result.data.task) {
            const task = result.data.task;

            // 填充编辑表单
            document.getElementById('editTaskId').value = task.id;
            document.getElementById('editTitle').value = task.title || '';
            document.getElementById('editDescription').value = task.description || '';
            document.getElementById('editProject').value = task.project_id || '';
            document.getElementById('editPhase').value = task.phase_code || '';
            document.getElementById('editStatus').value = task.status || 'todo';
            document.getElementById('editPriority').value = task.priority || 'medium';
            document.getElementById('editTemplate').checked = task.template_flag == 1;
            document.getElementById('editTags').value = task.template_tags || '';
            document.getElementById('editBlockReason').value = task.block_reason || '';

            // 显示/隐藏阻塞原因
            toggleBlockReason();

            // 填充项目下拉框
            await populateEditProjects();
            document.getElementById('editProject').value = task.project_id || '';

            // 填充阶段下拉框
            populateEditPhases();
            document.getElementById('editPhase').value = task.phase_code || '';

            // 设置项目类型选择
            const projectTypes = task.project_types ? task.project_types.split(',') : [];
            document.querySelectorAll('input[name="taskProjectTypes"]').forEach(cb => {
                cb.checked = projectTypes.includes(cb.value);
            });

            document.getElementById('editModal').style.display = 'flex';
        } else {
            showToast('加载任务失败', 'error');
        }
    } catch (error) {
        console.error('加载任务失败:', error);
        showToast('网络错误', 'error');
    }
}

async function populateEditProjects() {
    const select = document.getElementById('editProject');
    select.innerHTML = '<option value="">-- 无 --</option>';

    try {
        const response = await fetch('/ors/api/projects.php?action=list');
        const result = await response.json();
        if (result.success) {
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

function populateEditPhases() {
    const select = document.getElementById('editPhase');
    select.innerHTML = '<option value="">-- 选择阶段 --</option>';
    phases.forEach(p => {
        const option = document.createElement('option');
        option.value = p.phase_code;
        option.textContent = p.phase_name;
        select.appendChild(option);
    });
}

function toggleBlockReason() {
    const status = document.getElementById('editStatus').value;
    const group = document.getElementById('blockReasonGroup');
    group.style.display = status === 'blocked' ? 'block' : 'none';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

async function saveTask() {
    const id = document.getElementById('editTaskId').value;
    const title = document.getElementById('editTitle').value.trim();

    if (!title) {
        showToast('请输入任务标题', 'warning');
        return;
    }

    // 获取选中的项目类型
    const selectedTypes = Array.from(document.querySelectorAll('input[name="taskProjectTypes"]:checked'))
        .map(cb => cb.value);

    const data = {
        id: parseInt(id),
        title: title,
        description: document.getElementById('editDescription').value.trim(),
        project_id: document.getElementById('editProject').value || null,
        phase_code: document.getElementById('editPhase').value || null,
        status: document.getElementById('editStatus').value,
        priority: document.getElementById('editPriority').value,
        template_flag: document.getElementById('editTemplate').checked,
        template_tags: document.getElementById('editTags').value.trim(),
        project_types: selectedTypes.length > 0 ? selectedTypes.join(',') : null,
        block_reason: document.getElementById('editStatus').value === 'blocked'
            ? document.getElementById('editBlockReason').value
            : null
    };

    try {
        const response = await fetch('/ors/api/tasks.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('任务已更新', 'success');
            closeEditModal();
            loadTasks();
        } else {
            showToast(result.message || '保存失败', 'error');
        }
    } catch (error) {
        console.error('保存失败:', error);
        showToast('网络错误', 'error');
    }
}

async function deleteTask() {
    const id = document.getElementById('editTaskId').value;

    if (!confirm('确定要删除这个任务吗？此操作不可撤销。')) {
        return;
    }

    try {
        const response = await fetch('/ors/api/tasks.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id) })
        });

        const result = await response.json();
        if (result.success) {
            showToast('任务已删除', 'success');
            closeEditModal();
            loadTasks();
        } else {
            showToast(result.message || '删除失败', 'error');
        }
    } catch (error) {
        console.error('删除失败:', error);
        showToast('网络错误', 'error');
    }
}

// 监听状态变化显示/隐藏阻塞原因
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('editStatus');
    if (statusSelect) {
        statusSelect.addEventListener('change', toggleBlockReason);
    }
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('zh-CN', { month: 'short', day: 'numeric' });
}
</script>
