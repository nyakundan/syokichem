<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection and session start
include 'components/connect.php';

// Initialize variables
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$messages = []; // Array to store status messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $name = $_POST['name'] ?? '';
   $email = $_POST['email'] ?? '';
   $subject = $_POST['subject'] ?? '';
   $msg_content = $_POST['message'] ?? '';

   $errors = [];

   // Validate inputs
   if (empty($name)) {
      $errors[] = 'Name is required';
   }
   if (empty($email)) {
      $errors[] = 'Email is required';
   } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Invalid email format';
   }
   if (empty($subject)) {
      $errors[] = 'Subject is required';
   }
   if (empty($msg_content)) {
      $errors[] = 'Message is required';
   }

   if (empty($errors)) {
      try {
         $insert_message = $conn->prepare("
            INSERT INTO messages (name, email, subject, message) 
            VALUES (?, ?, ?, ?)
         ");
         $insert_message->execute([$name, $email, $subject, $msg_content]);
         
         $success_msg = 'Message sent successfully!';
         
         // Clear form data
         $name = $email = $subject = $msg_content = '';
      } catch (PDOException $e) {
         $errors[] = 'Sorry, something went wrong. Please try again later.';
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
   <title>Contact Us | Syokichem Pharmaceuticals Ltd.</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <style>
      :root {
         --primary-green: #4CAF50;
         --dark-green: #388E3C;
         --primary-yellow: #FFC107;
         --error-red: #dc3545;
         --light-gray: #f8f9fa;
         --text-medium: #666;
         --text-dark: #333;
      }
      
      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Rubik', sans-serif;
      }

      body {
         font-size: 16px;
         line-height: 1.6;
         color: #333;
         background-color: #f5f5f5;
      }

      .contact-container {
         max-width: 900px;
         margin: 40px auto;
         padding: 40px 20px;
         display: flex;
         flex-direction: column;
         gap: 40px;
         background: #fff;
         border-radius: 14px;
         box-shadow: 0 8px 32px rgba(0,0,0,0.09);
      }

      .contact-info {
         background: none;
         box-shadow: none;
         padding: 0;
         border-radius: 0;
         text-align: center;
      }

      .contact-info h2 {
         font-size: 2.6rem;
         color: var(--primary-green);
         margin-bottom: 1rem;
         position: relative;
         padding-bottom: 0.5rem;
         display: inline-block;
      }

      .contact-info h2::after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 50%;
         transform: translateX(-50%);
         width: 60px;
         height: 3px;
         background: var(--primary-yellow);
         border-radius: 2px;
      }

      .contact-info p {
         color: var(--text-medium);
         margin-bottom: 2rem;
         font-size: 1.1rem;
      }

      .contact-list {
         list-style: none;
         padding: 0;
         margin: 0 auto 2rem auto;
         display: flex;
         flex-wrap: wrap;
         justify-content: center;
         gap: 2.5rem;
      }

      .contact-list li {
         font-size: 1.1rem;
         color: var(--text-dark);
         display: flex;
         align-items: center;
         gap: 0.5rem;
      }

      .contact-list a {
         color: var(--primary-green);
         text-decoration: none !important;
         font-weight: 500;
      }

      .contact-list a:hover {
         color: var(--primary-yellow);
         text-decoration: none !important;
      }

      .modern-contact-grid {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
         gap: 2.5rem;
         margin-bottom: 2.5rem;
      }
      .modern-contact-item {
         background: #fff;
         border-radius: 18px;
         padding: 2.2rem 1.7rem 1.7rem 1.7rem;
         display: flex;
         flex-direction: column;
         align-items: center;
         box-shadow: 0 6px 32px rgba(139,195,74,0.11), 0 1.5px 6px rgba(0,0,0,0.03);
         transition: box-shadow 0.25s, transform 0.18s, border-color 0.2s;
         text-align: center;
         border: 1.5px solid #e8f5e9;
         position: relative;
         min-height: 220px;
      }
      .modern-contact-item:hover {
         box-shadow: 0 12px 36px rgba(139,195,74,0.18), 0 2px 10px rgba(0,0,0,0.06);
         transform: translateY(-5px) scale(1.025);
         border-color: var(--primary-green);
      }
      .modern-contact-item .icon {
         background: linear-gradient(135deg, var(--primary-green), var(--primary-yellow));
         color: #fff;
         border-radius: 50%;
         width: 5rem;
         height: 5rem;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 2.4rem;
         margin-bottom: 1.1rem;
         box-shadow: 0 3px 12px rgba(139,195,74,0.14);
         border: 3px solid #fff;
         position: relative;
         z-index: 1;
      }
      .modern-contact-item h3 {
         text-transform: capitalize;
         color: var(--primary-green);
         font-size: 1.4rem;
         margin-bottom: 0.6rem;
         margin-top: 0.2rem;
         font-weight: 700;
         letter-spacing: 0.01em;
      }
      .modern-contact-item p, .modern-contact-item a, .contact-main-link,
      .modern-contact-item a[href^="tel:"],
      .modern-contact-item a[href^="mailto:"] {
         color: #000 !important;
         background: none !important;
         font-weight: 500;
         text-decoration: underline dotted;
         font-size: 1.4rem;
         letter-spacing: 0.01em;
      }
      .contact-main-link:hover {
         color: var(--primary-yellow) !important;
         background: rgba(255,255,255,0.07);
         border-radius: 3px;
      }
      .contact-main-card {
         background: #fff !important;
         color: var(--text-dark);
         box-shadow: 0 6px 32px rgba(139,195,74,0.13);
         border: 1.5px solid #e8f5e9;
      }
      .contact-main-card h3 {
         color: var(--primary-green);
         font-size: 1.22rem;
         margin-bottom: 0.6rem;
         margin-top: 0.2rem;
         font-weight: 700;
         letter-spacing: 0.01em;
      }
      .contact-main-link,
      .modern-contact-item a[href^="tel:"],
      .modern-contact-item a[href^="mailto:"] {
         color: #000 !important;
         background: none !important;
         font-weight: 700;
         text-decoration: none !important;
         font-size: 1.4rem;
         letter-spacing: 0.01em;
      }
      .contact-main-link:hover {
         color: var(--primary-yellow) !important;
         background: rgba(255,255,255,0.07);
         border-radius: 3px;
         text-decoration: none !important;
      }
      body.contact-page {
         background: #f5f5f5;
      }

      .contact-form {
         background: none;
         box-shadow: none;
         padding: 0;
         border-radius: 0;
      }

      .contact-form h3 {
         font-size: 1.8rem;
         margin-bottom: 1.5rem;
         color: var(--primary-green);
      }

      .form-group {
         margin-bottom: 1.5rem;
      }

      .form-group label {
         display: block;
         margin-bottom: 0.5rem;
         font-weight: 500;
      }

      .form-control {
         width: 100%;
         padding: 1rem;
         border: 1px solid #ddd;
         border-radius: 5px;
         font-size: 1rem;
         transition: border-color 0.3s;
      }

      .form-control:focus {
         border-color: var(--primary-green);
         outline: none;
         box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
      }

      textarea.form-control {
         min-height: 150px;
         resize: vertical;
      }

      .btn-submit {
         width: 100%;
         padding: 1rem;
         background: var(--primary-green);
         color: #fff;
         border: none;
         border-radius: 5px;
         font-size: 1rem;
         cursor: pointer;
         transition: background 0.3s;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 1rem;
      }

      .btn-submit:hover {
         background: var(--dark-green);
      }

      /* Message notification styles */
      .contact-message {
         position: fixed;
         top: 20px;
         right: 20px;
         padding: 1rem 2rem;
         border-radius: 5px;
         color: #fff;
         display: flex;
         align-items: center;
         gap: 1rem;
         z-index: 1000;
         box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
         animation: slideIn 0.3s ease-out;
      }

      .error-message {
         background: var(--error-red);
      }

      .success-message {
         background: var(--primary-green);
      }

      @keyframes slideIn {
         from { transform: translateX(100%); opacity: 0; }
         to { transform: translateX(0); opacity: 1; }
      }

      @keyframes fadeOut {
         to { opacity: 0; transform: translateX(100%); }
      }

      .message-text {
         flex: 1;
      }

      .contact-message i {
         cursor: pointer;
         opacity: 0.8;
         transition: opacity 0.2s;
      }

      .contact-message i:hover {
         opacity: 1;
      }
   </style>
</head>
<body class="contact-page">
   
<?php include 'components/user_header.php'; ?>

<!-- Display status messages -->
<?php if(!empty($errors)): ?>
    <?php foreach($errors as $msg): ?>
        <?php $isError = stripos($msg, 'error') !== false || stripos($msg, 'fail') !== false; ?>
        <div class="contact-message <?= $isError ? 'error-message' : 'success-message' ?>">
            <div class="message-text"><?= htmlspecialchars($msg) ?></div>
            <i class="fas fa-times" onclick="this.parentElement.remove()"></i>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($success_msg)): ?>
    <div class="contact-message success-message">
        <div class="message-text"><?= htmlspecialchars($success_msg) ?></div>
        <i class="fas fa-times" onclick="this.parentElement.remove()"></i>
    </div>
<?php endif; ?>

<div class="contact-container">
   <div class="contact-info">
      <h2>Contact Us</h2>
      <p>Have questions or need assistance? Our team is here to help you with any inquiries about our products and services.</p>
      
      <div class="modern-contact-grid">
         <div class="modern-contact-item">
            <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
            <h3 style="text-decoration: none !important;">Our location</h3>
            <p style="text-decoration: none !important;">Distribution Centre: Mombasa Road, Syokimau-Katani Road, Kalembe Junction.</p>
            <p style="text-decoration: none !important;">Head Office: Westlands Square, Woodvale Lane</p>
         </div>
         <div class="modern-contact-item">
            <div class="icon"><i class="fas fa-phone-alt"></i></div>
            <h3>Contact</h3>
            <a class="contact-main-link" href="tel:+254792914662">+254792914662</a>
            <a class="contact-main-link" href="mailto:sales@syokichem.com">sales@syokichem.com</a>
         </div>
         <div class="modern-contact-item">
            <div class="icon"><i class="fas fa-clock"></i></div>
            <h3 style="text-decoration: none !important;">Working Hours</h3>
            <p style="text-decoration: none !important;">Mon - Fri: 8:00 AM - 6:00 PM</p>
            <p style="text-decoration: none !important;">Sat: 9:00 AM - 4:00 PM</p>
         </div>
      </div>
   </div>
   
   <form class="contact-form" action="" method="post">
      <h3>Send Us a Message</h3>
      <p>Fill out the form below and we'll get back to you as soon as possible</p>
      
      <div class="form-group">
         <label for="name">Your Name</label>
         <input type="text" id="name" name="name" placeholder="Enter your full name" required 
                value="<?= htmlspecialchars($name ?? '') ?>"
                class="form-control">
      </div>
      
      <div class="form-group">
         <label for="email">Email Address</label>
         <input type="email" id="email" name="email" placeholder="Enter your email address" required
                value="<?= htmlspecialchars($email ?? '') ?>"
                class="form-control">
      </div>
      
      <div class="form-group">
         <label for="subject">Subject</label>
         <input type="text" id="subject" name="subject" placeholder="Enter subject" required
                value="<?= htmlspecialchars($subject ?? '') ?>"
                class="form-control">
      </div>
      
      <div class="form-group">
         <label for="msg">Your Message</label>
         <textarea id="msg" name="message" placeholder="How can we help you?" required 
                   class="form-control"><?= htmlspecialchars($msg_content ?? '') ?></textarea>
      </div>
      
      <button type="submit" name="send" class="btn-submit">
         <i class="fas fa-paper-plane"></i> Send Message
      </button>
   </form>
</div>

<?php include 'components/footer.php'; ?>

<script>
   document.addEventListener('DOMContentLoaded', function() {
      // Auto-close messages after 5 seconds
      const messages = document.querySelectorAll('.contact-message');
      messages.forEach(msg => {
         setTimeout(() => {
            msg.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => msg.remove(), 500);
         }, 5000);
         
         // Close message when X is clicked
         msg.querySelector('i').addEventListener('click', function() {
            this.parentElement.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => this.parentElement.remove(), 500);
         });
      });
      
      // Form submission loading state
      const form = document.querySelector('.contact-form');
      if(form) {
         form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('[type="submit"]');
            if(submitBtn) {
               submitBtn.disabled = true;
               submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            }
         });
      }
   });
</script>

</body>
</html>