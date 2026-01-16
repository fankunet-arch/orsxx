<div class="page-header">
    <h2>Item Library</h2>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddItemModal()">+ Add Item</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="itemsTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Unit</th>
                <th>Must Buy</th>
                <th>Long Lead</th>
                <th>Lead Time</th>
                <th>Template</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr><td colspan="8" class="loading">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- Add/Edit Item Modal -->
<div id="itemModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="itemModalTitle">Add Item</h3>
            <button class="modal-close" onclick="closeItemModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="itemId">
            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" id="itemName" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select id="itemCategory">
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
                    <input type="text" id="itemUnit" value="pcs">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Must Buy Level</label>
                    <select id="itemMustBuy">
                        <option value="must">Must</option>
                        <option value="recommended" selected>Recommended</option>
                        <option value="optional">Optional</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lead Time (days)</label>
                    <input type="number" id="itemLeadTime" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="itemLongLead">
                    <span>Long Lead Item</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="itemTemplate" checked>
                    <span>Add to Template Library</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeItemModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveItem()">Save</button>
        </div>
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
                    <td>${item.must_buy_level || '-'}</td>
                    <td>${item.long_lead_flag ? 'Yes' : '-'}</td>
                    <td>${item.lead_time_days || '-'} days</td>
                    <td>${item.template_flag ? '<span class="badge badge-success">Yes</span>' : '-'}</td>
                    <td>
                        <button class="btn btn-xs" onclick="editItem(${item.id})">Edit</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No items found</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="8" class="error-state">Failed to load items</td></tr>';
    }
}

function showAddItemModal() {
    document.getElementById('itemModalTitle').textContent = 'Add Item';
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
        showToast('Item name is required', 'error');
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
            showToast('Item saved!', 'success');
            closeItemModal();
            loadItems();
        } else {
            showToast(result.message || 'Failed to save', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

function editItem(id) {
    showToast('Edit feature - load item and show modal', 'info');
}
</script>
