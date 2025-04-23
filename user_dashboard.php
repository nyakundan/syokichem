<?php
session_start();
include 'components/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('location:user_login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
$select_profile->execute([$user_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Get order stats (both regular and prescription)
$total_orders = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM `orders` WHERE user_id = ?) + 
        (SELECT COUNT(*) FROM `prescriptions` WHERE user_id = ?) 
    AS total_count
");
$total_orders->execute([$user_id, $user_id]);
$total_orders = $total_orders->fetchColumn();

$pending_orders = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM `orders` WHERE user_id = ? AND order_status = 'pending') + 
        (SELECT COUNT(*) FROM `prescriptions` WHERE user_id = ? AND status = 'pending') 
    AS pending_count
");
$pending_orders->execute([$user_id, $user_id]);
$pending_orders = $pending_orders->fetchColumn();

$completed_orders = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM `orders` WHERE user_id = ? AND order_status = 'delivered') + 
        (SELECT COUNT(*) FROM `prescriptions` WHERE user_id = ? AND status = 'completed') 
    AS completed_count
");
$completed_orders->execute([$user_id, $user_id]);
$completed_orders = $completed_orders->fetchColumn();

// Get recent orders (both regular and prescription)
$recent_orders = $conn->prepare("(
    SELECT id, 'regular' as type, placed_on as order_date, order_status, total_price, total_products 
    FROM `orders` 
    WHERE user_id = ?
    ORDER BY placed_on DESC 
    LIMIT 3
) UNION ALL (
    SELECT id, 'prescription' as type, created_at as order_date, status as order_status, total_price, NULL as total_products
    FROM `prescriptions` 
    WHERE user_id = ?
    ORDER BY created_at DESC 
    LIMIT 3
) ORDER BY order_date DESC LIMIT 6");
$recent_orders->execute([$user_id, $user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>User Dashboard - Syokichem Pharmaceuticals</title>
   
   <!-- Favicon -->
   <link rel="icon" href="images/favicon.png" type="image/png">
   
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
   
   <!-- Dashboard CSS -->
   <style>
   :root {
      --primary: #4361ee;
      --primary-light: #3f37c9;
      --secondary: #3a0ca3;
      --success: #4cc9f0;
      --danger: #f72585;
      --warning: #f8961e;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --white: #ffffff;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
   }

   body {
      font-family: inherit;
      line-height: 1.6;
      color: var(--dark);
   }

   h1, h2, h3, h4, h5, h6, p, span, a, li, td, th, input, textarea, select, button {
      font-family: inherit;
   }

   .stat-card, .order-card, .action-card {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
   }

   .stat-card:hover, .order-card:hover, .action-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transform: translateY(-3px);
   }

   .btn, .btn-view, .btn-back {
      font-weight: 600;
      letter-spacing: 0.5px;
      text-transform: none;
      border-radius: 8px;
      padding: 0.75rem 1.5rem;
      transition: all 0.2s ease;
   }

   .dashboard-title, .section-title {
      font-weight: 700;
      letter-spacing: -0.5px;
   }

   .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
   }

   .stat-label {
      font-size: 1rem;
      color: var(--gray);
   }

   .dashboard-container {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 1.5rem;
   }

   .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
   }

   .dashboard-title {
      font-size: 2.5rem;
      color: var(--dark);
      font-weight: 700;
   }

   .welcome-message {
      font-size: 1.4rem;
      color: var(--gray);
   }

   .welcome-message strong {
      color: var(--primary);
   }

   .dashboard-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 2rem;
   }

   .dashboard-sidebar {
      background: var(--white);
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      padding: 2rem;
      height: fit-content;
   }

   .user-profile {
      text-align: center;
      margin-bottom: 2rem;
   }

   .user-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid var(--primary);
      margin-bottom: 1rem;
   }

   .user-name {
      font-size: 1.4rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
   }

   .user-email {
      color: var(--gray);
      font-size: 1.2rem;
   }

   .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
   }

   .sidebar-menu li {
      margin-bottom: 0.8rem;
   }

   .sidebar-menu a {
      display: flex;
      align-items: center;
      padding: 0.8rem 1rem;
      border-radius: 8px;
      color: var(--dark);
      text-decoration: none;
      transition: all 0.2s ease;
      font-size: 1.4rem;
   }

   .sidebar-menu a:hover, .sidebar-menu a.active {
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
   }

   .sidebar-menu i {
      margin-right: 0.8rem;
      width: 20px;
      text-align: center;
      font-size: 1.4rem;
   }

   .dashboard-content {
      display: flex;
      flex-direction: column;
      gap: 2rem;
   }

   .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
   }

   .stat-card {
      background: var(--white);
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      padding: 1.5rem;
      text-align: center;
      transition: transform 0.3s ease;
   }

   .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.4rem;
   }

   .stat-icon.orders {
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
   }

   .stat-icon.pending {
      background: rgba(247, 37, 133, 0.1);
      color: var(--danger);
   }

   .stat-icon.completed {
      background: rgba(76, 201, 240, 0.1);
      color: var(--success);
   }

   .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
   }

   .stat-label {
      color: var(--gray);
      font-size: 1.4rem;
   }

   .recent-orders-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
   }

   .order-card {
      background: var(--white);
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      padding: 1.5rem;
      transition: all 0.3s ease;
   }

   .order-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
   }

   .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(0,0,0,0.05);
   }

   .order-id {
      font-weight: 600;
      color: var(--dark);
   }

   .order-date {
      color: var(--gray);
      font-size: 0.9rem;
   }

   .order-status {
      display: inline-flex;
      align-items: center;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 1rem;
   }

   .status-pending {
      background: rgba(247, 37, 133, 0.1);
      color: var(--danger);
   }

   .status-delivered {
      background: rgba(76, 201, 240, 0.1);
      color: var(--success);
   }

   .order-products {
      margin-bottom: 1rem;
      color: var(--gray);
   }

   .order-total {
      font-weight: 600;
      color: var(--dark);
   }

   .order-type {
      display: inline-block;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
      margin-bottom: 0.5rem;
   }

   .order-actions {
      margin-top: 1rem;
      display: flex;
      gap: 0.8rem;
   }

   .btn-view {
      padding: 0.5rem 1rem;
      border-radius: 8px;
      background: var(--primary);
      color: var(--white);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      transition: all 0.2s ease;
   }

   .btn-view:hover {
      background: var(--primary-light);
      transform: translateY(-2px);
   }

   .quick-actions {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
   }

   .action-card {
      background: var(--white);
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      padding: 1.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: transform 0.3s ease;
   }

   .action-card:hover {
      transform: translateY(-5px);
   }

   .action-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      color: var(--white);
   }

   .action-icon.shop {
      background: var(--primary);
   }

   .action-icon.profile {
      background: var(--secondary);
   }

   .action-icon.cart {
      background: var(--success);
   }

   .action-icon.support {
      background: var(--warning);
   }

   .action-content h3 {
      font-size: 1.4rem;
      margin-bottom: 0.3rem;
   }

   .action-content p {
      color: var(--gray);
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
   }

   .action-link {
      color: var(--primary);
      text-decoration: none;
      font-size: 1.2rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
   }

   @media (max-width: 1024px) {
      .dashboard-grid {
         grid-template-columns: 1fr;
      }

      .stats-grid {
         grid-template-columns: repeat(2, 1fr);
      }

      .quick-actions {
         grid-template-columns: 1fr;
      }
   }

   @media (max-width: 768px) {
      .stats-grid {
         grid-template-columns: 1fr;
      }

      .dashboard-header {
         flex-direction: column;
         align-items: flex-start;
         gap: 1rem;
      }

      .dashboard-title {
         font-size: 2.2rem;
      }

      .section-title {
         font-size: 2.2rem;
      }
   }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>
<?php include 'components/policy_dashboard.php';?>

<section class="dashboard-container">
   <div class="dashboard-header">
      <div>
         <h1 class="dashboard-title">Dashboard</h1>
         <p class="welcome-message">Welcome back, <strong><?= htmlspecialchars($fetch_profile['name']); ?></strong></p>
      </div>
      <div>
         <a href="update_profile.php" class="btn">Edit Profile</a>
      </div>
   </div>

   <div class="dashboard-grid">
      <aside class="dashboard-sidebar">
         <div class="user-profile">
            <img src="uploaded_img/<?= htmlspecialchars($fetch_profile['image']); ?>" class="user-avatar" alt="Profile Image">
            <h3 class="user-name"><?= htmlspecialchars($fetch_profile['name']); ?></h3>
            <p class="user-email"><?= htmlspecialchars($fetch_profile['email']); ?></p>
         </div>

         <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="orders.php"><i class="fas fa-clipboard-list"></i> My Orders</a></li>
            <li><a href="prescription.php"><i class="fas fa-prescription-bottle-alt"></i> My Prescriptions</a></li>
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> My Cart</a></li>
            <li><a href="update_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
            <li><a href="components/user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
         </ul>
      </aside>

      <main class="dashboard-content">
         <div class="stats-grid">
            <div class="stat-card">
               <div class="stat-icon orders">
                  <i class="fas fa-clipboard-list"></i>
               </div>
               <div class="stat-value"><?= $total_orders; ?></div>
               <div class="stat-label">Total Orders</div>
            </div>

            <div class="stat-card">
               <div class="stat-icon pending">
                  <i class="fas fa-clock"></i>
               </div>
               <div class="stat-value"><?= $pending_orders; ?></div>
               <div class="stat-label">Pending Orders</div>
            </div>

            <div class="stat-card">
               <div class="stat-icon completed">
                  <i class="fas fa-check-circle"></i>
               </div>
               <div class="stat-value"><?= $completed_orders; ?></div>
               <div class="stat-label">Completed Orders</div>
            </div>
         </div>

         <div class="recent-orders">
            <div class="section-header">
               <h2 class="section-title">Recent Orders</h2>
               <a href="orders.php" class="view-all">View All</a>
            </div>

            <?php if($recent_orders->rowCount() > 0): ?>
               <div class="recent-orders-grid">
                  <?php while($fetch_order = $recent_orders->fetch(PDO::FETCH_ASSOC)): 
                     $is_prescription = ($fetch_order['type'] == 'prescription');
                  ?>
                     <div class="order-card">
                        <div class="order-header">
                           <span class="order-id">#<?= $fetch_order['id']; ?></span>
                           <span class="order-date"><?= date('M j, Y', strtotime($fetch_order['order_date'])); ?></span>
                        </div>
                        
                        <span class="order-type">
                           <?= $is_prescription ? 'Prescription Order' : 'Regular Order' ?>
                        </span>
                        
                        <span class="order-status status-<?= $fetch_order['order_status']; ?>">
                           <?= ucfirst($fetch_order['order_status']); ?>
                        </span>
                        
                        <?php if(!$is_prescription): ?>
                           <div class="order-products">
                              <?= htmlspecialchars(truncateText($fetch_order['total_products'], 50)); ?>
                           </div>
                        <?php endif; ?>
                        
                        <div class="order-total">
                           Total: KSh <?= number_format($fetch_order['total_price'], 2); ?>
                        </div>
                        
                        <div class="order-actions">
                           <a href="view_order.php?id=<?= $fetch_order['id']; ?>&type=<?= $is_prescription ? 'prescription' : 'regular' ?>" class="btn-view">
                              <i class="fas fa-eye"></i> View Details
                           </a>
                        </div>
                     </div>
                  <?php endwhile; ?>
               </div>
            <?php else: ?>
               <p style="text-align: center; padding: 2rem;">No recent orders found</p>
            <?php endif; ?>
         </div>

         <div class="quick-actions">
            <div class="section-header">
               <h2 class="section-title">Quick Actions</h2>
            </div>
            
            <div class="actions-grid">
               <div class="action-card">
                  <div class="action-icon shop">
                     <i class="fas fa-shopping-bag"></i>
                  </div>
                  <div class="action-content">
                     <h3>Continue Shopping</h3>
                     <p>Browse our latest pharmaceutical products</p>
                     <a href="shop.php" class="action-link">Shop Now <i class="fas fa-arrow-right"></i></a>
                  </div>
               </div>

               <div class="action-card">
                  <div class="action-icon profile">
                     <i class="fas fa-user-edit"></i>
                  </div>
                  <div class="action-content">
                     <h3>Update Profile</h3>
                     <p>Manage your account information</p>
                     <a href="update_profile.php" class="action-link">Update Now <i class="fas fa-arrow-right"></i></a>
                  </div>
               </div>

               <div class="action-card">
                  <div class="action-icon cart">
                     <i class="fas fa-shopping-cart"></i>
                  </div>
                  <div class="action-content">
                     <h3>View Cart</h3>
                     <p>Review items in your shopping cart</p>
                     <a href="cart.php" class="action-link">View Cart <i class="fas fa-arrow-right"></i></a>
                  </div>
               </div>

               <div class="action-card">
                  <div class="action-icon support">
                     <i class="fas fa-headset"></i>
                  </div>
                  <div class="action-content">
                     <h3>Customer Support</h3>
                     <p>Need help? Contact our support team</p>
                     <a href="contact.php" class="action-link">Contact Us <i class="fas fa-arrow-right"></i></a>
                  </div>
               </div>
            </div>
         </div>
      </main>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>

<?php
// Helper function to truncate long text
function truncateText($text, $length) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
?>