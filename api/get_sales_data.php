<?php
// api/get_sales_data.php
session_start();
header('Content-Type: application/json');

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized Access']);
    exit();
}

require_once '../config/yarac_db.php';

$period = $_GET['period'] ?? 'monthly'; // Default ke bulanan

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

try {
    $sql = "";
    if ($period === 'daily') {
        // Data 7 hari terakhir
        $sql = "SELECT DATE_FORMAT(created_at, '%a') as label, SUM(total_amount) as value 
                FROM orders 
                WHERE status = 'delivered' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(created_at), DATE_FORMAT(created_at, '%a')
                ORDER BY DATE(created_at) ASC";
        $stmt = $db->prepare($sql);
    } elseif ($period === 'yearly') {
        // Data per tahun (5 tahun terakhir)
        $sql = "SELECT YEAR(created_at) as label, SUM(total_amount) as value 
                FROM orders 
                WHERE status = 'delivered' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR)
                GROUP BY YEAR(created_at)
                ORDER BY YEAR(created_at) ASC";
        $stmt = $db->prepare($sql);
    } else { // 'monthly'
        // Data per bulan dalam tahun ini
        $sql = "SELECT MONTHNAME(created_at) as label, SUM(total_amount) as value 
                FROM orders 
                WHERE status = 'delivered' AND YEAR(created_at) = YEAR(CURDATE())
                GROUP BY MONTH(created_at), MONTHNAME(created_at)
                ORDER BY MONTH(created_at) ASC";
        $stmt = $db->prepare($sql);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($results, 'label');
    // Ubah label bulan menjadi 3 huruf jika periodenya bulanan
    if ($period === 'monthly') {
        $labels = array_map(function($label) { return substr($label, 0, 3); }, $labels);
    }

    $values = array_map('intval', array_column($results, 'value'));
    
    echo json_encode(['labels' => $labels, 'values' => $values]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>