<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   header('location:index.php');
   exit;
}

if(isset($_POST['submit'])){

   // Sanitize and validate inputs
   $name = htmlspecialchars(trim($_POST['name']));
   $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
   $phone = htmlspecialchars(trim($_POST['phone']));
   $address = htmlspecialchars(trim($_POST['address']));
   $pass = $_POST['pass'];
   $cpass = $_POST['cpass'];

   // Validate password strength
   $passwordError = '';
   if(strlen($pass) < 8){
      $passwordError = 'Password must be at least 8 characters long';
   } elseif(!preg_match("#[0-9]+#", $pass)){
      $passwordError = 'Password must include at least one number';
   } elseif(!preg_match("#[a-zA-Z]+#", $pass)){
      $passwordError = 'Password must include at least one letter';
   }

   // Check if email exists
   $select_user = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
   $select_user->execute([$email]);
   $row = $select_user->fetch(PDO::FETCH_ASSOC);

   if($select_user->rowCount() > 0){
      $message[] = 'Email already exists!';
   } elseif(!empty($passwordError)){
      $message[] = $passwordError;
   } elseif($pass != $cpass){
      $message[] = 'Confirm password does not match!';
   } else {
      // Hash password using PHP password_hash()
      $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
      
      // Insert user with all fields
      $insert_user = $conn->prepare("INSERT INTO `users` 
         (name, email, password, phone, address, created_at) 
         VALUES(?, ?, ?, ?, ?, NOW())");
      $insert_user->execute([$name, $email, $hashed_password, $phone, $address]);
      
      if($insert_user->rowCount() > 0){
         // Get the newly inserted user ID
         $user_id = $conn->lastInsertId();
         
         // Set session and redirect
         $_SESSION['user_id'] = $user_id;
         $_SESSION['user_name'] = $name;
         
         $message[] = 'Registered successfully!';
         header('refresh:2;url=index.php');
      } else {
         $message[] = 'Registration failed. Please try again.';
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
   <title>Register | Syokichem Pharmaceuticals Ltd.</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
   /* Responsive message styling */
   .message {
      max-width: 400px;
      margin: 0 auto 1.5rem auto;
      padding: 1.2rem 1.5rem;
      border-radius: 8px;
      font-size: 1.15rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      background: #e9fbe9;
      color: #1b5e20;
      border: 1.5px solid #b2dfdb;
      animation: fadeIn 0.7s;
      word-break: break-word;
   }
   .message .fa-times {
      margin-left: 1rem;
      cursor: pointer;
      color: #888;
      font-size: 1.2rem;
   }
   .message.error {
      background: #ffe0e0;
      color: #b71c1c;
      border-color: #ffcdd2;
   }
   @media (max-width: 600px) {
      .form-container, form {
         padding: 0.5rem !important;
      }
      .message {
         max-width: 97vw;
         font-size: 1rem;
         padding: 0.8rem 0.7rem;
      }
   }
   @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-16px); }
      to { opacity: 1; transform: translateY(0); }
   }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="form-container">

   <form action="" method="post" enctype="multipart/form-data">
      <h3>Create Your Account</h3>
      
      <?php
      if(isset($message)){
         foreach($message as $msg){
            $isSuccess = stripos($msg, 'success') !== false;
            echo '
            <div class="message'.($isSuccess ? '' : ' error').'">
               <span>'.$msg.'</span>
               <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
            ';
         }
      }
      ?>
      
      <div class="inputBox">
         <span>Full Name</span>
         <input type="text" name="name" required placeholder="Enter your full name" maxlength="100" class="box">
      </div>
      
      <div class="inputBox">
         <span>Email Address</span>
         <input type="email" name="email" required placeholder="Enter your email" maxlength="100" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      </div>
      
      <div class="inputBox">
         <span>Phone Number</span>
         <input type="text" name="phone" placeholder="Enter your phone number" maxlength="20" class="box">
      </div>
      
      <div class="inputBox">
         <span>Address</span>
         <textarea name="address" class="box" placeholder="Enter your address" rows="2" maxlength="255"></textarea>
      </div>
      
      <div class="inputBox">
         <span>Password</span>
         <input type="password" name="pass" required placeholder="Create a password (min 8 characters)" minlength="8" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
         <small class="password-hint">Must include letters and numbers</small>
      </div>
      
      <div class="inputBox">
         <span>Confirm Password</span>
         <input type="password" name="cpass" required placeholder="Confirm your password" minlength="8" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      </div>
      
      <div class="inputBox">
         <span>Profile Picture (Optional)</span>
         <input type="file" name="image" class="box" accept="image/*">
      </div>
      
      <input type="submit" value="Register Now" class="btn" name="submit">
      <p>Already have an account? <a href="user_login.php">Login here</a></p>
   </form>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>