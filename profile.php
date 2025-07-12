<?php
session_start();

// ===================================================================
// 1. KEAMANAN & INISIALISASI
// ===================================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/yarac_db.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user_id = $_SESSION['user_id'];
$message = '';

// ===================================================================
// 2. LOGIKA PENANGANAN FORM (POST REQUESTS)
// ===================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An unknown error occurred.'];

    switch ($_POST['action']) {
        
        // --- Aksi: Update Profil Pengguna ---
        case 'update_profile':
            // Logika ini tidak perlu diubah dan sudah aman
            $user->first_name = trim($_POST['first_name']);
            $user->last_name = trim($_POST['last_name']);
            $user->phone = trim($_POST['phone']);
            $user->address = trim($_POST['address']);
            $user->id = $user_id;

            if ($user->updateProfile()) {
                $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
                $_SESSION['form_message'] = '<div class="alert alert-success">Profile updated successfully!</div>';
            } else {
                $_SESSION['form_message'] = '<div class="alert alert-error">Failed to update profile.</div>';
            }
            header('Location: profile.php#edit-profile');
            exit();

        // --- Aksi: Ganti Password ---
        case 'change_password':
             // Logika ini tidak perlu diubah dan sudah aman
            $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($currentUser && password_verify($_POST['current_password'], $currentUser['password'])) {
                if ($_POST['new_password'] === $_POST['confirm_new_password']) {
                    if (strlen($_POST['new_password']) >= 6) {
                        $user->id = $user_id;
                        if ($user->changePassword($_POST['new_password'])) {
                            $_SESSION['form_message'] = '<div class="alert alert-success">Password changed successfully.</div>';
                        } else {
                            $_SESSION['form_message'] = '<div class="alert alert-error">Failed to change password.</div>';
                        }
                    } else {
                         $_SESSION['form_message'] = '<div class="alert alert-error">New password must be at least 6 characters long.</div>';
                    }
                } else {
                    $_SESSION['form_message'] = '<div class="alert alert-error">New passwords do not match.</div>';
                }
            } else {
                $_SESSION['form_message'] = '<div class="alert alert-error">Incorrect current password.</div>';
            }
            header('Location: profile.php#change-password');
            exit();

        // --- Aksi: Konfirmasi Pesanan Diterima ---
        case 'confirm_receipt':
             // Logika ini tidak perlu diubah dan sudah aman
            $order_id = $_POST['order_id'] ?? 0;
            $stmt = $db->prepare("UPDATE orders SET status = 'completed' WHERE id = :order_id AND user_id = :user_id AND (status = 'delivered' OR status = 'shipped')");
            if ($stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]) && $stmt->rowCount() > 0) {
                $response = ['success' => true, 'message' => 'Order confirmed. You can now review the products.'];
            } else {
                $response['message'] = 'Failed to confirm order receipt or it was already confirmed.';
            }
            echo json_encode($response);
            exit();

        // --- Aksi: Kirim Ulasan Produk ---
 case 'submit_review':
            $product_id = $_POST['product_id'] ?? 0;
            $rating = $_POST['rating'] ?? 0;
            $review_text = trim($_POST['review'] ?? '');

            // Validasi: Pastikan user benar-benar membeli produk ini
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = :user_id AND oi.product_id = :product_id AND o.status = 'completed'");
            $check_stmt->execute([':user_id' => $user_id, ':product_id' => $product_id]);
            
            if ($check_stmt->fetchColumn() > 0) {
                // Cek apakah user sudah pernah review produk ini
                $exist_stmt = $db->prepare("SELECT id FROM product_reviews WHERE product_id = :product_id AND user_id = :user_id");
                $exist_stmt->execute([':product_id' => $product_id, ':user_id' => $user_id]);

                if($exist_stmt->fetch()){
                     $response = ['success' => false, 'message' => 'You have already reviewed this product.'];
                } else {
                    // 1. Masukkan ulasan baru
                    $insert_stmt = $db->prepare("INSERT INTO product_reviews (product_id, user_id, rating, review) VALUES (:product_id, :user_id, :rating, :review)");
                    if ($insert_stmt->execute([':product_id' => $product_id, ':user_id' => $user_id, ':rating' => $rating, ':review' => $review_text])) {
                        
                        // 2. [LANGKAH BARU] Update tabel products (Sinkronisasi)
                        $update_product_stmt = $db->prepare("
                            UPDATE products p 
                            SET 
                                p.rating = (SELECT AVG(pr.rating) FROM product_reviews pr WHERE pr.product_id = :product_id),
                                p.total_reviews = (SELECT COUNT(pr.id) FROM product_reviews pr WHERE pr.product_id = :product_id)
                            WHERE p.id = :product_id
                        ");
                        $update_product_stmt->execute([':product_id' => $product_id]);

                        $response = ['success' => true, 'message' => 'Thank you for your review!'];
                    } else {
                        $response['message'] = 'Failed to submit review.';
                    }
                }
            } else {
                $response['message'] = 'You can only review products you have purchased in a completed order.';
            }
            echo json_encode($response);
            exit();
    }
}

// ===================================================================
// 3. PENGAMBILAN DATA UNTUK DITAMPILKAN (GET REQUEST)
// ===================================================================

if (isset($_SESSION['form_message'])) {
    $message = $_SESSION['form_message'];
    unset($_SESSION['form_message']);
}

$user->getById($user_id);

// [PERUBAHAN LOGIKA PENGAMBILAN DATA]
// Query diubah untuk memeriksa ulasan berdasarkan user_id dan product_id saja.
$order_history_query = $db->prepare("
    SELECT 
        o.id as order_id, o.total_amount, o.status, o.created_at,
        oi.id as item_id, oi.product_id, oi.quantity, oi.price as item_price, oi.size,
        p.name as product_name, p.image as product_image,
        (SELECT COUNT(pr.id) FROM product_reviews pr WHERE pr.product_id = oi.product_id AND pr.user_id = o.user_id) as has_reviewed
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = :user_id
    ORDER BY o.created_at DESC
");
$order_history_query->execute([':user_id' => $user_id]);
$order_history_items = $order_history_query->fetchAll(PDO::FETCH_ASSOC);

$orders = [];
foreach ($order_history_items as $item) {
    if (!isset($orders[$item['order_id']])) {
        $orders[$item['order_id']] = [
            'details' => [ 'total_amount' => $item['total_amount'], 'status' => $item['status'], 'created_at' => $item['created_at'] ],
            'items' => []
        ];
    }
    $orders[$item['order_id']]['items'][] = $item;
}

$page_title = "My Profile - " . htmlspecialchars($_SESSION['user_name']);
include 'includes/header.php';
?>

<style>
.profile-page-container{padding:140px 20px 80px;background-color:var(--light-gray)}.profile-page-layout{display:flex;gap:30px;max-width:1200px;margin:0 auto;align-items:flex-start}.profile-sidebar{flex:0 0 280px;background:#fff;padding:25px;border-radius:20px;box-shadow:var(--shadow-medium);position:sticky;top:120px}.profile-main-content{flex-grow:1}.profile-sidebar h4{font-size:1.5rem;color:var(--forest-green);margin-bottom:20px;padding-bottom:15px;border-bottom:2px solid var(--light-gray)}.profile-nav a{display:flex;align-items:center;gap:15px;padding:16px 20px;color:var(--dark-gray);text-decoration:none;font-weight:600;border-radius:12px;margin-bottom:10px;transition:all var(--transition-medium);border:2px solid transparent}.profile-nav a:hover{background-color:var(--light-gray);color:var(--forest-green)}.profile-nav a.active{background-color:var(--forest-green);color:white;border-color:var(--olive-drab);transform:translateX(5px)}.profile-nav i{width:20px;text-align:center}.profile-content-section{display:none;background:#fff;padding:40px;border-radius:20px;box-shadow:var(--shadow-medium);animation:fadeIn .4s ease-in-out}.profile-content-section.active{display:block}.profile-content-section h3{font-size:2rem;color:var(--forest-green);margin-bottom:30px}.auth-form{max-width:100%}.form-row{display:grid;grid-template-columns:1fr 1fr;gap:25px}.form-group{margin-bottom:25px}.form-group label{display:block;margin-bottom:10px;font-weight:600;color:var(--dark-gray)}.form-group input{width:100%;padding:15px;border:2px solid #ddd;border-radius:10px;font-size:1rem}.form-group input:disabled{background-color:#f5f5f5;cursor:not-allowed}.btn-auth{width:auto;padding:15px 40px;font-size:1rem;margin-top:10px;border-radius:10px}.alert{padding:15px 20px;border-radius:10px;margin-bottom:20px;font-weight:500}.alert-success{background-color:#d4edda;color:#155724;border:1px solid #c3e6cb}.alert-error{background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb}.order-card{border:1px solid #e0e0e0;border-radius:15px;margin-bottom:30px;overflow:hidden;transition:box-shadow .3s}.order-card:hover{box-shadow:var(--shadow-medium)}.order-header{background-color:#f8f9fa;padding:20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e0e0e0}.order-header h4{margin:0;font-size:1.2rem}.order-header span{font-size:1rem}.status-badge{padding:5px 12px;border-radius:20px;color:white;font-size:.8rem;font-weight:700;text-transform:capitalize}.status-pending{background-color:#f39c12}.status-processing{background-color:#3498db}.status-shipped{background-color:#9b59b6}.status-delivered{background-color:#27ae60}.status-completed{background-color:var(--forest-green)}.status-cancelled{background-color:#e74c3c}.order-body{padding:10px 20px}.order-item{display:flex;align-items:center;gap:20px;padding:15px 0;border-bottom:1px solid #f0f0f0}.order-item:last-child{border-bottom:none}.order-item img{width:80px;height:80px;object-fit:cover;border-radius:10px}.order-item-info{flex-grow:1}.order-item-info h5{margin:0 0 5px;font-size:1rem}.order-item-info p{margin:0;color:#777;font-size:.9rem}.review-form-container{display:none;margin-top:15px;padding-top:15px;border-top:1px dashed #ccc}.review-form-container.active{display:block}.star-rating{display:flex;flex-direction:row-reverse;justify-content:flex-end}.star-rating input[type=radio]{display:none}.star-rating label{font-size:1.5rem;color:#ddd;cursor:pointer;transition:color .2s}.star-rating input[type=radio]:checked~label,.star-rating label:hover,.star-rating label:hover~label{color:#ffd700}.review-form textarea{width:100%;min-height:80px;padding:10px;border-radius:8px;border:1px solid #ccc;margin-top:10px;resize:vertical}.btn-review{background-color:var(--olive-drab);color:white;padding:8px 15px;border-radius:8px;border:none;cursor:pointer;margin-top:10px}.already-reviewed{color:var(--success);font-style:italic;font-size:.9rem;display:flex;align-items:center;gap:8px}@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
</style>

<div class="profile-page-container">
    <div class="profile-page-layout">
        <aside class="profile-sidebar">
            <h4>My Account</h4>
            <nav class="profile-nav">
                <a href="#edit-profile" class="profile-nav-item"><i class="fas fa-user-edit"></i> Edit Profile</a>
                <a href="#order-history" class="profile-nav-item"><i class="fas fa-history"></i> Order History</a>
                <a href="#change-password" class="profile-nav-item"><i class="fas fa-key"></i> Change Password</a>
            </nav>
        </aside>

        <main class="profile-main-content">
            <?php if($message) echo $message; ?>

            <section id="edit-profile" class="profile-content-section">
                <h3>Edit Your Profile</h3>
                <form class="auth-form" method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($user->first_name); ?>" required></div>
                        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($user->last_name); ?>" required></div>
                    </div>
                    <div class="form-group"><label>Email (Cannot be changed)</label><input type="email" value="<?php echo htmlspecialchars($user->email); ?>" disabled></div>
                    <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="<?php echo htmlspecialchars($user->phone); ?>"></div>
                    <div class="form-group"><label>Address</label><input type="text" name="address" value="<?php echo htmlspecialchars($user->address); ?>"></div>
                    <button type="submit" class="btn-auth">Save Changes</button>
                </form>
            </section>

            <section id="order-history" class="profile-content-section">
                <h3>Your Order History</h3>
                <?php if (empty($orders)): ?>
                    <p>You have no past orders. <a href="products.php">Start shopping now!</a></p>
                <?php else: ?>
                    <?php foreach ($orders as $order_id => $order): ?>
                        <div class="order-card" id="order-card-<?php echo $order_id; ?>">
                            <div class="order-header">
                                <h4>Order #<?php echo $order_id; ?></h4>
                                <span class="status-badge status-<?php echo $order['details']['status']; ?>" id="status-<?php echo $order_id; ?>"><?php echo ucfirst($order['details']['status']); ?></span>
                            </div>
                            <div class="order-body">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <img src="assets/images/products/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                        <div class="order-item-info">
                                            <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                            <p>Qty: <?php echo $item['quantity']; ?> | Size: <?php echo $item['size']; ?></p>
                                            
                                            <div class="review-form-container" id="review-container-<?php echo $item['item_id']; ?>" <?php if($order['details']['status'] === 'completed' && !$item['has_reviewed']) echo 'style="display:block;"'; ?>>
                                                <?php if ($item['has_reviewed']): ?>
                                                    <p class="already-reviewed"><i class="fas fa-check-circle"></i> You have reviewed this product.</p>
                                                <?php else: ?>
                                                    <form class="review-form" onsubmit="submitReview(event)">
                                                        <input type="hidden" name="action" value="submit_review">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <div class="star-rating">
                                                            <input type="radio" id="5-stars-<?php echo $item['item_id']; ?>" name="rating" value="5" required/><label for="5-stars-<?php echo $item['item_id']; ?>">★</label>
                                                            <input type="radio" id="4-stars-<?php echo $item['item_id']; ?>" name="rating" value="4"/><label for="4-stars-<?php echo $item['item_id']; ?>">★</label>
                                                            <input type="radio" id="3-stars-<?php echo $item['item_id']; ?>" name="rating" value="3"/><label for="3-stars-<?php echo $item['item_id']; ?>">★</label>
                                                            <input type="radio" id="2-stars-<?php echo $item['item_id']; ?>" name="rating" value="2"/><label for="2-stars-<?php echo $item['item_id']; ?>">★</label>
                                                            <input type="radio" id="1-star-<?php echo $item['item_id']; ?>" name="rating" value="1"/><label for="1-star-<?php echo $item['item_id']; ?>">★</label>
                                                        </div>
                                                        <textarea name="review" placeholder="Share your thoughts about this product..." rows="3"></textarea>
                                                        <button type="submit" class="btn-review">Submit Review</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="order-footer" style="padding: 20px; text-align: right; background: #f8f9fa;">
                                <?php if ($order['details']['status'] === 'delivered' || $order['details']['status'] === 'shipped'): ?>
                                    <button class="btn-auth" id="btn-confirm-<?php echo $order_id; ?>" onclick="confirmReceipt(<?php echo $order_id; ?>, this)">
                                        <i class="fas fa-box-open"></i> Mark as Received
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section id="change-password" class="profile-content-section">
                <h3>Change Your Password</h3>
                <form class="auth-form" method="POST" action="profile.php">
                     <input type="hidden" name="action" value="change_password">
                    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
                    <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
                    <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_new_password" required></div>
                    <button type="submit" class="btn-auth">Update Password</button>
                </form>
            </section>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Navigasi tab
    const navLinks = document.querySelectorAll('.profile-nav-item');
    const sections = document.querySelectorAll('.profile-content-section');
    function showSection(targetId) {
        targetId = targetId || 'edit-profile';
        sections.forEach(s => s.classList.remove('active'));
        const activeSection = document.getElementById(targetId);
        if (activeSection) activeSection.classList.add('active');
        navLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + targetId));
    }
    navLinks.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const targetId = e.currentTarget.getAttribute('href').substring(1);
            history.pushState(null, '', '#' + targetId);
            showSection(targetId);
        });
    });
    window.addEventListener('popstate', () => showSection(window.location.hash.substring(1)));
    showSection(window.location.hash.substring(1));
});

// Fungsi Konfirmasi Pesanan
async function confirmReceipt(orderId, button) {
    if (!confirm('Are you sure you have received this order?')) return;
    button.disabled = true; button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';
    const formData = new FormData();
    formData.append('action', 'confirm_receipt');
    formData.append('order_id', orderId);

    try {
        const response = await fetch('profile.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            document.getElementById(`status-${orderId}`).textContent = 'Completed';
            document.getElementById(`status-${orderId}`).className = 'status-badge status-completed';
            button.style.display = 'none';
            const orderCard = document.getElementById(`order-card-${orderId}`);
            orderCard.querySelectorAll('.review-form-container').forEach(container => {
                if(!container.querySelector('.already-reviewed')) container.style.display = 'block';
            });
            alert(result.message);
        } else {
            alert('Error: ' + result.message);
            button.disabled = false; button.innerHTML = '<i class="fas fa-box-open"></i> Mark as Received';
        }
    } catch (error) {
        alert('An error occurred.');
        button.disabled = false; button.innerHTML = '<i class="fas fa-box-open"></i> Mark as Received';
    }
}

// Fungsi Kirim Ulasan
async function submitReview(event) {
    event.preventDefault();
    const form = event.target;
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true; button.textContent = 'Submitting...';
    const formData = new FormData(form);

    try {
        const response = await fetch('profile.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            form.parentElement.innerHTML = `<p class="already-reviewed"><i class="fas fa-check-circle"></i> Review submitted!</p>`;
        } else {
            alert('Error: ' + result.message);
            button.disabled = false; button.textContent = 'Submit Review';
        }
    } catch (error) {
        alert('An error occurred.');
        button.disabled = false; button.textContent = 'Submit Review';
    }
}
</script>

<?php include 'includes/footer.php'; ?>