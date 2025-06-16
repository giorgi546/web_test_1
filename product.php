<?php
require_once 'includes/config.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: shop.php');
    exit;
}

// Get product details
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.status = 'active'";

$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Get related products from same category
$related_sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
                ORDER BY RAND() 
                LIMIT 4";

$related_stmt = $db->prepare($related_sql);
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll();

// Parse gallery images
$gallery_images = [];
if ($product['gallery_images']) {
    $gallery_images = json_decode($product['gallery_images'], true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($product['short_description'] ?? substr($product['description'], 0, 160)); ?>">
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Product page specific styles */
        .product-hero {
            background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
            padding: 2rem 0;
            margin-top: 80px;
            color: white;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .breadcrumb a:hover {
            color: white;
        }
        
        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            padding: 3rem 0;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .product-gallery {
            position: sticky;
            top: 120px;
            height: fit-content;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            background: white;
            border-radius: 16px;
            margin-bottom: 1rem;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            cursor: zoom-in;
        }
        
        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .main-image:hover img {
            transform: scale(1.05);
        }
        
        .image-thumbnails {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding: 0.5rem 0;
        }
        
        .thumbnail {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .thumbnail.active {
            border-color: #3B82F6;
            transform: scale(1.05);
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 1rem 0;
        }
        
        .product-category {
            color: #3B82F6;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .product-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1F2937;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stars {
            display: flex;
            gap: 0.25rem;
            color: #FBBF24;
        }
        
        .rating-text {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .product-price {
            margin-bottom: 2rem;
        }
        
        .price-current,
        .price-sale {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1F2937;
            display: block;
        }
        
        .price-original {
            font-size: 1.8rem;
            color: #9CA3AF;
            text-decoration: line-through;
            margin-left: 1rem;
        }
        
        .price-savings {
            display: inline-block;
            background: linear-gradient(135deg, #FEE2E2, #FECACA);
            color: #DC2626;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            margin-top: 0.5rem;
        }
        
        .product-description {
            color: #4B5563;
            line-height: 1.8;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .product-features {
            margin-bottom: 2rem;
        }
        
        .features-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 1rem;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
        }
        
        .features-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #4B5563;
            font-weight: 500;
        }
        
        .features-list i {
            color: #10B981;
            font-size: 1.1rem;
        }
        
        .quantity-section {
            margin-bottom: 2rem;
        }
        
        .quantity-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .qty-wrapper {
            display: flex;
            align-items: center;
            border: 3px solid #E5E7EB;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }
        
        .qty-btn {
            width: 50px;
            height: 50px;
            border: none;
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: 700;
            color: #374151;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:hover {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
        }
        
        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .qty-input {
            width: 80px;
            height: 50px;
            border: none;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            background: white;
        }
        
        .stock-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .stock-info.in-stock {
            color: #10B981;
        }
        
        .stock-info.low-stock {
            color: #F59E0B;
        }
        
        .stock-info.out-of-stock {
            color: #EF4444;
        }
        
        .product-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .add-to-cart-btn {
            flex: 2;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 1.2rem 2rem;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }
        
        .add-to-cart-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .wishlist-btn {
            flex: 1;
            background: transparent;
            border: 3px solid #E5E7EB;
            color: #6B7280;
            padding: 1.2rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        
        .wishlist-btn:hover {
            border-color: #EF4444;
            color: #EF4444;
            background: #FEF2F2;
            transform: translateY(-2px);
        }
        
        .product-meta {
            border-top: 2px solid #E5E7EB;
            padding-top: 2rem;
            margin-top: 2rem;
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #F3F4F6;
        }
        
        .meta-label {
            font-weight: 600;
            color: #374151;
        }
        
        .meta-value {
            color: #6B7280;
        }
        
        .related-products {
            padding: 4rem 0;
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1F2937;
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .related-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .related-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .related-image {
            height: 200px;
            overflow: hidden;
        }
        
        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .related-card:hover .related-image img {
            transform: scale(1.1);
        }
        
        .related-info {
            padding: 1.5rem;
        }
        
        .related-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .related-name a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .related-name a:hover {
            color: #3B82F6;
        }
        
        .related-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #10B981;
        }
        
        .zoom-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: zoom-out;
        }
        
        .zoom-image {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 12px;
        }
        
        .zoom-close {
            position: absolute;
            top: 30px;
            right: 40px;
            color: white;
            font-size: 3rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .zoom-close:hover {
            transform: scale(1.1);
            color: #EF4444;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 1024px) {
            .product-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .product-gallery {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .product-title {
                font-size: 2rem;
            }
            
            .price-current,
            .price-sale {
                font-size: 2rem;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .main-image {
                height: 400px;
            }
            
            .related-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .product-container {
                padding: 2rem 0;
            }
            
            .quantity-controls {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Product Hero -->
    <section class="product-hero">
        <div class="container">
            <nav class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="shop.php">Shop</a>
                <?php if ($product['category_name']): ?>
                    <i class="fas fa-chevron-right"></i>
                    <a href="shop.php?category=<?php echo urlencode($product['category_name']); ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                <?php endif; ?>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </nav>
        </div>
    </section>

    <!-- Product Details -->
    <section class="product-section">
        <div class="container">
            <div class="product-container">
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="main-image" onclick="openZoom('<?php echo $product['main_image'] ? 'uploads/products/' . $product['main_image'] : ''; ?>')">
                        <img src="<?php echo $product['main_image'] ? 'uploads/products/' . $product['main_image'] : 'https://via.placeholder.com/500x500/f3f4f6/9ca3af?text=No+Image'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             id="mainImage"
                             onerror="this.src='https://via.placeholder.com/500x500/f3f4f6/9ca3af?text=No+Image'">
                    </div>
                    
                    <?php if (!empty($gallery_images) || $product['main_image']): ?>
                        <div class="image-thumbnails">
                            <!-- Main image thumbnail -->
                            <div class="thumbnail active" onclick="changeMainImage('<?php echo $product['main_image'] ? 'uploads/products/' . $product['main_image'] : 'https://via.placeholder.com/500x500/f3f4f6/9ca3af?text=No+Image'; ?>')">
                                <img src="<?php echo $product['main_image'] ? 'uploads/products/' . $product['main_image'] : 'https://via.placeholder.com/500x500/f3f4f6/9ca3af?text=No+Image'; ?>" 
                                     alt="Main image"
                                     onerror="this.src='https://via.placeholder.com/80x80/f3f4f6/9ca3af?text=No+Image'">
                            </div>
                            
                            <!-- Gallery thumbnails -->
                            <?php foreach ($gallery_images as $image): ?>
                                <div class="thumbnail" onclick="changeMainImage('uploads/products/<?php echo $image; ?>')">
                                    <img src="uploads/products/<?php echo $image; ?>" 
                                         alt="Gallery image"
                                         onerror="this.src='https://via.placeholder.com/80x80/f3f4f6/9ca3af?text=No+Image'">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <div class="product-category">
                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                    </div>
                    
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <!-- Product Rating -->
                    <div class="product-rating">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                        <span class="rating-text">(4.2 out of 5 - <?php echo rand(15, 89); ?> reviews)</span>
                    </div>
                    
                    <!-- Price -->
                    <div class="product-price">
                        <?php if ($product['sale_price']): ?>
                            <span class="price-sale">$<?php echo number_format($product['sale_price'], 2); ?></span>
                            <span class="price-original">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php 
                            $savings = $product['price'] - $product['sale_price'];
                            $savings_percent = round(($savings / $product['price']) * 100);
                            ?>
                            <div class="price-savings">
                                <i class="fas fa-tag"></i> Save $<?php echo number_format($savings, 2); ?> (<?php echo $savings_percent; ?>% off)
                            </div>
                        <?php else: ?>
                            <span class="price-current">$<?php echo number_format($product['price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stock Status -->
                    <div class="stock-info <?php echo $product['stock_quantity'] <= 0 ? 'out-of-stock' : ($product['stock_quantity'] <= 5 ? 'low-stock' : 'in-stock'); ?>">
                        <i class="fas fa-<?php echo $product['stock_quantity'] <= 0 ? 'times-circle' : ($product['stock_quantity'] <= 5 ? 'exclamation-triangle' : 'check-circle'); ?>"></i>
                        <?php if ($product['stock_quantity'] <= 0): ?>
                            Out of Stock
                        <?php elseif ($product['stock_quantity'] <= 5): ?>
                            Only <?php echo $product['stock_quantity']; ?> left in stock - Order soon!
                        <?php else: ?>
                            In Stock (<?php echo $product['stock_quantity']; ?> available)
                        <?php endif; ?>
                    </div>
                    
                    <!-- Description -->
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <!-- Key Features -->
                    <div class="product-features">
                        <h3 class="features-title">Key Features:</h3>
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> High quality materials and construction</li>
                            <li><i class="fas fa-check"></i> Fast and reliable shipping</li>
                            <li><i class="fas fa-check"></i> 30-day money-back guarantee</li>
                            <li><i class="fas fa-check"></i> Expert customer support</li>
                            <?php if ($product['weight']): ?>
                                <li><i class="fas fa-check"></i> Lightweight design (<?php echo htmlspecialchars($product['weight']); ?> lbs)</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <!-- Quantity Selector -->
                        <div class="quantity-section">
                            <label class="quantity-label">
                                <i class="fas fa-shopping-cart"></i> Quantity:
                            </label>
                            <div class="quantity-controls">
                                <div class="qty-wrapper">
                                    <button type="button" class="qty-btn qty-decrease">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="qty-input" 
                                           value="1" 
                                           min="1" 
                                           max="<?php echo $product['stock_quantity']; ?>"
                                           id="quantityInput">
                                    <button type="button" class="qty-btn qty-increase">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="stock-info in-stock">
                                    <i class="fas fa-info-circle"></i>
                                    Maximum <?php echo $product['stock_quantity']; ?> items
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Actions -->
                        <div class="product-actions">
                            <button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                            <button class="wishlist-btn" data-id="<?php echo $product['id']; ?>">
                                <i class="far fa-heart"></i>
                                Wishlist
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="product-actions">
                            <button class="add-to-cart-btn" disabled>
                                <i class="fas fa-times"></i>
                                Out of Stock
                            </button>
                            <button class="wishlist-btn" data-id="<?php echo $product['id']; ?>">
                                <i class="far fa-heart"></i>
                                Wishlist
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Product Meta -->
                    <div class="product-meta">
                        <?php if ($product['sku']): ?>
                            <div class="meta-item">
                                <span class="meta-label">SKU:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['sku']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['weight']): ?>
                            <div class="meta-item">
                                <span class="meta-label">Weight:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['weight']); ?> lbs</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['dimensions']): ?>
                            <div class="meta-item">
                                <span class="meta-label">Dimensions:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['dimensions']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value">
                                <a href="shop.php?category=<?php echo urlencode($product['category_name']); ?>" style="color: #3B82F6; text-decoration: none;">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                </a>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Tags:</span>
                            <span class="meta-value">
                                <span style="display: inline-block; background: #F3F4F6; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem; font-size: 0.85rem;">Quality</span>
                                <span style="display: inline-block; background: #F3F4F6; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem; font-size: 0.85rem;">Popular</span>
                                <span style="display: inline-block; background: #F3F4F6; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">Trending</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <section class="related-products">
            <div class="container">
                <h2 class="section-title">Related Products</h2>
                <div class="related-grid">
                    <?php foreach ($related_products as $related): ?>
                        <div class="related-card">
                            <div class="related-image">
                                <img src="<?php echo $related['main_image'] ? 'uploads/products/' . $related['main_image'] : 'https://via.placeholder.com/280x200/f3f4f6/9ca3af?text=No+Image'; ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                                     onerror="this.src='https://via.placeholder.com/280x200/f3f4f6/9ca3af?text=No+Image'">
                            </div>
                            <div class="related-info">
                                <h3 class="related-name">
                                    <a href="product.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['name']); ?>
                                    </a>
                                </h3>
                                <div class="related-price">
                                    <?php if ($related['sale_price']): ?>
                                        $<?php echo number_format($related['sale_price'], 2); ?>
                                        <span style="color: #9CA3AF; text-decoration: line-through; font-size: 0.9rem; margin-left: 0.5rem;">
                                            $<?php echo number_format($related['price'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        $<?php echo number_format($related['price'], 2); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Image Zoom Overlay -->
    <div class="zoom-overlay" id="zoomOverlay" onclick="closeZoom()">
        <span class="zoom-close" onclick="closeZoom()">&times;</span>
        <img class="zoom-image" id="zoomImage" src="" alt="Zoomed image">
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/main.js"></script>
    
    <script>
        $(document).ready(function() {
            // Quantity controls
            $('.qty-increase').click(function() {
                const input = $('#quantityInput');
                const max = parseInt(input.attr('max'));
                const current = parseInt(input.val());
                
                if (current < max) {
                    input.val(current + 1);
                }
            });
            
            $('.qty-decrease').click(function() {
                const input = $('#quantityInput');
                const current = parseInt(input.val());
                
                if (current > 1) {
                    input.val(current - 1);
                }
            });
            
            // Validate quantity input
            $('#quantityInput').on('change', function() {
                const min = parseInt($(this).attr('min'));
                const max = parseInt($(this).attr('max'));
                let value = parseInt($(this).val());
                
                if (value < min) {
                    $(this).val(min);
                } else if (value > max) {
                    $(this).val(max);
                    showAlert(`Maximum quantity available: ${max}`, 'warning');
                }
            });
            
            // Add to cart with quantity
            $('.add-to-cart-btn').click(function() {
                const productId = $(this).data('id');
                const quantity = $('#quantityInput').val();
                
                if (!productId) {
                    showAlert('Invalid product ID', 'error');
                    return;
                }
                
                addToCart(productId, quantity);
            });
            
            // Wishlist functionality
            $('.wishlist-btn').click(function() {
                const productId = $(this).data('id');
                
                if (!productId) {
                    showAlert('Invalid product ID', 'error');
                    return;
                }
                
                toggleWishlist(productId);
            });
            
            // Image gallery functionality
            $('.thumbnail').click(function() {
                $('.thumbnail').removeClass('active');
                $(this).addClass('active');
            });
        });
        
        // Image gallery functions
        function changeMainImage(imageSrc) {
            $('#mainImage').attr('src', imageSrc);
            
            // Update active thumbnail
            $('.thumbnail').removeClass('active');
            event.currentTarget.classList.add('active');
        }
        
        function openZoom(imageSrc) {
            $('#zoomImage').attr('src', imageSrc);
            $('#zoomOverlay').fadeIn(300);
            $('body').css('overflow', 'hidden');
        }
        
        function closeZoom() {
            $('#zoomOverlay').fadeOut(300);
            $('body').css('overflow', 'auto');
        }
        
        // Close zoom with Escape key
        $(document).keyup(function(e) {
            if (e.keyCode === 27) { // Escape key
                closeZoom();
            }
        });
        
        // Add to cart function
        function addToCart(productId, quantity = 1) {
            if (!productId) {
                showAlert('Invalid product ID', 'error');
                return;
            }

            // Show loading state
            const button = $('.add-to-cart-btn');
            const originalText = button.html();
            button.html('<i class="fas fa-spinner fa-spin"></i> Adding...').prop('disabled', true);
            
            $.ajax({
                url: 'ajax/add_to_cart.php',
                method: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity
                },
                dataType: 'json',
                success: function(response) {
                    button.html(originalText).prop('disabled', false);
                    
                    if (response.success) {
                        updateCartCount();
                        showAlert(response.message || 'Product added to cart successfully!', 'success');
                        
                        // Add visual feedback
                        $('.cart-link').addClass('bounce');
                        setTimeout(function() {
                            $('.cart-link').removeClass('bounce');
                        }, 600);
                        
                    } else {
                        showAlert(response.message || 'Failed to add product to cart', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    button.html(originalText).prop('disabled', false);
                    console.error('AJAX Error:', error);
                    
                    if (xhr.status === 404) {
                        showAlert('Cart system not available. Please try again later.', 'error');
                    } else {
                        showAlert('An error occurred. Please try again.', 'error');
                    }
                }
            });
        }
        
        // Wishlist function
        function toggleWishlist(productId) {
            const button = $('.wishlist-btn');
            const icon = button.find('i');
            
            // Toggle heart icon
            if (icon.hasClass('far')) {
                icon.removeClass('far').addClass('fas');
                button.css('color', '#EF4444');
                showAlert('Added to wishlist!', 'success');
            } else {
                icon.removeClass('fas').addClass('far');
                button.css('color', '#6B7280');
                showAlert('Removed from wishlist', 'info');
            }
            
            // Placeholder for actual wishlist functionality
            // In a real app, you'd make an AJAX call here
        }
        
        // Update cart count
        function updateCartCount() {
            $.ajax({
                url: 'ajax/get_cart_count.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const count = response.count || 0;
                        $('#cartCount').text(count);
                        
                        if (count > 0) {
                            $('#cartCount').addClass('show');
                        } else {
                            $('#cartCount').removeClass('show');
                        }
                    }
                },
                error: function() {
                    console.log('Failed to update cart count');
                }
            });
        }
        
        // Show alert messages
        function showAlert(message, type = 'info') {
            const alertColors = {
                success: { bg: '#D1FAE5', border: '#10B981', text: '#065F46' },
                error: { bg: '#FEE2E2', border: '#EF4444', text: '#991B1B' },
                warning: { bg: '#FEF3C7', border: '#F59E0B', text: '#92400E' },
                info: { bg: '#DBEAFE', border: '#3B82F6', text: '#1E40AF' }
            };
            
            const colors = alertColors[type] || alertColors.info;
            
            const alert = `
                <div class="alert alert-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: ${colors.bg}; border: 1px solid ${colors.border}; color: ${colors.text}; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-weight: 500;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
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
        
        // Add bounce animation to cart icon
        $('<style>.bounce { animation: bounce 0.6s ease; } @keyframes bounce { 0%, 20%, 60%, 100% { transform: translateY(0); } 40% { transform: translateY(-10px); } 80% { transform: translateY(-5px); } }</style>').appendTo('head');
        
        // Smooth scroll to top when clicking breadcrumbs
        $('.breadcrumb a').click(function(e) {
            if ($(this).attr('href') === '#') {
                e.preventDefault();
                $('html, body').animate({scrollTop: 0}, 500);
            }
        });
        
        // Add loading spinner styles
        $('<style>.fa-spinner { animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
        
        // Initialize page
        $(document).ready(function() {
            // Update cart count on page load
            updateCartCount();
            
            // Add fade-in animation to product details
            $('.product-info').css('opacity', '0').animate({opacity: 1}, 800);
            $('.product-gallery').css('opacity', '0').animate({opacity: 1}, 600);
        });
    </script>
</body>
</html>