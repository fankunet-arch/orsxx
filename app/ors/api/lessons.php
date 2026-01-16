<?php
/**
 * Lessons API
 * Handles lesson/experience records and check items
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use ORS\Auth;
use ORS\Response;
use ORS\Validator;
use ORS\Lesson;
use ORS\CheckItem;
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

    case 'templates':
        handleTemplates();
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

    case 'checkItems':
        handleCheckItems();
        break;

    case 'updateCheckItem':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleUpdateCheckItem();
        break;

    default:
        Response::notFound('Action not found');
}

function handleList(): void
{
    $projectId = $_GET['project_id'] ?? null;

    if ($projectId) {
        $lessons = Lesson::where(['project_id' => (int)$projectId], 'created_at DESC');
    } else {
        $lessons = Lesson::all('category, title');
    }

    Response::success(['lessons' => $lessons]);
}

function handleTemplates(): void
{
    $lessons = Lesson::getTemplates();

    // Add tags to each lesson
    foreach ($lessons as &$lesson) {
        $tagRecords = TemplateTag::getForEntity('lesson', $lesson['id']);
        $lesson['tags'] = array_column($tagRecords, 'tag_name');
    }

    Response::success(['lessons' => $lessons]);
}

function handleCreate(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('title', 'Title is required')
        ->required('prevention_check_item', 'Prevention check item is required')
        ->maxLength('title', 500);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $user = ors_current_user();

    $data = [
        'project_id' => $validator->getInt('project_id'),
        'title' => $validator->getString('title'),
        'description' => $validator->getOrNull('description'),
        'category' => $validator->getOrNull('category'),
        'severity' => $validator->get('severity', 'medium'),
        'root_cause' => $validator->getOrNull('root_cause'),
        'prevention_check_item' => $validator->getString('prevention_check_item'),
        'check_timing' => $validator->getOrNull('check_timing'),
        'check_days_before_open' => $validator->getInt('check_days_before_open'),
        'check_days_after_sign' => $validator->getInt('check_days_after_sign'),
        'template_flag' => $validator->getBool('template_flag', true) ? 1 : 0,
        'template_source' => $validator->getOrNull('template_source'),
        'created_by' => $user['id']
    ];

    $id = Lesson::create($data);

    // Handle tags if provided
    $tags = $validator->get('template_tags');
    if ($tags && is_string($tags)) {
        $tags = array_filter(array_map('trim', explode(',', $tags)));
        TemplateTag::setForEntity('lesson', $id, $tags);
    }

    Response::success(['id' => $id, 'lesson' => Lesson::find($id)], 'Lesson created successfully');
}

function handleUpdate(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('id', 'Lesson ID is required')
        ->integer('id');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $id = $validator->getInt('id');
    $lesson = Lesson::find($id);

    if (!$lesson) {
        Response::notFound('Lesson not found');
    }

    $data = [];

    if (isset($input['title'])) $data['title'] = $validator->getString('title');
    if (isset($input['description'])) $data['description'] = $validator->getOrNull('description');
    if (isset($input['category'])) $data['category'] = $validator->getOrNull('category');
    if (isset($input['severity'])) $data['severity'] = $validator->get('severity');
    if (isset($input['root_cause'])) $data['root_cause'] = $validator->getOrNull('root_cause');
    if (isset($input['prevention_check_item'])) {
        $checkItem = $validator->getString('prevention_check_item');
        if (empty($checkItem)) {
            Response::validationError(['prevention_check_item' => 'Prevention check item is required']);
        }
        $data['prevention_check_item'] = $checkItem;
    }
    if (isset($input['check_timing'])) $data['check_timing'] = $validator->getOrNull('check_timing');
    if (isset($input['check_days_before_open'])) $data['check_days_before_open'] = $validator->getInt('check_days_before_open');
    if (isset($input['check_days_after_sign'])) $data['check_days_after_sign'] = $validator->getInt('check_days_after_sign');
    if (isset($input['template_flag'])) $data['template_flag'] = $validator->getBool('template_flag') ? 1 : 0;
    if (isset($input['template_source'])) $data['template_source'] = $validator->getOrNull('template_source');

    if (!empty($data)) {
        Lesson::updateById($id, $data);
    }

    // Handle tags if provided
    $tags = $validator->get('template_tags');
    if ($tags !== null) {
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }
        TemplateTag::setForEntity('lesson', $id, $tags);
    }

    Response::success(['lesson' => Lesson::find($id)], 'Lesson updated successfully');
}

function handleDelete(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if (!$id) {
        Response::error('Lesson ID is required');
    }

    if (!Lesson::find($id)) {
        Response::notFound('Lesson not found');
    }

    // Delete associated tags
    TemplateTag::setForEntity('lesson', $id, []);

    // Delete associated check items
    Database::delete('ors_check_item', 'lesson_id = ?', [$id]);

    Lesson::deleteById($id);
    Response::success([], 'Lesson deleted successfully');
}

function handleCheckItems(): void
{
    $projectId = (int)($_GET['project_id'] ?? 0);

    if (!$projectId) {
        Response::error('Project ID is required');
    }

    $checkItems = CheckItem::getByProject($projectId);
    Response::success(['check_items' => $checkItems]);
}

function handleUpdateCheckItem(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('id', 'Check item ID is required')
        ->integer('id')
        ->in('status', ['pending', 'passed', 'failed', 'skipped']);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $id = $validator->getInt('id');
    $checkItem = CheckItem::find($id);

    if (!$checkItem) {
        Response::notFound('Check item not found');
    }

    $user = ors_current_user();

    $data = [];

    if (isset($input['status'])) {
        $data['status'] = $validator->get('status');
        if ($data['status'] !== 'pending') {
            $data['checked_by'] = $user['id'];
            $data['checked_at'] = date('Y-m-d H:i:s');
        } else {
            $data['checked_by'] = null;
            $data['checked_at'] = null;
        }
    }
    if (isset($input['check_date'])) $data['check_date'] = $validator->getOrNull('check_date');
    if (isset($input['notes'])) $data['notes'] = $validator->getOrNull('notes');

    if (!empty($data)) {
        CheckItem::updateById($id, $data);
    }

    Response::success(['check_item' => CheckItem::find($id)], 'Check item updated successfully');
}
