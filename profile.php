<?php
session_start();

// Keamanan: Pastikan hanya user yang sudah login bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

require_once 'config/yarac_db.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Ambil data user terbaru
$user->getById($_SESSION['user_id']);

$page_title = "My Profile - " . htmlspecialchars($_SESSION['user_name']);
// Tidak perlu CSS tambahan dari file lain, style ada di halaman ini
$additional_css = [];

// [MODIFIKASI] Handle update profile
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Logika untuk update profil
        $user->first_name = trim($_POST['first_name']);
        $user->last_name = trim($_POST['last_name']);
        $user->phone = trim($_POST['phone']);
        $user->address = trim($_POST['address']);
        $user->id = $_SESSION['user_id'];

        if ($user->updateProfile()) {
            // Perbarui nama di session
            $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
            $message = '<div class="alert alert-success">Profile updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-error">Failed to update profile.</div>';
        }
    }
}

include 'includes/header.php';
?>

<style>
/* ========================================= */
/* CSS RAPI UNTUK HALAMAN PROFIL PENGGUNA    */
/* ========================================= */

.profile-page-container {
    padding: 140px 20px 80px;
    background-color: var(--light-gray); /* Latar belakang abu-abu muda */
}

.profile-page-layout {
    display: flex;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    align-items: flex-start; /* Konten atas sejajar */
}

/* Sidebar Navigasi */
.profile-sidebar {
    flex: 0 0 280px; /* Lebar sidebar dibuat sedikit lebih besar */
    background: #fff;
    padding: 25px;
    border-radius: 20px;
    box-shadow: var(--shadow-medium);
    height: fit-content;
    position: sticky; /* Membuat sidebar tetap terlihat saat scroll */
    top: 120px;
}

.profile-sidebar h4 {
    font-size: 1.5rem;
    color: var(--forest-green);
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-gray);
}

.profile-nav a {
    display: flex; /* Menggunakan flexbox untuk ikon dan teks */
    align-items: center;
    gap: 15px;
    padding: 16px 20px;
    color: var(--dark-gray);
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    border-radius: 12px;
    margin-bottom: 10px;
    transition: all var(--transition-medium);
    border: 2px solid transparent;
}

.profile-nav a:hover {
    background-color: var(--light-gray);
    color: var(--forest-green);
}

.profile-nav a.active {
    background-color: var(--forest-green);
    color: white;
    border-color: var(--olive-drab);
    transform: translateX(5px);
}

.profile-nav i {
    width: 20px; /* Memberi ruang untuk ikon */
    text-align: center;
}

/* Konten Utama */
.profile-main-content {
    flex-grow: 1;
}

.profile-content-section {
    display: none;
    background: #fff;
    padding: 40px;
    border-radius: 20px;
    box-shadow: var(--shadow-medium);
    animation: fadeIn 0.4s ease-in-out;
}

.profile-content-section.active {
    display: block;
}

.profile-content-section h3 {
    font-size: 2rem;
    color: var(--forest-green);
    margin-bottom: 30px;
}

/* Style untuk form yang lebih rapi */
.auth-form {
    max-width: 100%; /* Form mengisi kontainer */
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 25px;
}

.form-group {
  margin-bottom: 25px;
}

.form-group label {
  display: block;
  margin-bottom: 10px;
  font-weight: 600;
  color: var(--dark-gray);
}

.form-group input {
  width: 100%;
  padding: 15px;
  border: 2px solid #ddd;
  border-radius: 10px;
  font-size: 1rem;
}

.form-group input:disabled {
    background-color: #f5f5f5;
    color: #888;
    cursor: not-allowed;
}

.btn-auth {
    width: auto; /* Lebar tombol menyesuaikan konten */
    padding: 15px 40px;
    font-size: 1rem;
    margin-top: 10px;
    border-radius: 10px;
}

/* Alert message styles */
.alert {
  padding: 15px 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-weight: 500;
}
.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}
.alert-error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

</style>

<div class="profile-page-container">
    <div class="profile-page-layout">
        <aside class="profile-sidebar">
            <h4>My Account</h4>
            <nav class="profile-nav">
                <a href="#edit-profile" class="profile-nav-item active"><i class="fas fa-user-edit"></i> Edit Profile</a>
                <a href="#change-password" class="profile-nav-item"><i class="fas fa-key"></i> Change Password</a>
            </nav>
        </aside>

        <main class="profile-main-content">
            <?php echo $message; // Tampilkan pesan sukses/error di sini ?>

            <section id="edit-profile" class="profile-content-section active">
                <h3>Edit Your Profile</h3>
                <form class="auth-form" method="POST" action="profile.php#edit-profile">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user->first_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user->last_name); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email (Cannot be changed)</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user->phone); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user->address); ?>">
                    </div>
                    <button type="submit" class="btn-auth">Save Changes</button>
                </form>
            </section>

            <section id="order-history" class="profile-content-section">
                <h3>Your Order History</h3>
                <p>Your past orders will be displayed here. (This feature is under development)</p>
                </section>

            <section id="change-password" class="profile-content-section">
                <h3>Change Your Password</h3>
                <form class="auth-form" method="POST" action="profile.php#change-password">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" required>
                    </div>
                    <button type="submit" class="btn-auth">Update Password</button>
                </form>
            </section>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('.profile-nav-item');
    const sections = document.querySelectorAll('.profile-content-section');

    function showSection(targetId) {
        // Sembunyikan semua section
        sections.forEach(section => {
            section.classList.remove('active');
        });

        // Tampilkan section yang ditargetkan
        const activeSection = document.getElementById(targetId);
        if (activeSection) {
            activeSection.classList.add('active');
        }

        // Atur status aktif pada link navigasi
        navLinks.forEach(link => {
            if (link.getAttribute('href') === '#' + targetId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            // Update URL hash tanpa me-refresh halaman
            history.pushState(null, '', '#' + targetId);
            showSection(targetId);
        });
    });

    // Handle back/forward browser buttons
    window.addEventListener('popstate', () => {
        const hash = window.location.hash.substring(1) || 'edit-profile';
        showSection(hash);
    });

    // Tampilkan section awal berdasarkan hash di URL
    const initialHash = window.location.hash.substring(1) || 'edit-profile';
    showSection(initialHash);
});
</script>

<?php include 'includes/footer.php'; ?>