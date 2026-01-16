<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS - 今日记录</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <a href="/ors/" class="back-btn">&larr;</a>
            <h1>今日记录</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="tab-bar">
            <button class="tab-btn active" data-tab="tasks">任务</button>
            <button class="tab-btn" data-tab="purchases">采购</button>
        </div>

        <div id="tasksTab" class="tab-content active">
            <div id="tasksList" class="records-list">
                <div class="loading">加载中...</div>
            </div>
        </div>

        <div id="purchasesTab" class="tab-content">
            <div id="purchasesList" class="records-list">
                <div class="loading">加载中...</div>
            </div>
        </div>
    </main>

    <div class="fab-container">
        <a href="/ors/?action=quickTask" class="fab fab-task" title="快速任务">+任</a>
        <a href="/ors/?action=quickPurchase" class="fab fab-purchase" title="快速采购">+购</a>
    </div>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
    <script>
        const statusNames = {
            'todo': '待办',
            'doing': '进行中',
            'blocked': '阻塞',
            'done': '已完成'
        };

        // 标签页切换
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                this.classList.add('active');
                document.getElementById(this.dataset.tab + 'Tab').classList.add('active');
            });
        });

        // 加载数据
        loadTodayTasks();
        loadTodayPurchases();

        async function loadTodayTasks() {
            const container = document.getElementById('tasksList');
            try {
                const response = await fetch('/ors/api/tasks.php?action=today');
                const result = await response.json();

                if (result.success && result.data.tasks) {
                    if (result.data.tasks.length === 0) {
                        container.innerHTML = '<div class="empty-state">今日暂无任务</div>';
                        return;
                    }

                    container.innerHTML = result.data.tasks.map(task => `
                        <div class="record-card">
                            <div class="record-header">
                                <span class="status-badge status-${task.status}">${statusNames[task.status] || task.status}</span>
                                <span class="record-time">${formatTime(task.created_at)}</span>
                            </div>
                            <div class="record-title">${escapeHtml(task.title)}</div>
                            ${task.project_name ? `<div class="record-project">${escapeHtml(task.project_name)}</div>` : ''}
                            ${task.description ? `<div class="record-desc">${escapeHtml(task.description)}</div>` : ''}
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="error-state">加载失败</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="error-state">网络错误</div>';
            }
        }

        async function loadTodayPurchases() {
            const container = document.getElementById('purchasesList');
            try {
                const response = await fetch('/ors/api/purchases.php?action=today');
                const result = await response.json();

                if (result.success && result.data.purchases) {
                    if (result.data.purchases.length === 0) {
                        container.innerHTML = '<div class="empty-state">今日暂无采购</div>';
                        return;
                    }

                    container.innerHTML = result.data.purchases.map(purchase => `
                        <div class="record-card">
                            <div class="record-header">
                                <span class="currency-badge">${purchase.currency}</span>
                                <span class="record-price">${formatPrice(purchase.unit_price, purchase.currency)}</span>
                            </div>
                            <div class="record-title">${escapeHtml(purchase.free_text_item || purchase.linked_item_name || '未命名物品')}</div>
                            ${purchase.project_name ? `<div class="record-project">${escapeHtml(purchase.project_name)}</div>` : ''}
                            <div class="record-meta">
                                <span>数量: ${purchase.quantity}</span>
                                ${purchase.total_price_eur ? `<span>折合: ${formatPrice(purchase.total_price_eur, 'EUR')}</span>` : ''}
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="error-state">加载失败</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="error-state">网络错误</div>';
            }
        }

        function formatTime(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' });
        }

        function formatPrice(amount, currency) {
            return new Intl.NumberFormat('zh-CN', {
                style: 'decimal',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount) + ' ' + currency;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
