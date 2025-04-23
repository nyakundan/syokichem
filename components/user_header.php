<?php
// Log cart contents for debugging
error_log("Cart Contents: " . print_r($_SESSION['cart'] ?? 'No cart', true));

// Get categories and their subcategories
$categories = $conn->prepare("
    SELECT 
        c1.id as parent_id,
        c1.name as parent_name,
        c1.slug as parent_slug,
        c2.id as child_id,
        c2.name as child_name,
        c2.slug as child_slug
    FROM product_categories c1
    LEFT JOIN product_categories c2 ON c1.id = c2.parent_id
    WHERE c1.parent_id IS NULL
    ORDER BY c1.name, c2.name
");
$categories->execute();

// Organize categories into a hierarchical structure
$category_tree = [];
while($category = $categories->fetch(PDO::FETCH_ASSOC)) {
    $parent_id = $category['parent_id'];
    if(!isset($category_tree[$parent_id])) {
        $category_tree[$parent_id] = [
            'id' => $category['parent_id'],
            'name' => $category['parent_name'],
            'slug' => $category['parent_slug'],
            'subcategories' => []
        ];
    }
    
    if($category['child_id']) {
        $category_tree[$parent_id]['subcategories'][] = [
            'id' => $category['child_id'],
            'name' => $category['child_name'],
            'slug' => $category['child_slug']
        ];
    }
}

// Convert to array for easier iteration
$category_tree = array_values($category_tree);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Syokichem Pharmaceuticals Ltd.</title>
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <style>
   :root {
      /* Brand Colors */
      --primary-green: #8BC34A;
      --dark-green: #689F38;
      --primary-yellow: #FFEB3B;
      --dark-yellow: #FBC02D;
      --pure-white: #FFFFFF;
      
      /* Text Colors */
      --text-dark: #212121;
      --text-medium: #757575;
      
      /* Backgrounds */
      --light-gray: #F5F5F5;
      --medium-gray: #E0E0E0;
      
      /* Functional Colors */
      --error-red: #E53935;
      --success-blue: #1E88E5;
      
      /* Structural */
      --border: 0.1rem solid var(--medium-gray);
      --box-shadow: 0 0.2rem 0.5rem rgba(0,0,0,0.1);
      --round-corners: 0.4rem;
      --transition: all 0.3s ease;
   }
   
   * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Rubik', sans-serif;
   }
   
   body {
      font-size: 1.6rem;
      line-height: 1.6;
   }
   
   html {
      font-size: 62.5%;
   }
   
   .header {
      background-color: var(--pure-white);
      box-shadow: var(--box-shadow);
      position: sticky;
      top: 0;
      z-index: 1000;
   }
   
   /* Top Bar */
   .top-bar {
      background-color: var(--primary-green);
      color: var(--pure-white);
      padding: 1rem 0;
      font-size: 1.4rem;
   }
   .top-bar .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1.5rem;
   }
   .contact-info span, .contact-info a, .contact-info i {
      color: var(--pure-white) !important;
      font-weight: 500;
      text-decoration: none;
      transition: color 0.2s;
   }
   .contact-info a:hover {
      color: var(--primary-yellow) !important;
   }
   .contact-info i {
      margin-right: 0.5rem;
   }
   .auth-links a {
      color: var(--pure-white);
      margin-left: 1.5rem;
      text-decoration: none;
      transition: var(--transition);
   }
   .auth-links a:hover {
      color: var(--primary-yellow);
      opacity: 0.8;
   }
   
   /* Main Header */
   .main-header {
      padding: 1.5rem 0;
   }
   
   .main-header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1.5rem;
      flex-wrap: wrap;
   }
   
   .logo {
      display: flex;
      align-items: center;
      text-decoration: none;
      margin-right: 2rem;
   }
   
   .logo-img {
      height: 5rem;
      margin-right: 1rem;
   }
   
   .logo-text {
      display: flex;
      flex-direction: column;
   }
   
   .main-text {
      font-size: 2.4rem;
      font-weight: 700;
      color: var(--primary-green);
      line-height: 1.2;
   }
   
   .sub-text {
      font-size: 1.4rem;
      color: var(--text-medium);
   }
   
   /* Search Bar */
   .search-container {
      flex: 1;
      min-width: 30rem;
      margin: 0 2rem;
      position: relative;
   }
   
   .search-container form {
      display: flex;
   }
   
   .search-container input {
      width: 100%;
      padding: 1.2rem 1.5rem;
      border: var(--border);
      border-radius: var(--round-corners) 0 0 var(--round-corners);
      outline: none;
      font-size: 1.4rem;
   }
   
   .search-container button {
      padding: 0 1.5rem;
      background-color: var(--primary-green);
      color: var(--text-dark);
      border: none;
      border-radius: 0 var(--round-corners) var(--round-corners) 0;
      cursor: pointer;
      transition: var(--transition);
   }
   
   .search-container button:hover {
      background-color: var(--dark-green);
      color: var(--pure-white);
   }
   
   /* Header Actions */
   .header-actions {
      display: flex;
      align-items: center;
   }
   
   .action-icons {
      display: flex;
      margin-right: 1.5rem;
   }
   
   .action-icons a {
      color: var(--text-dark);
      margin-left: 1.5rem;
      position: relative;
      text-decoration: none;
      font-size: 2rem;
   }
   
   .count {
      position: absolute;
      top: -0.8rem;
      right: -0.8rem;
      background-color: var(--primary-yellow);
      color: var(--text-dark);
      border-radius: 50%;
      width: 2.2rem;
      height: 2.2rem;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.2rem;
      font-weight: bold;
   }
   
   /* Mobile Menu Toggle */
   .mobile-menu-toggle {
      display: none;
      background: none;
      border: none;
      font-size: 2.4rem;
      color: var(--text-dark);
      cursor: pointer;
      padding: 1rem;
   }
   
   /* Main Navigation */
   .main-navigation {
      background-color: var(--pure-white);
      border-top: 1px solid var(--medium-gray);
      border-bottom: 1px solid var(--medium-gray);
   }
   
   .nav-menu {
      display: flex;
      list-style: none;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1.5rem;
   }
   
   .nav-menu > li {
      position: relative;
   }
   
   .nav-menu > li > a {
      display: flex;
      align-items: center;
      padding: 1.2rem 1.5rem;
      color: var(--text-dark);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
      white-space: nowrap;
      font-size: 1.4rem;
   }
   
   .nav-menu > li > a:hover {
      color: var(--primary-green);
      background-color: rgba(139, 195, 74, 0.1);
   }
   
   .nav-menu > li > a i {
      margin-right: 0.8rem;
   }
   
   /* Mega Menu */
   .mega-menu {
      position: static !important;
   }
   
   .mega-menu-content {
      display: none;
      position: absolute;
      left: 0;
      width: 100%;
      background: var(--pure-white);
      box-shadow: 0 1rem 1.5rem rgba(0,0,0,0.1);
      z-index: 1000;
      padding: 2.5rem;
      border-top: 0.3rem solid var(--primary-green);
   }
   
   .mega-menu.active .mega-menu-content {
      display: flex;
   }
   
   .mega-menu-main-categories {
      width: 30rem;
      border-right: 1px solid var(--medium-gray);
      padding-right: 1.8rem;
   }
   
   .main-category {
      display: flex;
      align-items: center;
      padding: 1rem 1.2rem;
      color: var(--text-dark);
      text-decoration: none;
      margin-bottom: 0.6rem;
      border-radius: 0.6rem;
      transition: var(--transition);
      position: relative;
      font-weight: 500;
      font-size: 1.4rem;
   }
   
   .main-category:hover {
      background: var(--primary-green);
      color: var(--pure-white);
   }
   
   .main-category i:first-child {
      margin-right: 1rem;
      width: 2.4rem;
      text-align: center;
   }
   
   .main-category i.fa-chevron-right {
      position: absolute;
      right: 1.5rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.2rem;
      opacity: 0.7;
   }
   
   .mega-menu-subcategories {
      flex: 1;
      padding-left: 2.5rem;
      position: relative;
      min-height: 40rem;
   }
   
   .subcategory-group {
      display: none;
      grid-template-columns: repeat(auto-fill, minmax(24rem, 1fr));
      gap: 2.5rem;
      position: absolute;
      width: calc(100% - 2.5rem);
      height: 100%;
      top: 0;
      left: 2.5rem;
      background: var(--pure-white);
      padding: 0;
      overflow-y: auto;
   }
   
   .subcategory-group.active {
      display: grid;
   }
   
   .subcategory-column {
      display: flex;
      flex-direction: column;
      background-color: #f8f9fa;
      padding: 1.2rem;
      border-radius: 0.8rem;
      box-shadow: 0 0.2rem 0.4rem rgba(0,0,0,0.05);
   }
   
   .subcategory-header-link {
      text-decoration: none;
   }
   
   .subcategory-header {
      color: var(--primary-green);
      font-size: 1.6rem;
      font-weight: 600;
      margin: 0 0 1.2rem 0;
      padding-bottom: 0.6rem;
      border-bottom: 0.2rem solid var(--primary-yellow);
   }
   
   .subcategory-column a {
      display: block;
      padding: 0.7rem 1.2rem;
      color: var(--text-dark);
      text-decoration: none;
      transition: var(--transition);
      border-radius: 0.4rem;
      margin-bottom: 0.4rem;
      font-size: 1.4rem;
   }
   
   .subcategory-column a:hover {
      color: var(--primary-green);
      background-color: rgba(139, 195, 74, 0.1);
      transform: translateX(0.5rem);
   }
   
   /* Mobile Navigation */
   .mobile-nav {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      background: var(--pure-white);
      z-index: 2000;
      overflow-y: auto;
      padding: 2rem;
      transform: translateX(-100%);
      transition: var(--transition);
   }
   .mobile-nav.active {
      display: block;
      transform: translateX(0);
      z-index: 2100;
   }
   
   .mobile-nav-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--medium-gray);
   }
   
   .mobile-nav-close {
      background: none;
      border: none;
      font-size: 2.4rem;
      color: var(--text-dark);
      cursor: pointer;
   }
   
   .mobile-nav-menu {
      list-style: none;
   }
   
   .mobile-nav-item {
      border-bottom: 1px solid var(--medium-gray);
   }
   
   .mobile-nav-link {
      display: flex;
      align-items: center;
      padding: 1.5rem 0;
      color: var(--text-dark);
      text-decoration: none;
      font-weight: 500;
      font-size: 1.6rem;
   }
   
   .mobile-nav-link i {
      margin-right: 1rem;
      width: 2.4rem;
      text-align: center;
   }
   
   .mobile-submenu-toggle {
      margin-left: auto;
      transition: var(--transition);
   }
   
   .mobile-submenu {
      display: none;
      list-style: none;
      padding-left: 2rem;
   }
   
   .mobile-submenu.active {
      display: block;
   }
   
   .mobile-submenu-item {
      padding: 1rem 0;
   }
   
   .mobile-submenu-link {
      color: var(--text-medium);
      text-decoration: none;
      display: block;
      padding: 0.5rem 0;
      font-size: 1.4rem;
   }
   
   /* Responsive Styles */
   @media (max-width: 992px) {
      .search-container {
         order: 3;
         width: 100%;
         margin: 1.5rem 0 0 0;
      }
      
      .header-actions {
         margin-left: auto;
      }
      
      .mobile-menu-toggle {
         display: block;
         margin-left: 1.5rem;
      }
      
      .nav-menu {
         display: none;
      }
      
      .mega-menu-content {
         position: fixed;
         top: 0;
         left: 0;
         width: 100%;
         height: 100vh;
         flex-direction: column;
         overflow-y: auto;
         z-index: 2100;
      }
      
      .mega-menu-main-categories {
         width: 100%;
         border-right: none;
         padding-right: 0;
      }
      
      .mega-menu-subcategories {
         padding-left: 0;
         min-height: auto;
         display: none;
      }
      
      .mega-menu-subcategories.active {
         display: block;
      }
      
      .subcategory-group {
         position: static;
         width: 100%;
         grid-template-columns: 1fr;
      }
   }
   
   @media (max-width: 768px) {
      .top-bar .container {
         flex-direction: column;
         text-align: center;
      }
      
      .contact-info {
         margin-bottom: 0.5rem;
      }
      
      .auth-links a {
         margin: 0 1rem;
      }
      
      .main-header .container {
         flex-direction: column;
      }
      
      .logo {
         margin: 0 0 1.5rem 0;
      }
      
      .header-actions {
         width: 100%;
         justify-content: space-between;
         margin-top: 1.5rem;
      }
   }
   
   @media (max-width: 576px) {
      .action-icons a {
         margin-left: 1rem;
         font-size: 1.8rem;
      }
      
      .count {
         width: 1.8rem;
         height: 1.8rem;
         font-size: 1rem;
      }
   }
   </style>
</head>
<body>
<header class="header">
   <!-- Top Bar -->
   <div class="top-bar">
      <div class="container">
         <div class="contact-info">
            <span><i class="fas fa-building"></i> SYOKICHEM</span>
            <span><i class="fas fa-phone-alt"></i> <a href="tel:+254792914662">+254792914662</a></span>
            <span><i class="fas fa-envelope"></i> <a href="mailto:sales@syokichem.com">sales@syokichem.com</a></span>
            <span><i class="fas fa-globe"></i> <a href="https://www.syokichem.com">www.syokichem.com</a></span>
         </div>
         <div class="auth-links">
            <?php if(isset($user_id) && $user_id): ?>
               <a href="user_dashboard.php"><i class="fas fa-user-circle"></i> My Account</a>
            <?php else: ?>
               <a href="user_login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
               <a href="user_register.php"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
         </div>
      </div>
   </div>

   <!-- Main Header -->
   <section class="main-header">
      <div class="container">
         <a href="index.php" class="logo">
            <img src="images/logo.jpeg" alt="Syokichem Logo" class="logo-img">
            <div class="logo-text">
               <span class="main-text">SYOKICHEM</span>
               <span class="sub-text">PHARMACEUTICALS</span>
            </div>
         </a>

         <div class="search-container">
            <form action="search.php" method="GET" id="headerSearchForm" autocomplete="off">
               <input type="text" name="query" placeholder="Search medicines, products..." autocomplete="off" required>
               <button type="submit" id="headerSearchBtn"><i class="fas fa-search"></i></button>
            </form>
            <div id="searchProcessingMsg" style="display:none; color:#689F38; font-size:1.3rem; margin-top:0.5rem; text-align:center;">Processing...</div>
            <div id="ajaxSearchResults" style="display:none; background:#fff; position:absolute; left:0; right:0; z-index:2000; box-shadow:0 2px 8px rgba(0,0,0,0.08); max-height:350px; overflow:auto;"></div>
         </div>

         <div class="header-actions">
            <div class="action-icons">
               <a href="cart.php" class="cart-icon">
                  <i class="fas fa-shopping-cart"></i>
                  <?php if(isset($total_cart) && $total_cart > 0): ?>
                     <span class="count"><?= htmlspecialchars($total_cart) ?></span>
                  <?php endif; ?>
               </a>
            </div>
            
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
               <i class="fas fa-bars"></i>
            </button>
         </div>
      </div>
   </section>

   <!-- Desktop Navigation -->
   <nav class="main-navigation">
      <div class="container">
         <ul class="nav-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li class="mega-menu">
               <a href="javascript:void(0)" class="shop-trigger"><i class="fas fa-pills"></i> Shop By Category <i class="fas fa-chevron-down"></i></a>
               <div class="mega-menu-content">
                  <div class="mega-menu-main-categories">
                     <?php foreach($category_tree as $category): ?>
                     <a href="category.php?category=<?= $category['slug'] ?>" class="main-category" data-target="category-<?= $category['id'] ?>">
                        <i class="fas fa-chevron-right"></i>
                        <?= htmlspecialchars($category['name']) ?>
                     </a>
                     <?php endforeach; ?>
                  </div>
                  <div class="mega-menu-subcategories">
                     <?php foreach($category_tree as $category): ?>
                     <div class="subcategory-group" id="category-<?= $category['id'] ?>">
                        <div class="subcategory-column">
                           <div class="subcategory-header">
                              <h4><?= htmlspecialchars($category['name']) ?></h4>
                           </div>
                           <?php if(!empty($category['subcategories'])): ?>
                           <div class="subcategory-list">
                              <?php foreach($category['subcategories'] as $subcategory): ?>
                              <a href="category.php?category=<?= $subcategory['slug'] ?>" class="subcategory-link">
                                 <i class="fas fa-chevron-right"></i>
                                 <?= htmlspecialchars($subcategory['name']) ?>
                              </a>
                              <?php endforeach; ?>
                           </div>
                           <?php else: ?>
                           <p class="no-subcategories">No subcategories available</p>
                           <?php endif; ?>
                        </div>
                     </div>
                     <?php endforeach; ?>
                  </div>
               </div>
            </li>
            <li><a href="prescription.php"><i class="fas fa-prescription-bottle-alt"></i> Prescriptions</a></li>
            <li><a href="telemedicine.php"><i class="fas fa-user-md"></i> Consult Doctor</a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
            <li><a href="contact.php"><i class="fas fa-phone-alt"></i> Contact</a></li>
            <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQs</a></li>
         </ul>
      </div>
   </nav>
</header>

<!-- Mobile Navigation -->
<div class="mobile-nav" id="mobileNav">
   <div class="mobile-nav-header">
      <a href="index.php" class="logo">
         <img src="images/logo.jpeg" alt="Syokichem Logo" class="logo-img" style="height: 4rem;">
         <div class="logo-text">
            <span class="main-text">SYOKICHEM</span>
         </div>
      </a>
      <button class="mobile-nav-close" id="mobileNavClose">
         <i class="fas fa-times"></i>
      </button>
   </div>
   
   <ul class="mobile-nav-menu">
      <li class="mobile-nav-item"><a href="index.php" class="mobile-nav-link"><i class="fas fa-home"></i> Home</a></li>
      <li class="mobile-nav-item">
         <a href="javascript:void(0)" class="mobile-nav-link mobile-category-trigger">
            <i class="fas fa-pills"></i> Shop By Category
            <i class="fas fa-chevron-down mobile-submenu-toggle"></i>
         </a>
         <ul class="mobile-submenu">
            <?php foreach($category_tree as $category): ?>
            <li class="mobile-submenu-item">
               <a href="javascript:void(0)" class="mobile-nav-link mobile-subcategory-trigger">
                  <?= htmlspecialchars($category['name']) ?>
                  <i class="fas fa-chevron-down mobile-submenu-toggle"></i>
               </a>
               <?php if(!empty($category['subcategories'])): ?>
               <ul class="mobile-submenu">
                  <?php foreach($category['subcategories'] as $subcategory): ?>
                  <li class="mobile-submenu-item">
                     <a href="category.php?category=<?= $subcategory['slug'] ?>" class="mobile-submenu-link">
                        <?= htmlspecialchars($subcategory['name']) ?>
                     </a>
                  </li>
                  <?php endforeach; ?>
               </ul>
               <?php endif; ?>
            </li>
            <?php endforeach; ?>
         </ul>
      </li>
      <li class="mobile-nav-item"><a href="prescription.php" class="mobile-nav-link"><i class="fas fa-prescription-bottle-alt"></i> Prescriptions</a></li>
      <li class="mobile-nav-item"><a href="telemedicine.php" class="mobile-nav-link"><i class="fas fa-user-md"></i> Consult Doctor</a></li>
      <li class="mobile-nav-item"><a href="about.php" class="mobile-nav-link"><i class="fas fa-info-circle"></i> About Us</a></li>
      <li class="mobile-nav-item"><a href="contact.php" class="mobile-nav-link"><i class="fas fa-phone-alt"></i> Contact</a></li>
      <li class="mobile-nav-item"><a href="faq.php" class="mobile-nav-link"><i class="fas fa-question-circle"></i> FAQs</a></li>
   </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
   // Mobile Menu Toggle
   const mobileMenuToggle = document.getElementById('mobileMenuToggle');
   const mobileNav = document.getElementById('mobileNav');
   const mobileNavClose = document.getElementById('mobileNavClose');
   
   if (mobileMenuToggle && mobileNav) {
      mobileMenuToggle.addEventListener('click', function() {
         mobileNav.classList.add('active');
      });
   }
   
   if (mobileNavClose) {
      mobileNavClose.addEventListener('click', function() {
         mobileNav.classList.remove('active');
      });
   }
   
   // Mobile Submenu Toggle
   const mobileTriggers = document.querySelectorAll('.mobile-category-trigger, .mobile-subcategory-trigger');
   
   mobileTriggers.forEach(trigger => {
      trigger.addEventListener('click', function(e) {
         e.preventDefault();
         const parentItem = this.closest('.mobile-nav-item, .mobile-submenu-item');
         const submenu = parentItem.querySelector('.mobile-submenu');
         const icon = this.querySelector('.mobile-submenu-toggle');
         
         if (submenu) {
            submenu.classList.toggle('active');
            if (icon) {
               icon.classList.toggle('fa-chevron-down');
               icon.classList.toggle('fa-chevron-up');
            }
         }
      });
   });
   
   // Desktop Mega Menu
   const shopTrigger = document.querySelector('.shop-trigger');
   const megaMenu = document.querySelector('.mega-menu');
   const categoryLinks = document.querySelectorAll('.main-category');
   const subcategoryGroups = document.querySelectorAll('.subcategory-group');
   
   if (shopTrigger && megaMenu) {
      shopTrigger.addEventListener('click', function(e) {
         e.preventDefault();
         megaMenu.classList.toggle('active');
         
         // Reset to first category when opening
         if (megaMenu.classList.contains('active')) {
            subcategoryGroups.forEach(group => group.classList.remove('active'));
            if (subcategoryGroups.length > 0) {
               subcategoryGroups[0].classList.add('active');
            }
         }
      });
   }
   
   categoryLinks.forEach(link => {
      link.addEventListener('click', function(e) {
         e.preventDefault();
         const target = this.getAttribute('data-target');
         
         if (target) {
            subcategoryGroups.forEach(group => group.classList.remove('active'));
            document.getElementById(target)?.classList.add('active');
         }
      });
   });
   
   // Close mega menu when clicking outside
   document.addEventListener('click', function(e) {
      if (!e.target.closest('.mega-menu') && !e.target.closest('.shop-trigger')) {
         megaMenu.classList.remove('active');
      }
   });
   
   (function(){
      const form = document.getElementById('headerSearchForm');
      const input = form.querySelector('input[name="query"]');
      const processingMsg = document.getElementById('searchProcessingMsg');
      const resultsBox = document.getElementById('ajaxSearchResults');

      form.addEventListener('submit', function(e) {
         e.preventDefault();
         const query = input.value.trim();
         if(!query) {
            resultsBox.style.display = 'none';
            return;
         }
         processingMsg.style.display = 'block';
         resultsBox.innerHTML = '';
         resultsBox.style.display = 'none';
         fetch('ajax_search.php?query=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
               processingMsg.style.display = 'none';
               if(data.products && data.products.length > 0) {
                  let html = '<ul style="list-style:none;margin:0;padding:0;">';
                  data.products.forEach(function(p){
                     html += `<li style=\"display:flex;align-items:center;padding:0.7rem 1rem;border-bottom:1px solid #eee;\">
                        <img src='uploaded_img/${p.image_01}' alt='${p.name}' style=\"width:44px;height:44px;object-fit:cover;border-radius:6px;margin-right:1rem;\">
                        <div style=\"flex:1;\"><a href='quick_view.php?pid=${p.id}' style=\"font-weight:500;color:#222;text-decoration:none;\">${p.name}</a><br><span style=\"color:#689F38;font-size:1.2rem;\">KSh ${parseFloat(p.price).toLocaleString()}</span></div>
                        <span style=\"margin-left:1rem;font-size:1.1rem;\">${p.stock > 0 ? 'In Stock' : 'Out of Stock'}</span>
                     </li>`;
                  });
                  html += '</ul>';
                  resultsBox.innerHTML = html;
                  resultsBox.style.display = 'block';
               } else {
                  resultsBox.innerHTML = '<div style="padding:1rem;text-align:center;color:#888;">No products found.</div>';
                  resultsBox.style.display = 'block';
               }
            })
            .catch(()=>{
               processingMsg.style.display = 'none';
               resultsBox.innerHTML = '<div style="padding:1rem;text-align:center;color:#d32f2f;">Error searching. Try again.</div>';
               resultsBox.style.display = 'block';
            });
      });

      // Hide results on outside click
      document.addEventListener('click', function(e) {
         if(!form.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
         }
      });
      // Show results on focus if available
      input.addEventListener('focus', function(){
         if(resultsBox.innerHTML && resultsBox.innerHTML.trim() !== '') {
            resultsBox.style.display = 'block';
         }
      });
   })();
});
</script>
</body>
</html>