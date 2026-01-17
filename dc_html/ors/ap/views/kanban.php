<?php
// 获取当前项目ID
$projectId = $currentProjectId ?? null;
?>

<?php if (!$projectId): ?>
<div class="no-project-warning">
    请先在顶部选择一个项目，或前往 <a href="/ors/ap/?action=projects">项目中心</a> 创建/选择项目
</div>
<?php endif; ?>

<div class="page-header">
    <h2>项目任务</h2>
    <div class="page-actions">
        <select id="projectFilter" onchange="loadKanban()" style="display:none;">
            <option value="">全部项目</option>
        </select>
    </div>
</div>

<p class="page-description" style="color: var(--text-light); margin-bottom: 24px;">
    当前项目的任务看板。拖动卡片或点击按钮更新任务状态。
</p>

<div class="kanban-board">
    <div class="kanban-column" data-status="todo">
        <div class="column-header">
            <h4>待办</h4>
            <span class="column-count" id="countTodo">0</span>
        </div>
        <div class="column-body" id="colTodo"></div>
    </div>

    <div class="kanban-column" data-status="doing">
        <div class="column-header">
            <h4>进行中</h4>
            <span class="column-count" id="countDoing">0</span>
        </div>
        <div class="column-body" id="colDoing"></div>
    </div>

    <div class="kanban-column" data-status="blocked">
        <div class="column-header column-blocked">
            <h4>阻塞</h4>
            <span class="column-count" id="countBlocked">0</span>
        </div>
        <div class="column-body" id="colBlocked"></div>
    </div>

    <div class="kanban-column" data-status="done">
        <div class="column-header column-done">
            <h4>已完成</h4>
            <span class="column-count" id="countDone">0</span>
        </div>
        <div class="column-body" id="colDone"></div>
    </div>
</div>

<!-- 阻塞原因弹窗 -->
<div id="blockReasonModal" class="modal" style="display:none;">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>选择阻塞原因</h3>
            <button class="modal-close" onclick="closeBlockModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="blockTaskId">
            <div class="form-group">
                <label>阻塞原因 *</label>
                <select id="blockReason" required>
                    <option value="">-- 请选择原因 --</option>
                    <option value="waiting_vendor">等待供应商</option>
                    <option value="waiting_approval">等待审批</option>
                    <option value="waiting_material">等待物料</option>
                    <option value="waiting_budget">等待预算</option>
                    <option value="technical_issue">技术问题</option>
                    <option value="other">其他</option>
                </select>
            </div>
            <div class="form-group">
                <label>详细说明（可选）</label>
                <textarea id="blockReasonDetail" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeBlockModal()">取消</button>
            <button class="btn btn-danger" onclick="confirmBlock()">确认阻塞</button>
        </div>
    </div>
</div>

<script>
// 当前项目ID（从PHP传递）
const kanbanProjectId = <?php echo json_encode($projectId); ?>;

const blockReasons = {
    'waiting_vendor': '等待供应商',
    'waiting_approval': '等待审批',
    'waiting_material': '等待物料',
    'waiting_budget': '等待预算',
    'technical_issue': '技术问题',
    'other': '其他'
};

document.addEventListener('DOMContentLoaded', function() {
    if (kanbanProjectId) {
        loadKanban();
    }
});

async function loadKanban() {
    // 使用当前项目ID
    const projectId = kanbanProjectId;
    if (!projectId) {
        return;
    }
    const url = '/ors/api/tasks.php?action=kanban&project_id=' + projectId;

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
        console.error('加载看板失败:', error);
    }
}

function renderColumn(colId, countId, tasks) {
    document.getElementById(countId).textContent = tasks.length;
    const container = document.getElementById(colId);

    if (tasks.length === 0) {
        container.innerHTML = '<div class="empty-column">暂无任务</div>';
        return;
    }

    container.innerHTML = tasks.map(task => `
        <div class="kanban-card" data-id="${task.id}">
            <div class="card-title">${escapeHtml(task.title)}</div>
            ${task.block_reason ? `<div class="card-block-reason">${blockReasons[task.block_reason] || task.block_reason}</div>` : ''}
            <div class="card-actions">
                ${task.status !== 'todo' ? `<button class="btn btn-xs" onclick="updateStatus(${task.id}, 'todo')">待办</button>` : ''}
                ${task.status !== 'doing' ? `<button class="btn btn-xs btn-primary" onclick="updateStatus(${task.id}, 'doing')">进行</button>` : ''}
                ${task.status !== 'blocked' ? `<button class="btn btn-xs btn-warning" onclick="showBlockModal(${task.id})">阻塞</button>` : ''}
                ${task.status !== 'done' ? `<button class="btn btn-xs btn-success" onclick="updateStatus(${task.id}, 'done')">完成</button>` : ''}
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
        showToast('请选择阻塞原因', 'error');
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
            showToast('任务已阻塞', 'success');
            closeBlockModal();
            loadKanban();
        } else {
            showToast(result.message || '操作失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
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
            showToast('状态已更新', 'success');
            loadKanban();
        } else {
            showToast(result.message || '更新失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}
</script>
