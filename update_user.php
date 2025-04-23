<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
   exit;
}

if(isset($_POST['update'])){

   // Initialize variables with empty strings as defaults
   $name = $_POST['name'] ?? '';
   $email = $_POST['email'] ?? '';
   $number = $_POST['number'] ?? '';
   $address = $_POST['address'] ?? '';
   
   // Validate empty fields
   if(empty($name) || empty($email) || empty($number) || empty($address)){
      $message[] = 'Please fill in all fields!';
   }else{
      // Sanitize inputs
      $name = filter_var($name, FILTER_SANITIZE_STRING);
      $email = filter_var($email, FILTER_SANITIZE_EMAIL);
      $number = filter_var($number, FILTER_SANITIZE_STRING);
      $address = filter_var($address, FILTER_SANITIZE_STRING);
      
      // Check if email already exists (excluding current user)
      $verify_email = $conn->prepare("SELECT * FROM `users` WHERE email = ? AND id != ?");
      $verify_email->execute([$email, $user_id]);
      
      if($verify_email->rowCount() > 0){
         $message[] = 'Email already exists!';
      }else{
         // Update user information
         $update_profile = $conn->prepare("UPDATE `users` SET name = ?, email = ?, number = ?, address = ? WHERE id = ?");
         $update_profile->execute([$name, $email, $number, $address, $user_id]);
         
         $message[] = 'Profile updated successfully!';
         
         // Update session variables if needed
         $_SESSION['user_name'] = $name;
      }
   }
}

// Fetch current user data
$select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
$select_profile->execute([$user_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Set default values if not found
$current_name = $fetch_profile['name'] ?? '';
$current_email = $fetch_profile['email'] ?? '';
$current_number = $fetch_profile['number'] ?? '';
$current_address = $fetch_profile['address'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Profile</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="form-container">
   <form action="" method="post">
      <h3>Update Profile</h3>
      <input type="text" name="name" placeholder="<?= htmlspecialchars($current_name) ?>" maxlength="50" class="box" value="<?= htmlspecialchars($current_name) ?>">
      <input type="email" name="email" placeholder="<?= htmlspecialchars($current_email) ?>" maxlength="50" class="box" value="<?= htmlspecialchars($current_email) ?>">
      <input type="text" name="number" placeholder="<?= htmlspecialchars($current_number) ?>" min="0" max="9999999999" maxlength="10" class="box" value="<?= htmlspecialchars($current_number) ?>">
      <textarea name="address" class="box" placeholder="<?= htmlspecialchars($current_address) ?>" maxlength="200" cols="30" rows="5"><?= htmlspecialchars($current_address) ?></textarea>
      <input type="submit" value="Update Now" name="update" class="btn">
   </form>
</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>