<?php
require_once '../config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getRepair($db, $_GET['id']);
        } elseif (isset($_GET['status'])) {
            getRepairsByStatus($db, $_GET['status']);
        } else {
            getAllRepairs($db);
        }
        break;
    
    case 'POST':
        createRepair($db);
        break;
    
    case 'PUT':
        updateRepair($db);
        break;
    
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteRepair($db, $_GET['id']);
        } else {
            sendError('Repair ID required for deletion');
        }
        break;
    
    default:
        sendError('Method not allowed', 405);
}

function getAllRepairs($db) {
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $query = "SELECT r.*, c.name as customer_name, c.phone as customer_phone 
                  FROM repairs r 
                  JOIN customers c ON r.customer_id = c.id 
                  ORDER BY r.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $repairs = $stmt->fetchAll();
        
        sendSuccess($repairs);
    } catch(Exception $e) {
        sendError('Failed to fetch repairs: ' . $e->getMessage());
    }
}

function getRepair($db, $id) {
    try {
        // Get repair details
        $query = "SELECT r.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address 
                  FROM repairs r 
                  JOIN customers c ON r.customer_id = c.id 
                  WHERE r.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $repair = $stmt->fetch();
        
        if (!$repair) {
            sendError('Repair not found', 404);
        }
        
        // Get repair parts
        $partsQuery = "SELECT rp.*, p.name as part_name, p.brand, p.model 
                       FROM repair_parts rp 
                       JOIN products p ON rp.product_id = p.id 
                       WHERE rp.repair_id = ?";
        
        $partsStmt = $db->prepare($partsQuery);
        $partsStmt->execute([$id]);
        $parts = $partsStmt->fetchAll();
        
        $repair['parts'] = $parts;
        
        sendSuccess($repair);
    } catch(Exception $e) {
        sendError('Failed to fetch repair: ' . $e->getMessage());
    }
}

function getRepairsByStatus($db, $status) {
    try {
        $query = "SELECT r.*, c.name as customer_name, c.phone as customer_phone 
                  FROM repairs r 
                  JOIN customers c ON r.customer_id = c.id 
                  WHERE r.status = ? 
                  ORDER BY r.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$status]);
        $repairs = $stmt->fetchAll();
        
        sendSuccess($repairs);
    } catch(Exception $e) {
        sendError('Failed to fetch repairs by status: ' . $e->getMessage());
    }
}

function createRepair($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['customer', 'device_brand', 'device_model', 'issue_description'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                sendError("Field '$field' is required");
            }
        }
        
        $db->beginTransaction();
        
        // Create or get customer
        $customerId = createOrGetCustomer($db, $input['customer']);
        
        // Generate repair job number
        $repairPrefix = getSettingValue($db, 'repair_prefix', 'RPR');
        $repairNumber = generateRepairNumber($db, $repairPrefix);
        
        // Create repair record
        $query = "INSERT INTO repairs (customer_id, device_brand, device_model, issue_description, repair_cost, parts_cost, total_cost, priority, estimated_completion, customer_notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $repairCost = $input['repair_cost'] ?? 0;
        $partsCost = $input['parts_cost'] ?? 0;
        $totalCost = $repairCost + $partsCost;
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $customerId,
            $input['device_brand'],
            $input['device_model'],
            $input['issue_description'],
            $repairCost,
            $partsCost,
            $totalCost,
            $input['priority'] ?? 'medium',
            $input['estimated_completion'] ?? null,
            $input['customer_notes'] ?? ''
        ]);
        
        if (!$result) {
            throw new Exception('Failed to create repair record');
        }
        
        $repairId = $db->lastInsertId();
        
        // Add parts if provided
        if (isset($input['parts']) && !empty($input['parts'])) {
            foreach ($input['parts'] as $part) {
                addRepairPart($db, $repairId, $part);
            }
        }
        
        $db->commit();
        
        sendSuccess(['repair_id' => $repairId, 'repair_number' => $repairNumber], 'Repair job created successfully');
        
    } catch(Exception $e) {
        $db->rollBack();
        sendError('Failed to create repair: ' . $e->getMessage());
    }
}

function updateRepair($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            sendError('Repair ID is required');
        }
        
        $db->beginTransaction();
        
        // Update repair record
        $query = "UPDATE repairs SET device_brand = ?, device_model = ?, issue_description = ?, repair_cost = ?, 
                  parts_cost = ?, total_cost = ?, status = ?, priority = ?, estimated_completion = ?, 
                  actual_completion = ?, technician_notes = ?, customer_notes = ?, updated_at = NOW() 
                  WHERE id = ?";
        
        $repairCost = $input['repair_cost'] ?? 0;
        $partsCost = $input['parts_cost'] ?? 0;
        $totalCost = $repairCost + $partsCost;
        
        $actualCompletion = null;
        if ($input['status'] === 'completed' && !isset($input['actual_completion'])) {
            $actualCompletion = date('Y-m-d');
        } elseif (isset($input['actual_completion'])) {
            $actualCompletion = $input['actual_completion'];
        }
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['device_brand'],
            $input['device_model'],
            $input['issue_description'],
            $repairCost,
            $partsCost,
            $totalCost,
            $input['status'] ?? 'received',
            $input['priority'] ?? 'medium',
            $input['estimated_completion'] ?? null,
            $actualCompletion,
            $input['technician_notes'] ?? '',
            $input['customer_notes'] ?? '',
            $input['id']
        ]);
        
        if (!$result) {
            throw new Exception('Failed to update repair record');
        }
        
        // Update parts if provided
        if (isset($input['parts'])) {
            // Remove existing parts
            $deletePartsQuery = "DELETE FROM repair_parts WHERE repair_id = ?";
            $deletePartsStmt = $db->prepare($deletePartsQuery);
            $deletePartsStmt->execute([$input['id']]);
            
            // Add new parts
            foreach ($input['parts'] as $part) {
                addRepairPart($db, $input['id'], $part);
            }
        }
        
        $db->commit();
        
        sendSuccess(null, 'Repair updated successfully');
        
    } catch(Exception $e) {
        $db->rollBack();
        sendError('Failed to update repair: ' . $e->getMessage());
    }
}

function deleteRepair($db, $id) {
    try {
        $query = "DELETE FROM repairs WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            sendSuccess(null, 'Repair deleted successfully');
        } else {
            sendError('Failed to delete repair');
        }
    } catch(Exception $e) {
        sendError('Failed to delete repair: ' . $e->getMessage());
    }
}

function addRepairPart($db, $repairId, $partData) {
    // Validate product exists and has enough stock
    $productQuery = "SELECT id, name, price, stock_quantity FROM products WHERE id = ?";
    $productStmt = $db->prepare($productQuery);
    $productStmt->execute([$partData['product_id']]);
    $product = $productStmt->fetch();
    
    if (!$product) {
        throw new Exception("Part with ID {$partData['product_id']} not found");
    }
    
    if ($product['stock_quantity'] < $partData['quantity']) {
        throw new Exception("Insufficient stock for {$product['name']}. Available: {$product['stock_quantity']}, Requested: {$partData['quantity']}");
    }
    
    $unitPrice = $partData['unit_price'] ?? $product['price'];
    $totalPrice = $unitPrice * $partData['quantity'];
    
    // Insert repair part
    $partQuery = "INSERT INTO repair_parts (repair_id, product_id, quantity, unit_price, total_price) 
                  VALUES (?, ?, ?, ?, ?)";
    $partStmt = $db->prepare($partQuery);
    $partStmt->execute([
        $repairId,
        $partData['product_id'],
        $partData['quantity'],
        $unitPrice,
        $totalPrice
    ]);
    
    // Update product stock
    $stockQuery = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
    $stockStmt = $db->prepare($stockQuery);
    $stockStmt->execute([$partData['quantity'], $partData['product_id']]);
    
    // Log stock movement
    logStockMovement($db, $partData['product_id'], 'out', $partData['quantity'], 'repair', $repairId, 'Used in repair');
}

function createOrGetCustomer($db, $customerData) {
    try {
        // Check if customer exists
        $checkQuery = "SELECT id FROM customers WHERE phone = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$customerData['phone']]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Create new customer
        $insertQuery = "INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            $customerData['name'] ?? '',
            $customerData['phone'],
            $customerData['email'] ?? '',
            $customerData['address'] ?? ''
        ]);
        
        return $db->lastInsertId();
    } catch(Exception $e) {
        throw new Exception('Failed to create customer: ' . $e->getMessage());
    }
}

function getSettingValue($db, $key, $default = null) {
    try {
        $query = "SELECT setting_value FROM settings WHERE setting_key = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch(Exception $e) {
        return $default;
    }
}

function generateRepairNumber($db, $prefix) {
    $query = "SELECT COUNT(*) as count FROM repairs WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $dailyCount = $result['count'] + 1;
    $date = date('Ymd');
    
    return $prefix . $date . sprintf('%03d', $dailyCount);
}

function logStockMovement($db, $productId, $type, $quantity, $refType, $refId, $notes) {
    try {
        $query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, notes) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$productId, $type, $quantity, $refType, $refId, $notes]);
    } catch(Exception $e) {
        error_log('Failed to log stock movement: ' . $e->getMessage());
    }
}
?>