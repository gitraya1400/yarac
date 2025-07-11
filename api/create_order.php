<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

require_once '../config/yarac_db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Anda harus login untuk membuat pesanan.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cart']) || empty($data['cart'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data keranjang tidak valid atau kosong.']);
    exit;
}

$cart = $data['cart'];
$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit;
}

try {
    $db->beginTransaction();

    $total_amount = 0;
    foreach ($cart as $item) {
        $stmt = $db->prepare("SELECT price FROM products WHERE id = :id");
        $stmt->bindParam(':id', $item['id'], PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $total_amount += $product['price'] * $item['quantity'];
        } else {
            throw new Exception('Produk dengan ID ' . htmlspecialchars($item['id']) . ' tidak ditemukan.');
        }
    }

    $user_stmt = $db->prepare("SELECT address, phone FROM users WHERE id = :id");
    $user_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $shipping_address = $user_data['address'] ?: 'Alamat belum diisi';
    $phone = $user_data['phone'] ?: 'Telepon belum diisi';

    $order_stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, phone) VALUES (:user_id, :total_amount, :shipping_address, :phone)");
    $order_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $order_stmt->bindParam(':total_amount', $total_amount);
    $order_stmt->bindParam(':shipping_address', $shipping_address);
    $order_stmt->bindParam(':phone', $phone);
    $order_stmt->execute();

    $order_id = $db->lastInsertId();

    $item_stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, size) VALUES (:order_id, :product_id, :quantity, :price, :size)");
    foreach ($cart as $item) {
        $item_stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $item_stmt->bindValue(':product_id', $item['id'], PDO::PARAM_INT);
        $item_stmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
        $item_stmt->bindValue(':price', $item['price']);
        $item_stmt->bindValue(':size', $item['size']);
        $item_stmt->execute();
    }

    $db->commit();

    $whatsappMessage = "🛍️ *Yarac Fashion Store - Pesanan Baru*\n\n";
    $whatsappMessage .= "Nomor Pesanan: *#YRC" . $order_id . "*\n";
    $whatsappMessage .= "Total: *Rp " . number_format($total_amount, 0, ',', '.') . "*\n\n";
    foreach ($cart as $item) {
        $whatsappMessage .= "• " . htmlspecialchars($item['name']) . " (Ukuran: " . htmlspecialchars($item['size']) . ", Jml: " . $item['quantity'] . ")\n";
    }
    $whatsappMessage .= "\nTerima kasih telah berbelanja!";

    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dibuat!',
        'whatsappNumber' => '6281234567890',
        'whatsappMessage' => $whatsappMessage
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan Server: ' . $e->getMessage()]);
}
?>