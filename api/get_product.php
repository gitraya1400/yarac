<?php
// KODE FINAL DENGAN DEBUGGING ERROR AKTIF

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/yarac_db.php';
require_once '../classes/Product.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if(!$db){
         throw new Exception("Database connection failed");
    }

    $product = new Product($db);
    $product_id = intval($_GET['id']);

    if ($product->getById($product_id)) {
        $response = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'category' => $product->category,
            'gender' => $product->gender,
            'image' => $product->image,
            'stock' => $product->stock,
            'rating' => $product->rating,
            'total_reviews' => $product->total_reviews,
            'sizes' => $product->sizes,
            'featured' => $product->featured,
            'created_at' => $product->created_at
        ];
        echo json_encode($response);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Product with ID ' . $product_id . ' not found.']);
    }

} catch (Exception $e) {
    http_response_code(500); 
    // [PENTING] Menampilkan pesan error yang sebenarnya
    echo json_encode([
        'error' => 'An internal server error occurred.',
        'message' => $e->getMessage() 
    ]);
}
?>