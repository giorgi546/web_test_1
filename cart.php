<?php
require_once 'includes/config.php';

// Get cart items
$cart_items = get_cart_items();
$cart_total = 0;
$cart_count = 0;

foreach ($cart_items as $item) {
    $price = $item['sale_price'] ?: $item['price'];
    $cart_total += $price * $item['quantity'];
    $cart_count += $item['quantity'];
}

// Handle cart updates via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $product_id = intval($_POST['product_id']);
                $quantity = max(1, intval($_POST['quantity']));
                
                if (is_logged_in()) {
                    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$quantity, get_current_user_id(), $product_id]);
                } else {
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id] = $quantity;
                    }
                }
                
                $_SESSION['success'] = "Cart updated successfully";
                header('Location: cart.php');
                exit;
                
            case 'remove_item':
                $product_id = intval($_POST['product_id']);
                
                if (remove_from_cart($product_id)) {
                    $_SESSION['success'] = "Item removed from cart";
                } else {
                    $_SESSION['error'] = "Failed to remove item";
                }
                
                header('Location: cart.php');
                exit;
                
            case 'clear_cart':
                if (clear_cart()) {
                    $_SESSION['success'] = "Cart cleared successfully";
                } else {
                    $_SESSION['error'] = "Failed to clear cart";
                }
                
                header('Location: cart.php');
                exit;
        }
    }
}

// Calculate shipping
$shipping_cost = 0;
$free_shipping_threshold = 50;

if ($cart_total > 0 && $cart_total < $free_shipping_threshold) {
    $shipping_cost = 9.99;
}

$final_total = $cart_total + $shipping_cost;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Cart-specific styles */
        .cart-hero {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            padding: 4rem 0 2rem;
            margin-top: 80px;
            color: white;
            text-align: center;
        }
        
        .cart-hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            padding: 3rem 0;
        }
        
        .cart-items-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .cart-header {
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            padding: 2rem;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .cart-header h2 {
            font-size: 1.5rem;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .items-count {
            color: #6B7280;
            font-weight: 500;
        }
        
        .cart-items-list {
            padding: 0;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto auto auto;
            gap: 1.5rem;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid #F3F4F6;
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            background: linear-gradient(135deg, #FEFEFE 0%, #F8FAFC 100%);
        }
        
        .item-image {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .cart-item:hover .item-image img {
            transform: scale(1.05);
        }
        
        .item-details h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .item-details a {
            color: #1F2937;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .item-details a:hover {
            color: #3B82F6;
        }
        
        .item-price-unit {
            color: #6B7280;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
        
        .item-stock {
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        
        .stock-ok { color: #10B981; }
        .stock-low { color: #F59E0B; }
        .stock-out { color: #EF4444; }
        
        .quantity-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }
        
        .qty-btn {
            width: 45px;
            height: 45px;
            border: none;
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #374151;
        }
        
        .qty-btn:hover {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
            transform: scale(1.05);
        }
        
        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .qty-input {
            width: 60px;
            height: 45px;
            border: none;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
            background: white;
        }
        
        .item-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1F2937;
            text-align: right;
        }
        
        .original-price {
            font-size: 1rem;
            color: #9CA3AF;
            text-decoration: line-through;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .remove-btn {
            background: none;
            border: none;
            color: #EF4444;
            cursor: pointer;
            font-size: 1.5rem;
            padding: 0.75rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-btn:hover {
            background: #FEE2E2;
            transform: scale(1.1) rotate(5deg);
        }
        
        .cart-footer {
            padding: 2rem;
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .continue-shopping {
            color: #3B82F6;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .continue-shopping:hover {
            color: #2563EB;
            transform: translateX(-5px);
        }
        
        .clear-cart-btn {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .clear-cart-btn:hover {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }
        
        .cart-summary {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        
        .summary-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .summary-header h3 {
            font-size: 1.5rem;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }
        
        .summary-row.subtotal {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .summary-row.total {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1F2937;
            border-top: 2px solid #E5E7EB;
            padding-top: 1rem;
            margin-top: 1.5rem;
        }
        
        .shipping-info {
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            border: 2px solid #10B981;
            border-radius: 12px;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: center;
        }
        
        .shipping-progress {
            background: #FEF3C7;
            border: 2px solid #F59E0B;
            border-radius: 12px;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: center;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #E5E7EB;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10B981, #059669);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 1.2rem 2rem;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .checkout-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }
        
        .checkout-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .security-badges {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #E5E7EB;
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #6B7280;
            font-size: 0.85rem;
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .empty-cart i {
            font-size: 5rem;
            color: #D1D5DB;
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 60%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            80% { transform: translateY(-10px); }
        }
        
        .empty-cart h3 {
            font-size: 2rem;
            color: #1F2937;
            margin-bottom: 1rem;
        }
        
        .empty-cart p {
            color: #6B7280;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .shop-now-btn {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .shop-now-btn:hover {
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        
        /* Coupon Section */
        .coupon-section {
            margin: 1.5rem 0;
            padding: 1rem;
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            border-radius: 12px;
            border: 2px dashed #CBD5E1;
        }
        
        .coupon-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #374151;
        }
        
        .coupon-input {
            display: flex;
            gap: 0.5rem;
        }
        
        .coupon-input input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .coupon-input input:focus {
            outline: none;
            border-color: #3B82F6;
        }
        
        .apply-coupon-btn {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .apply-coupon-btn:hover {
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
                order: 1;
            }
            
            .cart-items-section {
                order: 2;
            }
        }
        
        @media (max-width: 768px) {
            .cart-hero h1 {
                font-size: 2rem;
            }
            
            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 1rem;
                text-align: left;
            }
            
            .item-image {
                width: 80px;
                height: 80px;
            }
            
            .item-actions {
                grid-column: 1 / -1;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid #F3F4F6;
            }
            
            .cart-footer {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
        
        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .updating {
            position: relative;
        }
        
        .updating::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            border: 2px solid #E5E7EB;
            border-top: 2px solid #3B82F6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Cart Hero Section -->
    <section class="cart-hero">
        <div class="container">
            <h1><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>
            <p>Review your items and proceed to checkout</p>
        </div>
    </section>

    <!-- Main Cart Content -->
    <div class="container">
        <?php if (!empty($cart_items)): ?>
            <div class="cart-layout">
                <!-- Cart Items Section -->
                <div class="cart-items-section">
                    <div class="cart-header">
                        <h2>Your Items</h2>
                        <p class="items-count"><?php echo $cart_count; ?> item<?php echo $cart_count !== 1 ? 's' : ''; ?> in your cart</p>
                    </div>
                    
                    <div class="cart-items-list">
                        <?php foreach ($cart_items as $item): ?>
                            <?php 
                            $item_price = $item['sale_price'] ?: $item['price'];
                            $item_total = $item_price * $item['quantity'];
                            ?>
                            <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                                <div class="item-image">
                                    <img src="<?php echo $item['main_image'] ? 'uploads/products/' . $item['main_image'] : 'images/placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                
                                <div class="item-details">
                                    <h3>
                                        <a href="product.php?id=<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h3>
                                    <div class="item-price-unit">
                                        <?php if ($item['sale_price']): ?>
                                            <span class="original-price">$<?php echo number_format($item['price'], 2); ?></span>
                                            $<?php echo number_format($item['sale_price'], 2); ?> each
                                        <?php else: ?>
                                            $<?php echo number_format($item['price'], 2); ?> each
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-stock <?php echo $item['stock_quantity'] <= 0 ? 'stock-out' : ($item['stock_quantity'] <= 5 ? 'stock-low' : 'stock-ok'); ?>">
                                        <i class="fas fa-<?php echo $item['stock_quantity'] <= 0 ? 'times-circle' : ($item['stock_quantity'] <= 5 ? 'exclamation-triangle' : 'check-circle'); ?>"></i>
                                        <?php if ($item['stock_quantity'] <= 0): ?>
                                            Out of stock
                                        <?php elseif ($item['stock_quantity'] <= 5): ?>
                                            Only <?php echo $item['stock_quantity']; ?> left
                                        <?php else: ?>
                                            In stock
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="quantity-wrapper">
                                    <div class="quantity-controls">
                                        <button type="button" class="qty-btn qty-decrease" data-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" 
                                               class="qty-input" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" 
                                               max="<?php echo $item['stock_quantity']; ?>"
                                               data-id="<?php echo $item['id']; ?>"
                                               readonly>
                                        <button type="button" class="qty-btn qty-increase" data-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="item-total">
                                    $<?php echo number_format($item_total, 2); ?>
                                </div>
                                
                                <button type="button" class="remove-btn" data-id="<?php echo $item['id']; ?>" title="Remove item">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-footer">
                        <a href="shop.php" class="continue-shopping">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                        <button type="button" class="clear-cart-btn" onclick="clearCart()">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-header">
                        <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                        <p>Review your order details</p>
                    </div>
                    
                    <div class="summary-row subtotal">
                        <span>Subtotal (<?php echo $cart_count; ?> items)</span>
                        <span>$<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo $shipping_cost > 0 ? '$' . number_format($shipping_cost, 2) : 'FREE'; ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>Calculated at checkout</span>
                    </div>
                    
                    <!-- Shipping Progress -->
                    <?php if ($cart_total < $free_shipping_threshold && $cart_total > 0): ?>
                        <div class="shipping-progress">
                            <?php 
                            $remaining = $free_shipping_threshold - $cart_total;
                            $progress = ($cart_total / $free_shipping_threshold) * 100;
                            ?>
                            <div style="font-weight: 600; margin-bottom: 0.5rem;">
                                <i class="fas fa-shipping-fast"></i> 
                                Add $<?php echo number_format($remaining, 2); ?> more for FREE shipping!
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, $progress); ?>%"></div>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 0.5rem;">
                                <?php echo round($progress); ?>% towards free shipping
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="shipping-info">
                            <i class="fas fa-check-circle"></i> 
                            <strong>You qualify for FREE shipping!</strong>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Coupon Section -->
                    <div class="coupon-section">
                        <div class="coupon-header">
                            <i class="fas fa-tag"></i>
                            <span>Have a promo code?</span>
                        </div>
                        <div class="coupon-input">
                            <input type="text" placeholder="Enter code" id="couponCode">
                            <button type="button" class="apply-coupon-btn" onclick="applyCoupon()">Apply</button>
                        </div>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($final_total, 2); ?></span>
                    </div>
                    
                    <?php if (is_logged_in()): ?>
                        <button type="button" class="checkout-btn" onclick="proceedToCheckout()">
                            <i class="fas fa-lock"></i> Secure Checkout
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=cart.php" class="checkout-btn" style="text-decoration: none; text-align: center; display: block;">
                            <i class="fas fa-sign-in-alt"></i> Login to Checkout
                        </a>
                    <?php endif; ?>
                    
                    <div class="security-badges">
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>SSL Secure</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-lock"></i>
                            <span>256-bit Encryption</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-credit-card"></i>
                            <span>Safe Payment</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added anything to your cart yet.<br>Start shopping to fill it up!</p>
                <a href="shop.php" class="shop-now-btn">
                    <i class="fas fa-shopping-bag"></i> Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Quantity controls
            $('.qty-increase').click(function() {
                const productId = $(this).data('id');
                const input = $(this).siblings('.qty-input');
                const max = parseInt(input.attr('max'));
                const current = parseInt(input.val());
                
                if (current < max) {
                    const newValue = current + 1;
                    input.val(newValue);
                    updateQuantity(productId, newValue);
                }
            });
            
            $('.qty-decrease').click(function() {
                const productId = $(this).data('id');
                const input = $(this).siblings('.qty-input');
                const current = parseInt(input.val());
                
                if (current > 1) {
                    const newValue = current - 1;
                    input.val(newValue);
                    updateQuantity(productId, newValue);
                }
            });
            
            // Remove item
            $('.remove-btn').click(function() {
                const productId = $(this).data('id');
                const itemName = $(this).closest('.cart-item').find('h3 a').text().trim();
                
                if (confirm(`Remove "${itemName}" from your cart?`)) {
                    removeItem(productId);
                }
            });
            
            // Add visual feedback for interactions
            $('.cart-item').hover(
                function() { $(this).addClass('hover'); },
                function() { $(this).removeClass('hover'); }
            );
        });
        
        function updateQuantity(productId, quantity) {
            const cartItem = $(`.cart-item[data-product-id="${productId}"]`);
            cartItem.addClass('updating');
            
            $.ajax({
                url: 'cart.php',
                method: 'POST',
                data: {
                    action: 'update_quantity',
                    product_id: productId,
                    quantity: quantity
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    cartItem.removeClass('updating');
                    showAlert('Failed to update quantity', 'error');
                }
            });
        }
        
        function removeItem(productId) {
            const cartItem = $(`.cart-item[data-product-id="${productId}"]`);
            cartItem.addClass('loading');
            
            $.ajax({
                url: 'cart.php',
                method: 'POST',
                data: {
                    action: 'remove_item',
                    product_id: productId
                },
                success: function() {
                    cartItem.fadeOut(300, function() {
                        location.reload();
                    });
                },
                error: function() {
                    cartItem.removeClass('loading');
                    showAlert('Failed to remove item', 'error');
                }
            });
        }
        
        function clearCart() {
            if (confirm('Are you sure you want to clear your entire cart?')) {
                $('.cart-items-section').addClass('loading');
                
                $.ajax({
                    url: 'cart.php',
                    method: 'POST',
                    data: {
                        action: 'clear_cart'
                    },
                    success: function() {
                        location.reload();
                    },
                    error: function() {
                        $('.cart-items-section').removeClass('loading');
                        showAlert('Failed to clear cart', 'error');
                    }
                });
            }
        }
        
        function applyCoupon() {
            const code = $('#couponCode').val().trim();
            
            if (!code) {
                showAlert('Please enter a coupon code', 'error');
                return;
            }
            
            $('.apply-coupon-btn').addClass('loading');
            
            // Placeholder for coupon functionality
            setTimeout(function() {
                $('.apply-coupon-btn').removeClass('loading');
                showAlert('Coupon system coming soon!', 'info');
            }, 1000);
        }
        
        function proceedToCheckout() {
            // Add loading state
            $('.checkout-btn').addClass('loading').text('Processing...');
            
            // Simulate checkout process
            setTimeout(function() {
                window.location.href = 'checkout.php';
            }, 1000);
        }
        
        function showAlert(message, type = 'info') {
            const alertClass = type === 'error' ? 'alert-error' : (type === 'success' ? 'alert-success' : 'alert-info');
            const alert = `
                <div class="alert ${alertClass}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px;">
                    ${message}
                </div>
            `;
            
            $('.alert').remove();
            $('body').append(alert);
            
            setTimeout(function() {
                $('.alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    </script>
</body>
</html>