<div class="page-header">
    <h2>Purchases</h2>
    <div class="page-actions">
        <select id="projectFilter" onchange="loadPurchases()">
            <option value="">All Projects</option>
        </select>
    </div>
</div>

<div class="summary-bar">
    <div class="summary-item">
        <span class="summary-label">Total EUR:</span>
        <span class="summary-value" id="totalEur">0.00</span>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="purchasesTable">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Currency</th>
                <th>FX Rate</th>
                <th>Total EUR</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="purchasesBody">
            <tr><td colspan="8" class="loading">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- Normalize Modal -->
<div id="normalizeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Normalize to Item Library</h3>
            <button class="modal-close" onclick="closeNormalizeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="normalizePurchaseId">
            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" id="normalizeItemName" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="normalizeCategory">
                    <option value="">-- Select --</option>
                    <option value="it_devices">IT Devices</option>
                    <option value="furniture">Furniture</option>
                    <option value="equipment">Equipment</option>
                    <option value="consumables">Consumables</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Unit</label>
                <input type="text" id="normalizeUnit" value="pcs">
            </div>
            <div class="form-group">
                <label>Must Buy Level</label>
                <select id="normalizeMustBuy">
                    <option value="must">Must</option>
                    <option value="recommended" selected>Recommended</option>
                    <option value="optional">Optional</option>
                </select>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="normalizeTemplate" checked>
                    <span>Add to Template Library</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeNormalizeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="executeNormalize()">Create Item</button>
        </div>
    </div>
</div>

<script>
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
        console.error('Failed to load projects:', error);
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
                const itemName = p.linked_item_name || p.free_text_item || 'Unknown';
                const isNormalized = !!p.item_id;
                return `
                <tr>
                    <td>
                        ${escapeHtml(itemName)}
                        ${!isNormalized ? `<button class="btn btn-xs btn-outline ml-2" onclick="showNormalizeModal(${p.id}, '${escapeHtml(p.free_text_item || '')}')">Normalize</button>` : ''}
                    </td>
                    <td>${p.quantity}</td>
                    <td>${formatNumber(p.unit_price)}</td>
                    <td><span class="currency-badge">${p.currency}</span></td>
                    <td>${p.fx_rate_to_eur || '-'}</td>
                    <td><strong>${formatNumber(p.total_price_eur)}</strong></td>
                    <td><span class="status-badge status-${p.status}">${p.status}</span></td>
                    <td>
                        <button class="btn btn-xs" onclick="editPurchase(${p.id})">Edit</button>
                    </td>
                </tr>
                `;
            }).join('');
            document.getElementById('totalEur').textContent = formatNumber(totalEur) + ' EUR';
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No purchases found</td></tr>';
            document.getElementById('totalEur').textContent = '0.00 EUR';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="8" class="error-state">Failed to load purchases</td></tr>';
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
        showToast('Item name is required', 'error');
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
            showToast('Item created and linked!', 'success');
            closeNormalizeModal();
            loadPurchases();
        } else {
            showToast(result.message || 'Failed to normalize', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

function editPurchase(id) {
    showToast('Edit feature - implement as needed', 'info');
}

function formatNumber(num) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
}
</script>
