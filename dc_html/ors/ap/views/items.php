<div class="page-header">
    <h2>物品模板</h2>
    <div class="page-actions">
        <button class="btn btn-outline" onclick="showBulkActions()">批量更新</button>
        <button class="btn btn-primary" onclick="showAddItemModal()">+ 新增物品</button>
    </div>
</div>

<p class="page-description" style="color: var(--text-light); margin-bottom: 24px;">
    管理标准化的物品清单模板。在项目采购过程中发现新物品时，可以沉淀到这里，供下次开业参考。
</p>

<div class="data-table-wrapper">
    <table class="data-table" id="itemsTable">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                <th class="sortable" data-sort-key="item_name">名称</th>
                <th class="sortable" data-sort-key="category">分类</th>
                <th class="sortable" data-sort-key="unit">单位</th>
                <th class="sortable" data-sort-key="must_buy_level">必买等级</th>
                <th class="sortable" data-sort-key="long_lead_flag">长周期</th>
                <th class="sortable" data-sort-key="lead_time_days">采购周期</th>
                <th class="sortable" data-sort-key="template_flag">模板</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr><td colspan="9" class="loading">加载中...</td></tr>
        </tbody>
    </table>
</div>

<!-- 新增/编辑物品弹窗 -->
<div id="itemModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="itemModalTitle">新增物品</h3>
            <button class="modal-close" onclick="closeItemModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="itemId">
            <div class="form-group">
                <label>物品名称 *</label>
                <input type="text" id="itemName" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>分类</label>
                    <select id="itemCategory">
                        <option value="">-- 请选择 --</option>
                        <option value="it_devices">IT设备</option>
                        <option value="furniture">家具</option>
                        <option value="equipment">设备</option>
                        <option value="consumables">耗材</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>单位</label>
                    <input type="text" id="itemUnit" value="pcs">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>必买等级</label>
                    <select id="itemMustBuy">
                        <option value="must">必买</option>
                        <option value="recommended" selected>推荐</option>
                        <option value="optional">可选</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>采购周期（天）</label>
                    <input type="number" id="itemLeadTime" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="itemLongLead">
                    <span>长周期采购项</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="itemTemplate" checked>
                    <span>加入模板库</span>
                </label>
            </div>
            <div class="form-group" id="projectTypesGroup">
                <label>适用店铺类型（留空表示全部适用）</label>
                <div class="checkbox-grid">
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="cafeteria"> 咖啡厅</label>
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="restaurant"> 餐厅</label>
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="retail"> 零售店</label>
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="bubble_tea"> 奶茶店</label>
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="ice_cream"> 冰淇淋店</label>
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="dessert"> 甜品店</label>
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="fried_chicken"> 炸鸡店</label>
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="poke"> POKE店</label>
                    <label class="checkbox-label"><input type="checkbox" name="projectTypes" value="sushi"> 寿司店</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeItemModal()">取消</button>
            <button class="btn btn-primary" onclick="saveItem()">保存</button>
        </div>
    </div>
</div>

<!-- 批量更新弹窗 -->
<div id="bulkModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>批量更新物品</h3>
            <button class="modal-close" onclick="closeBulkModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="bulkCount">已选择 0 个物品</p>
            <div class="form-group">
                <label>标记为模板</label>
                <select id="bulkTemplate">
                    <option value="">-- 不修改 --</option>
                    <option value="1">是</option>
                    <option value="0">否</option>
                </select>
            </div>
            <div class="form-group">
                <label>适用店铺类型</label>
                <select id="bulkProjectTypesAction">
                    <option value="">-- 不修改 --</option>
                    <option value="all">设为全部适用（清空选择）</option>
                    <option value="set">设为指定类型</option>
                </select>
            </div>
            <div class="form-group" id="bulkProjectTypesGroup" style="display:none;">
                <label>选择适用的店铺类型</label>
                <div class="checkbox-grid">
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="cafeteria"> 咖啡厅</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="restaurant"> 餐厅</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="retail"> 零售店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="bubble_tea"> 奶茶店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="ice_cream"> 冰淇淋店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="dessert"> 甜品店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="fried_chicken"> 炸鸡店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="poke"> POKE店</label>
                    <label class="checkbox-label"><input type="checkbox" name="bulkProjectTypes" value="sushi"> 寿司店</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeBulkModal()">取消</button>
            <button class="btn btn-primary" onclick="executeBulkUpdate()">应用更改</button>
        </div>
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

// 存储当前物品数据用于排序
let currentItems = [];
let currentSortKey = null;
let currentSortDir = 'asc';

// 必买等级排序顺序
const mustBuyOrder = { 'must': 0, 'recommended': 1, 'optional': 2 };

document.addEventListener('DOMContentLoaded', function() {
    loadItems();
    // 初始化排序点击事件
    document.querySelectorAll('#itemsTable th.sortable').forEach(th => {
        th.addEventListener('click', () => sortItems(th.dataset.sortKey));
    });
});

async function loadItems() {
    const tbody = document.getElementById('itemsBody');

    try {
        const response = await fetch('/ors/api/items.php?action=list');
        const result = await response.json();

        if (result.success && result.data.items.length > 0) {
            currentItems = result.data.items;
            renderItems();
        } else {
            currentItems = [];
            tbody.innerHTML = '<tr><td colspan="9" class="empty-state">暂无物品</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="9" class="error-state">加载失败</td></tr>';
    }
}

function renderItems() {
    const tbody = document.getElementById('itemsBody');
    tbody.innerHTML = currentItems.map(item => `
        <tr>
            <td><input type="checkbox" class="item-checkbox" value="${item.id}"></td>
            <td>${escapeHtml(item.item_name)}</td>
            <td>${categories[item.category] || '-'}</td>
            <td>${item.unit || '-'}</td>
            <td>${mustBuyLevels[item.must_buy_level] || '-'}</td>
            <td>${item.long_lead_flag ? '是' : '-'}</td>
            <td>${item.lead_time_days ? item.lead_time_days + ' 天' : '-'}</td>
            <td>${item.template_flag ? '<span class="badge badge-success">是</span>' : '-'}</td>
            <td>
                <button class="btn btn-xs" onclick="editItem(${item.id})">编辑</button>
            </td>
        </tr>
    `).join('');
}

function sortItems(key) {
    // 切换排序方向
    if (currentSortKey === key) {
        currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortKey = key;
        currentSortDir = 'asc';
    }

    // 更新表头样式
    document.querySelectorAll('#itemsTable th.sortable').forEach(th => {
        th.classList.remove('asc', 'desc');
        if (th.dataset.sortKey === key) {
            th.classList.add(currentSortDir);
        }
    });

    // 排序数据
    currentItems.sort((a, b) => {
        let valA = a[key];
        let valB = b[key];

        // 特殊排序规则
        if (key === 'must_buy_level') {
            valA = mustBuyOrder[valA] ?? 99;
            valB = mustBuyOrder[valB] ?? 99;
        } else if (key === 'long_lead_flag' || key === 'template_flag') {
            valA = valA ? 1 : 0;
            valB = valB ? 1 : 0;
        } else if (key === 'lead_time_days') {
            valA = parseInt(valA) || 0;
            valB = parseInt(valB) || 0;
        }

        // null 值处理
        if (valA === null || valA === undefined) valA = '';
        if (valB === null || valB === undefined) valB = '';

        // 字符串比较
        if (typeof valA === 'string') {
            valA = valA.toLowerCase();
            valB = valB.toLowerCase();
        }

        let result = 0;
        if (valA < valB) result = -1;
        if (valA > valB) result = 1;

        return currentSortDir === 'asc' ? result : -result;
    });

    renderItems();
}

function showAddItemModal() {
    document.getElementById('itemModalTitle').textContent = '新增物品';
    document.getElementById('itemId').value = '';
    document.getElementById('itemName').value = '';
    document.getElementById('itemCategory').value = '';
    document.getElementById('itemUnit').value = 'pcs';
    document.getElementById('itemMustBuy').value = 'recommended';
    document.getElementById('itemLeadTime').value = '';
    document.getElementById('itemLongLead').checked = false;
    document.getElementById('itemTemplate').checked = true;
    // 清空项目类型选择
    document.querySelectorAll('input[name="projectTypes"]').forEach(cb => cb.checked = false);
    document.getElementById('itemModal').style.display = 'flex';
}

function closeItemModal() {
    document.getElementById('itemModal').style.display = 'none';
}

async function saveItem() {
    const id = document.getElementById('itemId').value;
    // 获取选中的项目类型
    const selectedTypes = Array.from(document.querySelectorAll('input[name="projectTypes"]:checked'))
        .map(cb => cb.value);
    const data = {
        item_name: document.getElementById('itemName').value,
        category: document.getElementById('itemCategory').value,
        unit: document.getElementById('itemUnit').value,
        must_buy_level: document.getElementById('itemMustBuy').value,
        lead_time_days: document.getElementById('itemLeadTime').value || null,
        long_lead_flag: document.getElementById('itemLongLead').checked,
        template_flag: document.getElementById('itemTemplate').checked,
        project_types: selectedTypes.length > 0 ? selectedTypes.join(',') : null
    };

    if (!data.item_name) {
        showToast('物品名称不能为空', 'error');
        return;
    }

    const action = id ? 'update' : 'create';
    if (id) data.id = id;

    try {
        const response = await fetch('/ors/api/items.php?action=' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('物品已保存！', 'success');
            closeItemModal();
            loadItems();
        } else {
            showToast(result.message || '保存失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}

async function editItem(id) {
    try {
        const response = await fetch('/ors/api/items.php?action=get&id=' + id);
        const result = await response.json();

        if (result.success && result.data.item) {
            const item = result.data.item;

            // 填充编辑表单
            document.getElementById('itemModalTitle').textContent = '编辑物品';
            document.getElementById('itemId').value = item.id;
            document.getElementById('itemName').value = item.item_name || '';
            document.getElementById('itemCategory').value = item.category || '';
            document.getElementById('itemUnit').value = item.unit || 'pcs';
            document.getElementById('itemMustBuy').value = item.must_buy_level || 'recommended';
            document.getElementById('itemLeadTime').value = item.lead_time_days || '';
            document.getElementById('itemLongLead').checked = item.long_lead_flag == 1;
            document.getElementById('itemTemplate').checked = item.template_flag == 1;

            // 设置项目类型选择
            const projectTypes = item.project_types ? item.project_types.split(',') : [];
            document.querySelectorAll('input[name="projectTypes"]').forEach(cb => {
                cb.checked = projectTypes.includes(cb.value);
            });

            document.getElementById('itemModal').style.display = 'flex';
        } else {
            showToast('加载物品失败', 'error');
        }
    } catch (error) {
        console.error('加载物品失败:', error);
        showToast('网络错误', 'error');
    }
}

// 批量操作函数
function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => parseInt(cb.value));
}

function showBulkActions() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        showToast('请至少选择一个物品', 'warning');
        return;
    }
    document.getElementById('bulkCount').textContent = '已选择 ' + ids.length + ' 个物品';
    // 重置项目类型选择
    document.getElementById('bulkProjectTypesAction').value = '';
    document.getElementById('bulkProjectTypesGroup').style.display = 'none';
    document.querySelectorAll('input[name="bulkProjectTypes"]').forEach(cb => cb.checked = false);
    document.getElementById('bulkTemplate').value = '';
    document.getElementById('bulkModal').style.display = 'flex';
}

function closeBulkModal() {
    document.getElementById('bulkModal').style.display = 'none';
}

// 监听项目类型操作选择变化
document.addEventListener('DOMContentLoaded', function() {
    const actionSelect = document.getElementById('bulkProjectTypesAction');
    if (actionSelect) {
        actionSelect.addEventListener('change', function() {
            const group = document.getElementById('bulkProjectTypesGroup');
            group.style.display = this.value === 'set' ? 'block' : 'none';
        });
    }
});

async function executeBulkUpdate() {
    const ids = getSelectedIds();
    const template = document.getElementById('bulkTemplate').value;
    const projectTypesAction = document.getElementById('bulkProjectTypesAction').value;

    const data = { ids };
    if (template !== '') data.template_flag = template === '1';

    // 处理项目类型
    if (projectTypesAction === 'all') {
        data.project_types = null; // 设为全部适用
    } else if (projectTypesAction === 'set') {
        const selectedTypes = Array.from(document.querySelectorAll('input[name="bulkProjectTypes"]:checked'))
            .map(cb => cb.value);
        data.project_types = selectedTypes.length > 0 ? selectedTypes.join(',') : null;
    }

    try {
        const response = await fetch('/ors/api/items.php?action=bulkUpdate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('批量更新成功！', 'success');
            closeBulkModal();
            loadItems();
        } else {
            showToast(result.message || '批量更新失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}
</script>
