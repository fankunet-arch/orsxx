<div class="page-header">
    <h2>Task List</h2>
    <div class="page-actions">
        <select id="projectFilter" onchange="loadTasks()">
            <option value="">All Projects</option>
        </select>
        <button class="btn btn-primary" onclick="showBulkActions()">Bulk Update</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="tasksTable">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                <th>Title</th>
                <th>Status</th>
                <th>Phase</th>
                <th>Priority</th>
                <th>Template</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="tasksBody">
            <tr><td colspan="8" class="loading">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- Bulk Update Modal -->
<div id="bulkModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Bulk Update Tasks</h3>
            <button class="modal-close" onclick="closeBulkModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="bulkCount">0 tasks selected</p>
            <div class="form-group">
                <label>Phase</label>
                <select id="bulkPhase">
                    <option value="">-- No Change --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Mark as Template</label>
                <select id="bulkTemplate">
                    <option value="">-- No Change --</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="form-group">
                <label>Template Tags (comma-separated)</label>
                <input type="text" id="bulkTags" placeholder="e.g. must_buy, it">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeBulkModal()">Cancel</button>
            <button class="btn btn-primary" onclick="executeBulkUpdate()">Apply Changes</button>
        </div>
    </div>
</div>

<script>
let phases = [];

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
        console.error('Failed to load projects:', error);
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
        console.error('Failed to load phases:', error);
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
                    <td><span class="status-badge status-${task.status}">${task.status}</span></td>
                    <td>${phase ? escapeHtml(phase.phase_name) : '-'}</td>
                    <td>${task.priority || '-'}</td>
                    <td>${task.template_flag ? '<span class="badge badge-success">Yes</span>' : '-'}</td>
                    <td>${formatDate(task.created_at)}</td>
                    <td>
                        <button class="btn btn-xs" onclick="editTask(${task.id})">Edit</button>
                    </td>
                </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No tasks found</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="8" class="error-state">Failed to load tasks</td></tr>';
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
        showToast('Please select at least one task', 'warning');
        return;
    }
    document.getElementById('bulkCount').textContent = ids.length + ' tasks selected';
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
            showToast(result.message, 'success');
            closeBulkModal();
            loadTasks();
        } else {
            showToast(result.message || 'Bulk update failed', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

function editTask(id) {
    // For simplicity, redirect to a simple edit or show modal
    showToast('Edit feature - implement as needed', 'info');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>
