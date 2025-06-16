<?php
require_once '../includes/config.php';

// Require admin access
require_admin();

// Get dashboard statistics
$stats = [];

// Total products
$stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
$stmt->execute();
$stats['products'] = $stmt->fetch()['total'];

// Total users
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$stmt->execute();
$stats['users'] = $stmt->fetch()['total'];

// Total orders
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$stats['orders'] = $stmt->fetch()['total'];

// Total revenue
$stmt = $db->prepare("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
$stmt->execute();
$stats['revenue'] = $stmt->fetch()['total'] ?: 0;

// Recent orders
$stmt = $db->prepare("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Low stock products
$stmt = $db->prepare("SELECT * FROM products WHERE stock_quantity <= 5 AND status = 'active' ORDER BY stock_quantity ASC LIMIT 5");
$stmt->execute();
$low_stock = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
            color: white;
            padding: 2rem 0;
        }
        
        .admin-nav {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }
        
        .admin-nav a {
            color: #374151;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background: #3B82F6;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.products { background: #3B82F6; }
        .stat-icon.users { background: #10B981; }
        .stat-icon.orders { background: #F59E0B; }
        .stat-icon.revenue { background: #EF4444; }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .admin-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .admin-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            background: #F8FAFC;
            padding: 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1F2937;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #F3F4F6;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-number {
            font-weight: 600;
            color: #1F2937;
        }
        
        .order-customer {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .order-amount {
            font-weight: 600;
            color: #10B981;
        }
        
        .stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #F3F4F6;
        }
        
        .stock-item:last-child {
            border-bottom: none;
        }
        
        .stock-name {
            font-weight: 500;
            color: #1F2937;
        }
        
        .stock-count {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .stock-critical {
            background: #FEE2E2;
            color: #DC2626;
        }
        
        .stock-low {
            background: #FEF3C7;
            color: #D97706;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
            
            .admin-nav ul {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>
    </div>
    
    <nav class="admin-nav">
        <div class="container">
            <ul>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="../index.php">View Site</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon products">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['products']); ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['users']); ?></div>
                <div class="stat-label">Customers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['orders']); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['revenue'], 2); ?></div>
                <div class="stat-label">Revenue</div>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="admin-content">
            <!-- Recent Orders -->
            <div class="admin-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Orders</h3>
                </div>
                <div class="card-content">
                    <?php if (!empty($recent_orders)): ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                    <div class="order-customer"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                </div>
                                <div class="order-amount">$<?php echo number_format($order['total_amount'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #6B7280; text-align: center;">No orders yet</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Low Stock -->
            <div class="admin-card">
                <div class="card-header">
                    <h3 class="card-title">Low Stock Alert</h3>
                </div>
                <div class="card-content">
                    <?php if (!empty($low_stock)): ?>
                        <?php foreach ($low_stock as $product): ?>
                            <div class="stock-item">
                                <div class="stock-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <span class="stock-count <?php echo $product['stock_quantity'] <= 2 ? 'stock-critical' : 'stock-low'; ?>">
                                    <?php echo $product['stock_quantity']; ?> left
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #6B7280; text-align: center;">All products are well stocked!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>