<?php
// api/search_suggestions.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/yarac_db.php';

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get search query
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    // Validate query
    if (empty($query) || strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    // Clean and escape query
    $search_term = '%' . $query . '%';
    
    // Prepare SQL with LIMIT untuk performa
    $sql = "SELECT 
                id, 
                name, 
                category, 
                price, 
                image,
                CASE 
                    WHEN LOWER(name) LIKE LOWER(?) THEN 1
                    WHEN LOWER(category) LIKE LOWER(?) THEN 2
                    WHEN LOWER(description) LIKE LOWER(?) THEN 3
                    ELSE 4
                END as relevance_score
            FROM products 
            WHERE (LOWER(name) LIKE LOWER(?) 
                   OR LOWER(category) LIKE LOWER(?) 
                   OR LOWER(description) LIKE LOWER(?))
            AND status = 'active'
            ORDER BY relevance_score ASC, name ASC
            LIMIT 8";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $search_term, $search_term, $search_term,  // untuk relevance score
        $search_term, $search_term, $search_term   // untuk WHERE clause
    ]);
    
    $suggestions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $suggestions[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'price' => (int)$row['price'],
            'image' => $row['image'] ?: 'placeholder.jpg'
        ];
    }
    
    echo json_encode($suggestions);
    
} catch (PDOException $e) {
    error_log("Live Search Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Live Search Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>