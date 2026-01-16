<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS Capture - Home</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <h1>ORS <span class="badge">Capture</span></h1>
            <div class="user-info">
                <span><?php echo ors_e($user['display_name'] ?? $user['username']); ?></span>
                <a href="/ors/?action=logout" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="quick-actions">
            <a href="/ors/?action=quickTask" class="action-card action-task">
                <div class="action-icon">+</div>
                <div class="action-title">Quick Task</div>
                <div class="action-desc">Record a task in seconds</div>
            </a>

            <a href="/ors/?action=quickPurchase" class="action-card action-purchase">
                <div class="action-icon">$</div>
                <div class="action-title">Quick Purchase</div>
                <div class="action-desc">Log purchase quickly</div>
            </a>

            <a href="/ors/?action=today" class="action-card action-today">
                <div class="action-icon">T</div>
                <div class="action-title">Today's Records</div>
                <div class="action-desc">View today's entries</div>
            </a>

            <a href="/ors/?action=search" class="action-card action-search">
                <div class="action-icon">S</div>
                <div class="action-title">Search</div>
                <div class="action-desc">Find tasks & purchases</div>
            </a>
        </div>
    </main>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
</body>
</html>
