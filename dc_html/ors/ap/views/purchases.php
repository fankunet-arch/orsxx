<div class="page-header">
    <h2>采购列表</h2>
    <div class="page-actions">
        <select id="projectFilter" onchange="loadPurchases()">
            <option value="">全部项目</option>
        </select>
    </div>
</div>

<div class="summary-bar">
    <div class="summary-item">
        <span class="summary-label">EUR 总计:</span>
        <span class="summary-value" id="totalEur">0.00</span>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="purchasesTable">
        <thead>
            <tr>
                <th>物品</th>
                <th>数量</th>
                <th>单价</th>
                <th>币种</th>
                <th>汇率</th>
                <th>折合EUR</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="purchasesBody">
            <tr><td colspan="8" class="loading">加载中...</td></tr>
        </tbody>
    </table>
</div>

<!-- 归一化弹窗 -->
<div id="normalizeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>沉淀到物品库</h3>
            <button class="modal-close" onclick="closeNormalizeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="normalizePurchaseId">
            <div class="form-group">
                <label>物品名称 *</label>
                <input type="text" id="normalizeItemName" required>
            </div>
            <div class="form-group">
                <label>分类</label>
                <select id="normalizeCategory">
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
                <input type="text" id="normalizeUnit" value="pcs">
            </div>
            <div class="form-group">
                <label>必买等级</label>
                <select id="normalizeMustBuy">
                    <option value="must">必买</option>
                    <option value="recommended" selected>推荐</option>
                    <option value="optional">可选</option>
                </select>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="normalizeTemplate" checked>
                    <span>加入模板库</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeNormalizeModal()">取消</button>
            <button class="btn btn-primary" onclick="executeNormalize()">创建物品</button>
        </div>
    </div>
</div>

<script>
const statusNames = {
    'planned': '计划中',
    'ordered': '已下单',
    'shipped': '已发货',
    'received': '已收货',
    'cancelled': '已取消'
};

document.addEventListener('DOMContentLoaded', function() {
    loadProjects();
    loadPurchases();
});

async function loadProjects() {
    try {
        const response = await fetch('/ors/api/projects.php?action=list');
        const result = await response.json();
        if (result.success) {
            const select = document.getElementById('projectFilter');
            result.data.projects.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = p.project_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('加载项目失败:', error);
    }
}

async function loadPurchases() {
    const projectId = document.getElementById('projectFilter').value;
    const url = '/ors/api/purchases.php?action=list' + (projectId ? '&project_id=' + projectId : '');

    const tbody = document.getElementById('purchasesBody');

    try {
        const response = await fetch(url);
        const result = await response.json();

        if (result.success && result.data.purchases.length > 0) {
            let totalEur = 0;
            tbody.innerHTML = result.data.purchases.map(p => {
                totalEur += parseFloat(p.total_price_eur) || 0;
                const itemName = p.linked_item_name || p.free_text_item || '未命名';
                const isNormalized = !!p.item_id;
                return `
                <tr>
                    <td>
                        ${escapeHtml(itemName)}
                        ${!isNormalized ? `<button class="btn btn-xs btn-outline ml-2" onclick="showNormalizeModal(${p.id}, '${escapeHtml(p.free_text_item || '')}')">归一化</button>` : ''}
                    </td>
                    <td>${p.quantity}</td>
                    <td>${formatNumber(p.unit_price)}</td>
                    <td><span class="currency-badge">${p.currency}</span></td>
                    <td>${p.fx_rate_to_eur || '-'}</td>
                    <td><strong>${formatNumber(p.total_price_eur)}</strong></td>
                    <td><span class="status-badge status-${p.status}">${statusNames[p.status] || p.status}</span></td>
                    <td>
                        <button class="btn btn-xs" onclick="editPurchase(${p.id})">编辑</button>
                    </td>
                </tr>
                `;
            }).join('');
            document.getElementById('totalEur').textContent = formatNumber(totalEur) + ' EUR';
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state">暂无采购记录</td></tr>';
            document.getElementById('totalEur').textContent = '0.00 EUR';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="8" class="error-state">加载失败</td></tr>';
    }
}

function showNormalizeModal(purchaseId, itemName) {
    document.getElementById('normalizePurchaseId').value = purchaseId;
    document.getElementById('normalizeItemName').value = itemName;
    document.getElementById('normalizeModal').style.display = 'flex';
}

function closeNormalizeModal() {
    document.getElementById('normalizeModal').style.display = 'none';
}

async function executeNormalize() {
    const purchaseId = document.getElementById('normalizePurchaseId').value;
    const itemName = document.getElementById('normalizeItemName').value;
    const category = document.getElementById('normalizeCategory').value;
    const unit = document.getElementById('normalizeUnit').value;
    const mustBuyLevel = document.getElementById('normalizeMustBuy').value;
    const templateFlag = document.getElementById('normalizeTemplate').checked;

    if (!itemName) {
        showToast('物品名称不能为空', 'error');
        return;
    }

    try {
        const response = await fetch('/ors/api/purchases.php?action=normalize', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                purchase_id: purchaseId,
                item_name: itemName,
                category: category,
                unit: unit,
                must_buy_level: mustBuyLevel,
                template_flag: templateFlag
            })
        });

        const result = await response.json();
        if (result.success) {
            showToast('物品已创建并关联！', 'success');
            closeNormalizeModal();
            loadPurchases();
        } else {
            showToast(result.message || '操作失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}

function editPurchase(id) {
    showToast('编辑功能 - 待实现', 'info');
}

function formatNumber(num) {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
}
</script>
