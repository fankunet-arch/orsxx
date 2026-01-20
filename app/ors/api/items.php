<?php
/**
 * Items API
 * Handles item/inventory operations
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use ORS\Auth;
use ORS\Response;
use ORS\Validator;
use ORS\Item;
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

    case 'bulkUpdate':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleBulkUpdate();
        break;

    case 'search':
        handleSearch();
        break;

    case 'categories':
        handleCategories();
        break;

    default:
        Response::notFound('Action not found');
}

function handleList(): void
{
    $items = Item::all('category, item_name');
    Response::success(['items' => $items]);
}

function handleGet(): void
{
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('Item ID is required');
    }

    $item = Item::find($id);

    if (!$item) {
        Response::notFound('Item not found');
    }

    // Get tags for item
    $tagRecords = TemplateTag::getForEntity('item', $id);
    $item['tags'] = array_column($tagRecords, 'tag_name');

    Response::success(['item' => $item]);
}

function handleTemplates(): void
{
    $items = Item::getTemplates();

    // Add tags to each item
    foreach ($items as &$item) {
        $tagRecords = TemplateTag::getForEntity('item', $item['id']);
        $item['tags'] = array_column($tagRecords, 'tag_name');
    }

    Response::success(['items' => $items]);
}

function handleCreate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('item_name', 'Item name is required')
        ->maxLength('item_name', 200);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $user = ors_current_user();

    $data = [
        'item_name' => $validator->getString('item_name'),
        'category' => $validator->getOrNull('category'),
        'unit' => $validator->get('unit', 'pcs'),
        'must_buy_level' => $validator->get('must_buy_level', 'recommended'),
        'description' => $validator->getOrNull('description'),
        'estimated_unit_price_eur' => $validator->getFloat('estimated_unit_price_eur'),
        'long_lead_flag' => $validator->getBool('long_lead_flag') ? 1 : 0,
        'lead_time_days' => $validator->getInt('lead_time_days'),
        'template_flag' => $validator->getBool('template_flag') ? 1 : 0,
        'template_source' => $validator->getOrNull('template_source'),
        'project_types' => $validator->getOrNull('project_types'),
        'created_by' => $user['id']
    ];

    $id = Item::create($data);

    // Handle tags if provided
    $tags = $validator->get('template_tags');
    if ($tags && is_string($tags)) {
        $tags = array_filter(array_map('trim', explode(',', $tags)));
        TemplateTag::setForEntity('item', $id, $tags);
    }

    Response::success(['id' => $id, 'item' => Item::find($id)], 'Item created successfully');
}

function handleUpdate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('id', 'Item ID is required')
        ->integer('id');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $id = $validator->getInt('id');
    $item = Item::find($id);

    if (!$item) {
        Response::notFound('Item not found');
    }

    $data = [];

    if (isset($input['item_name'])) $data['item_name'] = $validator->getString('item_name');
    if (isset($input['category'])) $data['category'] = $validator->getOrNull('category');
    if (isset($input['unit'])) $data['unit'] = $validator->get('unit');
    if (isset($input['must_buy_level'])) $data['must_buy_level'] = $validator->get('must_buy_level');
    if (isset($input['description'])) $data['description'] = $validator->getOrNull('description');
    if (isset($input['estimated_unit_price_eur'])) $data['estimated_unit_price_eur'] = $validator->getFloat('estimated_unit_price_eur');
    if (isset($input['long_lead_flag'])) $data['long_lead_flag'] = $validator->getBool('long_lead_flag') ? 1 : 0;
    if (isset($input['lead_time_days'])) $data['lead_time_days'] = $validator->getInt('lead_time_days');
    if (isset($input['template_flag'])) $data['template_flag'] = $validator->getBool('template_flag') ? 1 : 0;
    if (isset($input['template_source'])) $data['template_source'] = $validator->getOrNull('template_source');
    if (array_key_exists('project_types', $input)) $data['project_types'] = $validator->getOrNull('project_types');

    if (!empty($data)) {
        Item::updateById($id, $data);
    }

    // Handle tags if provided
    $tags = $validator->get('template_tags');
    if ($tags !== null) {
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }
        TemplateTag::setForEntity('item', $id, $tags);
    }

    Response::success(['item' => Item::find($id)], 'Item updated successfully');
}

function handleDelete(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if (!$id) {
        Response::error('Item ID is required');
    }

    if (!Item::find($id)) {
        Response::notFound('Item not found');
    }

    // Check if item is used in purchases
    $usageCount = Database::fetchColumn(
        'SELECT COUNT(*) FROM ors_purchase WHERE item_id = ?',
        [$id]
    );

    if ($usageCount > 0) {
        Response::error('Cannot delete item: it is referenced by ' . $usageCount . ' purchase(s)');
    }

    // Delete associated tags
    TemplateTag::setForEntity('item', $id, []);

    Item::deleteById($id);
    Response::success([], 'Item deleted successfully');
}

function handleSearch(): void
{
    $keyword = trim($_GET['q'] ?? '');

    if (strlen($keyword) < 1) {
        // Return all items for autocomplete
        $items = Item::all('item_name');
        Response::success(['items' => $items]);
    }

    $items = Item::search($keyword);
    Response::success(['items' => $items]);
}

function handleCategories(): void
{
    $categories = [
        'it_devices' => 'IT设备',
        'furniture' => '家具',
        'equipment' => '设备器材',
        'consumables' => '消耗品',
        'other' => '其他'
    ];

    Response::success(['categories' => $categories]);
}

function handleBulkUpdate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $ids = $input['ids'] ?? [];

    if (empty($ids) || !is_array($ids)) {
        Response::error('Item IDs are required');
    }

    $updated = 0;
    $failed = 0;
    $errors = [];

    $data = [];
    if (isset($input['template_flag'])) $data['template_flag'] = filter_var($input['template_flag'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    if (array_key_exists('project_types', $input)) $data['project_types'] = $input['project_types'] ?: null;

    foreach ($ids as $id) {
        try {
            if (!Item::find($id)) {
                $failed++;
                $errors[] = "Item #{$id} not found";
                continue;
            }

            if (!empty($data)) {
                Item::updateById($id, $data);
            }
            $updated++;
        } catch (\Exception $e) {
            $failed++;
            $errors[] = "Item #{$id}: " . $e->getMessage();
        }
    }

    Response::success([
        'updated' => $updated,
        'failed' => $failed,
        'errors' => $errors
    ], "Updated {$updated} item(s)");
}
