<div class="page-header">
    <h2>Dashboard</h2>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value" id="statProjects">-</div>
        <div class="stat-label">Active Projects</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="statTasks">-</div>
        <div class="stat-label">Total Tasks</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="statPurchases">-</div>
        <div class="stat-label">Total Purchases</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="statCost">-</div>
        <div class="stat-label">Total Cost (EUR)</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Recent Tasks</h3>
            <a href="/ors/ap/?action=tasks" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" id="recentTasks">
            <div class="loading">Loading...</div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h3>Recent Purchases</h3>
            <a href="/ors/ap/?action=purchases" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" id="recentPurchases">
            <div class="loading">Loading...</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadRecentTasks();
    loadRecentPurchases();
});

async function loadDashboardStats() {
    try {
        const response = await fetch('/ors/api/projects.php?action=stats');
        const result = await response.json();
        if (result.success) {
            document.getElementById('statProjects').textContent = result.data.stats.active_projects || 0;
            document.getElementById('statTasks').textContent = result.data.stats.total_tasks || 0;
            document.getElementById('statPurchases').textContent = result.data.stats.total_purchases || 0;
        }
    } catch (error) {
        console.error('Failed to load stats:', error);
    }

    try {
        const response = await fetch('/ors/api/purchases.php?action=summary');
        const result = await response.json();
        if (result.success) {
            document.getElementById('statCost').textContent = formatNumber(result.data.total_eur || 0);
        }
    } catch (error) {
        console.error('Failed to load cost:', error);
    }
}

async function loadRecentTasks() {
    const container = document.getElementById('recentTasks');
    try {
        const response = await fetch('/ors/api/tasks.php?action=today');
        const result = await response.json();
        if (result.success && result.data.tasks.length > 0) {
            container.innerHTML = result.data.tasks.slice(0, 5).map(task => `
                <div class="list-item">
                    <span class="status-badge status-${task.status}">${task.status}</span>
                    <span class="item-title">${escapeHtml(task.title)}</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state">No recent tasks</div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="error-state">Failed to load</div>';
    }
}

async function loadRecentPurchases() {
    const container = document.getElementById('recentPurchases');
    try {
        const response = await fetch('/ors/api/purchases.php?action=today');
        const result = await response.json();
        if (result.success && result.data.purchases.length > 0) {
            container.innerHTML = result.data.purchases.slice(0, 5).map(p => `
                <div class="list-item">
                    <span class="currency-badge">${p.currency}</span>
                    <span class="item-title">${escapeHtml(p.free_text_item || p.linked_item_name || 'Unknown')}</span>
                    <span class="item-price">${formatNumber(p.total_price_eur)} EUR</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state">No recent purchases</div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="error-state">Failed to load</div>';
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}
</script>
