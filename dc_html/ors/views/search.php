<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS - Search</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <a href="/ors/" class="back-btn">&larr;</a>
            <h1>Search</h1>
        </div>
    </header>

    <main class="main-content">
        <div class="search-box">
            <input type="search" id="searchInput" placeholder="Search tasks & purchases..."
                   autocomplete="off" autofocus>
        </div>

        <div class="tab-bar">
            <button class="tab-btn active" data-tab="tasks">Tasks</button>
            <button class="tab-btn" data-tab="purchases">Purchases</button>
        </div>

        <div id="tasksTab" class="tab-content active">
            <div id="taskResults" class="records-list">
                <div class="empty-state">Enter a keyword to search</div>
            </div>
        </div>

        <div id="purchasesTab" class="tab-content">
            <div id="purchaseResults" class="records-list">
                <div class="empty-state">Enter a keyword to search</div>
            </div>
        </div>
    </main>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
    <script>
        let searchTimeout;

        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                this.classList.add('active');
                document.getElementById(this.dataset.tab + 'Tab').classList.add('active');
            });
        });

        // Search input
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                document.getElementById('taskResults').innerHTML = '<div class="empty-state">Enter at least 2 characters</div>';
                document.getElementById('purchaseResults').innerHTML = '<div class="empty-state">Enter at least 2 characters</div>';
                return;
            }

            document.getElementById('taskResults').innerHTML = '<div class="loading">Searching...</div>';
            document.getElementById('purchaseResults').innerHTML = '<div class="loading">Searching...</div>';

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
                        container.innerHTML = '<div class="empty-state">No tasks found</div>';
                        return;
                    }

                    container.innerHTML = result.data.tasks.map(task => `
                        <div class="record-card">
                            <div class="record-header">
                                <span class="status-badge status-${task.status}">${task.status}</span>
                                <span class="record-date">${formatDate(task.created_at)}</span>
                            </div>
                            <div class="record-title">${escapeHtml(task.title)}</div>
                            ${task.project_name ? `<div class="record-project">${escapeHtml(task.project_name)}</div>` : ''}
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="error-state">Search failed</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="error-state">Network error</div>';
            }
        }

        async function searchPurchases(query) {
            const container = document.getElementById('purchaseResults');
            try {
                const response = await fetch('/ors/api/purchases.php?action=search&q=' + encodeURIComponent(query));
                const result = await response.json();

                if (result.success && result.data.purchases) {
                    if (result.data.purchases.length === 0) {
                        container.innerHTML = '<div class="empty-state">No purchases found</div>';
                        return;
                    }

                    container.innerHTML = result.data.purchases.map(purchase => `
                        <div class="record-card">
                            <div class="record-header">
                                <span class="currency-badge">${purchase.currency}</span>
                                <span class="record-price">${formatPrice(purchase.unit_price, purchase.currency)}</span>
                            </div>
                            <div class="record-title">${escapeHtml(purchase.free_text_item || purchase.linked_item_name || 'Unknown')}</div>
                            ${purchase.project_name ? `<div class="record-project">${escapeHtml(purchase.project_name)}</div>` : ''}
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="error-state">Search failed</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="error-state">Network error</div>';
            }
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function formatPrice(amount, currency) {
            return new Intl.NumberFormat('en-US', {
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
