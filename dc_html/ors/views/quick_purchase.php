<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS - Quick Purchase</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <a href="/ors/" class="back-btn">&larr;</a>
            <h1>Quick Purchase</h1>
        </div>
    </header>

    <main class="main-content form-page">
        <form id="quickPurchaseForm" class="quick-form">
            <?php echo ors_csrf_field(); ?>

            <div class="form-group">
                <label for="free_text_item">Item Name *</label>
                <input type="text" id="free_text_item" name="free_text_item" required
                       placeholder="e.g. Receipt printer"
                       autofocus autocomplete="off">
            </div>

            <div class="form-row">
                <div class="form-group flex-2">
                    <label for="unit_price">Unit Price *</label>
                    <input type="number" id="unit_price" name="unit_price" required
                           step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="form-group flex-1">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency">
                        <option value="EUR" selected>EUR</option>
                        <option value="CNY">CNY</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group flex-1">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity"
                           value="1" min="1" step="1">
                </div>

                <div class="form-group flex-2">
                    <label for="fx_rate_to_eur">FX Rate to EUR</label>
                    <input type="number" id="fx_rate_to_eur" name="fx_rate_to_eur"
                           step="0.000001" placeholder="Auto">
                </div>
            </div>

            <div class="form-group">
                <label for="project_id">Project (optional)</label>
                <select id="project_id" name="project_id">
                    <option value="">-- No Project --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="2"
                          placeholder="Additional notes..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block btn-large">
                    Save Purchase
                </button>
            </div>
        </form>
    </main>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
    <script>
        // Load projects
        loadProjects();

        // Set default FX rates based on currency
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
                fxInput.placeholder = defaultRates[this.value] || 'Enter rate';
            }
        });

        // Initialize
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
                console.error('Failed to load projects:', error);
            }
        }

        document.getElementById('quickPurchaseForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

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

            // Add FX rate if not EUR
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
                    showToast('Purchase saved!', 'success');
                    // Clear form for next entry
                    document.getElementById('free_text_item').value = '';
                    document.getElementById('unit_price').value = '';
                    document.getElementById('quantity').value = '1';
                    document.getElementById('notes').value = '';
                    document.getElementById('free_text_item').focus();
                } else {
                    showToast(result.message || 'Save failed', 'error');
                }
            } catch (error) {
                showToast('Network error', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Purchase';
            }
        });
    </script>
</body>
</html>
