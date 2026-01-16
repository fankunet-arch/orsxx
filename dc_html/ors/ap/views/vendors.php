<div class="page-header">
    <h2>供应商黄页</h2>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddVendorModal()">+ 新增供应商</button>
    </div>
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="vendorsTable">
        <thead>
            <tr>
                <th>名称</th>
                <th>分类</th>
                <th>联系人</th>
                <th>电话</th>
                <th>评分</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="vendorsBody">
            <tr><td colspan="6" class="loading">加载中...</td></tr>
        </tbody>
    </table>
</div>

<!-- 新增/编辑供应商弹窗 -->
<div id="vendorModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="vendorModalTitle">新增供应商</h3>
            <button class="modal-close" onclick="closeVendorModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="vendorId">
            <div class="form-group">
                <label>供应商名称 *</label>
                <input type="text" id="vendorName" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>分类</label>
                    <select id="vendorCategory">
                        <option value="">-- 请选择 --</option>
                        <option value="it">IT/电子</option>
                        <option value="furniture">家具</option>
                        <option value="decoration">装修</option>
                        <option value="food">食材</option>
                        <option value="service">服务</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>联系人</label>
                    <input type="text" id="vendorContact">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>电话</label>
                    <input type="tel" id="vendorPhone">
                </div>
                <div class="form-group">
                    <label>邮箱</label>
                    <input type="email" id="vendorEmail">
                </div>
            </div>
            <div class="form-group">
                <label>地址</label>
                <textarea id="vendorAddress" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>评分（1-5分）</label>
                    <select id="vendorRating">
                        <option value="">-- 请选择 --</option>
                        <option value="5">5 - 非常好</option>
                        <option value="4">4 - 好</option>
                        <option value="3">3 - 一般</option>
                        <option value="2">2 - 差</option>
                        <option value="1">1 - 非常差</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>评价备注</label>
                    <input type="text" id="vendorRatingComment" placeholder="一句话评价">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeVendorModal()">取消</button>
            <button class="btn btn-primary" onclick="saveVendor()">保存</button>
        </div>
    </div>
</div>

<script>
const vendorCategories = {
    'it': 'IT/电子',
    'furniture': '家具',
    'decoration': '装修',
    'food': '食材',
    'service': '服务',
    'other': '其他'
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
                        <button class="btn btn-xs" onclick="editVendor(${v.id})">编辑</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">暂无供应商</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" class="error-state">加载失败</td></tr>';
    }
}

function renderRating(rating) {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
}

function showAddVendorModal() {
    document.getElementById('vendorModalTitle').textContent = '新增供应商';
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
        showToast('供应商名称不能为空', 'error');
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
            showToast('供应商已保存！', 'success');
            closeVendorModal();
            loadVendors();
        } else {
            showToast(result.message || '保存失败', 'error');
        }
    } catch (error) {
        showToast('网络错误', 'error');
    }
}

function editVendor(id) {
    showToast('编辑功能 - 待实现', 'info');
}
</script>
