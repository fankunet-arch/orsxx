<?php
/**
 * Purchases API
 * Handles all purchase-related operations
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use ORS\Auth;
use ORS\Response;
use ORS\Validator;
use ORS\Purchase;
use ORS\Item;
use ORS\Vendor;
use ORS\TemplateTag;
use ORS\Database;
use ORS\FxRate;

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

    case 'delete':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleDelete();
        break;

    case 'normalize':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleNormalize();
        break;

    case 'summary':
        handleSummary();
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
    $purchases = Purchase::getByProject($projectId ? (int)$projectId : null);
    Response::success(['purchases' => $purchases]);
}

function handleGet(): void
{
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('Purchase ID is required');
    }

    $purchase = Purchase::find($id);

    if (!$purchase) {
        Response::notFound('Purchase not found');
    }

    // Get linked item name if exists
    if ($purchase['item_id']) {
        $item = Item::find($purchase['item_id']);
        $purchase['linked_item_name'] = $item ? $item['item_name'] : null;
    }

    // Get vendor name if exists
    if ($purchase['vendor_id']) {
        $vendor = Vendor::find($purchase['vendor_id']);
        $purchase['vendor_name'] = $vendor ? $vendor['vendor_name'] : null;
    }

    Response::success(['purchase' => $purchase]);
}

function handleToday(): void
{
    $purchases = Purchase::getToday();
    Response::success(['purchases' => $purchases]);
}

function handleCreate(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Either item_id or free_text_item required
    if (empty($input['item_id']) && empty($input['free_text_item'])) {
        Response::validationError(['item' => 'Item name or item selection is required']);
    }

    $validator = Validator::make($input)
        ->numeric('unit_price')
        ->positive('unit_price')
        ->numeric('quantity')
        ->positive('quantity')
        ->in('currency', ['EUR', 'CNY', 'USD']);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $user = ors_current_user();
    $currency = $validator->get('currency', 'EUR');
    $unitPrice = $validator->getFloat('unit_price', 0);
    $quantity = $validator->getFloat('quantity', 1);

    // Get or calculate FX rate
    $fxRate = null;
    if ($currency !== 'EUR') {
        $fxRate = $validator->getFloat('fx_rate_to_eur');
        if (!$fxRate) {
            // Get default rate
            $fxRate = FxRate::getLatest($currency) ?? ($currency === 'CNY' ? ORS_DEFAULT_FX_CNY_EUR : ORS_DEFAULT_FX_USD_EUR);
        }
    }

    // Calculate total EUR
    $totalPriceEur = $unitPrice * $quantity;
    if ($currency !== 'EUR' && $fxRate) {
        $totalPriceEur = $totalPriceEur * $fxRate;
    }
    $totalPriceEur = round($totalPriceEur, 2);

    $data = [
        'project_id' => $validator->getInt('project_id'),
        'item_id' => $validator->getInt('item_id'),
        'free_text_item' => $validator->getOrNull('free_text_item'),
        'quantity' => $quantity,
        'unit' => $validator->get('unit', 'pcs'),
        'unit_price' => $unitPrice,
        'currency' => $currency,
        'fx_rate_to_eur' => $fxRate,
        'total_price_eur' => $totalPriceEur,
        'vendor_id' => $validator->getInt('vendor_id'),
        'status' => $validator->get('status', 'planned'),
        'notes' => $validator->getOrNull('notes'),
        'template_flag' => $validator->getBool('template_flag') ? 1 : 0,
        'created_by' => $user['id']
    ];

    $id = Purchase::create($data);

    Response::success(['id' => $id, 'purchase' => Purchase::find($id)], 'Purchase created successfully');
}

function handleUpdate(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('id', 'Purchase ID is required')
        ->integer('id');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $id = $validator->getInt('id');
    $purchase = Purchase::find($id);

    if (!$purchase) {
        Response::notFound('Purchase not found');
    }

    $data = [];

    if (isset($input['item_id'])) $data['item_id'] = $validator->getInt('item_id');
    if (isset($input['free_text_item'])) $data['free_text_item'] = $validator->getOrNull('free_text_item');
    if (isset($input['quantity'])) $data['quantity'] = $validator->getFloat('quantity', 1);
    if (isset($input['unit'])) $data['unit'] = $validator->get('unit', 'pcs');
    if (isset($input['unit_price'])) $data['unit_price'] = $validator->getFloat('unit_price');
    if (isset($input['currency'])) $data['currency'] = $validator->get('currency', 'EUR');
    if (isset($input['fx_rate_to_eur'])) $data['fx_rate_to_eur'] = $validator->getFloat('fx_rate_to_eur');
    if (isset($input['vendor_id'])) $data['vendor_id'] = $validator->getInt('vendor_id');
    if (isset($input['status'])) $data['status'] = $validator->get('status');
    if (isset($input['notes'])) $data['notes'] = $validator->getOrNull('notes');
    if (isset($input['order_date'])) $data['order_date'] = $validator->getOrNull('order_date');
    if (isset($input['expected_delivery'])) $data['expected_delivery'] = $validator->getOrNull('expected_delivery');
    if (isset($input['actual_delivery'])) $data['actual_delivery'] = $validator->getOrNull('actual_delivery');
    if (isset($input['latest_order_date'])) $data['latest_order_date'] = $validator->getOrNull('latest_order_date');
    if (isset($input['template_flag'])) $data['template_flag'] = $validator->getBool('template_flag') ? 1 : 0;

    // Recalculate total if price/quantity/currency/fx changed
    $needRecalc = isset($data['unit_price']) || isset($data['quantity']) || isset($data['currency']) || isset($data['fx_rate_to_eur']);
    if ($needRecalc) {
        $unitPrice = $data['unit_price'] ?? $purchase['unit_price'];
        $quantity = $data['quantity'] ?? $purchase['quantity'];
        $currency = $data['currency'] ?? $purchase['currency'];
        $fxRate = $data['fx_rate_to_eur'] ?? $purchase['fx_rate_to_eur'];

        $totalPriceEur = $unitPrice * $quantity;
        if ($currency !== 'EUR' && $fxRate) {
            $totalPriceEur = $totalPriceEur * $fxRate;
        }
        $data['total_price_eur'] = round($totalPriceEur, 2);
    }

    if (!empty($data)) {
        Purchase::updateById($id, $data);
    }

    Response::success(['purchase' => Purchase::find($id)], 'Purchase updated successfully');
}

function handleDelete(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if (!$id) {
        Response::error('Purchase ID is required');
    }

    if (!Purchase::find($id)) {
        Response::notFound('Purchase not found');
    }

    Purchase::deleteById($id);
    Response::success([], 'Purchase deleted successfully');
}

function handleNormalize(): void
{
    Auth::requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('purchase_id', 'Purchase ID is required')
        ->required('item_name', 'Item name is required');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $purchaseId = $validator->getInt('purchase_id');
    $purchase = Purchase::find($purchaseId);

    if (!$purchase) {
        Response::notFound('Purchase not found');
    }

    $user = ors_current_user();

    // Create new item
    $itemData = [
        'item_name' => $validator->getString('item_name'),
        'category' => $validator->getOrNull('category'),
        'unit' => $validator->get('unit', 'pcs'),
        'must_buy_level' => $validator->get('must_buy_level', 'recommended'),
        'description' => $validator->getOrNull('description'),
        'template_flag' => $validator->getBool('template_flag') ? 1 : 0,
        'template_source' => $validator->getOrNull('template_source'),
        'created_by' => $user['id']
    ];

    $itemId = Item::create($itemData);

    // Link purchase to item
    Purchase::updateById($purchaseId, [
        'item_id' => $itemId,
        'free_text_item' => null  // Clear free text
    ]);

    Response::success([
        'item_id' => $itemId,
        'item' => Item::find($itemId),
        'purchase' => Purchase::find($purchaseId)
    ], 'Item created and linked successfully');
}

function handleSummary(): void
{
    $projectId = $_GET['project_id'] ?? null;

    if (!$projectId) {
        // Get overall summary
        $total = Database::fetchColumn('SELECT SUM(total_price_eur) FROM ors_purchase');
        $byStatus = Database::fetchAll(
            'SELECT status, COUNT(*) as count, SUM(total_price_eur) as total_eur FROM ors_purchase GROUP BY status'
        );
        $byCurrency = Database::fetchAll(
            'SELECT currency, COUNT(*) as count, SUM(unit_price * quantity) as total FROM ors_purchase GROUP BY currency'
        );
    } else {
        $total = Database::fetchColumn(
            'SELECT SUM(total_price_eur) FROM ors_purchase WHERE project_id = ?',
            [(int)$projectId]
        );
        $byStatus = Database::fetchAll(
            'SELECT status, COUNT(*) as count, SUM(total_price_eur) as total_eur FROM ors_purchase WHERE project_id = ? GROUP BY status',
            [(int)$projectId]
        );
        $byCurrency = Database::fetchAll(
            'SELECT currency, COUNT(*) as count, SUM(unit_price * quantity) as total FROM ors_purchase WHERE project_id = ? GROUP BY currency',
            [(int)$projectId]
        );
    }

    Response::success([
        'total_eur' => round((float)$total, 2),
        'by_status' => $byStatus,
        'by_currency' => $byCurrency
    ]);
}

function handleSearch(): void
{
    $keyword = trim($_GET['q'] ?? '');

    if (strlen($keyword) < 2) {
        Response::success(['purchases' => []]);
    }

    $purchases = Database::fetchAll(
        'SELECT p.*, i.item_name as linked_item_name, pr.project_name
         FROM ors_purchase p
         LEFT JOIN ors_item i ON p.item_id = i.id
         LEFT JOIN ors_project pr ON p.project_id = pr.id
         WHERE p.free_text_item LIKE ? OR i.item_name LIKE ?
         ORDER BY p.created_at DESC
         LIMIT 50',
        ['%' . $keyword . '%', '%' . $keyword . '%']
    );

    Response::success(['purchases' => $purchases]);
}
