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
    <h2>项目仪表板</h2>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value" id="statProjects">-</div>
        <div class="stat-label">进行中项目</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="statTasks">-</div>
        <div class="stat-label">任务总数</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="statPurchases">-</div>
        <div class="stat-label">采购记录</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="statCost">-</div>
        <div class="stat-label">总成本 (EUR)</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="card-header">
            <h3>最近任务</h3>
            <a href="/ors/ap/?action=tasks" class="btn btn-sm btn-outline">查看全部</a>
        </div>
        <div class="card-body" id="recentTasks">
            <div class="loading">加载中...</div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h3>最近采购</h3>
            <a href="/ors/ap/?action=purchases" class="btn btn-sm btn-outline">查看全部</a>
        </div>
        <div class="card-body" id="recentPurchases">
            <div class="loading">加载中...</div>
        </div>
    </div>
</div>

<script>
const statusNames = {
    'todo': '待办',
    'doing': '进行中',
    'blocked': '阻塞',
    'done': '已完成'
};

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
        console.error('加载统计数据失败:', error);
    }

    try {
        const response = await fetch('/ors/api/purchases.php?action=summary');
        const result = await response.json();
        if (result.success) {
            document.getElementById('statCost').textContent = formatNumber(result.data.total_eur || 0);
        }
    } catch (error) {
        console.error('加载成本数据失败:', error);
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
                    <span class="status-badge status-${task.status}">${statusNames[task.status] || task.status}</span>
                    <span class="item-title">${escapeHtml(task.title)}</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state">暂无最近任务</div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="error-state">加载失败</div>';
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
                    <span class="item-title">${escapeHtml(p.free_text_item || p.linked_item_name || '未命名')}</span>
                    <span class="item-price">${formatNumber(p.total_price_eur)} EUR</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state">暂无最近采购</div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="error-state">加载失败</div>';
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}
</script>
