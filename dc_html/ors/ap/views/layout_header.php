<?php $user = ors_current_user(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORS 控制室</title>
    <link rel="stylesheet" href="/ors/ap/css/admin.css">
</head>
<body>
    <div class="app-container">
        <!-- 侧边导航 -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h1>ORS</h1>
                <span class="badge badge-primary">控制室</span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item <?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=dashboard">仪表板</a>
                </li>
                <li class="nav-section">任务管理</li>
                <li class="nav-item <?php echo ($action ?? '') === 'tasks' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=tasks">任务列表</a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'kanban' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=kanban">看板视图</a>
                </li>
                <li class="nav-section">采购管理</li>
                <li class="nav-item <?php echo ($action ?? '') === 'purchases' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=purchases">采购列表</a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'items' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=items">物品库</a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'vendors' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=vendors">供应商</a>
                </li>
                <li class="nav-section">模板中心</li>
                <li class="nav-item <?php echo ($action ?? '') === 'templates' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=templates">模板库</a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'lessons' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=lessons">踩坑记录</a>
                </li>
                <li class="nav-section">项目管理</li>
                <li class="nav-item <?php echo ($action ?? '') === 'projects' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=projects">项目列表</a>
                </li>
            </ul>
        </nav>

        <!-- 主内容区域 -->
        <div class="main-wrapper">
            <!-- 顶部栏 -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">&#9776;</button>
                </div>
                <div class="header-right">
                    <span class="user-name"><?php echo ors_e($user['display_name'] ?? $user['username']); ?></span>
                    <a href="/ors/ap/?action=logout" class="btn btn-sm btn-outline">退出</a>
                </div>
            </header>

            <!-- 页面内容 -->
            <main class="main-content">
