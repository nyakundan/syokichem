<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/components/connect.php';

// Check if user is authorized
//if (!isset($_SESSION['admin_id'])) {
   // $_SESSION['error'] = 'Unauthorized access';
   // header('Location: login.php');
  //  exit();
//}

// Set page title
$page_title = "Reports Dashboard";

// Date range filter with proper validation
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
}

// Initialize variables with default values
$sales_data = [];
$top_products = [];
$top_customers = [];
$categories_data = [];
$total_orders = 0;
$total_sales = 0.0;
$sales_error = $products_error = $customers_error = $categories_error = null;

// Sales Report Data
try {
    $sales_stmt = $conn->prepare("
        SELECT 
            DATE(placed_on) as date,
            COUNT(*) as order_count,
            SUM(total_price) as total_sales
        FROM orders
        WHERE order_status = 'delivered' 
        AND placed_on BETWEEN ? AND ?
        GROUP BY DATE(placed_on)
        ORDER BY date ASC
    ");
    $sales_stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $sales_data = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals with type safety
    $total_orders = array_sum(array_column($sales_data, 'order_count'));
    $total_sales = (float)array_sum(array_column($sales_data, 'total_sales'));
} catch (PDOException $e) {
    error_log("Error in sales report: " . $e->getMessage());
    $sales_error = "Failed to load sales data";
}

// Product Performance Data
try {
    $products_stmt = $conn->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            COALESCE(SUM(op.quantity), 0) as sold_quantity
        FROM products p
        LEFT JOIN order_products op ON p.id = op.product_id
        LEFT JOIN orders o ON op.order_id = o.id 
        AND o.order_status = 'delivered'
        AND o.placed_on BETWEEN ? AND ?
        GROUP BY p.id
        HAVING sold_quantity > 0
        ORDER BY sold_quantity DESC
        LIMIT 10
    ");
    $products_stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $top_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate revenue with proper type handling
    foreach ($top_products as &$product) {
        $product['sold_quantity'] = (int)$product['sold_quantity'];
        $product['price'] = (float)$product['price'];
        $product['revenue'] = $product['price'] * $product['sold_quantity'];
    }
    unset($product);
} catch (PDOException $e) {
    error_log("Error in product performance: " . $e->getMessage());
    $products_error = "Failed to load product data";
}

// Customer Data
try {
    $customers_stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            COUNT(o.id) as order_count,
            COALESCE(SUM(o.total_price), 0) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
            AND o.order_status = 'delivered'
            AND o.placed_on BETWEEN ? AND ?
        GROUP BY u.id
        HAVING order_count > 0
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $customers_stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $top_customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error in customer report: " . $e->getMessage());
    $customers_error = "Failed to load customer data";
}

// Category Performance
try {
    $categories_stmt = $conn->prepare("
        SELECT 
            c.id,
            c.name,
            COUNT(DISTINCT p.id) as product_count,
            COALESCE(SUM(op.quantity), 0) as total_sold
        FROM product_categories c
        LEFT JOIN products p ON p.category_id = c.id
        LEFT JOIN order_products op ON p.id = op.product_id
        LEFT JOIN orders o ON op.order_id = o.id 
        AND o.order_status = 'delivered'
        AND o.placed_on BETWEEN ? AND ?
        GROUP BY c.id
        ORDER BY total_sold DESC
    ");
    $categories_stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $categories_data = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error in category report: " . $e->getMessage());
    $categories_error = "Failed to load category data";
}

include __DIR__ . '/includes/admin_header.php';
?>

<div class="container">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-primary">Reports Dashboard</h1>
            <form method="get" class="d-flex gap-2">
                <div class="input-group">
                    <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    <span class="input-group-text">to</span>
                    <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary h-100 py-2">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Sales</div>
                                    <div class="h5 mb-0 font-weight-bold">
                                        Ksh.<?php echo number_format($total_sales, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success h-100 py-2">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Orders</div>
                                    <div class="h5 mb-0 font-weight-bold">
                                        <?php echo number_format($total_orders); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info h-100 py-2">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Avg. Order Value</div>
                                    <div class="h5 mb-0 font-weight-bold">
                                        Ksh.<?php echo $total_orders > 0 ? number_format($total_sales / $total_orders, 2) : '0.00'; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning h-100 py-2">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Active Categories</div>
                                    <div class="h5 mb-0 font-weight-bold">
                                        <?php echo count(array_filter($categories_data, fn($cat) => $cat['total_sold'] > 0)); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-folder fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Chart -->
            <div class="row mb-4">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Sales Overview</h6>
                        </div>
                        <div class="card-body">
                            <?php if (isset($sales_error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($sales_error); ?>
                                </div>
                            <?php elseif (empty($sales_data)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No sales data available for the selected period
                                </div>
                            <?php else: ?>
                                <div class="chart-area">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Category Distribution</h6>
                        </div>
                        <div class="card-body">
                            <?php if (isset($categories_error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($categories_error); ?>
                                </div>
                            <?php elseif (empty($categories_data)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No category data available
                                </div>
                            <?php else: ?>
                                <div class="chart-pie pt-4">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                                <div class="mt-4 text-center small">
                                    <?php foreach ($categories_data as $category): ?>
                                        <?php if ($category['total_sold'] > 0): ?>
                                            <span class="me-2">
                                                <i class="fas fa-circle" style="color: #<?php echo substr(md5($category['name']), 0, 6); ?>"></i> 
                                                <?php echo htmlspecialchars($category['name']); ?>
                                                (<?php echo (int)$category['total_sold']; ?>)
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Products and Customers -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Selling Products</h6>
                        </div>
                        <div class="card-body">
                            <?php if (isset($products_error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($products_error); ?>
                                </div>
                            <?php elseif (empty($top_products)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No product data available for the selected period
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-end">Sold</th>
                                                <th class="text-end">Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $product): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                    <td class="text-end"><?php echo number_format($product['sold_quantity']); ?></td>
                                                    <td class="text-end">Ksh.<?php echo number_format($product['revenue'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Customers</h6>
                        </div>
                        <div class="card-body">
                            <?php if (isset($customers_error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($customers_error); ?>
                                </div>
                            <?php elseif (empty($top_customers)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No customer data available for the selected period
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Customer</th>
                                                <th class="text-end">Orders</th>
                                                <th class="text-end">Total Spent</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_customers as $customer): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($customer['name']); ?>
                                                        <small class="text-muted d-block">
                                                            <?php echo htmlspecialchars($customer['email']); ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-end"><?php echo number_format($customer['order_count']); ?></td>
                                                    <td class="text-end">Ksh.<?php echo number_format($customer['total_spent'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    <?php if (!empty($sales_data)): ?>
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(fn($row) => date('M j', strtotime($row['date'])), $sales_data)); ?>,
            datasets: [{
                label: 'Daily Sales',
                data: <?php echo json_encode(array_map(fn($row) => (float)$row['total_sales'], $sales_data)); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: 20
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => 'Ksh.' + value.toLocaleString()
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: context => 'Ksh.' + context.raw.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Category Chart
    <?php if (!empty($categories_data)): ?>
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map(fn($cat) => $cat['name'], 
                array_filter($categories_data, fn($cat) => $cat['total_sold'] > 0))); ?>,
            datasets: [{
                data: <?php echo json_encode(array_map(fn($cat) => $cat['total_sold'], 
                    array_filter($categories_data, fn($cat) => $cat['total_sold'] > 0))); ?>,
                backgroundColor: <?php echo json_encode(array_map(
                    fn($cat) => '#' . substr(md5($cat['name']), 0, 6),
                    array_filter($categories_data, fn($cat) => $cat['total_sold'] > 0)
                )); ?>
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: context => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            return `${context.label}: ${context.raw} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>