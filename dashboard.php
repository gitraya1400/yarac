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

// Logika untuk menangani form submission (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An error occurred.'];
    $action = $_POST['action'];

    // --- LOGIKA UNTUK UPDATE STATUS ORDER ---
    if ($action === 'update_status') {
        $order_id = $_POST['order_id'] ?? null;
        $status = $_POST['status'] ?? null;
        if ($order_id && $status) {
            $stmt = $db->prepare("UPDATE orders SET status = :status WHERE id = :id");
            if ($stmt->execute([':status' => $status, ':id' => $order_id])) {
                $response = ['success' => true, 'message' => 'Order status updated successfully.'];
            } else {
                $response['message'] = 'Failed to update order status.';
            }
        } else {
            $response['message'] = 'Order ID and status are required.';
        }
    }
    // --- LOGIKA UNTUK DELETE ---
    elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        $entity = $_POST['entity'] ?? '';
        if ($id && $entity) {
            $table_name = ($entity === 'product') ? 'products' : 'advertisements';
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
            if ($stmt->execute([':id' => $id])) {
                $response = ['success' => true, 'message' => ucfirst($entity) . ' deleted successfully.'];
            } else {
                $response['message'] = 'Failed to delete ' . $entity . '.';
            }
        } else {
            $response['message'] = 'ID and entity are required for deletion.';
        }
    }
    // --- LOGIKA UNTUK ADD/UPDATE ---
    elseif ($action === 'save') {
        $entity = $_POST['entity'] ?? '';
        $id = $_POST['id'] ?? null;
        $image_filename = $_POST['existing_image'] ?? '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = ($entity === 'product') ? 'assets/images/products/' : 'assets/images/ads/';
            if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }
            $image_filename = uniqid() . '-' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $image_filename;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $response['message'] = 'Failed to upload new image.';
                echo json_encode($response); exit();
            }
        }
        
        if ($entity === 'product') {
            $name = $_POST['name'] ?? ''; $price = $_POST['price'] ?? 0; $stock = $_POST['stock'] ?? 0;
            $category = $_POST['category'] ?? 'casual'; $gender = $_POST['gender'] ?? 'unisex';
            if (empty($image_filename) && !$id) { $image_filename = 'placeholder.jpg'; }
            $sql = $id ? "UPDATE products SET name=:name, price=:price, stock=:stock, category=:category, gender=:gender, image=:image WHERE id=:id"
                       : "INSERT INTO products (name, price, stock, category, gender, image) VALUES (:name, :price, :stock, :category, :gender, :image)";
            $stmt = $db->prepare($sql);
            $params = [':name' => $name, ':price' => $price, ':stock' => $stock, ':category' => $category, ':gender' => $gender, ':image' => $image_filename];
        } 
        elseif ($entity === 'advertisement') {
            $title = $_POST['title'] ?? ''; $link = $_POST['link'] ?? '';
            $sort_order = $_POST['sort_order'] ?? 0; $active = $_POST['active'] ?? 1;
            $sql = $id ? "UPDATE advertisements SET title=:title, link=:link, image=:image, sort_order=:sort_order, active=:active WHERE id=:id"
                       : "INSERT INTO advertisements (title, link, image, sort_order, active) VALUES (:title, :link, :image, :sort_order, :active)";
            $stmt = $db->prepare($sql);
            $params = [':title' => $title, ':link' => $link, ':image' => $image_filename, ':sort_order' => $sort_order, ':active' => $active];
        }

        if (isset($stmt) && isset($params)) {
            if ($id) $params[':id'] = $id;
            if ($stmt->execute($params)) {
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

// Statistik
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM products) as total_products, 
    (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users, 
    (SELECT COUNT(*) FROM orders) as total_orders, 
    (SELECT SUM(total_amount) FROM orders WHERE status = 'delivered') as total_revenue, 
    (SELECT COUNT(*) FROM users WHERE role = 'user' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())) as new_users_monthly, 
    (SELECT COUNT(*) FROM orders WHERE status = 'processing') as processing_orders,
    (SELECT SUM(total_amount) FROM orders WHERE status = 'delivered' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())) as monthly_revenue,
    (SELECT COUNT(*) FROM orders WHERE status = 'cancelled') as cancelled_orders
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
$average_order_value = ($stats['total_orders'] > 0 && $stats['total_revenue'] > 0) ? $stats['total_revenue'] / $stats['total_orders'] : 0;

// Data untuk tabel-tabel di tab lain
$latest_orders_query = "SELECT o.id, u.first_name, u.last_name, o.total_amount, o.status, o.created_at, o.notes FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5";
$latest_orders = $db->query($latest_orders_query)->fetchAll(PDO::FETCH_ASSOC);
$products_data = $db->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$ads_data = $db->query("SELECT * FROM advertisements ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$subscribers_data = $db->query("SELECT email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$users_data = $db->query("SELECT id, first_name, last_name, email, role, phone, address, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);$orders_data = $db->query("SELECT o.id, o.user_id, o.total_amount, o.status, o.created_at, o.notes, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);$order_items_data = $db->query("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id")->fetchAll(PDO::FETCH_ASSOC);

// Gabungkan semua data ke dalam JSON untuk JavaScript
$json_data = json_encode([
    'products' => $products_data, 
    'advertisements' => $ads_data, 
    'users' => $users_data,
    'orders' => $orders_data,
    'order_items' => $order_items_data,
    'subscribers' => $subscribers_data
]);

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
        <div class="sidebar-header"><h3><i class="fas fa-shield-alt"></i> Admin Panel</h3></div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="#dashboard" class="nav-item active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="#products" class="nav-item"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="#orders" class="nav-item"><i class="fas fa-receipt"></i> Orders</a></li>
                <li><a href="#advertisements" class="nav-item"><i class="fas fa-bullhorn"></i> Advertisements</a></li>
                <li><a href="#users" class="nav-item"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="#subscribers" class="nav-item"><i class="fas fa-paper-plane"></i> Subscribers</a></li>
                <li><a href="index.php" class="nav-item" target="_blank"><i class="fas fa-store"></i> Back to Site</a></li>
                <li><a href="#" onclick="logout()" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-main">
        <section id="dashboard" class="admin-section active">
            <div class="section-header"><h1>Dashboard Overview</h1><p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p></div>
            <div class="stats-grid">
                <div class="stat-card"><h3>Rp <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></h3><p>Total Revenue (Delivered)</p></div>
                <div class="stat-card"><h3>Rp <?php echo number_format($average_order_value, 0, ',', '.'); ?></h3><p>Average Order Value</p></div>
                <div class="stat-card"><h3>Rp <?php echo number_format($stats['monthly_revenue'] ?? 0, 0, ',', '.'); ?></h3><p>Revenue (This Month)</p></div>
                <div class="stat-card"><h3><?php echo $stats['cancelled_orders'] ?? 0; ?></h3><p>Cancelled Orders</p></div>
                <div class="stat-card"><h3><?php echo $stats['total_products'] ?? 0; ?></h3><p>Total Products</p></div>
                <div class="stat-card"><h3><?php echo $stats['total_users'] ?? 0; ?></h3><p>Total Users</p></div>
                <div class="stat-card"><h3><?php echo $stats['new_users_monthly'] ?? 0; ?></h3><p>New Users (This Month)</p></div>
                <div class="stat-card"><h3><?php echo $stats['total_orders'] ?? 0; ?></h3><p>Total Orders</p></div>
                <div class="stat-card"><h3><?php echo $stats['processing_orders'] ?? 0; ?></h3><p>Processing Orders</p></div>
            </div>

            <div class="chart-container">
                <div class="chart-header">
                    <h2>Sales Dynamics</h2>
                    <div class="chart-filters">
                        <button class="filter-btn active" onclick="loadChartData('monthly')">This Month</button>
                        <button class="filter-btn" onclick="loadChartData('daily')">This Week</button>
                        <button class="filter-btn" onclick="loadChartData('yearly')">This Year</button>
                    </div>
                </div>
                <div id="chart-area"> 
                    <div class="chart-wrapper">
                        </div>
                </div>
            </div>

            <div style="margin-top: 50px;">
                <div class="section-header"><h2>Latest Orders</h2></div>
                <div class="table-container">
                    <table class="admin-table">
                        <thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Notes</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (!empty($latest_orders)): foreach ($latest_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                <td class="notes-cell" title="<?php echo htmlspecialchars($order['notes']); ?>">
                                    <?php echo !empty($order['notes']) ? substr(htmlspecialchars($order['notes']), 0, 30) . '...' : '-'; ?>
                                </td>
                                <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="5">No recent orders found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="products" class="admin-section"><div class="section-header"><h1>Product Management</h1><button class="btn-action btn-add-new" onclick="openModal('product')"><i class="fas fa-plus"></i> Add New Product</button></div><div class="table-container" id="products-content"><p>Loading products...</p></div></section>
        <section id="orders" class="admin-section"><div class="section-header"><h1>Order Management</h1></div><div class="table-container" id="orders-content"><p>Loading orders...</p></div></section>
        <section id="advertisements" class="admin-section"><div class="section-header"><h1>Advertisement Management</h1><button class="btn-action btn-add-new" onclick="openModal('advertisement')"><i class="fas fa-plus"></i> Add New Ad</button></div><div class="table-container" id="advertisements-content"><p>Loading advertisements...</p></div></section>
        <section id="users" class="admin-section"><div class="section-header"><h1>User Management</h1></div><div class="table-container" id="users-content"><p>Loading users...</p></div></section>
        <section id="users" class="admin-section">
            <div class="section-header"><h1>User Management</h1></div>
            <div class="table-container" id="users-content"><p>Loading users...</p></div>
        </section>

        <section id="subscribers" class="admin-section">
            <div class="section-header"><h1>Newsletter Subscribers</h1></div>
            <div class="table-container" id="subscribers-content"><p>Loading subscribers...</p></div>
        </section>

    </main>
</div>
    </main>
</div>
<div id="product-modal" class="admin-modal"><div class="modal-content"><span class="close-modal" onclick="closeModal('product')">&times;</span><h3 id="modal-title-product">Add/Edit Product</h3><form id="product-form" enctype="multipart/form-data"><input type="hidden" name="action" value="save"><input type="hidden" id="product-id" name="id"><input type="hidden" name="entity" value="product"><input type="hidden" id="product-existing-image" name="existing_image"><div class="image-preview-container"><div class="image-preview" id="product-image-preview"><img src="assets/images/placeholder.jpg" alt="Image Preview"><span class="image-preview-text">Image Preview</span></div><label for="product-image-upload" class="btn-upload"><i class="fas fa-upload"></i> Choose Image</label><input type="file" id="product-image-upload" name="image" accept="image/*"></div><div class="form-group"><label for="product-name">Product Name</label><input type="text" id="product-name" name="name" required></div><div class="form-row"><div class="form-group"><label for="product-price">Price</label><input type="number" step="1000" id="product-price" name="price" required></div><div class="form-group"><label for="product-stock">Stock</label><input type="number" id="product-stock" name="stock" required></div></div><div class="form-row"><div class="form-group"><label for="product-category">Category</label><select id="product-category" name="category" required><option value="shirts">Shirts</option><option value="casual">Casual</option><option value="formal">Formal</option></select></div><div class="form-group"><label for="product-gender">Gender</label><select id="product-gender" name="gender" required><option value="men">Men</option><option value="women">Women</option><option value="unisex">Unisex</option></select></div></div><button type="submit" class="btn-action">Save Product</button></form></div></div>
<div id="advertisement-modal" class="admin-modal"><div class="modal-content"><span class="close-modal" onclick="closeModal('advertisement')">&times;</span><h3 id="modal-title-advertisement">Add/Edit Advertisement</h3><form id="ad-form" enctype="multipart/form-data"><input type="hidden" name="action" value="save"><input type="hidden" id="ad-id" name="id"><input type="hidden" name="entity" value="advertisement"><input type="hidden" id="ad-existing-image" name="existing_image"><div class="image-preview-container"><div class="image-preview" id="ad-image-preview"><img src="assets/images/placeholder.jpg" alt="Image Preview"><span class="image-preview-text">Image Preview</span></div><label for="ad-image-upload" class="btn-upload"><i class="fas fa-upload"></i> Choose Image</label><input type="file" id="ad-image-upload" name="image" accept="image/*"></div><div class="form-group"><label for="ad-title">Ad Title</label><input type="text" id="ad-title" name="title" required></div><div class="form-group"><label for="ad-link">Link URL</label><input type="text" id="ad-link" name="link" placeholder="e.g., products.php?category=sale"></div><div class="form-row"><div class="form-group"><label for="ad-sort-order">Sort Order</label><input type="number" id="ad-sort-order" name="sort_order" value="0"></div><div class="form-group"><label for="ad-active">Active</label><select id="ad-active" name="active"><option value="1">Yes</option><option value="0">No</option></select></div></div><button type="submit" class="btn-action">Save Advertisement</button></form></div></div>
<div id="detail-modal" class="admin-modal"><div class="modal-content" id="detail-modal-content"><span class="close-modal" onclick="closeModal('detail')">&times;</span><h3 id="detail-modal-title">Details</h3><div id="detail-modal-body"></div></div></div>

<script>
    const adminData = <?php echo $json_data; ?>;

    document.addEventListener('DOMContentLoaded', () => {
        // Muat data chart default (bulanan) saat halaman pertama kali dibuka
        loadChartData('monthly');

        // Event listener untuk form
        document.getElementById('product-form').addEventListener('submit', (e) => handleFormSubmit(e, 'product'));
        document.getElementById('ad-form').addEventListener('submit', (e) => handleFormSubmit(e, 'advertisement'));

        // Event listener untuk preview gambar
        setupImagePreview('product-image-upload', 'product-image-preview');
        setupImagePreview('ad-image-upload', 'ad-image-preview');

        // Logika navigasi tab
        const navLinks = document.querySelectorAll('.sidebar-nav a.nav-item');
        function showSection(targetId) {
            document.querySelectorAll('.admin-section').forEach(section => section.classList.remove('active'));
            const activeSection = document.getElementById(targetId);
            if (activeSection) {
                activeSection.classList.add('active');
                if(targetId !== 'dashboard') loadContentForTab(targetId);
            }
            navLinks.forEach(link => {
                if (link.href.includes('#')) {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + targetId);
                }
            });
        }
        navLinks.forEach(link => {
            if (link.href.includes('#')) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    window.location.hash = targetId;
                });
            }
        });
        window.addEventListener('hashchange', () => { showSection(window.location.hash.substring(1) || 'dashboard'); });
        showSection(window.location.hash.substring(1) || 'dashboard');
    });

    // --- FUNGSI BARU UNTUK CHART ---
    async function loadChartData(period) {
        document.querySelectorAll('.chart-filters .filter-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.chart-filters .filter-btn[onclick="loadChartData('${period}')"]`).classList.add('active');
        const chartArea = document.getElementById('chart-area');
        chartArea.innerHTML = '<p>Loading chart data...</p>';
        try {
            const response = await fetch(`api/get_sales_data.php?period=${period}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            if (data.labels && data.values) {
                renderChart(data.labels, data.values);
            } else {
                chartArea.innerHTML = '<p>No sales data available for this period.</p>';
            }
        } catch (error) {
            console.error('Failed to load chart data:', error);
            chartArea.innerHTML = '<p>Error loading chart data. Please try again.</p>';
        }
    }

    function renderChart(labels, values) {
        const chartArea = document.getElementById('chart-area');
        const chartWrapper = document.createElement('div');
        chartWrapper.className = 'chart-wrapper';
        const maxValue = Math.max(...values, 1);
        if (labels.length === 0) {
            chartArea.innerHTML = '<p>No sales data available for this period.</p>';
            return;
        }
        labels.forEach((label, index) => {
            const value = values[index];
            const barHeight = (value / maxValue) * 100;
            const barGroup = document.createElement('div');
            barGroup.className = 'chart-bar-group';
            const bar = document.createElement('div');
            bar.className = 'chart-bar';
            bar.style.height = `${barHeight}%`;
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = `Rp ${value.toLocaleString('id-ID')}`;
            const barLabel = document.createElement('div');
            barLabel.className = 'chart-label';
            barLabel.textContent = label;
            bar.appendChild(tooltip);
            barGroup.appendChild(bar);
            barGroup.appendChild(barLabel);
            chartWrapper.appendChild(barGroup);
        });
        chartArea.innerHTML = '';
        chartArea.appendChild(chartWrapper);
    }
    // --- AKHIR FUNGSI CHART ---

    function setupImagePreview(inputId, previewId) {
        document.getElementById(inputId).addEventListener('change', e => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = event => document.querySelector(`#${previewId} img`).src = event.target.result;
                reader.readAsDataURL(file);
            }
        });
    }
    
    function loadContentForTab(type) {
        const container = document.getElementById(`${type}-content`);
        if (!container) return;
        let data = adminData[type];
        if(!data || data.length === 0) { container.innerHTML = '<p>No data available.</p>'; return; }
        let headerHTML = '', bodyHTML = '';
        if (type === 'orders') {
            headerHTML = `<tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Notes</th><th>Status</th><th>Actions</th></tr>`;
            bodyHTML = data.map(item => {
                const notesSnippet = item.notes ? (item.notes.substring(0, 25) + (item.notes.length > 25 ? '...' : '')) : '-';
                return `<tr><td>#${item.id}</td><td>${item.first_name} ${item.last_name}</td><td>Rp ${Number(item.total_amount).toLocaleString('id-ID')}</td><td class="notes-cell" title="${item.notes || 'No notes'}">${notesSnippet}</td><td><select class="order-status-select" data-order-id="${item.id}" onchange="updateOrderStatus(this)"><option value="pending" ${item.status === 'pending' ? 'selected' : ''}>Pending</option><option value="processing" ${item.status === 'processing' ? 'selected' : ''}>Processing</option><option value="shipped" ${item.status === 'shipped' ? 'selected' : ''}>Shipped</option><option value="delivered" ${item.status === 'delivered' ? 'selected' : ''}>Delivered</option><option value="completed" ${item.status === 'completed' ? 'selected' : ''}>Completed</option><option value="cancelled" ${item.status === 'cancelled' ? 'selected' : ''}>Cancelled</option></select></td><td><button class="btn-action" onclick="viewOrderDetails(${item.id})">Details</button></td></tr>`;
            }).join('');
        } else if (type === 'products') {
            headerHTML = `<tr><th>Image</th><th>Name</th><th>Price</th><th>Stock</th><th>Category</th><th>Actions</th></tr>`;
            bodyHTML = data.map(item => `<tr><td><img src="assets/images/products/${item.image || 'placeholder.jpg'}" class="table-image"></td><td>${item.name || 'N/A'}</td><td>Rp ${Number(item.price).toLocaleString('id-ID')}</td><td>${item.stock}</td><td>${item.category}</td><td><button class="btn-action" onclick="openModal('product', ${item.id})">Edit</button> <button class="btn-action btn-delete" onclick="deleteItem(${item.id}, 'product')">Delete</button></td></tr>`).join('');
        } else if (type === 'advertisements') {
            headerHTML = `<tr><th>Image</th><th>Title</th><th>Order</th><th>Active</th><th>Actions</th></tr>`;
            bodyHTML = data.map(item => `<tr><td><img src="assets/images/ads/${item.image || 'placeholder.jpg'}" class="table-image"></td><td>${item.title}</td><td>${item.sort_order}</td><td>${item.active == 1 ? 'Yes' : 'No'}</td><td><button class="btn-action" onclick="openModal('advertisement', ${item.id})">Edit</button> <button class="btn-action btn-delete" onclick="deleteItem(${item.id}, 'advertisement')">Delete</button></td></tr>`).join('');
        } else if (type === 'users') {
             headerHTML = `<tr><th>Name</th><th>Email</th><th>Role</th><th>Joined At</th><th>Actions</th></tr>`;
             bodyHTML = data.map(item => `<tr><td>${item.first_name} ${item.last_name}</td><td>${item.email}</td><td>${item.role}</td><td>${new Date(item.created_at).toLocaleDateString('id-ID')}</td><td><button class="btn-action" onclick="viewUserDetail(${item.id})">Detail</button></td></tr>`).join('');
        }
        else if (type === 'subscribers') {
        headerHTML = `<tr><th>#</th><th>Email</th><th>Subscribed At</th></tr>`;
        bodyHTML = data.map((item, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${item.email}</td>
                <td>${new Date(item.subscribed_at).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' })}</td>
            </tr>
        `).join('');
    }
        container.innerHTML = `<table class="admin-table"><thead>${headerHTML}</thead><tbody>${bodyHTML}</tbody></table>`;
    }
    
    function viewOrderDetails(orderId) {
        const order = adminData.orders.find(o => o.id == orderId);
        const items = adminData.order_items.filter(i => i.order_id == orderId);
        if (!order) return;
        let itemsHtml = items.map(item => `<div class="detail-item"><span>${item.product_name} (Size: ${item.size})</span><span>${item.quantity} x Rp ${Number(item.price).toLocaleString('id-ID')}</span></div>`).join('');
        let notesHtml = (order.notes && order.notes.trim() !== '') ? `<hr><h4>Customer Notes:</h4><p style="white-space: pre-wrap;">${order.notes}</p>` : '';
        const modalBody = `<h4>Customer: ${order.first_name} ${order.last_name} (${order.email})</h4><p><strong>Total:</strong> Rp ${Number(order.total_amount).toLocaleString('id-ID')}</p><p><strong>Date:</strong> ${new Date(order.created_at).toLocaleString('id-ID')}</p><div class="detail-list">${itemsHtml}</div>${notesHtml}`;
        document.getElementById('detail-modal-title').innerText = `Order Details #${orderId}`;
        document.getElementById('detail-modal-body').innerHTML = modalBody;
        document.getElementById('detail-modal').style.display = 'block';
    }

    // dashboard.php -> di dalam tag <script>

function viewUserDetail(userId) {
    const user = adminData.users.find(u => u.id == userId);
    const userOrders = adminData.orders.filter(o => o.user_id == userId);

    if (!user) {
        alert('User not found!');
        return;
    }

    // Bagian untuk menampilkan detail pengguna
    let userDetailsHtml = `
        <div class="user-details-grid">
            <div><strong><i class="fas fa-envelope"></i> Email:</strong> ${user.email}</div>
            <div><strong><i class="fas fa-phone"></i> Phone:</strong> ${user.phone || 'Not provided'}</div>
            <div><strong><i class="fas fa-calendar-alt"></i> Joined:</strong> ${new Date(user.created_at).toLocaleDateString('id-ID')}</div>
            <div><strong><i class="fas fa-user-tag"></i> Role:</strong> ${user.role}</div>
            <div class="address-detail"><strong><i class="fas fa-map-marker-alt"></i> Address:</strong> ${user.address || 'Not provided'}</div>
        </div>
        <hr>
    `;

    let ordersHtml = '<h4>Order History:</h4>';
    if (userOrders.length > 0) {
        ordersHtml += userOrders.map(order => `
            <div class="detail-item">
                <span>
                    Order #${order.id} - ${new Date(order.created_at).toLocaleDateString('id-ID')}
                </span>
                <span>
                    Rp ${Number(order.total_amount).toLocaleString('id-ID')} 
                    <span class="status-badge status-${order.status}">${order.status}</span>
                </span>
            </div>
        `).join('');
    } else {
        ordersHtml += '<p>This user has no order history.</p>';
    }

    const modalBody = userDetailsHtml + `<div class="detail-list">${ordersHtml}</div>`;
    
    document.getElementById('detail-modal-title').innerText = `History for ${user.first_name} ${user.last_name}`;
    document.getElementById('detail-modal-body').innerHTML = modalBody;
    document.getElementById('detail-modal').style.display = 'block';
}

    async function updateOrderStatus(selectElement) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('order_id', selectElement.dataset.orderId);
        formData.append('status', selectElement.value);
        try {
            const response = await fetch('dashboard.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) { alert('Status updated!'); } 
            else { alert('Failed to update status: ' + result.message); location.reload(); }
        } catch (error) { alert('An error occurred.'); }
    }
    
    function openModal(type, itemId = null) {
        const modal = document.getElementById(`${type}-modal`);
        if (!modal) return;
        const form = modal.querySelector('form');
        form.reset();
        const previewImg = modal.querySelector('.image-preview img');
        if (type === 'product') {
            document.getElementById('modal-title-product').innerText = itemId ? 'Edit Product' : 'Add New Product';
            document.getElementById('product-id').value = itemId || '';
            const existingImageInput = document.getElementById('product-existing-image');
            if (itemId) {
                const data = adminData.products.find(p => p.id == itemId);
                if(data) {
                    document.getElementById('product-name').value = data.name; document.getElementById('product-price').value = data.price; document.getElementById('product-stock').value = data.stock; document.getElementById('product-category').value = data.category; document.getElementById('product-gender').value = data.gender; existingImageInput.value = data.image; previewImg.src = data.image && data.image !== 'placeholder.jpg' ? `assets/images/products/${data.image}` : 'assets/images/placeholder.jpg';
                }
            } else {
                existingImageInput.value = ''; previewImg.src = 'assets/images/placeholder.jpg';
            }
        } else if (type === 'advertisement') {
            document.getElementById('modal-title-advertisement').innerText = itemId ? 'Edit Advertisement' : 'Add New Advertisement';
            document.getElementById('ad-id').value = itemId || '';
            document.getElementById('ad-existing-image').value = ''; previewImg.src = 'assets/images/placeholder.jpg';
            if(itemId){
                const data = adminData.advertisements.find(a => a.id == itemId);
                if (data) {
                    document.getElementById('ad-title').value = data.title; document.getElementById('ad-link').value = data.link; document.getElementById('ad-sort-order').value = data.sort_order; document.getElementById('ad-active').value = data.active; document.getElementById('ad-existing-image').value = data.image; previewImg.src = data.image ? `assets/images/ads/${data.image}` : 'assets/images/placeholder.jpg';
                }
            }
        }
        modal.style.display = 'block';
    }

    function closeModal(type) { if(document.getElementById(`${type}-modal`)) document.getElementById(`${type}-modal`).style.display = 'none'; }
    
    async function handleFormSubmit(event, entityType) {
        event.preventDefault();
        const formData = new FormData(event.target);
        try {
            const response = await fetch('dashboard.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) { 
                closeModal(entityType);
                location.reload(); 
            }
        } catch (error) { 
            alert(`An error occurred while saving the ${entityType}.`); 
        }
    }
    
    async function deleteItem(id, entity) {
        if (!confirm(`Are you sure you want to delete this ${entity}? This action cannot be undone.`)) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append('entity', entity);
        try {
            const response = await fetch('dashboard.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) location.reload();
        } catch (error) {
            alert(`An error occurred while deleting the ${entity}.`);
        }
    }

    function logout() { if (confirm("Are you sure you want to logout?")) { fetch("api/logout.php", { method: "POST" }).then(() => window.location.href = "login.php"); } }
</script>

<style>
/* CSS Tambahan untuk Kolom Notes dan Filter Chart */
.notes-cell { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: help; }
.status-badge{padding:5px 10px;border-radius:15px;color:white;font-size:12px;font-weight:700;text-transform:capitalize}.status-pending{background-color:#f39c12}.status-processing{background-color:#3498db}.status-shipped{background-color:#9b59b6}.status-delivered{background-color:#2ecc71}.status-completed{background-color:var(--forest-green)}.status-cancelled{background-color:#e74c3c}.btn-delete{background-color:#e74c3c!important}.btn-delete:hover{background-color:#c0392b!important}.order-status-select{padding:8px;border-radius:8px;border:1px solid #ccc;background-color:#f9f9f9;cursor:pointer}.detail-list{display:flex;flex-direction:column;gap:10px;margin-top:15px}.detail-item{display:flex;justify-content:space-between;padding:10px;background-color:#f8f9fa;border-radius:8px}#detail-modal-content{max-width:700px}
.chart-filters{display:flex;gap:10px}.filter-btn{background-color:var(--light-gray);border:1px solid #ddd;padding:8px 15px;border-radius:20px;font-weight:600;cursor:pointer;transition:all .3s ease;color:var(--dark-gray)}.filter-btn:hover{background-color:var(--moss-green);color:white}.filter-btn.active{background-color:var(--forest-green);color:white;border-color:var(--forest-green)}


.user-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
    font-size: 14px;
}
.user-details-grid div {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.user-details-grid .address-detail {
    grid-column: 1 / -1; /* Membuat alamat memakai lebar penuh */
}
.user-details-grid i {
    color: var(--olive-drab);
}
</style>

</body>
</html>