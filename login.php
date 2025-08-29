<?php
$pageTitle = "Login";
include 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validation
    if (empty($username)) {
        $errors[] = "Username or email is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    // Authenticate user
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, username, email, password, first_name, last_name, is_admin
                               FROM users
                               WHERE (username = ? OR email = ?)");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['is_admin'] = $user['is_admin'];

            // Set remember me cookie if requested
            if ($remember) {
                setcookie('remember_user', $user['id'], time() + (30 * 24 * 60 * 60), '/'); // 30 days
            }

            setFlashMessage('success', 'Welcome back, ' . $user['first_name'] . '!');

            // Redirect to intended page or dashboard
            $redirect_url = $_GET['redirect'] ?? 'index.php';
            redirect($redirect_url);

        } else {
            $errors[] = "Invalid username/email or password";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="form-container">
            <div class="text-center mb-4">
                <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
                <p class="text-muted">Welcome back! Please login to your account.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" data-validate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?php echo htmlspecialchars($username ?? ''); ?>"
                               placeholder="Enter username or email" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Remember me for 30 days
                    </label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
            </form>

            <div class="text-center mt-4">
                <p><a href="forgot-password.php">Forgot your password?</a></p>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>

            <!-- Demo Account Info -->
            <div class="alert alert-info mt-4">
                <h6><i class="fas fa-info-circle"></i> Demo Account</h6>
                <p class="mb-1"><strong>Admin:</strong> admin / admin123</p>
                <p class="mb-0"><small>Use this account to test admin features</small></p>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function () {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');

    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
