<?php
declare(strict_types=1);

// 1. SET ABSOLUTE PATHS
$base_url = 'http://localhost/ecommerce%20website';
$admin_url = $base_url.'/admin';
$dashboard_url = $admin_url.'/dashboard.php';

// 2. INITIALIZE SESSION
$session_name = 'PHARMACY_ADMIN';
session_name($session_name);
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/ecommerce%20website/admin',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// 3. DEBUGGING SETUP
$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_id' => session_id(),
    'request_uri' => $_SERVER['REQUEST_URI'],
    'script_path' => __FILE__,
    'base_path' => __DIR__
];

// 4. REDIRECT IF ALREADY LOGGED IN
if (isset($_SESSION['admin_id'])) {
    $debug['redirect'] = 'Already logged in, redirecting to dashboard';
    header("Location: $dashboard_url");
    exit();
}

// 5. PROCESS LOGIN FORM
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    
    // 5.1 Clean inputs
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $debug['inputs'] = ['email' => $email, 'password_length' => strlen($password)];
    
    // 5.2 Validate
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (empty($password)) {
        $error = 'Password required';
    } else {
        
        // 5.3 Database check - UPDATED PATH
        require __DIR__.'/components/connect.php'; // Changed from ../components
        
        try {
            $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // 5.4 Successful login
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                session_regenerate_id(true);
                
                $debug['login_success'] = true;
                header("Location: $dashboard_url");
                exit();
            } else {
                $error = 'Invalid credentials';
                $debug['login_failed'] = true;
            }
        } catch (PDOException $e) {
            $error = 'System error';
            $debug['database_error'] = $e->getMessage();
            error_log("Login error: ".$e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 500px; 
            margin: 2rem auto; 
            padding: 1rem; 
            background-color: #f5f5f5;
        }
        .login-form { 
            background: white;
            border: 1px solid #ddd; 
            padding: 2rem; 
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .error { 
            color: #d9534f; 
            margin: 1rem 0; 
            padding: 0.75rem; 
            border: 1px solid #d9534f;
            border-radius: 4px;
            background-color: #fdf7f7;
        }
        input { 
            width: 100%; 
            padding: 0.75rem; 
            margin: 0.5rem 0; 
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button { 
            background: #0066cc; 
            color: white; 
            border: none; 
            padding: 0.75rem; 
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
        }
        button:hover {
            background: #0052a3;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h1 style="text-align: center; color: #333;">Admin Login</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="off">
            <div style="margin-bottom: 1rem;">
                <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email:</label>
                <input type="email" id="email" name="email" placeholder="admin@example.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Password:</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
    
    <!-- DEBUG OUTPUT -->
    <div style="margin-top: 2rem; background: white; padding: 1.5rem; border-radius: 5px; border: 1px solid #eee;">
        <h3 style="margin-top: 0; color: #666;">Debug Information</h3>
        <pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto;"><?= 
            htmlspecialchars(print_r($debug, true), ENT_QUOTES, 'UTF-8') 
        ?></pre>
        <div style="margin-top: 1rem;">
            <p><strong>Session Status:</strong> <?= session_status() ?></p>
            <p><strong>Session Data:</strong></p>
            <pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px;"><?= 
                htmlspecialchars(print_r($_SESSION, true), ENT_QUOTES, 'UTF-8') 
            ?></pre>
        </div>
    </div>
</body>
</html>