<?php
/**
 * Vendors API
 * Handles vendor/supplier management
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use ORS\Auth;
use ORS\Response;
use ORS\Validator;
use ORS\Vendor;
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
    $category = $_GET['category'] ?? null;

    if ($category) {
        $vendors = Vendor::where(['category' => $category], 'vendor_name ASC');
    } else {
        $vendors = Vendor::all('category, vendor_name');
    }

    Response::success(['vendors' => $vendors]);
}

function handleGet(): void
{
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('Vendor ID is required');
    }

    $vendor = Vendor::find($id);

    if (!$vendor) {
        Response::notFound('Vendor not found');
    }

    // Get related purchases
    $purchases = Database::fetchAll(
        'SELECT p.*, i.item_name as linked_item_name, pr.project_name
         FROM ors_purchase p
         LEFT JOIN ors_item i ON p.item_id = i.id
         LEFT JOIN ors_project pr ON p.project_id = pr.id
         WHERE p.vendor_id = ?
         ORDER BY p.created_at DESC',
        [$id]
    );

    $vendor['purchases'] = $purchases;

    Response::success(['vendor' => $vendor]);
}

function handleCreate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('vendor_name', 'Vendor name is required')
        ->maxLength('vendor_name', 200)
        ->email('email')
        ->integer('rating')
        ->in('rating', ['1', '2', '3', '4', '5', 1, 2, 3, 4, 5]);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $user = ors_current_user();

    $data = [
        'vendor_name' => $validator->getString('vendor_name'),
        'category' => $validator->getOrNull('category'),
        'contact_person' => $validator->getOrNull('contact_person'),
        'phone' => $validator->getOrNull('phone'),
        'email' => $validator->getOrNull('email'),
        'address' => $validator->getOrNull('address'),
        'website' => $validator->getOrNull('website'),
        'rating' => $validator->getInt('rating'),
        'rating_comment' => $validator->getOrNull('rating_comment'),
        'notes' => $validator->getOrNull('notes'),
        'created_by' => $user['id']
    ];

    $id = Vendor::create($data);

    Response::success(['id' => $id, 'vendor' => Vendor::find($id)], 'Vendor created successfully');
}

function handleUpdate(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('id', 'Vendor ID is required')
        ->integer('id')
        ->email('email');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $id = $validator->getInt('id');
    $vendor = Vendor::find($id);

    if (!$vendor) {
        Response::notFound('Vendor not found');
    }

    $data = [];

    if (isset($input['vendor_name'])) $data['vendor_name'] = $validator->getString('vendor_name');
    if (isset($input['category'])) $data['category'] = $validator->getOrNull('category');
    if (isset($input['contact_person'])) $data['contact_person'] = $validator->getOrNull('contact_person');
    if (isset($input['phone'])) $data['phone'] = $validator->getOrNull('phone');
    if (isset($input['email'])) $data['email'] = $validator->getOrNull('email');
    if (isset($input['address'])) $data['address'] = $validator->getOrNull('address');
    if (isset($input['website'])) $data['website'] = $validator->getOrNull('website');
    if (isset($input['rating'])) $data['rating'] = $validator->getInt('rating');
    if (isset($input['rating_comment'])) $data['rating_comment'] = $validator->getOrNull('rating_comment');
    if (isset($input['notes'])) $data['notes'] = $validator->getOrNull('notes');

    if (!empty($data)) {
        Vendor::updateById($id, $data);
    }

    Response::success(['vendor' => Vendor::find($id)], 'Vendor updated successfully');
}

function handleDelete(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if (!$id) {
        Response::error('Vendor ID is required');
    }

    if (!Vendor::find($id)) {
        Response::notFound('Vendor not found');
    }

    // Check if vendor is used in purchases
    $usageCount = Database::fetchColumn(
        'SELECT COUNT(*) FROM ors_purchase WHERE vendor_id = ?',
        [$id]
    );

    if ($usageCount > 0) {
        Response::error('Cannot delete vendor: it is referenced by ' . $usageCount . ' purchase(s)');
    }

    Vendor::deleteById($id);
    Response::success([], 'Vendor deleted successfully');
}

function handleSearch(): void
{
    $keyword = trim($_GET['q'] ?? '');

    if (strlen($keyword) < 1) {
        $vendors = Vendor::all('vendor_name');
        Response::success(['vendors' => $vendors]);
    }

    $vendors = Database::fetchAll(
        'SELECT * FROM ors_vendor WHERE vendor_name LIKE ? OR contact_person LIKE ? ORDER BY vendor_name',
        ['%' . $keyword . '%', '%' . $keyword . '%']
    );

    Response::success(['vendors' => $vendors]);
}

function handleCategories(): void
{
    $categories = [
        'it' => 'IT/电子设备',
        'furniture' => '家具',
        'decoration' => '装修装饰',
        'food' => '食品供应',
        'service' => '服务',
        'other' => '其他'
    ];

    Response::success(['categories' => $categories]);
}
