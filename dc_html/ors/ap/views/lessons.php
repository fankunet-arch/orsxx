<div class="page-header">
    <h2>Lessons Learned</h2>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddLessonModal()">+ Add Lesson</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="lessonsTable">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Severity</th>
                <th>Check Timing</th>
                <th>Prevention Check Item</th>
                <th>Template</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="lessonsBody">
            <tr><td colspan="7" class="loading">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- Add/Edit Lesson Modal -->
<div id="lessonModal" class="modal" style="display:none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="lessonModalTitle">Add Lesson</h3>
            <button class="modal-close" onclick="closeLessonModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="lessonId">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" id="lessonTitle" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="lessonDescription" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select id="lessonCategory">
                        <option value="">-- Select --</option>
                        <option value="it">IT</option>
                        <option value="power">Power</option>
                        <option value="fire_safety">Fire Safety</option>
                        <option value="permit">Permits</option>
                        <option value="procurement">Procurement</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Severity</label>
                    <select id="lessonSeverity">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Root Cause</label>
                <textarea id="lessonRootCause" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Prevention Check Item * (Required for templates)</label>
                <textarea id="lessonPreventionCheckItem" rows="3" required
                    placeholder="What should be checked to prevent this issue?"></textarea>
            </div>
            <div class="form-group">
                <label>Check Timing Description</label>
                <input type="text" id="lessonCheckTiming" placeholder="e.g. 'After signing contract'">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Check Days Before Open</label>
                    <input type="number" id="lessonDaysBeforeOpen" min="0">
                </div>
                <div class="form-group">
                    <label>Check Days After Signing</label>
                    <input type="number" id="lessonDaysAfterSign" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="lessonTemplate" checked>
                    <span>Add to Template Library</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeLessonModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveLesson()">Save</button>
        </div>
    </div>
</div>

<script>
const lessonCategories = {
    'it': 'IT',
    'power': 'Power',
    'fire_safety': 'Fire Safety',
    'permit': 'Permits',
    'procurement': 'Procurement',
    'other': 'Other'
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
                    <td><span class="badge badge-${l.severity === 'critical' ? 'danger' : l.severity === 'high' ? 'warning' : 'info'}">${l.severity}</span></td>
                    <td>${escapeHtml(l.check_timing || '-')}</td>
                    <td class="text-truncate" style="max-width: 300px;">${escapeHtml(l.prevention_check_item || '-')}</td>
                    <td>${l.template_flag ? '<span class="badge badge-success">Yes</span>' : '-'}</td>
                    <td>
                        <button class="btn btn-xs" onclick="editLesson(${l.id})">Edit</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No lessons found</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="7" class="error-state">Failed to load lessons</td></tr>';
    }
}

function showAddLessonModal() {
    document.getElementById('lessonModalTitle').textContent = 'Add Lesson';
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
        showToast('Prevention check item is required', 'error');
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
        showToast('Title is required', 'error');
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
            showToast('Lesson saved!', 'success');
            closeLessonModal();
            loadLessons();
        } else {
            showToast(result.message || 'Failed to save', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

function editLesson(id) {
    showToast('Edit feature - load lesson and show modal', 'info');
}
</script>
