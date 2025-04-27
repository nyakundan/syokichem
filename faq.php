<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>FAQs - Syokichem Pharmaceuticals</title>
   <meta name="description" content="Frequently asked questions about Syokichem Pharmaceuticals - Kenya's trusted online pharmacy">
   
   <!-- Favicon -->
   <link rel="icon" href="images/favicon.ico" type="image/x-icon">

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
   
   <style>
      :root {
         --primary: #8BC34A;
         --primary-light: #4CAF50;
         --secondary: #FFC107;
         --dark: #263238;
         --light: #f8f9fa;
         --gray: #757575;
      }
      
      .faq-hero {
         background: linear-gradient(rgba(139, 195, 74, 0.93), rgba(139, 195, 74, 0.97)), url('images/faq-bg.jpg') center/cover no-repeat;
         color: white;
         padding: 6rem 0 4rem;
         text-align: center;
      }
      
      .faq-hero h1 {
         font-size: 2.8rem;
         margin-bottom: 1.5rem;
         font-weight: 700;
      }
      
      .faq-container {
         max-width: 900px;
         margin: 4rem auto;
         padding: 0 1.5rem;
      }
      
      .faq-category {
         margin-bottom: 3rem;
      }
      
      .faq-category h2 {
         color: var(--primary);
         font-size: 1.8rem;
         margin-bottom: 1.5rem;
         padding-bottom: 0.5rem;
         border-bottom: 2px solid var(--secondary);
      }
      
      .faq-item {
         margin-bottom: 1.5rem;
         border-radius: 8px;
         overflow: hidden;
         box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      }
      
      .faq-question {
         background-color: white;
         padding: 1.5rem;
         cursor: pointer;
         display: flex;
         justify-content: space-between;
         align-items: center;
         font-weight: 600;
         color: var(--dark);
         transition: all 0.3s ease;
      }
      
      .faq-question:hover {
         background-color: #f0f0f0;
      }
      
      .faq-question i {
         color: var(--primary);
         transition: transform 0.3s ease;
      }
      
      .faq-answer {
         padding: 0 1.5rem;
         max-height: 0;
         overflow: hidden;
         transition: max-height 0.3s ease, padding 0.3s ease;
         background-color: #f9f9f9;
      }
      
      .faq-item.active .faq-question {
         background-color: var(--primary);
         color: white;
      }
      
      .faq-item.active .faq-question i {
         color: white;
         transform: rotate(180deg);
      }
      
      .faq-item.active .faq-answer {
         max-height: 500px;
         padding: 1.5rem;
      }
      
      .search-container {
         max-width: 600px;
         margin: 2rem auto 3rem;
         position: relative;
      }
      
      .search-container input {
         width: 100%;
         padding: 1rem 1.5rem;
         border-radius: 50px;
         border: 2px solid #ddd;
         font-size: 1rem;
         transition: all 0.3s ease;
      }
      
      .search-container input:focus {
         border-color: var(--primary);
         outline: none;
         box-shadow: 0 0 0 3px rgba(0, 104, 55, 0.1);
      }
      
      .search-container button {
         position: absolute;
         right: 15px;
         top: 50%;
         transform: translateY(-50%);
         background: none;
         border: none;
         color: var(--primary);
         cursor: pointer;
      }
      
      .contact-prompt {
         text-align: center;
         margin-top: 4rem;
         padding: 2rem;
         background-color: var(--primary-light);
         color: white;
         border-radius: 10px;
      }
      
      @media (max-width: 768px) {
         .faq-hero h1 {
            font-size: 2.2rem;
         }
         
         .faq-category h2 {
            font-size: 1.5rem;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- FAQ Hero Section -->
<section class="faq-hero">
   <div class="container">
      <h1>Frequently Asked Questions</h1>
      <p>Find answers to common questions about our products, services, and policies</p>
   </div>
</section>



<!-- FAQ Container -->
<div class="faq-container">
   <!-- Ordering Questions -->
   <div class="faq-category">
      <h2>Ordering & Delivery</h2>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>How do I place an order?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>You can place an order through our website by browsing products and adding them to your cart. For prescription medications, you'll need to upload a valid prescription during checkout. Alternatively, you can call our pharmacy directly to place an order.</p>
         </div>
      </div>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>What are your delivery options and fees?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>We offer same-day delivery within Nairobi for orders placed before 2pm (delivery fee: KES 200). Nationwide deliveries are processed within 1-3 business days (delivery fees vary by location). Free delivery is available for orders over KES 3,000 within Nairobi.</p>
         </div>
      </div>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>Can I track my order?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>Yes, once your order is dispatched, you'll receive an SMS and email with a tracking link. You can also track your order by logging into your account on our website.</p>
         </div>
      </div>
   </div>
   
   <!-- Prescription Questions -->
   <div class="faq-category">
      <h2>Prescriptions & Medications</h2>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>How do I submit a prescription?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>You can upload a clear photo/scan of your prescription during checkout on our website. Alternatively, you can WhatsApp it to our pharmacy number or bring it to our physical location in Syokimau. All prescriptions are verified by our licensed pharmacists.</p>
         </div>
      </div>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>Do you offer prescription refills?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>Yes, we offer convenient prescription refills. Log into your account to request a refill, or contact our pharmacy team. For controlled substances, a new prescription may be required according to Kenyan regulations.</p>
         </div>
      </div>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>How do I know the medications are genuine?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>We source all medications directly from licensed manufacturers and distributors approved by the Pharmacy and Poisons Board of Kenya. All products come with proper packaging, batch numbers, and expiration dates. We guarantee the authenticity of every product we dispense.</p>
         </div>
      </div>
   </div>
   
   <!-- Payment & Account Questions -->
   <div class="faq-category">
      <h2>Payments & Account</h2>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>What payment methods do you accept?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>We accept M-Pesa, debit/credit cards (Visa, Mastercard), bank transfers, and cash on delivery (Nairobi only). All online payments are processed through secure payment gateways.</p>
         </div>
      </div>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>Can I create an account without placing an order?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>Yes, you can create an account at any time by clicking "Register" at the top of our website. Having an account lets you track orders, save prescriptions, and get personalized health recommendations.</p>
         </div>
      </div>
      
      <div class="faq-item">
         <div class="faq-question">
            <span>How do I reset my password?</span>
            <i class="fas fa-chevron-down"></i>
         </div>
         <div class="faq-answer">
            <p>Click "Forgot Password" on the login page and enter your registered email address. You'll receive a link to create a new password. If you don't see the email, please check your spam folder.</p>
         </div>
      </div>
   </div>
</div>

<!-- Contact Prompt -->
<div class="contact-prompt container" style="background: var(--primary); color: #fff;">
   <h3>Didn't find what you were looking for?</h3>
   <p>Our pharmacy team is available 24/7 to answer your questions</p>
   <a href="contact.php" class="btn btn-primary" style="background-color: var(--secondary); color: var(--dark); margin-top: 1rem;">Contact Us</a>
</div>

<?php include 'components/footer.php'; ?>

<script>
// FAQ Toggle Functionality
document.querySelectorAll('.faq-question').forEach(question => {
   question.addEventListener('click', () => {
      const item = question.parentNode;
      item.classList.toggle('active');
      
      // Close other open items in the same category
      const category = item.parentNode;
      category.querySelectorAll('.faq-item').forEach(otherItem => {
         if(otherItem !== item && otherItem.classList.contains('active')) {
            otherItem.classList.remove('active');
         }
      });
   });
});

// FAQ Search Functionality
document.getElementById('faqSearch').addEventListener('input', function() {
   const searchTerm = this.value.toLowerCase();
   const faqItems = document.querySelectorAll('.faq-item');
   
   faqItems.forEach(item => {
      const question = item.querySelector('.faq-question span').textContent.toLowerCase();
      const answer = item.querySelector('.faq-answer p').textContent.toLowerCase();
      
      if(question.includes(searchTerm) || answer.includes(searchTerm)) {
         item.style.display = 'block';
         // Open matching items
         if(searchTerm.length > 2 && !item.classList.contains('active')) {
            item.classList.add('active');
         }
      } else {
         item.style.display = 'none';
      }
   });
   
   // Show/hide category headings based on visible items
   document.querySelectorAll('.faq-category').forEach(category => {
      const visibleItems = category.querySelectorAll('.faq-item[style="display: block"]');
      if(visibleItems.length > 0) {
         category.style.display = 'block';
      } else {
         category.style.display = 'none';
      }
   });
});

// Open FAQ item if URL has hash
window.addEventListener('DOMContentLoaded', () => {
   if(window.location.hash) {
      const targetItem = document.querySelector(window.location.hash);
      if(targetItem && targetItem.classList.contains('faq-item')) {
         targetItem.classList.add('active');
         targetItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
   }
});
</script>
</body>
</html>