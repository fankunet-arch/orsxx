<div class="page-header">
    <h2>Vendor Directory</h2>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddVendorModal()">+ Add Vendor</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="vendorsTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>Rating</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="vendorsBody">
            <tr><td colspan="6" class="loading">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- Add/Edit Vendor Modal -->
<div id="vendorModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="vendorModalTitle">Add Vendor</h3>
            <button class="modal-close" onclick="closeVendorModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="vendorId">
            <div class="form-group">
                <label>Vendor Name *</label>
                <input type="text" id="vendorName" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select id="vendorCategory">
                        <option value="">-- Select --</option>
                        <option value="it">IT/Electronics</option>
                        <option value="furniture">Furniture</option>
                        <option value="decoration">Decoration</option>
                        <option value="food">Food Supply</option>
                        <option value="service">Service</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" id="vendorContact">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" id="vendorPhone">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="vendorEmail">
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea id="vendorAddress" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Rating (1-5)</label>
                    <select id="vendorRating">
                        <option value="">-- Select --</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Very Poor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rating Comment</label>
                    <input type="text" id="vendorRatingComment" placeholder="One-line comment">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeVendorModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveVendor()">Save</button>
        </div>
    </div>
</div>

<script>
const vendorCategories = {
    'it': 'IT/Electronics',
    'furniture': 'Furniture',
    'decoration': 'Decoration',
    'food': 'Food Supply',
    'service': 'Service',
    'other': 'Other'
};

document.addEventListener('DOMContentLoaded', function() {
    loadVendors();
});

async function loadVendors() {
    const tbody = document.getElementById('vendorsBody');

    try {
        const response = await fetch('/ors/api/vendors.php?action=list');
        const result = await response.json();

        if (result.success && result.data.vendors.length > 0) {
            tbody.innerHTML = result.data.vendors.map(v => `
                <tr>
                    <td>${escapeHtml(v.vendor_name)}</td>
                    <td>${vendorCategories[v.category] || '-'}</td>
                    <td>${escapeHtml(v.contact_person || '-')}</td>
                    <td>${escapeHtml(v.phone || '-')}</td>
                    <td>${v.rating ? renderRating(v.rating) : '-'}</td>
                    <td>
                        <button class="btn btn-xs" onclick="editVendor(${v.id})">Edit</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No vendors found</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" class="error-state">Failed to load vendors</td></tr>';
    }
}

function renderRating(rating) {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
}

function showAddVendorModal() {
    document.getElementById('vendorModalTitle').textContent = 'Add Vendor';
    document.getElementById('vendorId').value = '';
    document.getElementById('vendorName').value = '';
    document.getElementById('vendorCategory').value = '';
    document.getElementById('vendorContact').value = '';
    document.getElementById('vendorPhone').value = '';
    document.getElementById('vendorEmail').value = '';
    document.getElementById('vendorAddress').value = '';
    document.getElementById('vendorRating').value = '';
    document.getElementById('vendorRatingComment').value = '';
    document.getElementById('vendorModal').style.display = 'flex';
}

function closeVendorModal() {
    document.getElementById('vendorModal').style.display = 'none';
}

async function saveVendor() {
    const id = document.getElementById('vendorId').value;
    const data = {
        vendor_name: document.getElementById('vendorName').value,
        category: document.getElementById('vendorCategory').value,
        contact_person: document.getElementById('vendorContact').value,
        phone: document.getElementById('vendorPhone').value,
        email: document.getElementById('vendorEmail').value,
        address: document.getElementById('vendorAddress').value,
        rating: document.getElementById('vendorRating').value || null,
        rating_comment: document.getElementById('vendorRatingComment').value
    };

    if (!data.vendor_name) {
        showToast('Vendor name is required', 'error');
        return;
    }

    const action = id ? 'update' : 'create';
    if (id) data.id = id;

    try {
        const response = await fetch('/ors/api/vendors.php?action=' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            showToast('Vendor saved!', 'success');
            closeVendorModal();
            loadVendors();
        } else {
            showToast(result.message || 'Failed to save', 'error');
        }
    } catch (error) {
        showToast('Network error', 'error');
    }
}

function editVendor(id) {
    showToast('Edit feature - load vendor and show modal', 'info');
}
</script>
