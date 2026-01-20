<?php
/**
 * Projects API
 * Handles project management operations
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use ORS\Auth;
use ORS\Response;
use ORS\Validator;
use ORS\Project;
use ORS\Task;
use ORS\Purchase;
use ORS\CheckItem;
use ORS\Lesson;
use ORS\Item;
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

    case 'create':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleCreate();
        break;

    case 'update':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleUpdate();
        break;

    case 'delete':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleDelete();
        break;

    case 'generateFromTemplate':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleGenerateFromTemplate();
        break;

    case 'stats':
        handleStats();
        break;

    default:
        Response::notFound('Action not found');
}

function handleList(): void
{
    $projects = Project::all('status ASC, target_open_date ASC');
    Response::success(['projects' => $projects]);
}

function handleGet(): void
{
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('Project ID is required');
    }

    $project = Project::find($id);

    if (!$project) {
        Response::notFound('Project not found');
    }

    // Get related data counts
    $taskCount = Task::count(['project_id' => $id]);
    $purchaseCount = Purchase::count(['project_id' => $id]);
    $totalCost = Purchase::getProjectTotalEur($id);

    $project['task_count'] = $taskCount;
    $project['purchase_count'] = $purchaseCount;
    $project['total_cost_eur'] = $totalCost;

    Response::success(['project' => $project]);
}

function handleCreate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('project_name', 'Project name is required')
        ->maxLength('project_name', 200)
        ->date('target_open_date');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $user = ors_current_user();

    $data = [
        'project_name' => $validator->getString('project_name'),
        'project_type' => $validator->get('project_type', 'cafeteria'),
        'city' => $validator->getOrNull('city'),
        'area_m2' => $validator->getFloat('area_m2'),
        'address' => $validator->getOrNull('address'),
        'target_open_date' => $validator->getOrNull('target_open_date'),
        'status' => $validator->get('status', 'planning'),
        'notes' => $validator->getOrNull('notes'),
        'created_by' => $user['id']
    ];

    $id = Project::create($data);

    Response::success(['id' => $id, 'project' => Project::find($id)], 'Project created successfully');
}

function handleUpdate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('id', 'Project ID is required')
        ->integer('id');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $id = $validator->getInt('id');
    $project = Project::find($id);

    if (!$project) {
        Response::notFound('Project not found');
    }

    $data = [];

    if (isset($input['project_name'])) $data['project_name'] = $validator->getString('project_name');
    if (isset($input['project_type'])) $data['project_type'] = $validator->get('project_type');
    if (isset($input['city'])) $data['city'] = $validator->getOrNull('city');
    if (isset($input['area_m2'])) $data['area_m2'] = $validator->getFloat('area_m2');
    if (isset($input['address'])) $data['address'] = $validator->getOrNull('address');
    if (isset($input['target_open_date'])) $data['target_open_date'] = $validator->getOrNull('target_open_date');
    if (isset($input['actual_open_date'])) $data['actual_open_date'] = $validator->getOrNull('actual_open_date');
    if (isset($input['status'])) $data['status'] = $validator->get('status');
    if (isset($input['notes'])) $data['notes'] = $validator->getOrNull('notes');

    if (!empty($data)) {
        Project::updateById($id, $data);
    }

    Response::success(['project' => Project::find($id)], 'Project updated successfully');
}

function handleDelete(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if (!$id) {
        Response::error('Project ID is required');
    }

    if (!Project::find($id)) {
        Response::notFound('Project not found');
    }

    // Check for related data
    $taskCount = Task::count(['project_id' => $id]);
    $purchaseCount = Purchase::count(['project_id' => $id]);

    if ($taskCount > 0 || $purchaseCount > 0) {
        Response::error("Cannot delete project: it has {$taskCount} tasks and {$purchaseCount} purchases. Archive instead.");
    }

    Project::deleteById($id);
    Response::success([], 'Project deleted successfully');
}

function handleGenerateFromTemplate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('project_id', 'Project ID is required')
        ->integer('project_id');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $projectId = $validator->getInt('project_id');
    $project = Project::find($projectId);

    if (!$project) {
        Response::notFound('Project not found');
    }

    $targetOpenDate = $project['target_open_date'] ? new \DateTime($project['target_open_date']) : null;
    $projectType = $project['project_type'] ?? null;

    $createdTasks = 0;
    $createdPurchases = 0;
    $createdCheckItems = 0;

    Database::beginTransaction();

    try {
        // 1. Generate tasks from templates (filtered by project type)
        $templateTasks = Task::getTemplates($projectType);
        foreach ($templateTasks as $template) {
            $latestStartDate = null;
            if ($targetOpenDate && $template['lead_time_days']) {
                $latestStartDate = (clone $targetOpenDate)
                    ->modify("-{$template['lead_time_days']} days")
                    ->format('Y-m-d');
            }

            Task::create([
                'project_id' => $projectId,
                'title' => $template['title'],
                'description' => $template['description'],
                'phase_code' => $template['phase_code'],
                'status' => 'todo',
                'priority' => $template['priority'] ?? 'medium',
                'blocking_flag' => $template['blocking_flag'],
                'lead_time_days' => $template['lead_time_days'],
                'latest_start_date' => $latestStartDate,
                'template_flag' => 0,
                'source_task_id' => $template['id'],
                'created_by' => ors_current_user()['id']
            ]);
            $createdTasks++;
        }

        // 2. Generate purchases from template items (filtered by project type)
        $templateItems = Item::getTemplates($projectType);
        foreach ($templateItems as $item) {
            $latestOrderDate = null;
            if ($targetOpenDate && $item['lead_time_days']) {
                $latestOrderDate = (clone $targetOpenDate)
                    ->modify("-{$item['lead_time_days']} days")
                    ->format('Y-m-d');
            }

            Purchase::create([
                'project_id' => $projectId,
                'item_id' => $item['id'],
                'free_text_item' => null,
                'quantity' => 1,
                'unit' => $item['unit'],
                'unit_price' => $item['estimated_unit_price_eur'],
                'currency' => 'EUR',
                'total_price_eur' => $item['estimated_unit_price_eur'],
                'status' => 'planned',
                'latest_order_date' => $latestOrderDate,
                'template_flag' => 0,
                'created_by' => ors_current_user()['id']
            ]);
            $createdPurchases++;
        }

        // 3. Generate check items from template lessons (filtered by project type)
        $templateLessons = Lesson::getTemplates($projectType);
        foreach ($templateLessons as $lesson) {
            $checkDate = null;
            if ($targetOpenDate) {
                if ($lesson['check_days_before_open']) {
                    $checkDate = (clone $targetOpenDate)
                        ->modify("-{$lesson['check_days_before_open']} days")
                        ->format('Y-m-d');
                }
            }

            // For "days after sign", we use a placeholder date (project creation date + days)
            if (!$checkDate && $lesson['check_days_after_sign']) {
                $signDate = new \DateTime($project['created_at']);
                $checkDate = $signDate
                    ->modify("+{$lesson['check_days_after_sign']} days")
                    ->format('Y-m-d');
            }

            CheckItem::create([
                'project_id' => $projectId,
                'lesson_id' => $lesson['id'],
                'check_content' => $lesson['prevention_check_item'],
                'check_date' => $checkDate,
                'status' => 'pending'
            ]);
            $createdCheckItems++;
        }

        Database::commit();

        Response::success([
            'created_tasks' => $createdTasks,
            'created_purchases' => $createdPurchases,
            'created_check_items' => $createdCheckItems
        ], 'Template applied successfully');

    } catch (\Exception $e) {
        Database::rollback();
        Response::error('Failed to generate from template: ' . $e->getMessage());
    }
}

function handleStats(): void
{
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        // Overall stats
        $stats = [
            'total_projects' => Project::count(),
            'active_projects' => Project::count(['status' => 'active']),
            'total_tasks' => Task::count(),
            'total_purchases' => Purchase::count()
        ];
    } else {
        // Project-specific stats
        $stats = [
            'tasks_by_status' => Database::fetchAll(
                'SELECT status, COUNT(*) as count FROM ors_task WHERE project_id = ? GROUP BY status',
                [$id]
            ),
            'purchases_by_status' => Database::fetchAll(
                'SELECT status, COUNT(*) as count FROM ors_purchase WHERE project_id = ? GROUP BY status',
                [$id]
            ),
            'total_cost_eur' => Purchase::getProjectTotalEur($id),
            'check_items' => Database::fetchAll(
                'SELECT status, COUNT(*) as count FROM ors_check_item WHERE project_id = ? GROUP BY status',
                [$id]
            )
        ];
    }

    Response::success(['stats' => $stats]);
}
