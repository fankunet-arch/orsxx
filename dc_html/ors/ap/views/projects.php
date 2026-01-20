<div class="page-header">
    <h2>项目中心</h2>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddProjectModal()">+ 新建项目</button>
    </div>
</div>

<p class="page-description" style="color: var(--text-light); margin-bottom: 24px;">
    项目执行的核心入口。创建新项目后，可以从模板一键生成任务、采购和检查清单。
</p>

<div class="data-table-wrapper">
    <table class="data-table" id="projectsTable">
        <thead>
            <tr>
                <th>项目名称</th>
                <th>类型</th>
                <th>城市</th>
                <th>面积</th>
                <th>目标开业日</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="projectsBody">
            <tr><td colspan="7" class="loading">加载中...</td></tr>
        </tbody>
    </table>
</div>

<!-- 新建/编辑项目弹窗 -->
<div id="projectModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="projectModalTitle">新建项目</h3>
            <button class="modal-close" onclick="closeProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="projectId">
            <div class="form-group">
                <label>项目名称 *</label>
                <input type="text" id="projectName" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>类型</label>
                    <select id="projectType">
                        <option value="cafeteria">咖啡厅</option>
                        <option value="restaurant">餐厅</option>
                        <option value="retail">零售店</option>
                        <option value="bubble_tea">奶茶店</option>
                        <option value="ice_cream">冰淇淋店</option>
                        <option value="dessert">甜品店</option>
                        <option value="fried_chicken">炸鸡店</option>
                        <option value="poke">POKE店</option>
                        <option value="sushi">寿司店</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>城市</label>
                    <input type="text" id="projectCity">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>面积（平方米）</label>
                    <input type="number" id="projectArea" step="0.01">
                </div>
                <div class="form-group">
                    <label>目标开业日期</label>
                    <input type="date" id="projectOpenDate">
                </div>
            </div>
            <div class="form-group">
                <label>地址</label>
                <textarea id="projectAddress" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>状态</label>
                <select id="projectStatus">
                    <option value="planning">规划中</option>
                    <option value="active">进行中</option>
                    <option value="completed">已完成</option>
                    <option value="archived">已归档</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeProjectModal()">取消</button>
            <button class="btn btn-primary" onclick="saveProject()">保存</button>
        </div>
    </div>
</div>

<!-- 从模板生成弹窗 -->
<div id="generateModal" class="modal" style="display:none;">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>从模板生成</h3>
            <button class="modal-close" onclick="closeGenerateModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="generateProjectId">
            <p>将自动生成以下内容：</p>
            <ul>
                <li>任务清单（来自任务模板）</li>
                <li>采购清单（来自物品模板）</li>
                <li>检查清单（来自踩坑记录模板）</li>
            </ul>
            <p class="text-warning">将根据目标开业日期自动计算截止日期。</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeGenerateModal()">取消</button>
            <button class="btn btn-primary" onclick="executeGenerate()">一键生成</button>
        </div>
    </div>
</div>

<script>
const statusNames = {
    'planning': '规划中',
    'active': '进行中',
    'completed': '已完成',
    'archived': '已归档'
};

const typeNames = {
    'cafeteria': '咖啡厅',
    'restaurant': '餐厅',
    'retail': '零售店',
    'bubble_tea': '奶茶店',
    'ice_cream': '冰淇淋店',
    'dessert': '甜品店',
    'fried_chicken': '炸鸡店',
    'poke': 'POKE店',
    'sushi': '寿司店'
};

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
                    <td>${typeNames[p.project_type] || p.project_type || '-'}</td>
                    <td>${escapeHtml(p.city || '-')}</td>
                    <td>${p.area_m2 ? p.area_m2 + ' m²' : '-'}</td>
                    <td>${p.target_open_date || '-'}</td>
                    <td><span class="status-badge status-${p.status}">${statusNames[p.status] || p.status}</span></td>
                    <td>
                        <button class="btn btn-xs" onclick="editProject(${p.id})">编辑</button>
                        <button class="btn btn-xs btn-primary" onclick="showGenerateModal(${p.id})">生成</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">暂无项目</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="7" class="error-state">加载失败</td></tr>';
    }
}

function showAddProjectModal() {
    document.getElementById('projectModalTitle').textContent = '新建项目';
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
        showToast('项目名称不能为空', 'error');
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
            showToast('项目已保存！', 'success');
            closeProjectModal();
            loadProjects();
        } else {
            showToast(result.message || '保存失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}

async function editProject(id) {
    try {
        const response = await fetch('/ors/api/projects.php?action=get&id=' + id);
        const result = await response.json();

        if (result.success && result.data.project) {
            const project = result.data.project;

            // 填充编辑表单
            document.getElementById('projectModalTitle').textContent = '编辑项目';
            document.getElementById('projectId').value = project.id;
            document.getElementById('projectName').value = project.project_name || '';
            document.getElementById('projectType').value = project.project_type || 'cafeteria';
            document.getElementById('projectCity').value = project.city || '';
            document.getElementById('projectArea').value = project.area_m2 || '';
            document.getElementById('projectOpenDate').value = project.target_open_date || '';
            document.getElementById('projectAddress').value = project.address || '';
            document.getElementById('projectStatus').value = project.status || 'planning';

            document.getElementById('projectModal').style.display = 'flex';
        } else {
            showToast('加载项目失败', 'error');
        }
    } catch (error) {
        console.error('加载项目失败:', error);
        showToast('网络错误', 'error');
    }
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
            showToast(`已生成：${data.created_tasks} 个任务，${data.created_purchases} 个采购，${data.created_check_items} 个检查项`, 'success');
            closeGenerateModal();
        } else {
            showToast(result.message || '生成失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}
</script>
