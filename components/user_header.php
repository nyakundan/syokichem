<?php
// Log cart contents for debugging
error_log("Cart Contents: " . print_r($_SESSION['cart'] ?? 'No cart', true));

// Recursive function to build category tree
function buildCategoryTree(array $elements, $parentId = null) {
    $branch = array();
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildCategoryTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            } else {
                $element['children'] = [];
            }
            $branch[] = $element;
        }
    }
    return $branch;
}

// Recursive function to build full category tree
function buildFullCategoryTree($elements, $parentId = null) {
    $branch = array();
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildFullCategoryTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            } else {
                $element['children'] = [];
            }
            $branch[] = $element;
        }
    }
    return $branch;
}

// Helper: chunk categories for column breaking
function chunkCategories($categories, $chunkSize = 10) {
    return array_chunk($categories, $chunkSize);
}

// Recursive render: render all chunks/columns, and for each item with children, render its own submenu (recursive)
function renderCategoryLevel($categories, $level = 0, $chunkSize = 10) {
    if (!$categories) return;
    $chunks = chunkCategories($categories, $chunkSize);
    foreach ($chunks as $chunkIdx => $chunk) {
        $left = ($level + $chunkIdx) * 100;
        $ulClass = ($level === 0) ? 'category-level level-0' : 'category-level';
        echo '<ul class="' . $ulClass . '" style="list-style:none; margin:0; padding:0; min-width:200px; position:absolute; left:' . $left . '%; top:0; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); border-radius:0.6rem; padding:0.5rem 0; z-index:' . (999 + $level + $chunkIdx) . ';">';
        foreach ($chunk as $cat) {
            $hasChildren = !empty($cat['children']);
            $activeClass = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'active-category' : '';
            echo '<li class="category-item" style="position:relative;">';
            echo '<a class="' . $activeClass . '" href="shop.php?category=' . htmlspecialchars($cat['id']) . '" style="display:flex;align-items:center;justify-content:space-between;text-decoration:none;color:#8BC34A;padding:0.7rem 1.2rem;font-weight:500;">' . htmlspecialchars($cat['name']);
            if ($hasChildren) echo '<i class="fas fa-chevron-right" style="margin-left:0.7rem;"></i>';
            echo '</a>';
            if ($hasChildren) {
                echo '<div class="category-submenu" style="position:absolute;left:100%;top:0;">';
                renderCategoryLevel($cat['children'], $level + 1, $chunkSize);
                echo '</div>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}

// Fetch all categories
$categories_stmt = $conn->prepare("SELECT id, name, slug, parent_id FROM product_categories ORDER BY name");
$categories_stmt->execute();
$categories_flat = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
$category_tree = buildCategoryTree($categories_flat);

// Fetch all categories for tree
$all_categories_stmt = $conn->prepare("SELECT id, name, slug, parent_id FROM product_categories");
$all_categories_stmt->execute();
$all_categories = $all_categories_stmt->fetchAll(PDO::FETCH_ASSOC);
$category_tree_full = buildFullCategoryTree($all_categories);

// Get only the first 8 main categories
$main_categories = array_slice($category_tree, 0, 8);
$main_categories_full = array_slice($category_tree_full, 0, 8);

// Fetch only the 8 main categories (top-level)
$main_categories_stmt = $conn->prepare("SELECT id, name, slug FROM product_categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name LIMIT 8");
$main_categories_stmt->execute();
$main_categories = $main_categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build an array of main category IDs
$main_cat_ids = array_column($main_categories, 'id');
$subcategories_by_parent = [];
if (!empty($main_cat_ids)) {
    // Prepare placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($main_cat_ids), '?'));
    $subcategories_stmt = $conn->prepare("SELECT id, name, slug, parent_id FROM product_categories WHERE parent_id IN ($placeholders)");
    $subcategories_stmt->execute($main_cat_ids);
    $subcategories = $subcategories_stmt->fetchAll(PDO::FETCH_ASSOC);
    // Organize subcategories by parent_id
    foreach ($subcategories as $subcat) {
        $subcategories_by_parent[$subcat['parent_id']][] = $subcat;
    }
}
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
      color: var(--pure-white);
      font-weight: 500;
      text-decoration: none;
      transition: color 0.2s;
   }
   .contact-info a:hover {
      color: var(--primary-yellow);
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
     max-height: 80px; /* or increase further if needed */
     height: 110px;
     width: auto;
     display: block;
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
      justify-content: flex-end;
      gap: 1.2rem;
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
      color: var(--primary-green);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
      white-space: nowrap;
      font-size: 1.4rem;
   }
   
   .nav-menu > li > a i {
      margin-right: 0.8rem;
   }
   
   /* Mega Menu */
   .mega-menu {
      position: relative;
   }
   
   .mega-menu-content {
      display: none;
      position: absolute;
      left: 0;
      top: 100%;
      width: 380px;
      background: #fff;
      box-shadow: 0 6px 32px rgba(0,0,0,0.09);
      border-radius: 0 0 1.2rem 1.2rem;
      z-index: 1000;
      padding: 0;
      margin: 0;
      border: none;
   }
   .mega-menu:hover .mega-menu-content,
   .mega-menu:focus-within .mega-menu-content {
      display: block;
   }
   .mega-menu-main-categories {
      width: 30rem;
      border-right: none;
      padding-right: 0;
   }
   
   .main-category {
      display: flex;
      align-items: center;
      padding: 1rem 1.2rem;
      color: var(--primary-green);
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
      color: var(--primary-green);
      text-decoration: none;
      transition: var(--transition);
      border-radius: 0.4rem;
      margin-bottom: 0.4rem;
      font-size: 1.4rem;
   }
   
   .subcategory-column a:hover {
      color: var(--dark-green);
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
      color: var(--primary-green);
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
      color: var(--primary-green);
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
         position: static !important;
         width: 100vw !important;
         max-width: 100vw !important;
         border-radius: 0;
         padding: 0.5rem 0;
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
   
   /* Navigation links (header) as plain words, no background or border */
   .nav-menu > li > a,
   .mobile-nav-link {
      background: none;
      color: var(--primary-green);
      font-weight: 600;
      border: none;
      border-radius: 0;
      box-shadow: none;
      padding: 1.2rem 1.5rem;
      transition: color 0.2s;
   }
   .nav-menu > li > a:hover,
   .mobile-nav-link:hover {
      color: var(--dark-green);
      background: none;
   }

   /* Categories and subcategories remain as buttons */
   .main-category,
   .subcategory-link,
   .mobile-submenu-link {
      background: var(--primary-green);
      color: #fff;
      border-radius: 0.4rem;
      font-weight: 600;
      border: none;
      box-shadow: none;
      transition: background 0.2s, color 0.2s;
   }
   .main-category:hover,
   .subcategory-link:hover,
   .mobile-submenu-link:hover {
      background: var(--dark-green);
      color: #fff;
   }
   
   /* Remove default background for nav links to keep only color */
   .nav-menu > li > a,
   .main-category,
   .subcategory-link,
   .mobile-nav-link,
   .mobile-submenu-link {
      box-shadow: none;
      border: none;
   }
   
   .phone-btn {
      background: none;
      color: var(--primary-green) !important;
      font-weight: 600;
      border: none;
      border-radius: 0;
      box-shadow: none;
      padding: 1.2rem 1.5rem;
      transition: color 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.7rem;
      letter-spacing: 0.03em;
      margin-left: 0;
      margin-right: 0;
   }
   .phone-btn:hover {
      color: var(--dark-green) !important;
      background: none;
   }
   
   .offers-link {
      color: var(--primary-green);
      font-weight: 600;
   }
   .offers-link:hover {
      color: var(--dark-green);
   }
   
   .category-menu-wrapper {
      position: relative;
      margin: 0;
      padding: 0;
      height: 100%;
   }
   .category-level {
      list-style: none;
      margin: 0;
      padding: 0;
      min-width: 200px;
      position: absolute;
      left: 100%;
      top: 0;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.08);
      border-radius: 0.6rem;
      padding: 0.5rem 0;
      z-index: 999;
   }
   .category-level.level-0 {
      position: static;
      left: 0;
      top: 0;
      min-width: 200px;
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      padding: 0;
      z-index: 1000;
   }
   .category-submenu {
      display: none;
      position: absolute;
      left: 100%;
      top: 0;
      min-width: 220px;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.08);
      border-radius: 0 0.6rem 0.6rem 0;
      padding: 0.5rem 0;
      z-index: 1001;
      margin-left: 0;
      transform: translateX(0); /* Ensure no extra gap */
   }

   /* Mega Menu: Remove gap between main and submenu */
   .mega-menu-content {
      display: none;
      position: absolute;
      left: 0;
      top: 100%;
      min-width: unset;
      width: auto;
      background: #fff;
      box-shadow: 0 1rem 1.5rem rgba(0,0,0,0.1);
      border-radius: 0.8rem;
      padding: 0;
      margin: 0;
      border: none;
      z-index: 1000;
   }
   .category-menu-wrapper {
      position: relative;
      display: flex;
      flex-direction: column;
      margin: 0;
      padding: 0;
      height: 100%;
      background: none;
   }
   .main-category-wrap {
      position: relative;
      display: block;
      margin: 0;
      padding: 0;
      background: none;
   }
   .category-submenu {
      display: none;
      position: absolute;
      left: 100%;
      top: 0;
      min-width: 220px;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.08);
      border-radius: 0 0.6rem 0.6rem 0;
      padding: 0.5rem 0;
      z-index: 1001;
      margin-left: 0;
      border-left: none;
   }
   .main-category-wrap:hover > .category-submenu,
   .main-category-wrap.open > .category-submenu {
      display: block;
   }
   /* Remove right border-radius from main menu card for seamless look */
   .mega-menu-content {
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
   }
   .category-submenu {
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
   }

   /* Remove inline display:none from PHP output if present */
   .category-submenu {
      margin-left: 0 !important;
      border-left: none !important;
      left: 100% !important;
      top: 0 !important;
      /* Remove any background or border that creates a visible line */
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.08);
      border-radius: 0 0.6rem 0.6rem 0;
      padding: 0.5rem 0;
      z-index: 1001;
      min-width: 220px;
   }
   .main-category-wrap {
      margin-bottom: 0;
   }
   .category-menu-wrapper {
      gap: 0;
   }
   /* Ensure submenu is flush with main menu */
   .main-category-wrap > .category-submenu {
      left: 100% !important;
      margin-left: 0 !important;
   }

   /* Remove the gap/line between main and submenus */
   .category-submenu {
      margin-left: 0 !important;
      border-left: 0 !important;
      left: 100% !important;
      top: 0 !important;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.08);
      border-radius: 0 0.6rem 0.6rem 0;
      padding: 0.5rem 0;
      z-index: 1001;
      min-width: 220px;
      /* Remove any ::before or ::after lines */
   }
   .main-category-wrap {
      margin-bottom: 0;
   }
   .category-menu-wrapper {
      gap: 0;
   }
   .main-category-wrap > .category-submenu {
      left: 100% !important;
      margin-left: 0 !important;
   }
   /* Remove connecting line if present */
   .category-submenu::before,
   .category-submenu::after {
      display: none !important;
      content: none !important;
      background: none !important;
      border: none !important;
      width: 0 !important;
      height: 0 !important;
   }
   /* Remove any border-right or border-left from main menu */
   .main-category-link, .main-category-wrap {
      border-right: none !important;
      border-left: none !important;
   }
   .lime-btn {
      background-color: var(--primary-green);
      color: #fff;
      border-radius: 0.4rem;
      padding: 0.7rem 1.5rem;
      border: none;
      margin-left: 1rem;
      font-weight: 500;
      transition: background 0.2s, color 0.2s;
      text-decoration: none;
      display: inline-block;
   }
   .lime-btn:hover {
      background-color: var(--dark-green);
      color: #fff;
      opacity: 0.9;
   }
   .header-actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 1.2rem;
   }
   </style>
</head>
<body>
<header class="header">
   <!-- Top Bar -->
   <div class="top-bar">
      <div class="container">
         <div class="contact-info">
            <!-- Brand name removed as requested -->
         </div>
         <div class="auth-links">
            <?php if(isset($user_id) && $user_id): ?>
               <a href="user_dashboard.php"><i class="fas fa-user-circle"></i> My Account</a>
            <?php else: ?>
               <a href="user_login.php" class="lime-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
               <a href="user_register.php" class="lime-btn"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
         </div>
      </div>
   </div>

   <!-- Main Header -->
   <section class="main-header">
      <div class="container">
         <a href="index.php" class="logo">
            <img src="images/logo.png" alt="Syokichem Logo" class="logo-img">
            
         </a>

         <div class="search-container">
            <form action="search.php" method="GET" id="headerSearchForm" autocomplete="on">
               <input type="text" name="query" placeholder="Search medicines, products..." autocomplete="on" required>
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
                  <div class="category-menu-wrapper">
                    <?php foreach ($main_categories_full as $cat): ?>
                      <div class="main-category-wrap">
                        <a href="shop.php?category=<?= htmlspecialchars($cat['id']) ?>" class="main-category-link">
                          <?= htmlspecialchars($cat['name']) ?>
                          <?php if (!empty($cat['children'])): ?>
                            <i class="fas fa-chevron-right"></i>
                          <?php endif; ?>
                        </a>
                        <?php if (!empty($cat['children'])): ?>
                          <div class="category-submenu">
                            <?php foreach ($cat['children'] as $child): ?>
                              <div class="submenu-item">
                                <a href="shop.php?category=<?= htmlspecialchars($child['id']) ?>" class="submenu-link">
                                  <?= htmlspecialchars($child['name']) ?>
                                  <?php if (!empty($child['children'])): ?>
                                    <i class="fas fa-chevron-right"></i>
                                  <?php endif; ?>
                                </a>
                                <?php if (!empty($child['children'])): ?>
                                  <div class="category-submenu sub-submenu">
                                    <?php foreach ($child['children'] as $grandchild): ?>
                                      <a href="shop.php?category=<?= htmlspecialchars($grandchild['id']) ?>" class="submenu-link">
                                        <?= htmlspecialchars($grandchild['name']) ?>
                                      </a>
                                    <?php endforeach; ?>
                                  </div>
                                <?php endif; ?>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
               </div>
            </li>
            <li><a href="prescription.php"><i class="fas fa-prescription-bottle-alt"></i> Submit Prescription</a></li>
            <li><a href="telemedicine.php"><i class="fas fa-user-md"></i> Book a Consultation</a></li>
            <li><a href="special_offers.php" class="offers-link"><i class="fas fa-gift"></i> Offers</a></li>
            <li>
              <a href="tel:+254792914662" class="nav-link phone-btn">
                <i class="fas fa-phone-alt"></i> +254792914662
              </a>
            </li>
         </ul>
      </div>
   </nav>
</header>

<!-- Mobile Navigation -->
<div class="mobile-nav" id="mobileNav">
   <div class="mobile-nav-header">
      <a href="index.php" class="logo">
         <img src="images/logo.png" alt="Syokichem Logo" class="logo-img" style="height: 4rem;">
         <div class="logo-text">
            
         </div>
      </a>
      <button class="mobile-nav-close" id="mobileNavClose">
         <i class="fas fa-times"></i>
      </button>
   </div>
   
   <ul class="mobile-nav-menu">
      <li class="mobile-nav-item"><a href="index.php" class="mobile-nav-link"><i class="fas fa-home"></i> Home</a></li>
      <!-- Replace the mobile categories section with the full hierarchy -->
      <li class="mobile-nav-item">
         <a href="javascript:void(0)" class="mobile-nav-link mobile-category-trigger">
            <i class="fas fa-pills"></i> Shop By Category
            <i class="fas fa-chevron-down mobile-submenu-toggle"></i>
         </a>
         <ul class="mobile-submenu">
            <?php foreach ($main_categories_full as $cat): ?>
            <li class="mobile-submenu-item">
               <?php if (!empty($cat['children'])): ?>
               <a href="javascript:void(0)" class="mobile-submenu-link mobile-category-trigger">
                  <?= htmlspecialchars($cat['name']) ?>
                  <i class="fas fa-chevron-down mobile-submenu-toggle"></i>
               </a>
               <ul class="mobile-submenu">
                  <?php foreach ($cat['children'] as $child): ?>
                  <li class="mobile-submenu-item">
                     <?php if (!empty($child['children'])): ?>
                     <a href="javascript:void(0)" class="mobile-submenu-link mobile-category-trigger">
                        <?= htmlspecialchars($child['name']) ?>
                        <i class="fas fa-chevron-down mobile-submenu-toggle"></i>
                     </a>
                     <ul class="mobile-submenu">
                        <?php foreach ($child['children'] as $grandchild): ?>
                        <li class="mobile-submenu-item">
                           <a href="shop.php?category=<?= htmlspecialchars($grandchild['id']) ?>" class="mobile-submenu-link">
                              <?= htmlspecialchars($grandchild['name']) ?>
                           </a>
                        </li>
                        <?php endforeach; ?>
                     </ul>
                     <?php else: ?>
                     <a href="shop.php?category=<?= htmlspecialchars($child['id']) ?>" class="mobile-submenu-link">
                        <?= htmlspecialchars($child['name']) ?>
                     </a>
                     <?php endif; ?>
                  </li>
                  <?php endforeach; ?>
               </ul>
               <?php else: ?>
               <a href="shop.php?category=<?= htmlspecialchars($cat['id']) ?>" class="mobile-submenu-link">
                  <?= htmlspecialchars($cat['name']) ?>
               </a>
               <?php endif; ?>
            </li>
            <?php endforeach; ?>
         </ul>
      </li>
      <li class="mobile-nav-item"><a href="prescription.php" class="mobile-nav-link"><i class="fas fa-prescription-bottle-alt"></i> Submit Prescription</a></li>
      <li class="mobile-nav-item"><a href="telemedicine.php" class="mobile-nav-link"><i class="fas fa-user-md"></i> Book a Consultation</a></li>
      <li class="mobile-nav-item"><a href="special_offers.php" class="mobile-nav-link offers-link"><i class="fas fa-gift"></i> Offers</a></li>
      <li class="mobile-nav-item">
        <a href="tel:+254792914662" class="mobile-nav-link">
          <i class="fas fa-phone-alt"></i> +254792914662
        </a>
      </li>
   </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Mobile: accordion for all levels
  if (window.innerWidth <= 992) {
    document.querySelectorAll('.category-item').forEach(function(item) {
      var link = item.querySelector('a');
      var submenu = item.querySelector('.category-submenu');
      if (submenu && link) {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          item.classList.toggle('expanded');
        });
      }
    });
  }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
  
  // Close mega menu when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.mega-menu') && !e.target.closest('.shop-trigger')) {
      megaMenuContent.style.display = 'none';
    }
  });
  
  // Ensure the image path is consistent with the upload path in add.php
  // Update the AJAX search result rendering logic to include product images
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
                        html += `<li style="display:flex;align-items:center;padding:0.7rem 1rem;border-bottom:1px solid #eee;">
                            <img src='images/products/${p.image_01}' alt='${p.name}' style="width:44px;height:44px;object-fit:cover;border-radius:6px;margin-right:1rem;">
                            <div style="flex:1;"><a href='quick_view.php?pid=${p.id}' style="font-weight:500;color:#222;text-decoration:none;">${p.name}</a><br><span style="color:#689F38;font-size:1.2rem;">KSh ${parseFloat(p.price).toLocaleString()}</span></div>
                            <span style="margin-left:1rem;font-size:1.1rem;">${p.stock > 0 ? 'In Stock' : 'Out of Stock'}</span>
                        </li>`;
                    });
                    html += '</ul>';
                    resultsBox.innerHTML = html;
                    resultsBox.style.display = 'block';

                    // Auto-refresh logic to clear results after a few seconds
                    setTimeout(() => {
                        resultsBox.style.display = 'none';
                        resultsBox.innerHTML = '';
                    }, 5000); // 5 seconds
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
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Only apply this on desktop
  if (window.innerWidth > 992) {
    document.querySelectorAll('.main-category-wrap').forEach(function(item) {
      item.addEventListener('mouseenter', function() {
        item.classList.add('open');
      });
      item.addEventListener('mouseleave', function() {
        item.classList.remove('open');
      });
    });
  }
});
</script>
</body>
</html>