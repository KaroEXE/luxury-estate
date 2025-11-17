<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

error_log("=== New Request ===");
error_log("Request URI: " . $_SERVER['REQUEST_URI']);

try {
    require_once 'config/database.php';
    
    class Auth {
        public function requireLogin() {
            return true;
        }
    }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
function exitJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

class PropertyAPI {
    private $conn;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auth = new Auth();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                $this->handleGet($action);
                break;
            case 'POST':
                $this->handlePost($action);
                break;
            case 'PUT':
                $this->handlePut($action);
                break;
            case 'DELETE':
                $this->handleDelete($action);
                break;
            default:
                $this->sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'list':
                $this->getProperties();
                break;
            case 'single':
                $this->getProperty();
                break;
            case 'search':
                $this->searchProperties();
                break;
            default:
                $this->getProperties();
        }
    }
    
    private function handlePost($action) {
        $this->auth->requireLogin();
        
        switch ($action) {
            case 'create':
                $this->createProperty();
                break;
            case 'favorite':
                $this->toggleFavorite();
                break;
            case 'contact':
                $this->submitContact();
                break;
            default:
                $this->sendResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }
    
    private function handlePut($action) {
        $this->auth->requireRole('agent');
        
        switch ($action) {
            case 'update':
                $this->updateProperty();
                break;
            default:
                $this->sendResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }
    
    private function handleDelete($action) {
        $this->auth->requireRole('agent');
        
        switch ($action) {
            case 'delete':
                $this->deleteProperty();
                break;
            default:
                $this->sendResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }
    
    private function getProperties() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT p.*, u.first_name, u.last_name, u.phone as agent_phone 
                    FROM properties p 
                    LEFT JOIN users u ON p.agent_id = u.id 
                    WHERE p.status = 'available' 
                    ORDER BY p.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $properties = $stmt->fetchAll();
            
            foreach ($properties as &$property) {
                $property['images'] = $this->getPropertyImages($property['id']);
            }
            
            $this->sendResponse(['success' => true, 'data' => $properties]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    
    private function getProperty() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->sendResponse(['success' => false, 'message' => 'Property ID required'], 400);
            return;
        }
        
        try {
            $sql = "SELECT p.*, u.first_name, u.last_name, u.phone as agent_phone, u.email as agent_email 
                    FROM properties p 
                    LEFT JOIN users u ON p.agent_id = u.id 
                    WHERE p.id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $property = $stmt->fetch();
            
            if (!$property) {
                $this->sendResponse(['success' => false, 'message' => 'Property not found'], 404);
                return;
            }
            
            $property['images'] = $this->getPropertyImages($property['id']);
            
            $this->sendResponse(['success' => true, 'data' => $property]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    
    private function searchProperties() {
        try {
            $search = $_GET['search'] ?? '';
            $type = $_GET['type'] ?? '';
            $minPrice = $_GET['min_price'] ?? '';
            $maxPrice = $_GET['max_price'] ?? '';
            $location = $_GET['location'] ?? '';
            
            $sql = "SELECT p.*, u.first_name, u.last_name, u.phone as agent_phone 
                    FROM properties p 
                    LEFT JOIN users u ON p.agent_id = u.id 
                    WHERE p.status = 'available'";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (p.title LIKE :search OR p.description LIKE :search OR p.features LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            if (!empty($type)) {
                $sql .= " AND p.property_type = :type";
                $params[':type'] = $type;
            }
            
            if (!empty($minPrice)) {
                $sql .= " AND p.price >= :min_price";
                $params[':min_price'] = $minPrice;
            }
            
            if (!empty($maxPrice)) {
                $sql .= " AND p.price <= :max_price";
                $params[':max_price'] = $maxPrice;
            }
            
            if (!empty($location)) {
                $sql .= " AND p.location LIKE :location";
                $params[':location'] = "%$location%";
            }
            
            $sql .= " ORDER BY p.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $properties = $stmt->fetchAll();
            
            // Get images for each property
            foreach ($properties as &$property) {
                $property['images'] = $this->getPropertyImages($property['id']);
            }
            
            $this->sendResponse(['success' => true, 'data' => $properties]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    
    private function getPropertyImages($propertyId) {
        try {
            $sql = "SELECT image_url FROM property_images WHERE property_id = :property_id ORDER BY is_primary DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':property_id', $propertyId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function createProperty() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $requiredFields = ['title', 'price', 'property_type', 'bedrooms', 'bathrooms', 'area_sqft', 'location', 'address'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->sendResponse(['success' => false, 'message' => "Field $field is required"], 400);
                    return;
                }
            }
            
            $user = $this->auth->getCurrentUser();
            
            $sql = "INSERT INTO properties (title, description, price, property_type, bedrooms, bathrooms, area_sqft, location, address, latitude, longitude, features, agent_id) 
                    VALUES (:title, :description, :price, :property_type, :bedrooms, :bathrooms, :area_sqft, :location, :address, :latitude, :longitude, :features, :agent_id)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':property_type', $data['property_type']);
            $stmt->bindParam(':bedrooms', $data['bedrooms']);
            $stmt->bindParam(':bathrooms', $data['bathrooms']);
            $stmt->bindParam(':area_sqft', $data['area_sqft']);
            $stmt->bindParam(':location', $data['location']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':latitude', $data['latitude']);
            $stmt->bindParam(':longitude', $data['longitude']);
            $stmt->bindParam(':features', $data['features']);
            $stmt->bindParam(':agent_id', $user['id']);
            
            if ($stmt->execute()) {
                $propertyId = $this->conn->lastInsertId();
                
                // Handle images if provided
                if (!empty($data['images'])) {
                    $this->savePropertyImages($propertyId, $data['images']);
                }
                
                $this->sendResponse(['success' => true, 'message' => 'Property created successfully', 'property_id' => $propertyId]);
            } else {
                $this->sendResponse(['success' => false, 'message' => 'Failed to create property'], 500);
            }
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    
    private function savePropertyImages($propertyId, $images) {
        try {
            $sql = "INSERT INTO property_images (property_id, image_url, is_primary) VALUES (:property_id, :image_url, :is_primary)";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($images as $index => $imageUrl) {
                $stmt->bindParam(':property_id', $propertyId);
                $stmt->bindParam(':image_url', $imageUrl);
                $stmt->bindValue(':is_primary', $index === 0 ? 1 : 0);
                $stmt->execute();
            }
        } catch (Exception $e) {
            // Log error but don't fail the property creation
            error_log("Failed to save property images: " . $e->getMessage());
        }
    }
    
    private function toggleFavorite() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $propertyId = $data['property_id'] ?? '';
            
            if (empty($propertyId)) {
                $this->sendResponse(['success' => false, 'message' => 'Property ID required'], 400);
                return;
            }
            
            $user = $this->auth->getCurrentUser();
            
            // Check if already favorited
            $sql = "SELECT id FROM user_favorites WHERE user_id = :user_id AND property_id = :property_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->bindParam(':property_id', $propertyId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Remove from favorites
                $sql = "DELETE FROM user_favorites WHERE user_id = :user_id AND property_id = :property_id";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':user_id', $user['id']);
                $stmt->bindParam(':property_id', $propertyId);
                $stmt->execute();
                
                $this->sendResponse(['success' => true, 'message' => 'Removed from favorites', 'favorited' => false]);
            } else {
                // Add to favorites
                $sql = "INSERT INTO user_favorites (user_id, property_id) VALUES (:user_id, :property_id)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':user_id', $user['id']);
                $stmt->bindParam(':property_id', $propertyId);
                $stmt->execute();
                
                $this->sendResponse(['success' => true, 'message' => 'Added to favorites', 'favorited' => true]);
            }
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    
    private function submitContact() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $requiredFields = ['name', 'email', 'message'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->sendResponse(['success' => false, 'message' => "Field $field is required"], 400);
                    return;
                }
            }
            
            $sql = "INSERT INTO contact_inquiries (name, email, phone, message, property_id) VALUES (:name, :email, :phone, :message, :property_id)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':message', $data['message']);
            $stmt->bindParam(':property_id', $data['property_id']);
            
            if ($stmt->execute()) {
                $this->sendResponse(['success' => true, 'message' => 'Contact inquiry submitted successfully']);
            } else {
                $this->sendResponse(['success' => false, 'message' => 'Failed to submit inquiry'], 500);
            }
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

    $api = new PropertyAPI();
    $api->handleRequest();
} catch (Throwable $e) {
    $error = [
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log('Error: ' . print_r($error, true));
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    echo json_encode($error);
    exit;
}
?>
