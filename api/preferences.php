<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to manage preferences';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($method === 'GET') {
        // Get user preferences
        $query = "SELECT dark_mode, language, currency 
                  FROM user_preferences 
                  WHERE user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$preferences) {
            // Create default preferences
            $insert_query = "INSERT INTO user_preferences (user_id, dark_mode, language, currency) 
                            VALUES (:user_id, FALSE, 'en', 'USD')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $user_id);
            $insert_stmt->execute();
            
            $preferences = ['dark_mode' => false, 'language' => 'en', 'currency' => 'USD'];
        }
        
        $response['success'] = true;
        $response['data'] = $preferences;
        
    } elseif ($method === 'POST') {
        // Update user preferences
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if preferences exist
        $check_query = "SELECT id FROM user_preferences WHERE user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing preferences
            $updates = [];
            $params = [':user_id' => $user_id];
            
            if (isset($data['dark_mode'])) {
                $updates[] = "dark_mode = :dark_mode";
                $params[':dark_mode'] = $data['dark_mode'] ? 1 : 0;
            }
            if (isset($data['language'])) {
                $updates[] = "language = :language";
                $params[':language'] = $data['language'];
            }
            if (isset($data['currency'])) {
                $updates[] = "currency = :currency";
                $params[':currency'] = $data['currency'];
            }
            
            if (!empty($updates)) {
                $query = "UPDATE user_preferences SET " . implode(', ', $updates) . 
                        " WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Preferences updated';
                } else {
                    $response['message'] = 'Failed to update preferences';
                }
            }
        } else {
            // Create new preferences
            $dark_mode = isset($data['dark_mode']) ? ($data['dark_mode'] ? 1 : 0) : 0;
            $language = isset($data['language']) ? $data['language'] : 'en';
            $currency = isset($data['currency']) ? $data['currency'] : 'USD';
            
            $query = "INSERT INTO user_preferences (user_id, dark_mode, language, currency) 
                      VALUES (:user_id, :dark_mode, :language, :currency)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':dark_mode', $dark_mode);
            $stmt->bindParam(':language', $language);
            $stmt->bindParam(':currency', $currency);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Preferences created';
            } else {
                $response['message'] = 'Failed to create preferences';
            }
        }
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>
