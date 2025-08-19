<?php
// Database configuration for Mobile Phone Shop POS System
class Database {
    private $host = 'localhost';
    private $db_name = 'mobile_shop_pos';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Helper function to get database connection
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Response helper function
function sendResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Error handler
function sendError($message, $status = 400) {
    sendResponse(['error' => $message, 'success' => false], $status);
}

// Success handler
function sendSuccess($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    sendResponse($response);
}
?>