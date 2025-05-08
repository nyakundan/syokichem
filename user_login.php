<?php
include 'components/connect.php';
session_start();

// Redirect if already logged in
if(isset($_SESSION['user_id'])){
    header('location:index.php');
    exit;
}

if(isset($_POST['submit'])){

    // Sanitize inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = $_POST['pass'];

    // Check if email exists
    $select_user = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
    $select_user->execute([$email]);
    $user = $select_user->fetch(PDO::FETCH_ASSOC);

    if($select_user->rowCount() > 0){
        // Verify password against hashed password
        if(password_verify($pass, $user['password'])){
            // Check if password needs rehashing (if algorithm changed)
            if(password_needs_rehash($user['password'], PASSWORD_DEFAULT)){
                $newHash = password_hash($pass, PASSWORD_DEFAULT);
                $update_hash = $conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
                $update_hash->execute([$newHash, $user['id']]);
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Set remember me cookie if checked
            if(isset($_POST['remember'])){
                $token = bin2hex(random_bytes(32));
                $expiry = time() + 60 * 60 * 24 * 30; // 30 days
                
                setcookie('remember_token', $token, $expiry, '/');
                
                // Store token in database
                $insert_token = $conn->prepare("INSERT INTO `user_tokens` (user_id, token, expires_at) VALUES (?, ?, ?)");
                $insert_token->execute([$user['id'], $token, date('Y-m-d H:i:s', $expiry)]);
            }
            
            // Redirect to home page
            header('location:index.php');
            exit;
        } else {
            $message[] = 'Incorrect password!';
        }
    } else {
        $message[] = 'Email not registered!';
    }
}

// Check for remember me token
if(isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])){
    $token = $_COOKIE['remember_token'];
    $check_token = $conn->prepare("SELECT * FROM `user_tokens` WHERE token = ? AND expires_at > NOW()");
    $check_token->execute([$token]);
    
    if($check_token->rowCount() > 0){
        $token_data = $check_token->fetch(PDO::FETCH_ASSOC);
        $user_id = $token_data['user_id'];
        
        $select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
        $select_user->execute([$user_id]);
        
        if($select_user->rowCount() > 0){
            $user = $select_user->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            header('location:index.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Syokichem Pharmaceuticals Ltd.</title>
    
    <!-- font awesome cdn link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- custom css file link -->
    <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="form-container">

    <form action="" method="post">
        <h3>Welcome Back</h3>
        
        <?php
        if(isset($message)){
            foreach($message as $msg){
                echo '
                <div class="message">
                    <span>'.$msg.'</span>
                    <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
                </div>
                ';
            }
        }
        ?>
        
        <div class="inputBox">
            <span>Email Address</span>
            <input type="email" name="email" required placeholder="Enter your registered email" maxlength="100" class="box" value="<?= isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : '' ?>">
        </div>
        
        <div class="inputBox">
            <span>Password</span>
            <input type="password" name="pass" required placeholder="Enter your password" minlength="8" maxlength="20" class="box">
            <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
        </div>
        
        <div class="remember-me">
            <input type="checkbox" name="remember" id="remember" <?= isset($_COOKIE['remember_email']) ? 'checked' : '' ?>>
            <label for="remember">Remember me</label>
        </div>
        
        <input type="submit" value="Login Now" class="btn" name="submit">
        
        <p>Don't have an account? <a href="user_register.php">Register now</a></p>
    </form>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>