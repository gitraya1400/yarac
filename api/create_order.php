<?php
// Memulai sesi untuk mengakses data login pengguna
session_start();

// Mengatur header respons sebagai JSON untuk komunikasi dengan JavaScript
header('Content-Type: application/json');

// --- Keamanan: Hanya izinkan metode POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan. Hanya POST yang didukung.']);
    exit;
}

// Memuat file konfigurasi database
require_once '../config/yarac_db.php';

// --- Keamanan: Validasi Sesi Pengguna ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // 403 Forbidden
    echo json_encode(['success' => false, 'message' => 'Anda harus login untuk membuat pesanan.']);
    exit;
}

// Mengambil data mentah (raw data) yang dikirim dari JavaScript
$data = json_decode(file_get_contents('php://input'), true);

// --- Validasi Data Input ---
if (!$data || !isset($data['cart']) || empty($data['cart'])) {
    http_response_code(400); // 400 Bad Request
    echo json_encode(['success' => false, 'message' => 'Data keranjang tidak valid atau kosong.']);
    exit;
}

// Inisialisasi variabel dari data yang diterima
$cart = $data['cart'];
$notes = isset($data['notes']) ? trim($data['notes']) : null; // Mengambil catatan, jika ada
$user_id = $_SESSION['user_id'];

// Membuat koneksi ke database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit;
}

try {
    // Memulai transaksi database untuk memastikan semua query berhasil atau tidak sama sekali
    $db->beginTransaction();

    // Menghitung total harga dari semua item di keranjang
    $total_amount = 0;
    foreach ($cart as $item) {
        $stmt = $db->prepare("SELECT price FROM products WHERE id = :id");
        $stmt->bindParam(':id', $item['id'], PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $total_amount += $product['price'] * $item['quantity'];
        } else {
            // Jika produk tidak ditemukan, batalkan transaksi
            throw new Exception('Produk dengan ID ' . htmlspecialchars($item['id']) . ' tidak ditemukan.');
        }
    }

    // Mengambil data alamat dan telepon pengguna untuk pengiriman
    $user_stmt = $db->prepare("SELECT address, phone FROM users WHERE id = :id");
    $user_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $shipping_address = $user_data['address'] ?: 'Alamat belum diisi';
    $phone = $user_data['phone'] ?: 'Telepon belum diisi';

    // Menyimpan data pesanan utama ke tabel `orders`
    $order_stmt = $db->prepare(
        "INSERT INTO orders (user_id, total_amount, shipping_address, phone, notes) 
         VALUES (:user_id, :total_amount, :shipping_address, :phone, :notes)"
    );
    
    // Mengikat semua parameter ke query SQL
    $order_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $order_stmt->bindParam(':total_amount', $total_amount);
    $order_stmt->bindParam(':shipping_address', $shipping_address);
    $order_stmt->bindParam(':phone', $phone);
    $order_stmt->bindParam(':notes', $notes);
    $order_stmt->execute();

    // Mendapatkan ID dari pesanan yang baru saja dibuat
    $order_id = $db->lastInsertId();

    // Menyimpan setiap item di keranjang ke tabel `order_items`
    $item_stmt = $db->prepare(
        "INSERT INTO order_items (order_id, product_id, quantity, price, size) 
         VALUES (:order_id, :product_id, :quantity, :price, :size)"
    );
    foreach ($cart as $item) {
        $item_stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $item_stmt->bindValue(':product_id', $item['id'], PDO::PARAM_INT);
        $item_stmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
        $item_stmt->bindValue(':price', $item['price']);
        $item_stmt->bindValue(':size', $item['size']);
        $item_stmt->execute();
    }

    // Jika semua query berhasil, konfirmasi transaksi
    $db->commit();

  // api/create_order.php

        // Membuat pesan WhatsApp dari sudut pandang PELANGGAN
        $whatsappMessage = "Halo *Yarac Fashion Store*,\n\nSaya ingin mengonfirmasi pesanan saya dengan rincian berikut:\n\n";
        $whatsappMessage .= "Nomor Pesanan: *#YRC" . $order_id . "*\n";
        $whatsappMessage .= "Total Pembayaran: *Rp " . number_format($total_amount, 0, ',', '.') . "*\n\n";
        $whatsappMessage .= "*Rincian Produk yang Dipesan:*\n";
        foreach ($cart as $item) {
            $whatsappMessage .= "• " . htmlspecialchars($item['name']) . " (Ukuran: " . htmlspecialchars($item['size']) . ", Jumlah: " . $item['quantity'] . ")\n";
        }

        // Menambahkan catatan dari pelanggan
        if (!empty($notes)) {
            $whatsappMessage .= "\n*Catatan dari Saya:*\n" . htmlspecialchars($notes) . "\n";
        }

        $whatsappMessage .= "\nMohon untuk segera diproses. Terima kasih!";

    // Mengirimkan respons sukses kembali ke JavaScript
    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dibuat!',
        'whatsappNumber' => '6282261134482', // Ganti dengan nomor WhatsApp 
        'whatsappMessage' => $whatsappMessage
    ]);

} catch (Exception $e) {
    // Jika terjadi error di mana saja dalam blok 'try', batalkan semua perubahan
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    // Kirim respons error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan Server: ' . $e->getMessage()]);
}
?>