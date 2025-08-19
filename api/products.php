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
            getProduct($db, $_GET['id']);
        } elseif (isset($_GET['search'])) {
            searchProducts($db, $_GET['search']);
        } elseif (isset($_GET['barcode'])) {
            getProductByBarcode($db, $_GET['barcode']);
        } else {
            getAllProducts($db);
        }
        break;
    
    case 'POST':
        addProduct($db);
        break;
    
    case 'PUT':
        updateProduct($db);
        break;
    
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteProduct($db, $_GET['id']);
        } else {
            sendError('Product ID required for deletion');
        }
        break;
    
    default:
        sendError('Method not allowed', 405);
}

function getAllProducts($db) {
    try {
        $query = "SELECT * FROM products ORDER BY name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        sendSuccess($products);
    } catch(Exception $e) {
        sendError('Failed to fetch products: ' . $e->getMessage());
    }
}

function getProduct($db, $id) {
    try {
        $query = "SELECT * FROM products WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if ($product) {
            sendSuccess($product);
        } else {
            sendError('Product not found', 404);
        }
    } catch(Exception $e) {
        sendError('Failed to fetch product: ' . $e->getMessage());
    }
}

function searchProducts($db, $search) {
    try {
        $query = "SELECT * FROM products WHERE name LIKE ? OR brand LIKE ? OR model LIKE ? OR barcode LIKE ? ORDER BY name";
        $searchTerm = "%$search%";
        $stmt = $db->prepare($query);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $products = $stmt->fetchAll();
        
        sendSuccess($products);
    } catch(Exception $e) {
        sendError('Failed to search products: ' . $e->getMessage());
    }
}

function getProductByBarcode($db, $barcode) {
    try {
        $query = "SELECT * FROM products WHERE barcode = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$barcode]);
        $product = $stmt->fetch();
        
        if ($product) {
            sendSuccess($product);
        } else {
            sendError('Product not found', 404);
        }
    } catch(Exception $e) {
        sendError('Failed to fetch product by barcode: ' . $e->getMessage());
    }
}

function addProduct($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'brand', 'model', 'category', 'price', 'cost_price'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                sendError("Field '$field' is required");
            }
        }
        
        $query = "INSERT INTO products (name, brand, model, category, price, cost_price, stock_quantity, min_stock_level, description, barcode, image_url) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['name'],
            $input['brand'],
            $input['model'],
            $input['category'],
            $input['price'],
            $input['cost_price'],
            $input['stock_quantity'] ?? 0,
            $input['min_stock_level'] ?? 5,
            $input['description'] ?? '',
            $input['barcode'] ?? null,
            $input['image_url'] ?? null
        ]);
        
        if ($result) {
            $productId = $db->lastInsertId();
            
            // Log stock movement if initial stock > 0
            if (($input['stock_quantity'] ?? 0) > 0) {
                logStockMovement($db, $productId, 'in', $input['stock_quantity'], 'adjustment', null, 'Initial stock');
            }
            
            sendSuccess(['id' => $productId], 'Product added successfully');
        } else {
            sendError('Failed to add product');
        }
    } catch(Exception $e) {
        sendError('Failed to add product: ' . $e->getMessage());
    }
}

function updateProduct($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            sendError('Product ID is required');
        }
        
        // Get current stock for comparison
        $currentQuery = "SELECT stock_quantity FROM products WHERE id = ?";
        $currentStmt = $db->prepare($currentQuery);
        $currentStmt->execute([$input['id']]);
        $currentProduct = $currentStmt->fetch();
        
        if (!$currentProduct) {
            sendError('Product not found', 404);
        }
        
        $query = "UPDATE products SET name = ?, brand = ?, model = ?, category = ?, price = ?, cost_price = ?, 
                  stock_quantity = ?, min_stock_level = ?, description = ?, barcode = ?, image_url = ?, updated_at = NOW() 
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['name'],
            $input['brand'],
            $input['model'],
            $input['category'],
            $input['price'],
            $input['cost_price'],
            $input['stock_quantity'] ?? 0,
            $input['min_stock_level'] ?? 5,
            $input['description'] ?? '',
            $input['barcode'] ?? null,
            $input['image_url'] ?? null,
            $input['id']
        ]);
        
        if ($result) {
            // Log stock adjustment if quantity changed
            $newStock = $input['stock_quantity'] ?? 0;
            $oldStock = $currentProduct['stock_quantity'];
            
            if ($newStock != $oldStock) {
                $difference = $newStock - $oldStock;
                $movementType = $difference > 0 ? 'in' : 'out';
                logStockMovement($db, $input['id'], $movementType, abs($difference), 'adjustment', null, 'Stock adjustment');
            }
            
            sendSuccess(null, 'Product updated successfully');
        } else {
            sendError('Failed to update product');
        }
    } catch(Exception $e) {
        sendError('Failed to update product: ' . $e->getMessage());
    }
}

function deleteProduct($db, $id) {
    try {
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            sendSuccess(null, 'Product deleted successfully');
        } else {
            sendError('Failed to delete product');
        }
    } catch(Exception $e) {
        sendError('Failed to delete product: ' . $e->getMessage());
    }
}

function logStockMovement($db, $productId, $type, $quantity, $refType, $refId, $notes) {
    try {
        $query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, notes) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$productId, $type, $quantity, $refType, $refId, $notes]);
    } catch(Exception $e) {
        // Log error but don't fail the main operation
        error_log('Failed to log stock movement: ' . $e->getMessage());
    }
}
?>