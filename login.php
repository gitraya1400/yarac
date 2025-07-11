<?php
// [FINAL] login.php dengan Pengecekan Role Admin

session_start(); // Selalu mulai session di paling atas

// Jika pengguna sudah login, arahkan ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    // [MODIFIKASI] Langsung arahkan admin ke dashboard.php
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

require_once 'config/yarac_db.php';
require_once 'classes/User.php';

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields";
    } else {
        if ($user->login($email, $password)) {
            // Set session variables
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_role'] = $user->role;

            // [PENTING] Logika Pengecekan Role
            if ($user->role === 'admin') {
                header("Location: dashboard.php"); // Pengalihan untuk admin
            } else {
                header("Location: index.php"); // Pengalihan untuk user
            }
            exit();
        } else {
            $error_message = "Invalid email or password";
        }
    }
}


// Mulai menampilkan halaman HTML
$page_title = "Sign In - Yarac Fashion Store";
$additional_css = ['auth.css'];
include 'includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h2>Sign In</h2>
                <p>Welcome back to Yarac Fashion Store</p>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>


                <form class="auth-form" method="POST" action="login.php">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-auth">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>

                <div class="auth-switch">
                    Don't have an account? <a href="register.php">Sign up here</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>