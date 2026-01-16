<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS - 快速采购</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <a href="/ors/" class="back-btn">&larr;</a>
            <h1>快速采购</h1>
        </div>
    </header>

    <main class="main-content form-page">
        <form id="quickPurchaseForm" class="quick-form">
            <?php echo ors_csrf_field(); ?>

            <div class="form-group">
                <label for="free_text_item">物品名称 *</label>
                <input type="text" id="free_text_item" name="free_text_item" required
                       placeholder="例如：小票打印机"
                       autofocus autocomplete="off">
            </div>

            <div class="form-row">
                <div class="form-group flex-2">
                    <label for="unit_price">单价 *</label>
                    <input type="number" id="unit_price" name="unit_price" required
                           step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="form-group flex-1">
                    <label for="currency">币种</label>
                    <select id="currency" name="currency">
                        <option value="EUR" selected>EUR</option>
                        <option value="CNY">CNY</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group flex-1">
                    <label for="quantity">数量</label>
                    <input type="number" id="quantity" name="quantity"
                           value="1" min="1" step="1">
                </div>

                <div class="form-group flex-2">
                    <label for="fx_rate_to_eur">汇率（转EUR）</label>
                    <input type="number" id="fx_rate_to_eur" name="fx_rate_to_eur"
                           step="0.000001" placeholder="自动">
                </div>
            </div>

            <div class="form-group">
                <label for="project_id">所属项目（可选）</label>
                <select id="project_id" name="project_id">
                    <option value="">-- 不选择项目 --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="notes">备注（可选）</label>
                <textarea id="notes" name="notes" rows="2"
                          placeholder="补充说明..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block btn-large">
                    保存采购
                </button>
            </div>
        </form>
    </main>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
    <script>
        // 加载项目列表
        loadProjects();

        // 币种默认汇率
        const defaultRates = {
            'EUR': null,
            'CNY': <?php echo ORS_DEFAULT_FX_CNY_EUR; ?>,
            'USD': <?php echo ORS_DEFAULT_FX_USD_EUR; ?>
        };

        document.getElementById('currency').addEventListener('change', function() {
            const fxInput = document.getElementById('fx_rate_to_eur');
            if (this.value === 'EUR') {
                fxInput.value = '';
                fxInput.disabled = true;
            } else {
                fxInput.disabled = false;
                fxInput.placeholder = defaultRates[this.value] || '输入汇率';
            }
        });

        // 初始化
        document.getElementById('fx_rate_to_eur').disabled = true;

        async function loadProjects() {
            try {
                const response = await fetch('/ors/api/projects.php?action=list');
                const result = await response.json();
                if (result.success && result.data.projects) {
                    const select = document.getElementById('project_id');
                    result.data.projects.forEach(project => {
                        const option = document.createElement('option');
                        option.value = project.id;
                        option.textContent = project.project_name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('加载项目失败:', error);
            }
        }

        document.getElementById('quickPurchaseForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '保存中...';

            const formData = new FormData(this);
            const currency = formData.get('currency');

            const data = {
                free_text_item: formData.get('free_text_item'),
                unit_price: parseFloat(formData.get('unit_price')) || 0,
                currency: currency,
                quantity: parseInt(formData.get('quantity')) || 1,
                project_id: formData.get('project_id') || null,
                notes: formData.get('notes') || null
            };

            // 非EUR时添加汇率
            if (currency !== 'EUR') {
                const fxRate = formData.get('fx_rate_to_eur');
                data.fx_rate_to_eur = fxRate ? parseFloat(fxRate) : defaultRates[currency];
            }

            try {
                const response = await fetch('/ors/api/purchases.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('采购已保存！', 'success');
                    // 清空表单以便继续录入
                    document.getElementById('free_text_item').value = '';
                    document.getElementById('unit_price').value = '';
                    document.getElementById('quantity').value = '1';
                    document.getElementById('notes').value = '';
                    document.getElementById('free_text_item').focus();
                } else {
                    showToast(result.message || '保存失败', 'error');
                }
            } catch (error) {
                showToast('网络错误', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '保存采购';
            }
        });
    </script>
</body>
</html>
