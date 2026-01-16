<div class="page-header">
    <h2>物品库</h2>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddItemModal()">+ 新增物品</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="itemsTable">
        <thead>
            <tr>
                <th>名称</th>
                <th>分类</th>
                <th>单位</th>
                <th>必买等级</th>
                <th>长周期</th>
                <th>采购周期</th>
                <th>模板</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr><td colspan="8" class="loading">加载中...</td></tr>
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
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeItemModal()">取消</button>
            <button class="btn btn-primary" onclick="saveItem()">保存</button>
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

document.addEventListener('DOMContentLoaded', function() {
    loadItems();
});

async function loadItems() {
    const tbody = document.getElementById('itemsBody');

    try {
        const response = await fetch('/ors/api/items.php?action=list');
        const result = await response.json();

        if (result.success && result.data.items.length > 0) {
            tbody.innerHTML = result.data.items.map(item => `
                <tr>
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
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state">暂无物品</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="8" class="error-state">加载失败</td></tr>';
    }
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
    document.getElementById('itemModal').style.display = 'flex';
}

function closeItemModal() {
    document.getElementById('itemModal').style.display = 'none';
}

async function saveItem() {
    const id = document.getElementById('itemId').value;
    const data = {
        item_name: document.getElementById('itemName').value,
        category: document.getElementById('itemCategory').value,
        unit: document.getElementById('itemUnit').value,
        must_buy_level: document.getElementById('itemMustBuy').value,
        lead_time_days: document.getElementById('itemLeadTime').value || null,
        long_lead_flag: document.getElementById('itemLongLead').checked,
        template_flag: document.getElementById('itemTemplate').checked
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

function editItem(id) {
    showToast('编辑功能 - 待实现', 'info');
}
</script>
