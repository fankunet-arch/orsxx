<?php
// 获取当前项目ID
$projectId = $currentProjectId ?? null;
?>

<?php if (!$projectId): ?>
<div class="no-project-warning">
    请先在顶部选择一个项目，或前往 <a href="/ors/ap/?action=projects">项目中心</a> 创建新项目
</div>
<?php endif; ?>

<div class="page-header">
    <h2>项目检查清单</h2>
    <div class="page-actions">
        <?php if ($projectId): ?>
        <button class="btn btn-primary" onclick="generateFromLessons()">从踩坑经验生成</button>
        <?php endif; ?>
    </div>
</div>

<p class="page-description" style="color: var(--text-light); margin-bottom: 24px;">
    基于踩坑经验自动生成的检查项，确保开业过程中不遗漏关键事项。
</p>

<?php if ($projectId): ?>
<div class="checklist-summary summary-bar">
    <div class="summary-item">
        <span class="summary-label">总检查项：</span>
        <span class="summary-value" id="totalItems">0</span>
    </div>
    <div class="summary-item">
        <span class="summary-label">已完成：</span>
        <span class="summary-value" id="completedItems">0</span>
    </div>
    <div class="summary-item">
        <span class="summary-label">完成率：</span>
        <span class="summary-value" id="completionRate">0%</span>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="checklistTable">
        <thead>
            <tr>
                <th style="width: 50px;">状态</th>
                <th>检查项</th>
                <th>来源</th>
                <th>分类</th>
                <th>检查时间点</th>
                <th>严重程度</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="checklistBody">
            <tr><td colspan="7" class="loading">加载中...</td></tr>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="empty-state" style="padding: 60px 20px;">
    <p style="font-size: 1.125rem; margin-bottom: 16px;">请先选择一个项目</p>
    <p style="margin-bottom: 24px;">检查清单是项目级别的，需要先选择一个项目才能查看。</p>
    <a href="/ors/ap/?action=projects" class="btn btn-primary">前往项目中心</a>
</div>
<?php endif; ?>

<script>
const projectId = <?php echo json_encode($projectId); ?>;

const severityNames = {
    'low': '低',
    'medium': '中',
    'high': '高',
    'critical': '严重'
};

const categoryNames = {
    'it': 'IT',
    'power': '电力',
    'fire_safety': '消防',
    'permit': '证照',
    'procurement': '采购',
    'other': '其他'
};

document.addEventListener('DOMContentLoaded', function() {
    if (projectId) {
        loadChecklist();
    }
});

async function loadChecklist() {
    const tbody = document.getElementById('checklistBody');

    try {
        const response = await fetch('/ors/api/lessons.php?action=checkItems&project_id=' + projectId);
        const result = await response.json();

        if (result.success) {
            const items = result.data.check_items || [];

            // 更新统计
            const total = items.length;
            const completed = items.filter(i => i.checked).length;
            document.getElementById('totalItems').textContent = total;
            document.getElementById('completedItems').textContent = completed;
            document.getElementById('completionRate').textContent = total > 0 ? Math.round(completed / total * 100) + '%' : '0%';

            if (items.length > 0) {
                tbody.innerHTML = items.map(item => `
                    <tr class="${item.checked ? 'row-completed' : ''}">
                        <td>
                            <input type="checkbox"
                                   ${item.checked ? 'checked' : ''}
                                   onchange="toggleCheckItem(${item.id}, this.checked)"
                                   class="check-toggle">
                        </td>
                        <td class="${item.checked ? 'text-completed' : ''}">${escapeHtml(item.check_content)}</td>
                        <td>${escapeHtml(item.lesson_title || '-')}</td>
                        <td>${categoryNames[item.category] || '-'}</td>
                        <td>${escapeHtml(item.check_timing || '-')}</td>
                        <td>
                            <span class="badge badge-${item.severity === 'critical' ? 'danger' : item.severity === 'high' ? 'warning' : 'info'}">
                                ${severityNames[item.severity] || '-'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-xs" onclick="viewLessonDetail(${item.lesson_id})">查看经验</button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">暂无检查项，点击"从踩坑经验生成"创建检查清单</td></tr>';
            }
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="error-state">加载失败</td></tr>';
        }
    } catch (error) {
        console.error('加载检查清单失败:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="error-state">加载失败</td></tr>';
    }
}

async function toggleCheckItem(id, checked) {
    try {
        const response = await fetch('/ors/api/lessons.php?action=updateCheckItem', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, checked })
        });

        const result = await response.json();
        if (result.success) {
            loadChecklist(); // 重新加载以更新统计
        } else {
            showToast('更新失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}

async function generateFromLessons() {
    if (!confirm('确定要从踩坑经验生成检查清单吗？这将根据模板库中的踩坑经验自动创建检查项。')) {
        return;
    }

    try {
        const response = await fetch('/ors/api/lessons.php?action=generateChecklist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: projectId })
        });

        const result = await response.json();
        if (result.success) {
            showToast('检查清单已生成！', 'success');
            loadChecklist();
        } else {
            showToast(result.message || '生成失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}

function viewLessonDetail(lessonId) {
    window.open('/ors/ap/?action=lessons&id=' + lessonId, '_blank');
}
</script>

<style>
.row-completed {
    background: #f0fdf4 !important;
}

.text-completed {
    text-decoration: line-through;
    color: var(--text-light);
}

.check-toggle {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: var(--success-color);
}
</style>
