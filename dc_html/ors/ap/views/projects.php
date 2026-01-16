<div class="page-header">
    <h2>Projects</h2>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddProjectModal()">+ New Project</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="projectsTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>City</th>
                <th>Area</th>
                <th>Target Open</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="projectsBody">
            <tr><td colspan="7" class="loading">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- Add/Edit Project Modal -->
<div id="projectModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="projectModalTitle">New Project</h3>
            <button class="modal-close" onclick="closeProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="projectId">
            <div class="form-group">
                <label>Project Name *</label>
                <input type="text" id="projectName" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select id="projectType">
                        <option value="cafeteria">Cafeteria</option>
                        <option value="restaurant">Restaurant</option>
                        <option value="retail">Retail</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" id="projectCity">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Area (m2)</label>
                    <input type="number" id="projectArea" step="0.01">
                </div>
                <div class="form-group">
                    <label>Target Open Date</label>
                    <input type="date" id="projectOpenDate">
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea id="projectAddress" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select id="projectStatus">
                    <option value="planning">Planning</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeProjectModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveProject()">Save</button>
        </div>
    </div>
</div>

<!-- Generate from Template Modal -->
<div id="generateModal" class="modal" style="display:none;">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>Generate from Template</h3>
            <button class="modal-close" onclick="closeGenerateModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="generateProjectId">
            <p>This will generate:</p>
            <ul>
                <li>Tasks from task templates</li>
                <li>Purchase list from item templates</li>
                <li>Check items from lesson templates</li>
            </ul>
            <p class="text-warning">Target open date will be used to calculate deadlines.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeGenerateModal()">Cancel</button>
            <button class="btn btn-primary" onclick="executeGenerate()">Generate</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadProjects();
});

async function loadProjects() {
    const tbody = document.getElementById('projectsBody');

    try {
        const response = await fetch('/ors/api/projects.php?action=list');
        const result = await response.json();

        if (result.success && result.data.projects.length > 0) {
            tbody.innerHTML = result.data.projects.map(p => `
                <tr>
                    <td>${escapeHtml(p.project_name)}</td>
                    <td>${p.project_type || '-'}</td>
                    <td>${escapeHtml(p.city || '-')}</td>
                    <td>${p.area_m2 ? p.area_m2 + ' m2' : '-'}</td>
                    <td>${p.target_open_date || '-'}</td>
                    <td><span class="status-badge status-${p.status}">${p.status}</span></td>
                    <td>
                        <button class="btn btn-xs" onclick="editProject(${p.id})">Edit</button>
                        <button class="btn btn-xs btn-primary" onclick="showGenerateModal(${p.id})">Generate</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No projects found</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="7" class="error-state">Failed to load projects</td></tr>';
    }
}

function showAddProjectModal() {
    document.getElementById('projectModalTitle').textContent = 'New Project';
    document.getElementById('projectId').value = '';
    document.getElementById('projectName').value = '';
    document.getElementById('projectType').value = 'cafeteria';
    document.getElementById('projectCity').value = '';
    document.getElementById('projectArea').value = '';
    document.getElementById('projectOpenDate').value = '';
    document.getElementById('projectAddress').value = '';
    document.getElementById('projectStatus').value = 'planning';
    document.getElementById('projectModal').style.display = 'flex';
}

function closeProjectModal() {
    document.getElementById('projectModal').style.display = 'none';
}

async function saveProject() {
    const id = document.getElementById('projectId').value;
    const data = {
        project_name: document.getElementById('projectName').value,
        project_type: document.getElementById('projectType').value,
        city: document.getElementById('projectCity').value,
        area_m2: document.getElementById('projectArea').value || null,
        target_open_date: document.getElementById('projectOpenDate').value || null,
        address: document.getElementById('projectAddress').value,
        status: document.getElementById('projectStatus').value
    };

    if (!data.project_name) {
        showToast('Project name is required', 'error');
        return;
    }

    const action = id ? 'update' : 'create';
    if (id) data.id = id;

    try {
        const response = await fetch('/ors/api/projects.php?action=' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('Project saved!', 'success');
            closeProjectModal();
            loadProjects();
        } else {
            showToast(result.message || 'Failed to save', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

function editProject(id) {
    showToast('Edit feature - load project and show modal', 'info');
}

function showGenerateModal(projectId) {
    document.getElementById('generateProjectId').value = projectId;
    document.getElementById('generateModal').style.display = 'flex';
}

function closeGenerateModal() {
    document.getElementById('generateModal').style.display = 'none';
}

async function executeGenerate() {
    const projectId = document.getElementById('generateProjectId').value;

    try {
        const response = await fetch('/ors/api/projects.php?action=generateFromTemplate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: projectId })
        });

        const result = await response.json();
        if (result.success) {
            const data = result.data;
            showToast(`Generated: ${data.created_tasks} tasks, ${data.created_purchases} purchases, ${data.created_check_items} check items`, 'success');
            closeGenerateModal();
        } else {
            showToast(result.message || 'Failed to generate', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}
</script>
