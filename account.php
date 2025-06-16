<?php
require_once 'includes/config.php';

// Require login
if (!is_logged_in()) {
    header('Location: login.php?redirect=account.php');
    exit;
}

// Get current user info
$user_id = get_current_user_id();
$user = new User($db);
$user_data = $user->getById($user_id);

if (!$user_data) {
    $_SESSION['error'] = "User account not found";
    header('Location: logout.php');
    exit;
}

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $profile_data = [
                    'first_name' => trim($_POST['first_name']),
                    'last_name' => trim($_POST['last_name']),
                    'phone' => trim($_POST['phone'])
                ];
                
                if ($user->updateProfile($user_id, $profile_data)) {
                    $_SESSION['success'] = "Profile updated successfully";
                    // Update session name
                    $_SESSION['user_name'] = $profile_data['first_name'] . ' ' . $profile_data['last_name'];
                } else {
                    $_SESSION['error'] = "Failed to update profile";
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    $_SESSION['error'] = "New passwords do not match";
                } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
                    $_SESSION['error'] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
                } else {
                    $user->changePassword($user_id, $current_password, $new_password);
                }
                break;
        }
        
        header('Location: account.php');
        exit;
    }
}

// Get user's recent orders
$orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$orders_stmt = $db->prepare($orders_sql);
$orders_stmt->execute([$user_id]);
$recent_orders = $orders_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .account-hero {
            background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            padding: 4rem 0 2rem;
            margin-top: 80px;
            color: white;
            text-align: center;
        }
        
        .account-hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .account-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 3rem;
            padding: 3rem 0;
        }
        
        .account-sidebar {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
            transform: translateX(5px);
        }
        
        .account-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .content-header {
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            padding: 2rem;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .content-header h2 {
            font-size: 1.75rem;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .content-body {
            padding: 2rem;
        }
        
        .account-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .account-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .profile-card {
            background: #F8FAFC;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn-update {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-update:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .orders-table th {
            background: #F8FAFC;
            font-weight: 600;
            color: #374151;
        }
        
        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #FEF3C7;
            color: #D97706;
        }
        
        .status-processing {
            background: #DBEAFE;
            color: #2563EB;
        }
        
        .status-shipped {
            background: #D1FAE5;
            color: #059669;
        }
        
        .status-delivered {
            background: #D1FAE5;
            color: #065F46;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .welcome-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3B82F6;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 1024px) {
            .account-layout {
                grid-template-columns: 1fr;
            }
            
            .account-sidebar {
                position: static;
                order: 2;
            }
            
            .account-content {
                order: 1;
            }
            
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
                gap: 0.5rem;
            }
            
            .sidebar-menu li {
                margin-bottom: 0;
                flex-shrink: 0;
            }
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .account-hero h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Account Hero -->
    <section class="account-hero">
        <div class="container">
            <h1><i class="fas fa-user-circle"></i> My Account</h1>
            <p>Welcome back, <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>!</p>
        </div>
    </section>

    <!-- Account Layout -->
    <div class="container">
        <div class="account-layout">
            <!-- Sidebar -->
            <aside class="account-sidebar">
                <ul class="sidebar-menu">
                    <li>
                        <a href="#dashboard" class="menu-link active" data-section="dashboard">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="#profile" class="menu-link" data-section="profile">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li>
                        <a href="#orders" class="menu-link" data-section="orders">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </li>
                    <li>
                        <a href="#security" class="menu-link" data-section="security">
                            <i class="fas fa-shield-alt"></i> Security
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="account-content">
                <!-- Dashboard Section -->
                <div id="dashboard" class="account-section active">
                    <div class="content-header">
                        <h2>Dashboard</h2>
                        <p>Overview of your account activity</p>
                    </div>
                    <div class="content-body">
                        <div class="welcome-card">
                            <h3>Welcome back!</h3>
                            <p>You last logged in on <?php echo date('F j, Y \a\t g:i A'); ?></p>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo count($recent_orders); ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php 
                                    $total_spent = 0;
                                    foreach ($recent_orders as $order) {
                                        $total_spent += $order['total_amount'];
                                    }
                                    echo '$' . number_format($total_spent, 2);
                                    ?>
                                </div>
                                <div class="stat-label">Total Spent</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo get_cart_count(); ?></div>
                                <div class="stat-label">Items in Cart</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Section -->
                <div id="profile" class="account-section">
                    <div class="content-header">
                        <h2>Profile Information</h2>
                        <p>Update your personal details</p>
                    </div>
                    <div class="content-body">
                        <div class="profile-grid">
                            <div class="profile-card">
                                <h3 class="card-title">
                                    <i class="fas fa-user"></i> Personal Information
                                </h3>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="form-group">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" id="first_name" name="first_name" class="form-input" 
                                               value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" class="form-input" 
                                               value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" id="email" class="form-input" 
                                               value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                                        <small style="color: #6B7280; font-size: 0.85rem;">Email cannot be changed</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" class="form-input" 
                                               value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <button type="submit" class="btn-update">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Section -->
                <div id="orders" class="account-section">
                    <div class="content-header">
                        <h2>Order History</h2>
                        <p>View your recent purchases</p>
                    </div>
                    <div class="content-body">
                        <?php if (!empty($recent_orders)): ?>
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['id']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="order-status status-<?php echo $order['status'] ?? 'pending'; ?>">
                                                    <?php echo ucfirst($order['status'] ?? 'Pending'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order.php?id=<?php echo $order['id']; ?>" style="color: #3B82F6; text-decoration: none;">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="text-align: center; padding: 3rem; color: #6B7280;">
                                <i class="fas fa-shopping-bag" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <h3>No orders yet</h3>
                                <p>Start shopping to see your orders here!</p>
                                <a href="shop.php" class="btn-update" style="text-decoration: none; display: inline-block; margin-top: 1rem;">
                                    <i class="fas fa-shopping-cart"></i> Start Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Security Section -->
                <div id="security" class="account-section">
                    <div class="content-header">
                        <h2>Security Settings</h2>
                        <p>Manage your account security</p>
                    </div>
                    <div class="content-body">
                        <div class="profile-card">
                            <h3 class="card-title">
                                <i class="fas fa-lock"></i> Change Password
                            </h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="form-input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-input" 
                                           minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                    <small style="color: #6B7280; font-size: 0.85rem;">
                                        Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters required
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                           minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                </div>
                                
                                <button type="submit" class="btn-update">
                                    <i class="fas fa-shield-alt"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle sidebar menu clicks
            $('.menu-link').click(function(e) {
                e.preventDefault();
                
                const section = $(this).data('section');
                
                // Update active menu item
                $('.menu-link').removeClass('active');
                $(this).addClass('active');
                
                // Show corresponding section
                $('.account-section').removeClass('active');
                $('#' + section).addClass('active');
                
                // Update URL hash
                window.location.hash = section;
            });
            
            // Handle browser back/forward
            $(window).on('hashchange', function() {
                const hash = window.location.hash.substring(1);
                if (hash) {
                    $('.menu-link').removeClass('active');
                    $(`.menu-link[data-section="${hash}"]`).addClass('active');
                    
                    $('.account-section').removeClass('active');
                    $('#' + hash).addClass('active');
                }
            });
            
            // Set initial section from URL hash
            const initialHash = window.location.hash.substring(1);
            if (initialHash && $('#' + initialHash).length) {
                $('.menu-link').removeClass('active');
                $(`.menu-link[data-section="${initialHash}"]`).addClass('active');
                
                $('.account-section').removeClass('active');
                $('#' + initialHash).addClass('active');
            }
            
            // Password confirmation validation
            $('#confirm_password').on('input', function() {
                const newPassword = $('#new_password').val();
                const confirmPassword = $(this).val();
                
                if (confirmPassword && newPassword !== confirmPassword) {
                    $(this).css('border-color', '#EF4444');
                } else {
                    $(this).css('border-color', '#E5E7EB');
                }
            });
            
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut(300);
            }, 5000);
        });
    </script>
</body>
</html>