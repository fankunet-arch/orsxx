<?php
$user = ors_current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS 现场记录</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body>
    <header class="app-header">
        <div class="header-content">
            <h1>ORS <span class="badge">现场</span></h1>
            <div class="user-info">
                <span><?php echo ors_e($user['display_name'] ?? $user['username']); ?></span>
                <a href="/ors/?action=logout" class="logout-btn">退出</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="quick-actions">
            <a href="/ors/?action=quickTask" class="action-card action-task">
                <div class="action-icon">+</div>
                <div class="action-title">快速任务</div>
                <div class="action-desc">秒级记录待办事项</div>
            </a>

            <a href="/ors/?action=quickPurchase" class="action-card action-purchase">
                <div class="action-icon">¥</div>
                <div class="action-title">快速采购</div>
                <div class="action-desc">快速登记采购信息</div>
            </a>

            <a href="/ors/?action=today" class="action-card action-today">
                <div class="action-icon">今</div>
                <div class="action-title">今日记录</div>
                <div class="action-desc">查看今天的记录</div>
            </a>

            <a href="/ors/?action=search" class="action-card action-search">
                <div class="action-icon">搜</div>
                <div class="action-title">搜索</div>
                <div class="action-desc">查找任务和采购</div>
            </a>
        </div>
    </main>

    <div id="toast" class="toast"></div>

    <script src="/ors/js/mobile.js"></script>
</body>
</html>
