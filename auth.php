<?php
session_start();
require_once 'includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
        echo json_encode([
            'logged_in' => true,
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'] ?? ''
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    
    if (isset($_POST['first_name'])) {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $phone = $_POST['phone'] ?? null;
        
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit;
        }
        
        $result = $auth->register($username, $email, $password, $first_name, $last_name, $phone);
        echo json_encode($result);
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required']);
            exit;
        }
        
        $result = $auth->login($username, $password);
        echo json_encode($result);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
