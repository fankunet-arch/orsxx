<?php $user = ors_current_user(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORS Control Panel</title>
    <link rel="stylesheet" href="/ors/ap/css/admin.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h1>ORS</h1>
                <span class="badge badge-primary">Control</span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item <?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=dashboard">Dashboard</a>
                </li>
                <li class="nav-section">Tasks</li>
                <li class="nav-item <?php echo ($action ?? '') === 'tasks' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=tasks">Task List</a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'kanban' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=kanban">Kanban Board</a>
                </li>
                <li class="nav-section">Procurement</li>
                <li class="nav-item <?php echo ($action ?? '') === 'purchases' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=purchases">Purchases</a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'items' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=items">Item Library</a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'vendors' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=vendors">Vendors</a>
                </li>
                <li class="nav-section">Templates</li>
                <li class="nav-item <?php echo ($action ?? '') === 'templates' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=templates">Template Library</a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'lessons' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=lessons">Lessons Learned</a>
                </li>
                <li class="nav-section">Management</li>
                <li class="nav-item <?php echo ($action ?? '') === 'projects' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=projects">Projects</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <div class="main-wrapper">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">&#9776;</button>
                </div>
                <div class="header-right">
                    <span class="user-name"><?php echo ors_e($user['display_name'] ?? $user['username']); ?></span>
                    <a href="/ors/ap/?action=logout" class="btn btn-sm btn-outline">Logout</a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="main-content">
