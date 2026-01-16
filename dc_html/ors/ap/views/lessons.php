<div class="page-header">
    <h2>踩坑记录</h2>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddLessonModal()">+ 新增记录</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="lessonsTable">
        <thead>
            <tr>
                <th>标题</th>
                <th>分类</th>
                <th>严重程度</th>
                <th>检查时间点</th>
                <th>预防检查项</th>
                <th>模板</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="lessonsBody">
            <tr><td colspan="7" class="loading">加载中...</td></tr>
        </tbody>
    </table>
</div>

<!-- 新增/编辑踩坑记录弹窗 -->
<div id="lessonModal" class="modal" style="display:none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="lessonModalTitle">新增踩坑记录</h3>
            <button class="modal-close" onclick="closeLessonModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="lessonId">
            <div class="form-group">
                <label>标题 *</label>
                <input type="text" id="lessonTitle" required>
            </div>
            <div class="form-group">
                <label>描述</label>
                <textarea id="lessonDescription" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>分类</label>
                    <select id="lessonCategory">
                        <option value="">-- 请选择 --</option>
                        <option value="it">IT</option>
                        <option value="power">电力</option>
                        <option value="fire_safety">消防</option>
                        <option value="permit">证照</option>
                        <option value="procurement">采购</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>严重程度</label>
                    <select id="lessonSeverity">
                        <option value="low">低</option>
                        <option value="medium" selected>中</option>
                        <option value="high">高</option>
                        <option value="critical">严重</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>根本原因</label>
                <textarea id="lessonRootCause" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>预防检查项 *（模板必填）</label>
                <textarea id="lessonPreventionCheckItem" rows="3" required
                    placeholder="应该检查什么来预防此问题？"></textarea>
            </div>
            <div class="form-group">
                <label>检查时间点描述</label>
                <input type="text" id="lessonCheckTiming" placeholder="例如：'签约后'">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>开业前提前天数</label>
                    <input type="number" id="lessonDaysBeforeOpen" min="0">
                </div>
                <div class="form-group">
                    <label>签约后天数</label>
                    <input type="number" id="lessonDaysAfterSign" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="lessonTemplate" checked>
                    <span>添加到模板库</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeLessonModal()">取消</button>
            <button class="btn btn-primary" onclick="saveLesson()">保存</button>
        </div>
    </div>
</div>

<script>
const lessonCategories = {
    'it': 'IT',
    'power': '电力',
    'fire_safety': '消防',
    'permit': '证照',
    'procurement': '采购',
    'other': '其他'
};

const severityNames = {
    'low': '低',
    'medium': '中',
    'high': '高',
    'critical': '严重'
};

document.addEventListener('DOMContentLoaded', function() {
    loadLessons();
});

async function loadLessons() {
    const tbody = document.getElementById('lessonsBody');

    try {
        const response = await fetch('/ors/api/lessons.php?action=list');
        const result = await response.json();

        if (result.success && result.data.lessons.length > 0) {
            tbody.innerHTML = result.data.lessons.map(l => `
                <tr>
                    <td>${escapeHtml(l.title)}</td>
                    <td>${lessonCategories[l.category] || '-'}</td>
                    <td><span class="badge badge-${l.severity === 'critical' ? 'danger' : l.severity === 'high' ? 'warning' : 'info'}">${severityNames[l.severity] || l.severity}</span></td>
                    <td>${escapeHtml(l.check_timing || '-')}</td>
                    <td class="text-truncate" style="max-width: 300px;">${escapeHtml(l.prevention_check_item || '-')}</td>
                    <td>${l.template_flag ? '<span class="badge badge-success">是</span>' : '-'}</td>
                    <td>
                        <button class="btn btn-xs" onclick="editLesson(${l.id})">编辑</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">暂无踩坑记录</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="7" class="error-state">加载失败</td></tr>';
    }
}

function showAddLessonModal() {
    document.getElementById('lessonModalTitle').textContent = '新增踩坑记录';
    document.getElementById('lessonId').value = '';
    document.getElementById('lessonTitle').value = '';
    document.getElementById('lessonDescription').value = '';
    document.getElementById('lessonCategory').value = '';
    document.getElementById('lessonSeverity').value = 'medium';
    document.getElementById('lessonRootCause').value = '';
    document.getElementById('lessonPreventionCheckItem').value = '';
    document.getElementById('lessonCheckTiming').value = '';
    document.getElementById('lessonDaysBeforeOpen').value = '';
    document.getElementById('lessonDaysAfterSign').value = '';
    document.getElementById('lessonTemplate').checked = true;
    document.getElementById('lessonModal').style.display = 'flex';
}

function closeLessonModal() {
    document.getElementById('lessonModal').style.display = 'none';
}

async function saveLesson() {
    const id = document.getElementById('lessonId').value;
    const preventionCheckItem = document.getElementById('lessonPreventionCheckItem').value;

    if (!preventionCheckItem) {
        showToast('预防检查项不能为空', 'error');
        return;
    }

    const data = {
        title: document.getElementById('lessonTitle').value,
        description: document.getElementById('lessonDescription').value,
        category: document.getElementById('lessonCategory').value,
        severity: document.getElementById('lessonSeverity').value,
        root_cause: document.getElementById('lessonRootCause').value,
        prevention_check_item: preventionCheckItem,
        check_timing: document.getElementById('lessonCheckTiming').value,
        check_days_before_open: document.getElementById('lessonDaysBeforeOpen').value || null,
        check_days_after_sign: document.getElementById('lessonDaysAfterSign').value || null,
        template_flag: document.getElementById('lessonTemplate').checked
    };

    if (!data.title) {
        showToast('标题不能为空', 'error');
        return;
    }

    const action = id ? 'update' : 'create';
    if (id) data.id = id;

    try {
        const response = await fetch('/ors/api/lessons.php?action=' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('踩坑记录已保存！', 'success');
            closeLessonModal();
            loadLessons();
        } else {
            showToast(result.message || '保存失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}

function editLesson(id) {
    showToast('编辑功能 - 待实现', 'info');
}
</script>
