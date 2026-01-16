<div class="page-header">
    <h2>模板库</h2>
</div>

<div class="tab-bar-inline">
    <button class="tab-btn active" data-tab="tasksTab" onclick="switchTab('tasksTab')">任务模板</button>
    <button class="tab-btn" data-tab="itemsTab" onclick="switchTab('itemsTab')">物品模板</button>
    <button class="tab-btn" data-tab="lessonsTab" onclick="switchTab('lessonsTab')">踩坑记录模板</button>
</div>

<div id="tasksTab" class="tab-content active">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>阶段</th>
                    <th>关键路径</th>
                    <th>提前期</th>
                    <th>标签</th>
                </tr>
            </thead>
            <tbody id="taskTemplatesBody">
                <tr><td colspan="5" class="loading">加载中...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="itemsTab" class="tab-content">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>名称</th>
                    <th>分类</th>
                    <th>必买等级</th>
                    <th>长周期</th>
                    <th>采购周期</th>
                    <th>标签</th>
                </tr>
            </thead>
            <tbody id="itemTemplatesBody">
                <tr><td colspan="6" class="loading">加载中...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="lessonsTab" class="tab-content">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>分类</th>
                    <th>严重程度</th>
                    <th>检查时间点</th>
                    <th>预防检查项</th>
                </tr>
            </thead>
            <tbody id="lessonTemplatesBody">
                <tr><td colspan="5" class="loading">加载中...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const categories = {
    'it_devices': 'IT设备',
    'furniture': '家具',
    'equipment': '设备',
    'consumables': '耗材',
    'other': '其他'
};

const mustBuyLevels = {
    'must': '必买',
    'recommended': '推荐',
    'optional': '可选'
};

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

let phases = [];

document.addEventListener('DOMContentLoaded', function() {
    loadPhases();
    loadTaskTemplates();
    loadItemTemplates();
    loadLessonTemplates();
});

function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

async function loadPhases() {
    try {
        const response = await fetch('/ors/api/phases.php?action=list');
        const result = await response.json();
        if (result.success) {
            phases = result.data.phases;
        }
    } catch (error) {
        console.error('加载阶段失败:', error);
    }
}

async function loadTaskTemplates() {
    const tbody = document.getElementById('taskTemplatesBody');
    try {
        const response = await fetch('/ors/api/tasks.php?action=templates');
        const result = await response.json();
        if (result.success && result.data.tasks.length > 0) {
            tbody.innerHTML = result.data.tasks.map(t => {
                const phase = phases.find(p => p.phase_code === t.phase_code);
                return `
                <tr>
                    <td>${escapeHtml(t.title)}</td>
                    <td>${phase ? escapeHtml(phase.phase_name) : '-'}</td>
                    <td>${t.blocking_flag ? '<span class="badge badge-danger">是</span>' : '-'}</td>
                    <td>${t.lead_time_days ? t.lead_time_days + ' 天' : '-'}</td>
                    <td>${t.tags && t.tags.length > 0 ? t.tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join(' ') : '-'}</td>
                </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">暂无任务模板</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="error-state">加载失败</td></tr>';
    }
}

async function loadItemTemplates() {
    const tbody = document.getElementById('itemTemplatesBody');
    try {
        const response = await fetch('/ors/api/items.php?action=templates');
        const result = await response.json();
        if (result.success && result.data.items.length > 0) {
            tbody.innerHTML = result.data.items.map(i => `
                <tr>
                    <td>${escapeHtml(i.item_name)}</td>
                    <td>${categories[i.category] || '-'}</td>
                    <td>${mustBuyLevels[i.must_buy_level] || '-'}</td>
                    <td>${i.long_lead_flag ? '是' : '-'}</td>
                    <td>${i.lead_time_days ? i.lead_time_days + ' 天' : '-'}</td>
                    <td>${i.tags && i.tags.length > 0 ? i.tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join(' ') : '-'}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">暂无物品模板</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" class="error-state">加载失败</td></tr>';
    }
}

async function loadLessonTemplates() {
    const tbody = document.getElementById('lessonTemplatesBody');
    try {
        const response = await fetch('/ors/api/lessons.php?action=templates');
        const result = await response.json();
        if (result.success && result.data.lessons.length > 0) {
            tbody.innerHTML = result.data.lessons.map(l => `
                <tr>
                    <td>${escapeHtml(l.title)}</td>
                    <td>${lessonCategories[l.category] || '-'}</td>
                    <td><span class="badge badge-${l.severity === 'critical' ? 'danger' : l.severity === 'high' ? 'warning' : 'info'}">${severityNames[l.severity] || l.severity}</span></td>
                    <td>${escapeHtml(l.check_timing || '-')}</td>
                    <td>${escapeHtml(l.prevention_check_item || '-')}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">暂无踩坑记录模板</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="error-state">加载失败</td></tr>';
    }
}
</script>
