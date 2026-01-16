<?php
/**
 * Tasks API
 * Handles all task-related operations
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use ORS\Auth;
use ORS\Response;
use ORS\Validator;
use ORS\Task;
use ORS\TemplateTag;
use ORS\Database;

// Require authentication
Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleList();
        break;

    case 'get':
        handleGet();
        break;

    case 'today':
        handleToday();
        break;

    case 'create':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleCreate();
        break;

    case 'update':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleUpdate();
        break;

    case 'updateStatus':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleUpdateStatus();
        break;

    case 'delete':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleDelete();
        break;

    case 'templates':
        handleTemplates();
        break;

    case 'bulkUpdate':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleBulkUpdate();
        break;

    case 'kanban':
        handleKanban();
        break;

    case 'search':
        handleSearch();
        break;

    default:
        Response::notFound('Action not found');
}

function handleList(): void
{
    $projectId = $_GET['project_id'] ?? null;
    $tasks = Task::getByProject($projectId ? (int)$projectId : null);
    Response::success(['tasks' => $tasks]);
}

function handleGet(): void
{
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('任务ID不能为空');
    }

    $task = Task::find($id);

    if (!$task) {
        Response::notFound('任务不存在');
    }

    // 获取关联的模板标签
    $tagRecords = TemplateTag::getForEntity('task', $task['id']);
    $task['template_tags'] = implode(', ', array_column($tagRecords, 'tag_name'));

    Response::success(['task' => $task]);
}

function handleToday(): void
{
    $tasks = Task::getToday();
    Response::success(['tasks' => $tasks]);
}

function handleCreate(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('title', 'Title is required')
        ->maxLength('title', 500);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $user = ors_current_user();

    $data = [
        'title' => $validator->getString('title'),
        'description' => $validator->getOrNull('description'),
        'project_id' => $validator->getInt('project_id'),
        'phase_code' => $validator->getOrNull('phase_code'),
        'status' => $validator->get('status', 'todo'),
        'priority' => $validator->get('priority', 'medium'),
        'due_date' => $validator->getOrNull('due_date'),
        'blocking_flag' => $validator->getBool('blocking_flag') ? 1 : 0,
        'lead_time_days' => $validator->getInt('lead_time_days'),
        'template_flag' => $validator->getBool('template_flag') ? 1 : 0,
        'template_source' => $validator->getOrNull('template_source'),
        'created_by' => $user['id']
    ];

    $id = Task::create($data);

    // Handle tags if provided
    $tags = $validator->get('template_tags');
    if ($tags && is_string($tags)) {
        $tags = array_filter(array_map('trim', explode(',', $tags)));
        TemplateTag::setForEntity('task', $id, $tags);
    }

    Response::success(['id' => $id, 'task' => Task::find($id)], 'Task created successfully');
}

function handleUpdate(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('id', 'Task ID is required')
        ->integer('id');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $id = $validator->getInt('id');
    $task = Task::find($id);

    if (!$task) {
        Response::notFound('Task not found');
    }

    $data = [];

    // Only update provided fields
    if (isset($input['title'])) $data['title'] = $validator->getString('title');
    if (isset($input['description'])) $data['description'] = $validator->getOrNull('description');
    if (isset($input['project_id'])) $data['project_id'] = $validator->getInt('project_id');
    if (isset($input['phase_code'])) $data['phase_code'] = $validator->getOrNull('phase_code');
    if (isset($input['status'])) $data['status'] = $validator->get('status');
    if (isset($input['priority'])) $data['priority'] = $validator->get('priority');
    if (isset($input['due_date'])) $data['due_date'] = $validator->getOrNull('due_date');
    if (isset($input['latest_start_date'])) $data['latest_start_date'] = $validator->getOrNull('latest_start_date');
    if (isset($input['blocking_flag'])) $data['blocking_flag'] = $validator->getBool('blocking_flag') ? 1 : 0;
    if (isset($input['lead_time_days'])) $data['lead_time_days'] = $validator->getInt('lead_time_days');
    if (isset($input['template_flag'])) $data['template_flag'] = $validator->getBool('template_flag') ? 1 : 0;
    if (isset($input['template_source'])) $data['template_source'] = $validator->getOrNull('template_source');
    if (isset($input['block_reason'])) $data['block_reason'] = $validator->getOrNull('block_reason');
    if (isset($input['block_reason_detail'])) $data['block_reason_detail'] = $validator->getOrNull('block_reason_detail');

    // Track completion
    if (isset($data['status']) && $data['status'] === 'done' && $task['status'] !== 'done') {
        $data['completed_at'] = date('Y-m-d H:i:s');
    }

    if (!empty($data)) {
        Task::updateById($id, $data);
    }

    // Handle tags if provided
    $tags = $validator->get('template_tags');
    if ($tags !== null) {
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }
        TemplateTag::setForEntity('task', $id, $tags);
    }

    Response::success(['task' => Task::find($id)], 'Task updated successfully');
}

function handleUpdateStatus(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('id', 'Task ID is required')
        ->required('status', 'Status is required')
        ->in('status', ['todo', 'doing', 'blocked', 'done']);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $id = $validator->getInt('id');
    $status = $validator->get('status');

    $task = Task::find($id);
    if (!$task) {
        Response::notFound('Task not found');
    }

    // If setting to blocked, require block_reason
    if ($status === 'blocked') {
        $blockReason = $validator->get('block_reason');
        if (empty($blockReason)) {
            Response::validationError(['block_reason' => 'Block reason is required when status is blocked']);
        }

        $data = [
            'status' => 'blocked',
            'block_reason' => $blockReason,
            'block_reason_detail' => $validator->getOrNull('block_reason_detail')
        ];
    } else {
        $data = ['status' => $status];
        if ($status === 'done' && $task['status'] !== 'done') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        // Clear block reason when not blocked
        if ($status !== 'blocked') {
            $data['block_reason'] = null;
            $data['block_reason_detail'] = null;
        }
    }

    Task::updateById($id, $data);
    Response::success(['task' => Task::find($id)], 'Status updated successfully');
}

function handleDelete(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if (!$id) {
        Response::error('Task ID is required');
    }

    if (!Task::find($id)) {
        Response::notFound('Task not found');
    }

    // Delete associated tags
    TemplateTag::setForEntity('task', $id, []);

    Task::deleteById($id);
    Response::success([], 'Task deleted successfully');
}

function handleTemplates(): void
{
    $tasks = Task::getTemplates();

    // Add tags to each task
    foreach ($tasks as &$task) {
        $tagRecords = TemplateTag::getForEntity('task', $task['id']);
        $task['tags'] = array_column($tagRecords, 'tag_name');
    }

    Response::success(['tasks' => $tasks]);
}

function handleBulkUpdate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if (empty($input['ids']) || !is_array($input['ids'])) {
        Response::error('Task IDs are required');
    }

    $ids = array_map('intval', $input['ids']);
    $updated = 0;
    $failed = 0;
    $errors = [];

    $data = [];
    if (isset($input['phase_code'])) $data['phase_code'] = $input['phase_code'] ?: null;
    if (isset($input['template_flag'])) $data['template_flag'] = filter_var($input['template_flag'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    if (isset($input['template_source'])) $data['template_source'] = $input['template_source'] ?: null;
    if (isset($input['status'])) $data['status'] = $input['status'];
    if (isset($input['priority'])) $data['priority'] = $input['priority'];

    foreach ($ids as $id) {
        try {
            if (!Task::find($id)) {
                $failed++;
                $errors[] = "Task #{$id} not found";
                continue;
            }

            if (!empty($data)) {
                Task::updateById($id, $data);
            }

            // Handle tags
            if (isset($input['template_tags'])) {
                $tags = $input['template_tags'];
                if (is_string($tags)) {
                    $tags = array_filter(array_map('trim', explode(',', $tags)));
                }
                TemplateTag::setForEntity('task', $id, $tags);
            }

            $updated++;
        } catch (\Exception $e) {
            $failed++;
            $errors[] = "Task #{$id}: " . $e->getMessage();
        }
    }

    Response::success([
        'updated' => $updated,
        'failed' => $failed,
        'errors' => $errors
    ], "{$updated} tasks updated, {$failed} failed");
}

function handleKanban(): void
{
    $projectId = $_GET['project_id'] ?? null;

    $sql = 'SELECT * FROM ors_task WHERE 1=1';
    $params = [];

    if ($projectId) {
        $sql .= ' AND project_id = ?';
        $params[] = (int)$projectId;
    }

    $sql .= ' ORDER BY priority DESC, created_at DESC';

    $tasks = Database::fetchAll($sql, $params);

    // Group by status
    $kanban = [
        'todo' => [],
        'doing' => [],
        'blocked' => [],
        'done' => []
    ];

    foreach ($tasks as $task) {
        $status = $task['status'] ?? 'todo';
        if (isset($kanban[$status])) {
            $kanban[$status][] = $task;
        }
    }

    Response::success(['kanban' => $kanban]);
}

function handleSearch(): void
{
    $keyword = trim($_GET['q'] ?? '');

    if (strlen($keyword) < 2) {
        Response::success(['tasks' => []]);
    }

    $tasks = Database::fetchAll(
        'SELECT t.*, p.project_name FROM ors_task t
         LEFT JOIN ors_project p ON t.project_id = p.id
         WHERE t.title LIKE ? OR t.description LIKE ?
         ORDER BY t.created_at DESC
         LIMIT 50',
        ['%' . $keyword . '%', '%' . $keyword . '%']
    );

    Response::success(['tasks' => $tasks]);
}
