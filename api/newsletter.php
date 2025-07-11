<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/yarac_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

$email = trim($_POST['email']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if email already exists
    $check_query = "SELECT id FROM newsletter_subscribers WHERE email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already subscribed']);
        exit;
    }
    
    // Insert new subscriber
    $insert_query = "INSERT INTO newsletter_subscribers (email) VALUES (?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(1, $email);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully subscribed to newsletter']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to subscribe']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
