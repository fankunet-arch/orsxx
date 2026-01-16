<div class="page-header">
    <h2>Template Library</h2>
</div>

<div class="tab-bar-inline">
    <button class="tab-btn active" data-tab="tasksTab" onclick="switchTab('tasksTab')">Task Templates</button>
    <button class="tab-btn" data-tab="itemsTab" onclick="switchTab('itemsTab')">Item Templates</button>
    <button class="tab-btn" data-tab="lessonsTab" onclick="switchTab('lessonsTab')">Lesson Templates</button>
</div>

<div id="tasksTab" class="tab-content active">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Phase</th>
                    <th>Blocking</th>
                    <th>Lead Time</th>
                    <th>Tags</th>
                </tr>
            </thead>
            <tbody id="taskTemplatesBody">
                <tr><td colspan="5" class="loading">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="itemsTab" class="tab-content">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Must Buy</th>
                    <th>Long Lead</th>
                    <th>Lead Time</th>
                    <th>Tags</th>
                </tr>
            </thead>
            <tbody id="itemTemplatesBody">
                <tr><td colspan="6" class="loading">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="lessonsTab" class="tab-content">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Severity</th>
                    <th>Check Timing</th>
                    <th>Prevention Check Item</th>
                </tr>
            </thead>
            <tbody id="lessonTemplatesBody">
                <tr><td colspan="5" class="loading">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const categories = {
    'it_devices': 'IT Devices',
    'furniture': 'Furniture',
    'equipment': 'Equipment',
    'consumables': 'Consumables',
    'other': 'Other'
};

const lessonCategories = {
    'it': 'IT',
    'power': 'Power',
    'fire_safety': 'Fire Safety',
    'permit': 'Permits',
    'procurement': 'Procurement',
    'other': 'Other'
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
        console.error('Failed to load phases:', error);
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
                    <td>${t.blocking_flag ? '<span class="badge badge-danger">Yes</span>' : '-'}</td>
                    <td>${t.lead_time_days || '-'} days</td>
                    <td>${t.tags && t.tags.length > 0 ? t.tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join(' ') : '-'}</td>
                </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No task templates</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="error-state">Failed to load</td></tr>';
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
                    <td>${i.must_buy_level || '-'}</td>
                    <td>${i.long_lead_flag ? 'Yes' : '-'}</td>
                    <td>${i.lead_time_days || '-'} days</td>
                    <td>${i.tags && i.tags.length > 0 ? i.tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join(' ') : '-'}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No item templates</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" class="error-state">Failed to load</td></tr>';
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
                    <td><span class="badge badge-${l.severity === 'critical' ? 'danger' : l.severity === 'high' ? 'warning' : 'info'}">${l.severity}</span></td>
                    <td>${escapeHtml(l.check_timing || '-')}</td>
                    <td>${escapeHtml(l.prevention_check_item || '-')}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No lesson templates</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="error-state">Failed to load</td></tr>';
    }
}
</script>
