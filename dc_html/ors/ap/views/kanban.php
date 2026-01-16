<div class="page-header">
    <h2>Task Kanban</h2>
    <div class="page-actions">
        <select id="projectFilter" onchange="loadKanban()">
            <option value="">All Projects</option>
        </select>
    </div>
</div>

<div class="kanban-board">
    <div class="kanban-column" data-status="todo">
        <div class="column-header">
            <h4>Todo</h4>
            <span class="column-count" id="countTodo">0</span>
        </div>
        <div class="column-body" id="colTodo"></div>
    </div>

    <div class="kanban-column" data-status="doing">
        <div class="column-header">
            <h4>Doing</h4>
            <span class="column-count" id="countDoing">0</span>
        </div>
        <div class="column-body" id="colDoing"></div>
    </div>

    <div class="kanban-column" data-status="blocked">
        <div class="column-header column-blocked">
            <h4>Blocked</h4>
            <span class="column-count" id="countBlocked">0</span>
        </div>
        <div class="column-body" id="colBlocked"></div>
    </div>

    <div class="kanban-column" data-status="done">
        <div class="column-header column-done">
            <h4>Done</h4>
            <span class="column-count" id="countDone">0</span>
        </div>
        <div class="column-body" id="colDone"></div>
    </div>
</div>

<!-- Block Reason Modal -->
<div id="blockReasonModal" class="modal" style="display:none;">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>Select Block Reason</h3>
            <button class="modal-close" onclick="closeBlockModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="blockTaskId">
            <div class="form-group">
                <label>Block Reason *</label>
                <select id="blockReason" required>
                    <option value="">-- Select Reason --</option>
                    <option value="waiting_vendor">Waiting for Vendor</option>
                    <option value="waiting_approval">Waiting for Approval</option>
                    <option value="waiting_material">Waiting for Material</option>
                    <option value="waiting_budget">Waiting for Budget</option>
                    <option value="technical_issue">Technical Issue</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Details (optional)</label>
                <textarea id="blockReasonDetail" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeBlockModal()">Cancel</button>
            <button class="btn btn-danger" onclick="confirmBlock()">Confirm Block</button>
        </div>
    </div>
</div>

<script>
const blockReasons = {
    'waiting_vendor': 'Waiting for Vendor',
    'waiting_approval': 'Waiting for Approval',
    'waiting_material': 'Waiting for Material',
    'waiting_budget': 'Waiting for Budget',
    'technical_issue': 'Technical Issue',
    'other': 'Other'
};

document.addEventListener('DOMContentLoaded', function() {
    loadProjects();
    loadKanban();
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

async function loadKanban() {
    const projectId = document.getElementById('projectFilter').value;
    const url = '/ors/api/tasks.php?action=kanban' + (projectId ? '&project_id=' + projectId : '');

    try {
        const response = await fetch(url);
        const result = await response.json();
        if (result.success) {
            const kanban = result.data.kanban;
            renderColumn('colTodo', 'countTodo', kanban.todo);
            renderColumn('colDoing', 'countDoing', kanban.doing);
            renderColumn('colBlocked', 'countBlocked', kanban.blocked);
            renderColumn('colDone', 'countDone', kanban.done);
        }
    } catch (error) {
        console.error('Failed to load kanban:', error);
    }
}

function renderColumn(colId, countId, tasks) {
    document.getElementById(countId).textContent = tasks.length;
    const container = document.getElementById(colId);

    if (tasks.length === 0) {
        container.innerHTML = '<div class="empty-column">No tasks</div>';
        return;
    }

    container.innerHTML = tasks.map(task => `
        <div class="kanban-card" data-id="${task.id}">
            <div class="card-title">${escapeHtml(task.title)}</div>
            ${task.block_reason ? `<div class="card-block-reason">${blockReasons[task.block_reason] || task.block_reason}</div>` : ''}
            <div class="card-actions">
                ${task.status !== 'todo' ? `<button class="btn btn-xs" onclick="updateStatus(${task.id}, 'todo')">Todo</button>` : ''}
                ${task.status !== 'doing' ? `<button class="btn btn-xs btn-primary" onclick="updateStatus(${task.id}, 'doing')">Doing</button>` : ''}
                ${task.status !== 'blocked' ? `<button class="btn btn-xs btn-warning" onclick="showBlockModal(${task.id})">Block</button>` : ''}
                ${task.status !== 'done' ? `<button class="btn btn-xs btn-success" onclick="updateStatus(${task.id}, 'done')">Done</button>` : ''}
            </div>
        </div>
    `).join('');
}

function showBlockModal(taskId) {
    document.getElementById('blockTaskId').value = taskId;
    document.getElementById('blockReason').value = '';
    document.getElementById('blockReasonDetail').value = '';
    document.getElementById('blockReasonModal').style.display = 'flex';
}

function closeBlockModal() {
    document.getElementById('blockReasonModal').style.display = 'none';
}

async function confirmBlock() {
    const taskId = document.getElementById('blockTaskId').value;
    const blockReason = document.getElementById('blockReason').value;
    const blockReasonDetail = document.getElementById('blockReasonDetail').value;

    if (!blockReason) {
        showToast('Please select a block reason', 'error');
        return;
    }

    try {
        const response = await fetch('/ors/api/tasks.php?action=updateStatus', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: taskId,
                status: 'blocked',
                block_reason: blockReason,
                block_reason_detail: blockReasonDetail
            })
        });

        const result = await response.json();
        if (result.success) {
            showToast('Task blocked', 'success');
            closeBlockModal();
            loadKanban();
        } else {
            showToast(result.message || 'Failed to block task', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

async function updateStatus(taskId, status) {
    try {
        const response = await fetch('/ors/api/tasks.php?action=updateStatus', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: taskId, status: status })
        });

        const result = await response.json();
        if (result.success) {
            showToast('Status updated', 'success');
            loadKanban();
        } else {
            showToast(result.message || 'Failed to update status', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}
</script>
