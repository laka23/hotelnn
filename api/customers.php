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
            getCustomer($db, $_GET['id']);
        } elseif (isset($_GET['search'])) {
            searchCustomers($db, $_GET['search']);
        } elseif (isset($_GET['phone'])) {
            getCustomerByPhone($db, $_GET['phone']);
        } else {
            getAllCustomers($db);
        }
        break;
    
    case 'POST':
        addCustomer($db);
        break;
    
    case 'PUT':
        updateCustomer($db);
        break;
    
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteCustomer($db, $_GET['id']);
        } else {
            sendError('Customer ID required for deletion');
        }
        break;
    
    default:
        sendError('Method not allowed', 405);
}

function getAllCustomers($db) {
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $query = "SELECT c.*, 
                  COUNT(DISTINCT s.id) as total_sales,
                  COUNT(DISTINCT r.id) as total_repairs,
                  COALESCE(SUM(s.final_amount), 0) as total_spent
                  FROM customers c 
                  LEFT JOIN sales s ON c.id = s.customer_id 
                  LEFT JOIN repairs r ON c.id = r.customer_id 
                  GROUP BY c.id 
                  ORDER BY c.name 
                  LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $customers = $stmt->fetchAll();
        
        sendSuccess($customers);
    } catch(Exception $e) {
        sendError('Failed to fetch customers: ' . $e->getMessage());
    }
}

function getCustomer($db, $id) {
    try {
        $query = "SELECT c.*, 
                  COUNT(DISTINCT s.id) as total_sales,
                  COUNT(DISTINCT r.id) as total_repairs,
                  COALESCE(SUM(s.final_amount), 0) as total_spent
                  FROM customers c 
                  LEFT JOIN sales s ON c.id = s.customer_id 
                  LEFT JOIN repairs r ON c.id = r.customer_id 
                  WHERE c.id = ? 
                  GROUP BY c.id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        
        if ($customer) {
            // Get recent sales
            $salesQuery = "SELECT id, total_amount, final_amount, sale_date FROM sales WHERE customer_id = ? ORDER BY sale_date DESC LIMIT 10";
            $salesStmt = $db->prepare($salesQuery);
            $salesStmt->execute([$id]);
            $customer['recent_sales'] = $salesStmt->fetchAll();
            
            // Get recent repairs
            $repairsQuery = "SELECT id, device_brand, device_model, status, total_cost, created_at FROM repairs WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10";
            $repairsStmt = $db->prepare($repairsQuery);
            $repairsStmt->execute([$id]);
            $customer['recent_repairs'] = $repairsStmt->fetchAll();
            
            sendSuccess($customer);
        } else {
            sendError('Customer not found', 404);
        }
    } catch(Exception $e) {
        sendError('Failed to fetch customer: ' . $e->getMessage());
    }
}

function searchCustomers($db, $search) {
    try {
        $query = "SELECT c.*, 
                  COUNT(DISTINCT s.id) as total_sales,
                  COUNT(DISTINCT r.id) as total_repairs,
                  COALESCE(SUM(s.final_amount), 0) as total_spent
                  FROM customers c 
                  LEFT JOIN sales s ON c.id = s.customer_id 
                  LEFT JOIN repairs r ON c.id = r.customer_id 
                  WHERE c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? 
                  GROUP BY c.id 
                  ORDER BY c.name";
        
        $searchTerm = "%$search%";
        $stmt = $db->prepare($query);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $customers = $stmt->fetchAll();
        
        sendSuccess($customers);
    } catch(Exception $e) {
        sendError('Failed to search customers: ' . $e->getMessage());
    }
}

function getCustomerByPhone($db, $phone) {
    try {
        $query = "SELECT * FROM customers WHERE phone = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$phone]);
        $customer = $stmt->fetch();
        
        if ($customer) {
            sendSuccess($customer);
        } else {
            sendError('Customer not found', 404);
        }
    } catch(Exception $e) {
        sendError('Failed to fetch customer by phone: ' . $e->getMessage());
    }
}

function addCustomer($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($input['name']) || empty($input['name'])) {
            sendError('Customer name is required');
        }
        
        if (!isset($input['phone']) || empty($input['phone'])) {
            sendError('Customer phone is required');
        }
        
        // Check if phone already exists
        $checkQuery = "SELECT id FROM customers WHERE phone = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$input['phone']]);
        if ($checkStmt->fetch()) {
            sendError('Customer with this phone number already exists');
        }
        
        $query = "INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['name'],
            $input['phone'],
            $input['email'] ?? '',
            $input['address'] ?? ''
        ]);
        
        if ($result) {
            $customerId = $db->lastInsertId();
            sendSuccess(['id' => $customerId], 'Customer added successfully');
        } else {
            sendError('Failed to add customer');
        }
    } catch(Exception $e) {
        sendError('Failed to add customer: ' . $e->getMessage());
    }
}

function updateCustomer($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            sendError('Customer ID is required');
        }
        
        // Check if phone already exists for another customer
        if (isset($input['phone'])) {
            $checkQuery = "SELECT id FROM customers WHERE phone = ? AND id != ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$input['phone'], $input['id']]);
            if ($checkStmt->fetch()) {
                sendError('Another customer with this phone number already exists');
            }
        }
        
        $query = "UPDATE customers SET name = ?, phone = ?, email = ?, address = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['name'],
            $input['phone'],
            $input['email'] ?? '',
            $input['address'] ?? '',
            $input['id']
        ]);
        
        if ($result) {
            sendSuccess(null, 'Customer updated successfully');
        } else {
            sendError('Failed to update customer');
        }
    } catch(Exception $e) {
        sendError('Failed to update customer: ' . $e->getMessage());
    }
}

function deleteCustomer($db, $id) {
    try {
        // Check if customer has any sales or repairs
        $checkQuery = "SELECT 
                       (SELECT COUNT(*) FROM sales WHERE customer_id = ?) as sales_count,
                       (SELECT COUNT(*) FROM repairs WHERE customer_id = ?) as repairs_count";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$id, $id]);
        $counts = $checkStmt->fetch();
        
        if ($counts['sales_count'] > 0 || $counts['repairs_count'] > 0) {
            sendError('Cannot delete customer with existing sales or repairs');
        }
        
        $query = "DELETE FROM customers WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            sendSuccess(null, 'Customer deleted successfully');
        } else {
            sendError('Failed to delete customer');
        }
    } catch(Exception $e) {
        sendError('Failed to delete customer: ' . $e->getMessage());
    }
}
?>