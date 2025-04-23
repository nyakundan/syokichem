<?php
include 'components/connect.php';

if(isset($_POST['submit'])){
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    $select_user = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
    $select_user->execute([$email]);
    
    if($select_user->rowCount() > 0){
        $user = $select_user->fetch(PDO::FETCH_ASSOC);
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
        
        // Store token in database
        $insert_token = $conn->prepare("INSERT INTO `password_resets` (email, token, expires_at) VALUES (?, ?, ?)");
        $insert_token->execute([$email, $token, $expires]);
        
        // Send reset email (in production)
        $reset_link = "https://yourdomain.com/reset_password.php?token=$token";
        
        // In development, just show the link
        $message[] = "Reset link: <a href='$reset_link'>$reset_link</a> (This would be emailed in production)";
    } else {
        $message[] = "If this email exists, a reset link has been sent";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="form-container">
    <form action="" method="post">
        <h3>Reset Password</h3>
        
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
            <input type="email" name="email" required placeholder="Enter your registered email" class="box">
        </div>
        
        <input type="submit" value="Send Reset Link" class="btn" name="submit">
        <p>Remember your password? <a href="user_login.php">Login here</a></p>
    </form>
</section>

<?php include 'components/footer.php'; ?>
</body>
</html>