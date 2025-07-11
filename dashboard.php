<?php
session_start();
require_once 'config/yarac_db.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$db_instance = new Database();
$db = $db_instance->getConnection();

// ===================================================================
// BAGIAN UNTUK MENANGANI SEMUA PERMINTAAN (ADD/UPDATE/DELETE)
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An error occurred.'];
    $entity = $_POST['entity'] ?? '';

    // --- LOGIKA UNTUK DELETE ---
    if (isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $table_name = ($entity === 'product') ? 'products' : 'advertisements';
            if ($table_name) {
                // Hapus juga file gambar jika ada
                $image_dir = ($entity === 'product') ? 'assets/images/products/' : 'assets/images/ads/';
                $stmt = $db->prepare("SELECT image FROM $table_name WHERE id = :id");
                $stmt->execute([':id' => $id]);
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($row['image']) && $row['image'] !== 'placeholder.jpg' && file_exists($image_dir . $row['image'])) {
                        unlink($image_dir . $row['image']);
                    }
                }
                
                $query = "DELETE FROM $table_name WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => ucfirst($entity) . ' deleted successfully.'];
                } else {
                    $response['message'] = 'Failed to delete ' . $entity . '.';
                }
            } else {
                $response['message'] = 'Invalid entity for deletion.';
            }
        } else {
            $response['message'] = 'ID is required for deletion.';
        }
    }
    // --- LOGIKA UNTUK ADD/UPDATE ---
    else {
        $id = $_POST['id'] ?? null;
        $image_filename = $_POST['existing_image'] ?? '';

        // [LOGIKA UPLOAD GAMBAR UMUM]
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = ($entity === 'product') ? 'assets/images/products/' : 'assets/images/ads/';
            if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }
            
            $image_filename = uniqid() . '-' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $image_filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $response['message'] = 'Failed to upload new image.';
                echo json_encode($response);
                exit();
            }
        }
        
        // [PERBAIKAN] Penanganan untuk entitas 'product'
        if ($entity === 'product') {
            $name = $_POST['name'] ?? ''; $price = $_POST['price'] ?? 0; $stock = $_POST['stock'] ?? 0;
            $category = $_POST['category'] ?? 'casual'; $gender = $_POST['gender'] ?? 'unisex';
            
            // Jika tidak ada gambar baru diupload dan ini adalah penambahan baru, gunakan placeholder
            if (empty($image_filename) && !$id) {
                $image_filename = 'placeholder.jpg';
            }

            if ($id) { // Update
                $query = "UPDATE products SET name=:name, price=:price, stock=:stock, category=:category, gender=:gender, image=:image WHERE id=:id";
            } else { // Add
                $query = "INSERT INTO products (name, price, stock, category, gender, image) VALUES (:name, :price, :stock, :category, :gender, :image)";
            }
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':image', $image_filename);
        } 
        elseif ($entity === 'advertisement') {
            if ($id) { // Hanya Update
                $title = $_POST['title'] ?? ''; $link = $_POST['link'] ?? '';
                $sort_order = $_POST['sort_order'] ?? 0; $active = $_POST['active'] ?? 1;
                $query = "UPDATE advertisements SET title=:title, link=:link, image=:image, sort_order=:sort_order, active=:active WHERE id=:id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':link', $link); 
                $stmt->bindParam(':image', $image_filename);
                $stmt->bindParam(':sort_order', $sort_order);
                $stmt->bindParam(':active', $active);
            } else { $stmt = null; $response['message'] = 'Adding new advertisements is disabled.'; }
        }

        if (isset($stmt)) {
            if ($id) $stmt->bindParam(':id', $id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => ucfirst($entity) . ' saved successfully!'];
            } else {
                $response['message'] = 'Failed to save ' . $entity . '.';
            }
        }
    }
    
    echo json_encode($response);
    exit();
}

// ===================================================================
// BAGIAN UNTUK MENGAMBIL DATA DAN MENAMPILKAN HALAMAN (READ)
// ===================================================================
$stats_query = "SELECT (SELECT COUNT(*) FROM products) as total_products, (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users, (SELECT COUNT(*) FROM orders) as total_orders, (SELECT SUM(total_amount) FROM orders) as total_revenue";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$latest_orders_query = "SELECT o.id, u.first_name, u.last_name, o.total_amount, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5";
$latest_orders_stmt = $db->prepare($latest_orders_query);
$latest_orders_stmt->execute();
$latest_orders = $latest_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

$low_stock_query = "SELECT id, name, category, price, stock FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock_products = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

$products_data = $db->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$ads_data = $db->query("SELECT * FROM advertisements ORDER BY sort_order ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$users_data = $db->query("SELECT id, first_name, last_name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$json_data = json_encode(['products' => $products_data, 'advertisements' => $ads_data, 'users' => $users_data]);
$page_title = "Admin Dashboard - Yarac Fashion Store";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-header"><h3><i class="fas fa-tachometer-alt"></i> Admin Panel</h3></div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="#dashboard" class="nav-item active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="#products" class="nav-item"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="#advertisements" class="nav-item"><i class="fas fa-bullhorn"></i> Advertisements</a></li>
                <li><a href="#users" class="nav-item"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="index.php" class="nav-item"><i class="fas fa-arrow-left"></i> Back to Site</a></li>
                <li><a href="#" onclick="logout()" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-main">
        <section id="dashboard" class="admin-section active">
            <div class="section-header">
                <h1>Dashboard Overview</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><h3><?php echo $stats['total_products'] ?? 0; ?></h3><p>Total Products</p></div>
                <div class="stat-card"><h3><?php echo $stats['total_users'] ?? 0; ?></h3><p>Total Users</p></div>
                <div class="stat-card"><h3><?php echo $stats['total_orders'] ?? 0; ?></h3><p>Total Orders</p></div>
                <div class="stat-card"><h3>Rp <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></h3><p>Total Revenue</p></div>
            </div>
            <div class="section-header" style="margin-top: 50px;"><h2>Latest Orders</h2></div>
            <div class="table-container">
                <table class="admin-table">
                    <thead><tr><th>Order ID</th><th>Customer</th><th>Total Amount</th><th>Status</th><th>Order Date</th></tr></thead>
                    <tbody>
                        <?php if (!empty($latest_orders)): foreach ($latest_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                            <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                            <td><?php echo ucfirst($order['status']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5">No recent orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="section-header" style="margin-top: 50px;"><h2>Low Stock Products</h2></div>
            <div class="table-container">
                <table class="admin-table">
                    <thead><tr><th>Product Name</th><th>Category</th><th>Stock</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (!empty($low_stock_products)): foreach ($low_stock_products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo ucfirst($product['category']); ?></td>
                            <td><span style="color: red; font-weight: bold;"><?php echo $product['stock']; ?></span></td>
                            <td><button class="btn-action" onclick="openModal('product', <?php echo $product['id']; ?>)">Edit</button></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4">No products currently low on stock.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="products" class="admin-section">
            <div class="section-header">
                <h1>Product Management</h1>
                <button class="btn-action btn-add-new" onclick="openModal('product')">+ Add New Product</button>
            </div>
            <div class="table-container" id="products-content"><p>Loading products...</p></div>
        </section>

        <section id="advertisements" class="admin-section">
            <div class="section-header"><h1>Advertisement Management</h1></div>
            <div class="table-container" id="advertisements-content"><p>Loading advertisements...</p></div>
        </section>

        <section id="users" class="admin-section">
            <div class="section-header"><h1>User Management</h1></div>
            <div class="table-container" id="users-content"><p>Loading users...</p></div>
        </section>
    </main>
</div>

<div id="product-modal" class="admin-modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('product')">&times;</span>
        <h3 id="modal-title">Add/Edit Product</h3>
        <form id="product-form" enctype="multipart/form-data">
            <input type="hidden" id="product-id" name="id">
            <input type="hidden" name="entity" value="product">
            <input type="hidden" id="product-existing-image" name="existing_image">

            <div class="image-preview-container">
                <div class="image-preview" id="product-image-preview">
                    <img src="assets/images/placeholder.jpg" alt="Image Preview">
                    <span class="image-preview-text">Image Preview</span>
                </div>
                <label for="product-image-upload" class="btn-upload"><i class="fas fa-upload"></i> Choose Image</label>
                <input type="file" id="product-image-upload" name="image" accept="image/*">
            </div>

            <div class="form-group"><label for="product-name">Product Name</label><input type="text" id="product-name" name="name" required></div>
            <div class="form-row">
                <div class="form-group"><label for="product-price">Price</label><input type="number" step="1000" id="product-price" name="price" required></div>
                <div class="form-group"><label for="product-stock">Stock</label><input type="number" id="product-stock" name="stock" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="product-category">Category</label><select id="product-category" name="category" required><option value="shirts">Shirts</option><option value="casual">Casual</option><option value="formal">Formal</option></select></div>
                <div class="form-group"><label for="product-gender">Gender</label><select id="product-gender" name="gender" required><option value="men">Men</option><option value="women">Women</option><option value="unisex">Unisex</option></select></div>
            </div>
            <button type="submit" class="btn-action">Save Product</button>
        </form>
    </div>
</div>

<div id="ad-modal" class="admin-modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('ad')">&times;</span>
        <h3 id="ad-modal-title">Edit Advertisement</h3>
        <form id="ad-form" enctype="multipart/form-data">
            <input type="hidden" id="ad-id" name="id"><input type="hidden" name="entity" value="advertisement"><input type="hidden" id="ad-existing-image" name="existing_image">
            <div class="image-preview-container">
                <div class="image-preview" id="ad-image-preview"><img src="assets/images/placeholder.jpg" alt="Image Preview"><span class="image-preview-text">Image Preview</span></div>
                <label for="ad-image-upload" class="btn-upload"><i class="fas fa-upload"></i> Choose Image</label><input type="file" id="ad-image-upload" name="image" accept="image/*">
            </div>
            <div class="form-group"><label for="ad-title">Ad Title</label><input type="text" id="ad-title" name="title" required></div>
            <div class="form-group"><label for="ad-link">Link URL</label><input type="text" id="ad-link" name="link" placeholder="e.g., products.php?category=sale"></div>
            <div class="form-row">
                <div class="form-group"><label for="ad-sort-order">Sort Order</label><input type="number" id="ad-sort-order" name="sort_order" value="0"></div>
                <div class="form-group"><label for="ad-active">Active</label><select id="ad-active" name="active"><option value="1">Yes</option><option value="0">No</option></select></div>
            </div>
            <button type="submit" class="btn-action">Save Advertisement</button>
        </form>
    </div>
</div>

<script>
    const adminData = <?php echo $json_data; ?>;
    const productModal = document.getElementById('product-modal');
    const adModal = document.getElementById('ad-modal');

    // [PERBAIKAN] JavaScript untuk openModal produk
    function openModal(type, itemId = null) {
        const modal = (type === 'product') ? productModal : adModal;
        if (!modal) return;
        
        const form = modal.querySelector('form');
        form.reset();
        const previewImg = modal.querySelector('.image-preview img');

        if (type === 'product') {
            document.getElementById('modal-title').innerText = itemId ? 'Edit Product' : 'Add New Product';
            document.getElementById('product-id').value = itemId || '';
            const existingImageInput = document.getElementById('product-existing-image');

            if (itemId) {
                const data = adminData.products.find(p => p.id == itemId);
                if(data) {
                    document.getElementById('product-name').value = data.name;
                    document.getElementById('product-price').value = data.price;
                    document.getElementById('product-stock').value = data.stock;
                    document.getElementById('product-category').value = data.category;
                    document.getElementById('product-gender').value = data.gender;
                    existingImageInput.value = data.image;
                    previewImg.src = data.image && data.image !== 'placeholder.jpg' ? `assets/images/products/${data.image}` : 'assets/images/placeholder.jpg';
                }
            } else {
                existingImageInput.value = '';
                previewImg.src = 'assets/images/placeholder.jpg';
            }
        } else if (type === 'ad') {
            if (itemId) {
                const data = adminData.advertisements.find(a => a.id == itemId);
                if (data) {
                    document.getElementById('ad-modal-title').innerText = 'Edit Advertisement';
                    document.getElementById('ad-id').value = data.id;
                    document.getElementById('ad-title').value = data.title;
                    document.getElementById('ad-link').value = data.link;
                    document.getElementById('ad-sort-order').value = data.sort_order;
                    document.getElementById('ad-active').value = data.active;
                    document.getElementById('ad-existing-image').value = data.image;
                    previewImg.src = data.image ? `assets/images/ads/${data.image}` : 'assets/images/placeholder.jpg';
                }
            } else { return; }
        }
        modal.style.display = 'block';
    }

    function closeModal(type) {
        const modal = (type === 'product') ? productModal : adModal;
        modal.style.display = 'none';
    }

    async function deleteItem(id, entity) {
        // Fungsi delete tidak berubah
        if (!confirm(`Are you sure you want to delete this ${entity}?`)) return;
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        formData.append('id', id);
        formData.append('entity', entity);
        try {
            const response = await fetch('dashboard.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) location.reload();
        } catch (error) {
            alert('An error occurred during deletion.');
            console.error(error);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Event listener untuk form produk (tidak berubah)
        document.getElementById('product-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('dashboard.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.success) { closeModal('product'); location.reload(); }
            } catch (error) { alert('An error occurred while saving the product.'); }
        });

        // Event listener untuk form iklan (tidak berubah)
        document.getElementById('ad-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('dashboard.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.success) { closeModal('ad'); location.reload(); }
            } catch (error) { alert('An error occurred while saving the advertisement.'); console.error(error); }
        });

        // [PERBAIKAN] Event listener untuk preview gambar PRODUK
        document.getElementById('product-image-upload').addEventListener('change', function(e){
            const previewImg = document.getElementById('product-image-preview').querySelector('img');
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => { previewImg.src = event.target.result; }
                reader.readAsDataURL(file);
            }
        });

        // Event listener untuk preview gambar IKLAN
        document.getElementById('ad-image-upload').addEventListener('change', function(e){
            const previewImg = document.getElementById('ad-image-preview').querySelector('img');
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => { previewImg.src = event.target.result; }
                reader.readAsDataURL(file);
            }
        });

        // Logika Navigasi (tidak berubah)
        const navLinks = document.querySelectorAll('.sidebar-nav a.nav-item');
        function showSection(targetId) {
            document.querySelectorAll('.admin-section').forEach(section => section.classList.remove('active'));
            const activeSection = document.getElementById(targetId);
            if (activeSection) activeSection.classList.add('active');
            navLinks.forEach(link => link.classList.toggle('active', link.getAttribute('href') === '#' + targetId));
            if (targetId !== 'dashboard') loadContent(targetId);
        }
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href').substring(1);
                if (document.getElementById(targetId)) { e.preventDefault(); window.location.hash = targetId; }
            });
        });
        window.addEventListener('hashchange', () => { showSection(window.location.hash.substring(1) || 'dashboard'); });
        showSection(window.location.hash.substring(1) || 'dashboard');
    });

    // [PERBAIKAN] Fungsi loadContent untuk menampilkan gambar produk
    function loadContent(type) {
        const container = document.getElementById(`${type}-content`);
        const data = adminData[type];
        if (!data || !container) { console.error(`Data or container for type '${type}' not found.`); return; }
        let headerHTML = '', bodyHTML = '';

        if (type === 'products') {
            headerHTML = `<tr><th>Image</th><th>Name</th><th>Price</th><th>Stock</th><th>Category</th><th>Actions</th></tr>`;
            bodyHTML = data.map(item => `
                <tr>
                    <td><img src="assets/images/products/${item.image || 'placeholder.jpg'}" alt="${item.name}" class="table-image"></td>
                    <td>${item.name || 'N/A'}</td>
                    <td>Rp ${Number(item.price).toLocaleString('id-ID')}</td>
                    <td>${item.stock}</td>
                    <td>${item.category}</td>
                    <td><button class="btn-action" onclick="openModal('product', ${item.id})">Edit</button> <button class="btn-action" onclick="deleteItem(${item.id}, 'product')">Delete</button></td>
                </tr>`).join('');
        } else if (type === 'advertisements') {
            headerHTML = `<tr><th>Image</th><th>Title</th><th>Link</th><th>Active</th><th>Actions</th></tr>`;
            bodyHTML = data.map(item => `
                <tr>
                    <td><img src="assets/images/ads/${item.image || 'placeholder.jpg'}" alt="${item.title}" class="table-image"></td>
                    <td>${item.title}</td>
                    <td><a href="${item.link}" target="_blank">${item.link}</a></td>
                    <td>${item.active == 1 ? 'Yes' : 'No'}</td>
                    <td><button class="btn-action" onclick="openModal('ad', ${item.id})">Edit</button> <button class="btn-action" onclick="deleteItem(${item.id}, 'advertisement')">Delete</button></td>
                </tr>`).join('');
        } else if (type === 'users') {
             headerHTML = `<tr><th>Name</th><th>Email</th><th>Role</th><th>Joined At</th></tr>`;
             bodyHTML = data.map(item => `
                <tr><td>${item.first_name} ${item.last_name}</td><td>${item.email}</td><td>${item.role}</td><td>${new Date(item.created_at).toLocaleDateString('id-ID')}</td></tr>`).join('');
        }
        container.innerHTML = `<table class="admin-table"><thead>${headerHTML}</thead><tbody>${bodyHTML}</tbody></table>`;
    }
    
    function logout() {
        if (!confirm("Are you sure you want to logout?")) return;
        fetch("api/logout.php", { method: "POST" })
            .then(res => res.json())
            .then(data => { if(data.success) window.location.href = "login.php"; })
            .catch(err => { console.error(err); window.location.href = "login.php"; });
    }
</script>

</body>
</html>