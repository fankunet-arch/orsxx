<?php
$user = ors_current_user();
// 获取当前选中的项目ID
$currentProjectId = $_GET['project_id'] ?? $_SESSION['ors_current_project_id'] ?? null;
if (isset($_GET['project_id'])) {
    $_SESSION['ors_current_project_id'] = $_GET['project_id'];
}
?>
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
                <!-- 项目执行区块 - 主要工作区 -->
                <li class="nav-category">
                    <span class="nav-category-title">项目执行</span>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'projects' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=projects">
                        <span class="nav-icon">📁</span>
                        项目中心
                    </a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=dashboard">
                        <span class="nav-icon">📊</span>
                        项目仪表板
                    </a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'kanban' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=kanban">
                        <span class="nav-icon">▦</span>
                        项目任务
                    </a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'purchases' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=purchases">
                        <span class="nav-icon">🛒</span>
                        项目采购
                    </a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'checklist' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=checklist">
                        <span class="nav-icon">✓</span>
                        项目检查清单
                    </a>
                </li>

                <!-- 分隔线 -->
                <li class="nav-divider"></li>

                <!-- 经验沉淀区块 - 全局模板 -->
                <li class="nav-category">
                    <span class="nav-category-title">经验沉淀</span>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'tasks' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=tasks">
                        <span class="nav-icon">📋</span>
                        任务模板
                    </a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'items' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=items">
                        <span class="nav-icon">📦</span>
                        物品模板
                    </a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'vendors' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=vendors">
                        <span class="nav-icon">🏪</span>
                        供应商库
                    </a>
                </li>
                <li class="nav-item <?php echo ($action ?? '') === 'lessons' ? 'active' : ''; ?>">
                    <a href="/ors/ap/?action=lessons">
                        <span class="nav-icon">⚠</span>
                        踩坑经验
                    </a>
                </li>
            </ul>
        </nav>

        <!-- 主内容区域 -->
        <div class="main-wrapper">
            <!-- 顶部栏 -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">&#9776;</button>
                    <!-- 项目选择器 -->
                    <div class="project-selector" id="projectSelector">
                        <select id="currentProjectSelect" onchange="switchProject(this.value)">
                            <option value="">-- 选择项目 --</option>
                        </select>
                    </div>
                </div>
                <div class="header-right">
                    <span class="user-name"><?php echo ors_e($user['display_name'] ?? $user['username']); ?></span>
                    <a href="/ors/ap/?action=logout" class="btn btn-sm btn-outline">退出</a>
                </div>
            </header>

            <!-- 页面内容 -->
            <main class="main-content">
                <script>
                // 当前项目ID（从PHP传递）
                var currentProjectId = <?php echo json_encode($currentProjectId); ?>;

                // 加载项目列表到选择器
                async function loadProjectSelector() {
                    try {
                        const result = await api('projects.php?action=list');
                        const select = document.getElementById('currentProjectSelect');
                        if (!select) return;

                        // 只显示进行中的项目 - 注意API返回的是 result.data.projects
                        const projects = result.data?.projects || result.data || [];
                        const activeProjects = projects.filter(p => p.status === 'active' || p.status === 'planning');

                        select.innerHTML = '<option value="">-- 选择项目 --</option>';
                        activeProjects.forEach(project => {
                            const selected = currentProjectId == project.id ? 'selected' : '';
                            // 数据库字段是 project_name，不是 name
                            const projectName = project.project_name || project.name || '未命名项目';
                            select.innerHTML += `<option value="${project.id}" ${selected}>${escapeHtml(projectName)}</option>`;
                        });
                    } catch (error) {
                        console.error('加载项目列表失败:', error);
                    }
                }

                // 切换项目
                function switchProject(projectId) {
                    const url = new URL(window.location.href);
                    if (projectId) {
                        url.searchParams.set('project_id', projectId);
                    } else {
                        url.searchParams.delete('project_id');
                    }
                    window.location.href = url.toString();
                }

                // 页面加载时初始化项目选择器
                document.addEventListener('DOMContentLoaded', loadProjectSelector);
                </script>
