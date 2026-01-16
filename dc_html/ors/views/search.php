<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS - 搜索</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <a href="/ors/" class="back-btn">&larr;</a>
            <h1>搜索</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="search-box">
            <input type="search" id="searchInput" placeholder="搜索任务和采购..."
                   autocomplete="off" autofocus>
        </div>

        <div class="tab-bar">
            <button class="tab-btn active" data-tab="tasks">任务</button>
            <button class="tab-btn" data-tab="purchases">采购</button>
        </div>

        <div id="tasksTab" class="tab-content active">
            <div id="taskResults" class="records-list">
                <div class="empty-state">输入关键词开始搜索</div>
            </div>
        </div>

        <div id="purchasesTab" class="tab-content">
            <div id="purchaseResults" class="records-list">
                <div class="empty-state">输入关键词开始搜索</div>
            </div>
        </div>
    </main>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
    <script>
        let searchTimeout;

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

        // 搜索输入
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                document.getElementById('taskResults').innerHTML = '<div class="empty-state">请输入至少2个字符</div>';
                document.getElementById('purchaseResults').innerHTML = '<div class="empty-state">请输入至少2个字符</div>';
                return;
            }

            document.getElementById('taskResults').innerHTML = '<div class="loading">搜索中...</div>';
            document.getElementById('purchaseResults').innerHTML = '<div class="loading">搜索中...</div>';

            searchTimeout = setTimeout(() => {
                searchTasks(query);
                searchPurchases(query);
            }, 300);
        });

        async function searchTasks(query) {
            const container = document.getElementById('taskResults');
            try {
                const response = await fetch('/ors/api/tasks.php?action=search&q=' + encodeURIComponent(query));
                const result = await response.json();

                if (result.success && result.data.tasks) {
                    if (result.data.tasks.length === 0) {
                        container.innerHTML = '<div class="empty-state">未找到相关任务</div>';
                        return;
                    }

                    container.innerHTML = result.data.tasks.map(task => `
                        <div class="record-card">
                            <div class="record-header">
                                <span class="status-badge status-${task.status}">${statusNames[task.status] || task.status}</span>
                                <span class="record-date">${formatDate(task.created_at)}</span>
                            </div>
                            <div class="record-title">${escapeHtml(task.title)}</div>
                            ${task.project_name ? `<div class="record-project">${escapeHtml(task.project_name)}</div>` : ''}
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="error-state">搜索失败</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="error-state">网络错误</div>';
            }
        }

        async function searchPurchases(query) {
            const container = document.getElementById('purchaseResults');
            try {
                const response = await fetch('/ors/api/purchases.php?action=search&q=' + encodeURIComponent(query));
                const result = await response.json();

                if (result.success && result.data.purchases) {
                    if (result.data.purchases.length === 0) {
                        container.innerHTML = '<div class="empty-state">未找到相关采购</div>';
                        return;
                    }

                    container.innerHTML = result.data.purchases.map(purchase => `
                        <div class="record-card">
                            <div class="record-header">
                                <span class="currency-badge">${purchase.currency}</span>
                                <span class="record-price">${formatPrice(purchase.unit_price, purchase.currency)}</span>
                            </div>
                            <div class="record-title">${escapeHtml(purchase.free_text_item || purchase.linked_item_name || '未命名')}</div>
                            ${purchase.project_name ? `<div class="record-project">${escapeHtml(purchase.project_name)}</div>` : ''}
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="error-state">搜索失败</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="error-state">网络错误</div>';
            }
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('zh-CN', { month: 'short', day: 'numeric' });
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
