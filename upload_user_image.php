<?php
include 'components/connect.php';

if(isset($_FILES['image']) && isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
   
   $image = $_FILES['image']['name'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_size = $_FILES['image']['size'];
   $image_folder = 'uploads/'.$image;
   
   // Validate image
   if($image_size > 2000000){
      $message[] = 'Image size is too large (max 2MB)';
   } else {
      // Move uploaded file
      move_uploaded_file($image_tmp_name, $image_folder);
      
      // Update user record
      $update_image = $conn->prepare("UPDATE `users` SET image = ? WHERE id = ?");
      $update_image->execute([$image, $user_id]);
      
      $message[] = 'Profile picture updated successfully!';
   }
}
?>