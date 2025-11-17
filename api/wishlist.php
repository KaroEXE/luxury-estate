<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to manage wishlist';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($method === 'GET') {
        // Get user's wishlist
        $query = "SELECT p.*, pi.image_url, 
                  GROUP_CONCAT(pi.image_url) as all_images
                  FROM user_favorites uf
                  JOIN properties p ON uf.property_id = p.id
                  LEFT JOIN property_images pi ON p.id = pi.property_id
                  WHERE uf.user_id = :user_id
                  GROUP BY p.id
                  ORDER BY uf.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the response
        foreach ($wishlist as &$property) {
            $property['images'] = $property['all_images'] ? explode(',', $property['all_images']) : [];
            unset($property['all_images']);
        }
        
        $response['success'] = true;
        $response['data'] = $wishlist;
        
    } elseif ($method === 'POST') {
        // Add to wishlist
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['property_id'])) {
            $response['message'] = 'Property ID is required';
            echo json_encode($response);
            exit;
        }
        
        $property_id = $data['property_id'];
        
        // Check if already in wishlist
        $check_query = "SELECT id FROM user_favorites 
                       WHERE user_id = :user_id AND property_id = :property_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':property_id', $property_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $response['message'] = 'Property already in wishlist';
            echo json_encode($response);
            exit;
        }
        
        // Add to wishlist
        $query = "INSERT INTO user_favorites (user_id, property_id) 
                  VALUES (:user_id, :property_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':property_id', $property_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Added to wishlist';
        } else {
            $response['message'] = 'Failed to add to wishlist';
        }
        
    } elseif ($method === 'DELETE') {
        // Remove from wishlist
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['property_id'])) {
            $response['message'] = 'Property ID is required';
            echo json_encode($response);
            exit;
        }
        
        $property_id = $data['property_id'];
        
        $query = "DELETE FROM user_favorites 
                  WHERE user_id = :user_id AND property_id = :property_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':property_id', $property_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Removed from wishlist';
        } else {
            $response['message'] = 'Failed to remove from wishlist';
        }
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>
