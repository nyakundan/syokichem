<?php
// File: user_logout.php
// Location: http://localhost/ecommerce%20website/components/user_logout.php

// Include the database connection
include 'connect.php'; // Ensure this line is present to establish the database connection

// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Set the page title
$page_title = "Logged Out - Syokichem";

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= $page_title ?></title>
   
   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- Google Fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
   <!-- Main CSS -->
   <link rel="stylesheet" href="../css/style.css">
   
   <style>
      :root {
         --main-color: #0eb582; /* Your brand green color */
         --light-bg: #f0fdfa;
         --dark-color: #1f2b38;
         --light-color: #f5f5f5;
         --text-color: #444;
         --border: .1rem solid rgba(0, 0, 0, .1);
         --box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1);
      }
      
      .logout-container {
         display: flex;
         justify-content: center;
         align-items: center;
         min-height: 80vh;
         padding: 2rem;
         background-color: var(--light-bg);
      }
      
      .logout-content {
         background: white;
         padding: 3rem;
         border-radius: 1rem;
         box-shadow: var(--box-shadow);
         text-align: center;
         max-width: 500px;
         width: 100%;
      }
      
      .logout-icon {
         font-size: 5rem;
         color: var(--main-color);
         margin-bottom: 1.5rem;
      }
      
      .logout-title {
         font-size: 2.2rem;
         color: var(--dark-color);
         margin-bottom: 1rem;
      }
      
      .logout-message {
         font-size: 1.1rem;
         color: var(--text-color);
         margin-bottom: 2rem;
         line-height: 1.6;
      }
      
      .logout-btn {
         display: inline-block;
         margin-top: 1rem;
         padding: 0.8rem 2rem;
         background: var(--main-color);
         color: white;
         border-radius: 0.5rem;
         font-weight: 500;
         text-transform: capitalize;
      }
      
      .logout-btn:hover {
         background: var(--dark-color);
         transform: translateY(-3px);
      }
   </style>
</head>
<body>
   
   <?php include 'user_header.php'; ?>

   <section class="logout-container">
      <div class="logout-content">
         <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
         </div>
         <h1 class="logout-title">You've Been Logged Out</h1>
         <p class="logout-message">You have successfully logged out of your Syokichem account. Thank you for visiting our pharmacy.</p>
         <div class="button-group">
            <a href="../index.php" class="logout-btn">
               <i class="fas fa-home"></i> Return Home
            </a>
            <a href="../user_login.php" class="logout-btn" style="margin-left: 1rem;">
               <i class="fas fa-sign-in-alt"></i> Login Again
            </a>
         </div>
      </div>
   </section>

   <?php include 'footer.php'; ?>

   <script>
      // Add smooth transition when buttons are clicked
      document.querySelectorAll('.logout-btn').forEach(button => {
         button.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.style.opacity = '0.8';
            setTimeout(() => {
               window.location.href = this.href;
            }, 300);
         });
      });
   </script>

</body>
</html>