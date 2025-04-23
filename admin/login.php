<?php
// --- CSRF Robustness Patch ---
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Set session cookie params BEFORE session_start()
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line");
}

// Start aggressive output buffering
ob_start(function($buffer) {
    header_remove();
    return $buffer;
});

// Enhanced error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/login_errors.log');

// ==============================================
// PATH CONFIGURATION - MOST CRITICAL PART
// ==============================================

// Server-side path (with space)
$basePath = '/ecommerce website/admin/';

// URL-encoded path for redirects (space -> %20)
$urlPath = '/ecommerce%20website/admin/';

// ==============================================
// DATABASE CONNECTION
// ==============================================

try {
    require_once __DIR__ . '/components/connect.php';
    $conn->query("SELECT 1"); // Test connection
} catch (PDOException $e) {
    error_log("DB Connection Failed: " . $e->getMessage());
    die("System error: Database unavailable");
}

// ==============================================
// LOGIN PROCESSING
// ==============================================

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF Protection
        if (empty($_POST['csrf_token'])){
            throw new Exception("Security token missing");
        }
        
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            throw new Exception("Security token invalid");
        }

        // Validate Input
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if (empty($password)) {
            throw new Exception("Password cannot be empty");
        }

        // Database Query
        $stmt = $conn->prepare("SELECT id, name, email, password, status FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            throw new Exception("Invalid credentials");
        }
        
        if ($admin['status'] !== 'active') {
            throw new Exception("Account inactive");
        }
        
        if (!password_verify($password, $admin['password'])) {
            throw new Exception("Invalid credentials");
        }

        // Login Successful
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'logged_in' => true
        ];
        
        // Update last login
        $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);

        // Generate new CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Force session write
        session_write_close();

        // ==============================================
        // REDIRECT LOGIC - CRITICAL FIXES HERE
        // ==============================================
        
        $redirect = $_SESSION['login_redirect'] ?? 'dashboard.php';
        $redirect = ltrim($redirect, '/'); // Remove leading slashes
        
        // Use URL-encoded path for the redirect
        $redirectUrl = 'http://' . $_SERVER['HTTP_HOST'] . $urlPath . $redirect;
        
        error_log("SUCCESS REDIRECT TO: " . $redirectUrl);

        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Final redirect
        header("Location: $redirectUrl");
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Login Error: " . $e->getMessage());
        sleep(1); // Throttle brute force
    }
}

// ==============================================
// REDIRECT IF ALREADY LOGGED IN
// ==============================================

if (isset($_SESSION['admin']['logged_in']) && $_SESSION['admin']['logged_in'] === true) {
    $redirectUrl = 'http://' . $_SERVER['HTTP_HOST'] . $urlPath . 'dashboard.php';
    
    // Clear buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header("Location: $redirectUrl");
    exit;
}

// ==============================================
// CSRF TOKEN GENERATION
// ==============================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==============================================
// HTML OUTPUT
// ==============================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { 
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        }
        .login-container { 
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            background-color: #4e73df;
            color: white;
            padding: 1.75rem;
            text-align: center;
        }
        .login-body {
            padding: 2.5rem;
            background: white;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.2);
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 0.75rem;
            font-weight: 500;
        }
        .alert {
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h2><i class="fas fa-lock me-2"></i> Admin Portal</h2>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> Sign In
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <a href="forgot-password.php" class="text-decoration-none">
                            <i class="fas fa-question-circle me-1"></i> Forgot Password?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Signing In...';
        });
    </script>
</body>
</html>
<?php
// Final buffer flush
ob_end_flush();