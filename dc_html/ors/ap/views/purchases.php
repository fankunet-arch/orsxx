<?php
// 获取当前项目ID
$projectId = $currentProjectId ?? null;
?>

<?php if (!$projectId): ?>
<div class="no-project-warning">
    请先在顶部选择一个项目，或前往 <a href="/ors/ap/?action=projects">项目中心</a> 创建/选择项目
</div>
<?php endif; ?>

<div class="page-header">
    <h2>项目采购</h2>
    <div class="page-actions">
        <select id="projectFilter" onchange="loadPurchases()" style="display:none;">
            <option value="">全部项目</option>
        </select>
    </div>
</div>

<p class="page-description" style="color: var(--text-light); margin-bottom: 24px;">
    当前项目的采购记录。可将采购项"沉淀"到物品模板库，供下次开业使用。
</p>

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

<!-- 编辑采购弹窗 -->
<div id="purchaseModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="purchaseModalTitle">编辑采购</h3>
            <button class="modal-close" onclick="closePurchaseModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="purchaseId">
            <div class="form-group">
                <label>物品名称</label>
                <input type="text" id="purchaseItemName" readonly style="background: #f5f5f5;">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>数量</label>
                    <input type="number" id="purchaseQuantity" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label>单位</label>
                    <input type="text" id="purchaseUnit">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>单价</label>
                    <input type="number" id="purchaseUnitPrice" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label>币种</label>
                    <select id="purchaseCurrency">
                        <option value="EUR">EUR</option>
                        <option value="CNY">CNY</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>汇率（到EUR）</label>
                <input type="number" id="purchaseFxRate" min="0" step="0.000001" placeholder="如币种为EUR则留空">
            </div>
            <div class="form-group">
                <label>状态</label>
                <select id="purchaseStatus">
                    <option value="planned">计划中</option>
                    <option value="ordered">已下单</option>
                    <option value="shipped">已发货</option>
                    <option value="received">已收货</option>
                    <option value="cancelled">已取消</option>
                </select>
            </div>
            <div class="form-group">
                <label>备注</label>
                <textarea id="purchaseNotes" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closePurchaseModal()">取消</button>
            <button class="btn btn-primary" onclick="savePurchase()">保存</button>
        </div>
    </div>
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
// 当前项目ID（从PHP传递）
const purchaseProjectId = <?php echo json_encode($projectId); ?>;

const statusNames = {
    'planned': '计划中',
    'ordered': '已下单',
    'shipped': '已发货',
    'received': '已收货',
    'cancelled': '已取消'
};

document.addEventListener('DOMContentLoaded', function() {
    if (purchaseProjectId) {
        loadPurchases();
    }
});

async function loadPurchases() {
    // 使用当前项目ID
    const projectId = purchaseProjectId;
    if (!projectId) {
        return;
    }
    const url = '/ors/api/purchases.php?action=list&project_id=' + projectId;

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

async function editPurchase(id) {
    try {
        const response = await fetch('/ors/api/purchases.php?action=get&id=' + id);
        const result = await response.json();

        if (result.success && result.data.purchase) {
            const purchase = result.data.purchase;

            // 填充编辑表单
            document.getElementById('purchaseModalTitle').textContent = '编辑采购';
            document.getElementById('purchaseId').value = purchase.id;
            document.getElementById('purchaseItemName').value = purchase.linked_item_name || purchase.free_text_item || '';
            document.getElementById('purchaseQuantity').value = purchase.quantity || 1;
            document.getElementById('purchaseUnit').value = purchase.unit || 'pcs';
            document.getElementById('purchaseUnitPrice').value = purchase.unit_price || '';
            document.getElementById('purchaseCurrency').value = purchase.currency || 'EUR';
            document.getElementById('purchaseFxRate').value = purchase.fx_rate_to_eur || '';
            document.getElementById('purchaseStatus').value = purchase.status || 'planned';
            document.getElementById('purchaseNotes').value = purchase.notes || '';

            document.getElementById('purchaseModal').style.display = 'flex';
        } else {
            showToast('加载采购记录失败', 'error');
        }
    } catch (error) {
        console.error('加载采购记录失败:', error);
        showToast('网络错误', 'error');
    }
}

function closePurchaseModal() {
    document.getElementById('purchaseModal').style.display = 'none';
}

async function savePurchase() {
    const id = document.getElementById('purchaseId').value;
    const data = {
        id: parseInt(id),
        quantity: document.getElementById('purchaseQuantity').value || 1,
        unit: document.getElementById('purchaseUnit').value || 'pcs',
        unit_price: document.getElementById('purchaseUnitPrice').value || 0,
        currency: document.getElementById('purchaseCurrency').value || 'EUR',
        fx_rate_to_eur: document.getElementById('purchaseFxRate').value || null,
        status: document.getElementById('purchaseStatus').value || 'planned',
        notes: document.getElementById('purchaseNotes').value || null
    };

    try {
        const response = await fetch('/ors/api/purchases.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('采购记录已更新', 'success');
            closePurchaseModal();
            loadPurchases();
        } else {
            showToast(result.message || '保存失败', 'error');
        }
    } catch (error) {
        console.error('保存失败:', error);
        showToast('网络错误', 'error');
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
}
</script>
