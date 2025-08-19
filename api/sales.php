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
            getSale($db, $_GET['id']);
        } elseif (isset($_GET['receipt'])) {
            getSaleReceipt($db, $_GET['receipt']);
        } else {
            getAllSales($db);
        }
        break;
    
    case 'POST':
        createSale($db);
        break;
    
    default:
        sendError('Method not allowed', 405);
}

function getAllSales($db) {
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone 
                  FROM sales s 
                  LEFT JOIN customers c ON s.customer_id = c.id 
                  ORDER BY s.sale_date DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $sales = $stmt->fetchAll();
        
        sendSuccess($sales);
    } catch(Exception $e) {
        sendError('Failed to fetch sales: ' . $e->getMessage());
    }
}

function getSale($db, $id) {
    try {
        // Get sale details
        $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address 
                  FROM sales s 
                  LEFT JOIN customers c ON s.customer_id = c.id 
                  WHERE s.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $sale = $stmt->fetch();
        
        if (!$sale) {
            sendError('Sale not found', 404);
        }
        
        // Get sale items
        $itemsQuery = "SELECT si.*, p.name as product_name, p.brand, p.model 
                       FROM sale_items si 
                       JOIN products p ON si.product_id = p.id 
                       WHERE si.sale_id = ?";
        
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll();
        
        $sale['items'] = $items;
        
        sendSuccess($sale);
    } catch(Exception $e) {
        sendError('Failed to fetch sale: ' . $e->getMessage());
    }
}

function getSaleReceipt($db, $id) {
    try {
        // Get shop settings
        $settingsQuery = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('shop_name', 'shop_address', 'shop_phone', 'shop_email', 'tax_rate', 'currency_symbol')";
        $settingsStmt = $db->prepare($settingsQuery);
        $settingsStmt->execute();
        $settingsData = $settingsStmt->fetchAll();
        
        $settings = [];
        foreach ($settingsData as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // Get sale with customer info
        $sale = getSaleData($db, $id);
        
        $receipt = [
            'settings' => $settings,
            'sale' => $sale
        ];
        
        sendSuccess($receipt);
    } catch(Exception $e) {
        sendError('Failed to generate receipt: ' . $e->getMessage());
    }
}

function createSale($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($input['items']) || empty($input['items'])) {
            sendError('Sale items are required');
        }
        
        if (!isset($input['payment_method'])) {
            sendError('Payment method is required');
        }
        
        $db->beginTransaction();
        
        // Create customer if provided and doesn't exist
        $customerId = null;
        if (isset($input['customer']) && !empty($input['customer']['phone'])) {
            $customerId = createOrGetCustomer($db, $input['customer']);
        }
        
        // Calculate totals
        $totalAmount = 0;
        $validatedItems = [];
        
        foreach ($input['items'] as $item) {
            // Validate product exists and has enough stock
            $productQuery = "SELECT id, name, price, stock_quantity FROM products WHERE id = ?";
            $productStmt = $db->prepare($productQuery);
            $productStmt->execute([$item['product_id']]);
            $product = $productStmt->fetch();
            
            if (!$product) {
                throw new Exception("Product with ID {$item['product_id']} not found");
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for {$product['name']}. Available: {$product['stock_quantity']}, Requested: {$item['quantity']}");
            }
            
            $unitPrice = $item['unit_price'] ?? $product['price'];
            $totalPrice = $unitPrice * $item['quantity'];
            $totalAmount += $totalPrice;
            
            $validatedItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice
            ];
        }
        
        $discount = $input['discount'] ?? 0;
        $taxRate = $input['tax_rate'] ?? 0;
        $taxAmount = ($totalAmount - $discount) * ($taxRate / 100);
        $finalAmount = $totalAmount - $discount + $taxAmount;
        
        // Create sale record
        $saleQuery = "INSERT INTO sales (customer_id, total_amount, discount, tax_amount, final_amount, payment_method, notes) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $saleStmt = $db->prepare($saleQuery);
        $saleResult = $saleStmt->execute([
            $customerId,
            $totalAmount,
            $discount,
            $taxAmount,
            $finalAmount,
            $input['payment_method'],
            $input['notes'] ?? ''
        ]);
        
        if (!$saleResult) {
            throw new Exception('Failed to create sale record');
        }
        
        $saleId = $db->lastInsertId();
        
        // Create sale items and update stock
        foreach ($validatedItems as $item) {
            // Insert sale item
            $itemQuery = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) 
                          VALUES (?, ?, ?, ?, ?)";
            $itemStmt = $db->prepare($itemQuery);
            $itemStmt->execute([
                $saleId,
                $item['product_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['total_price']
            ]);
            
            // Update product stock
            $stockQuery = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stockStmt = $db->prepare($stockQuery);
            $stockStmt->execute([$item['quantity'], $item['product_id']]);
            
            // Log stock movement
            logStockMovement($db, $item['product_id'], 'out', $item['quantity'], 'sale', $saleId, 'Sale transaction');
        }
        
        $db->commit();
        
        sendSuccess(['sale_id' => $saleId], 'Sale completed successfully');
        
    } catch(Exception $e) {
        $db->rollBack();
        sendError('Failed to create sale: ' . $e->getMessage());
    }
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

function getSaleData($db, $id) {
    $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address 
              FROM sales s 
              LEFT JOIN customers c ON s.customer_id = c.id 
              WHERE s.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        throw new Exception('Sale not found');
    }
    
    // Get sale items
    $itemsQuery = "SELECT si.*, p.name as product_name, p.brand, p.model 
                   FROM sale_items si 
                   JOIN products p ON si.product_id = p.id 
                   WHERE si.sale_id = ?";
    
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute([$id]);
    $items = $itemsStmt->fetchAll();
    
    $sale['items'] = $items;
    
    return $sale;
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