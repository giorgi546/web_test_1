<?php
require_once 'includes/config.php';

// Smart image mapping for products
$image_map = [
    'Smartphone Pro' => 's24-ultra.webp',
    'Laptop Ultra' => 'asus-ROG.jpg', 
    'Cotton T-Shirt' => 't-shirt.jpeg',
    'Programming Book' => 'hacking-book.jpg'
];

// Simple product fetching without complex class methods
$filters = [];
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE clause for filters
$where_conditions = ['p.status = :status'];
$params = ['status' => 'active'];

if (!empty($_GET['category'])) {
    $where_conditions[] = 'c.name = :category';
    $params['category'] = sanitize_input($_GET['category']);
}

if (!empty($_GET['search'])) {
    $where_conditions[] = '(p.name LIKE :search OR p.description LIKE :search)';
    $params['search'] = '%' . sanitize_input($_GET['search']) . '%';
}

if (!empty($_GET['min_price'])) {
    $where_conditions[] = 'COALESCE(p.sale_price, p.price) >= :min_price';
    $params['min_price'] = floatval($_GET['min_price']);
}

if (!empty($_GET['max_price'])) {
    $where_conditions[] = 'COALESCE(p.sale_price, p.price) <= :max_price';
    $params['max_price'] = floatval($_GET['max_price']);
}

if (isset($_GET['featured'])) {
    $where_conditions[] = 'p.featured = 1';
}

$where_clause = implode(' AND ', $where_conditions);

// Get products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE {$where_clause}
        ORDER BY p.created_at DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll();

// Get total count
$count_sql = "SELECT COUNT(*) as total 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE {$where_clause}";

$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue(':' . $key, $value);
}
$count_stmt->execute();
$total_products = $count_stmt->fetch()['total'];
$total_pages = ceil($total_products / $per_page);

// Get categories
$cat_stmt = $db->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Shop-specific styles */
        .shop-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 6rem 0 4rem;
            margin-top: 80px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .shop-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse at center, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        
        .shop-hero-content {
            position: relative;
            z-index: 2;
        }
        
        .shop-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .shop-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .breadcrumb a:hover {
            color: white;
        }
        
        .shop-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            padding: 3rem 0;
        }
        
        .filters-sidebar {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        
        .filter-section h3 {
            color: #1F2937;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 700;
            border-bottom: 3px solid #667eea;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-group {
            margin-bottom: 2rem;
        }
        
        .filter-group h4 {
            color: #374151;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .filter-option {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            cursor: pointer;
            padding: 0.75rem 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .filter-option:hover {
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            transform: translateX(5px);
        }
        
        .filter-option input {
            margin-right: 0.75rem;
            accent-color: #667eea;
            transform: scale(1.2);
        }
        
        .filter-option label {
            cursor: pointer;
            font-weight: 500;
            color: #374151;
        }
        
        .price-filter {
            margin-bottom: 2rem;
        }
        
        .price-inputs {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .price-inputs input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .price-inputs input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .clear-filters-btn {
            width: 100%;
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .clear-filters-btn:hover {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }
        
        .products-area {
            min-height: 800px;
        }
        
        .shop-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .product-count {
            color: #6B7280;
            font-weight: 600;
        }
        
        .results-highlight {
            color: #667eea;
            font-weight: 700;
        }
        
        .sort-dropdown {
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            background: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sort-dropdown:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #F3F4F6;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .product-image-container {
            position: relative;
            height: 250px;
            overflow: hidden;
        }
        
        .product-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover img {
            transform: scale(1.1);
        }
        
        .product-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: all 0.3s ease;
            display: flex;
            gap: 10px;
        }
        
        .product-card:hover .product-overlay {
            opacity: 1;
        }
        
        .overlay-btn {
            background: rgba(255,255,255,0.95);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
            font-size: 1.1rem;
            backdrop-filter: blur(10px);
        }
        
        .overlay-btn:hover {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }
        
        .product-badges {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
        }
        
        .badge {
            display: block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            color: white;
        }
        
        .badge.sale {
            background: linear-gradient(135deg, #EF4444, #DC2626);
        }
        
        .badge.featured {
            background: linear-gradient(135deg, #10B981, #059669);
        }
        
        .badge.new {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }
        
        .product-details {
            padding: 2rem;
        }
        
        .product-category {
            color: #667eea;
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }
        
        .product-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        
        .product-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .product-title a:hover {
            color: #667eea;
        }
        
        .product-description {
            color: #6B7280;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .current-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1F2937;
        }
        
        .original-price {
            color: #9CA3AF;
            text-decoration: line-through;
            font-size: 1.1rem;
        }
        
        .discount-badge {
            background: linear-gradient(135deg, #FEE2E2, #FECACA);
            color: #DC2626;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .product-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .add-cart-btn {
            flex: 1;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.875rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .add-cart-btn:hover {
            background: linear-gradient(135deg, #5a67d8, #6b46c1);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .wishlist-btn {
            width: 45px;
            height: 45px;
            border: 2px solid #E5E7EB;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6B7280;
        }
        
        .wishlist-btn:hover {
            border-color: #EF4444;
            color: #EF4444;
            background: #FEF2F2;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin: 3rem 0;
            gap: 0.5rem;
        }
        
        .page-btn {
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            background: white;
            text-decoration: none;
            color: #374151;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .page-btn:hover,
        .page-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .empty-results {
            text-align: center;
            padding: 4rem 2rem;
            color: #6B7280;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .empty-results i {
            font-size: 5rem;
            margin-bottom: 2rem;
            color: #D1D5DB;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 60%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            80% { transform: translateY(-10px); }
        }
        
        .empty-results h3 {
            font-size: 2rem;
            color: #1F2937;
            margin-bottom: 1rem;
        }
        
        .empty-results p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .filter-mobile-toggle {
            display: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .filter-mobile-toggle:hover {
            background: linear-gradient(135deg, #5a67d8, #6b46c1);
            transform: translateY(-2px);
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .shop-layout {
                grid-template-columns: 1fr;
            }
            
            .filters-sidebar {
                position: static;
                order: 2;
            }
            
            .products-area {
                order: 1;
            }
            
            .filter-mobile-toggle {
                display: block;
            }
            
            .filters-sidebar {
                display: none;
            }
            
            .filters-sidebar.show {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .shop-hero h1 {
                font-size: 2rem;
            }
            
            .shop-toolbar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.5rem;
            }
            
            .product-details {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .shop-layout {
                padding: 2rem 0;
                gap: 1rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .filters-sidebar {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Shop Hero Section -->
    <section class="shop-hero">
        <div class="container">
            <div class="shop-hero-content">
                <nav class="breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Shop</span>
                    <?php if (!empty($_GET['category'])): ?>
                        <i class="fas fa-chevron-right"></i>
                        <span><?php echo htmlspecialchars($_GET['category']); ?></span>
                    <?php endif; ?>
                </nav>
                
                <h1>
                    <?php if (!empty($_GET['search'])): ?>
                        Search: "<?php echo htmlspecialchars($_GET['search']); ?>"
                    <?php elseif (!empty($_GET['category'])): ?>
                        <?php echo htmlspecialchars($_GET['category']); ?>
                    <?php else: ?>
                        All Products
                    <?php endif; ?>
                </h1>
                <p>Discover amazing products at unbeatable prices</p>
            </div>
        </div>
    </section>

    <!-- Main Shop Content -->
    <div class="container">
        <div class="shop-layout">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar" id="filtersSidebar">
                <div class="filter-section">
                    <h3><i class="fas fa-filter"></i> Filters</h3>
                    
                    <!-- Categories -->
                    <div class="filter-group">
                        <h4>Categories</h4>
                        <?php foreach ($categories as $cat): ?>
                            <div class="filter-option">
                                <input type="checkbox" 
                                       id="cat_<?php echo $cat['id']; ?>" 
                                       class="category-filter" 
                                       value="<?php echo htmlspecialchars($cat['name']); ?>"
                                       <?php echo (!empty($_GET['category']) && $_GET['category'] === $cat['name']) ? 'checked' : ''; ?>>
                                <label for="cat_<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="filter-group">
                        <h4>Price Range</h4>
                        <div class="price-filter">
                            <div class="price-inputs">
                                <input type="number" 
                                       id="minPrice" 
                                       placeholder="Min ($)" 
                                       value="<?php echo $_GET['min_price'] ?? ''; ?>"
                                       min="0">
                                <input type="number" 
                                       id="maxPrice" 
                                       placeholder="Max ($)" 
                                       value="<?php echo $_GET['max_price'] ?? ''; ?>"
                                       min="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Special Offers -->
                    <div class="filter-group">
                        <h4>Special Offers</h4>
                        <div class="filter-option">
                            <input type="checkbox" 
                                   id="featured" 
                                   class="featured-filter" 
                                   <?php echo isset($_GET['featured']) ? 'checked' : ''; ?>>
                            <label for="featured">Featured Products</label>
                        </div>
                    </div>
                    
                    <button class="clear-filters-btn" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear All Filters
                    </button>
                </div>
            </aside>

            <!-- Products Area -->
            <main class="products-area">
                <!-- Mobile Filter Toggle -->
                <button class="filter-mobile-toggle" onclick="toggleFilters()">
                    <i class="fas fa-filter"></i> Show Filters
                </button>
                
                <!-- Toolbar -->
                <div class="shop-toolbar">
                    <div class="product-count">
                        Showing <span class="results-highlight"><?php echo count($products); ?></span> of 
                        <span class="results-highlight"><?php echo $total_products; ?></span> products
                        <?php if ($page > 1): ?>
                            (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)
                        <?php endif; ?>
                    </div>
                    <select class="sort-dropdown" onchange="sortProducts(this.value)">
                        <option value="newest">Newest First</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="name">Name A-Z</option>
                        <option value="featured">Featured First</option>
                    </select>
                </div>

                <!-- Products Grid -->
                <?php if (!empty($products)): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $item): ?>
                            <?php
                            // Get correct image path
                            $image_src = 'images/placeholder.jpg'; // Default fallback
                            
                            if ($item['main_image']) {
                                // If product has image in database, use uploads folder
                                $image_src = 'uploads/products/' . $item['main_image'];
                            } elseif (isset($image_map[$item['name']])) {
                                // Use mapped image from images folder
                                $image_src = 'images/' . $image_map[$item['name']];
                            }
                            ?>
                            <div class="product-card">
                                <div class="product-image-container">
                                    <img src="<?php echo $image_src; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         onerror="this.src='images/asus-ROG.jpg'">
                                    
                                    <div class="product-overlay">
                                        <button class="overlay-btn quick-view" data-id="<?php echo $item['id']; ?>" title="Quick View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="overlay-btn add-to-cart" data-id="<?php echo $item['id']; ?>" title="Add to Cart">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="product-badges">
                                        <?php if ($item['sale_price']): ?>
                                            <span class="badge sale">Sale</span>
                                        <?php endif; ?>
                                        <?php if ($item['featured']): ?>
                                            <span class="badge featured">Featured</span>
                                        <?php endif; ?>
                                        <?php if (strtotime($item['created_at']) > strtotime('-7 days')): ?>
                                            <span class="badge new">New</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="product-details">
                                    <div class="product-category">
                                        <?php echo htmlspecialchars($item['category_name'] ?? 'General'); ?>
                                    </div>
                                    
                                    <h3 class="product-title">
                                        <a href="product.php?id=<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <p class="product-description">
                                        <?php echo htmlspecialchars($item['short_description'] ?? substr($item['description'], 0, 100) . '...'); ?>
                                    </p>
                                    
                                    <div class="product-price">
                                        <?php if ($item['sale_price']): ?>
                                            <span class="current-price">$<?php echo number_format($item['sale_price'], 2); ?></span>
                                            <span class="original-price">$<?php echo number_format($item['price'], 2); ?></span>
                                            <?php $discount = round((($item['price'] - $item['sale_price']) / $item['price']) * 100); ?>
                                            <span class="discount-badge">-<?php echo $discount; ?>%</span>
                                        <?php else: ?>
                                            <span class="current-price">$<?php echo number_format($item['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <button class="add-cart-btn" data-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                        <button class="wishlist-btn" data-id="<?php echo $item['id']; ?>" title="Add to Wishlist">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" class="page-btn">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" 
                                   class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" class="page-btn">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-results">
                        <i class="fas fa-search"></i>
                        <h3>No Products Found</h3>
                        <p>We couldn't find any products matching your criteria.<br>Try adjusting your search or filters.</p>
                        <button class="btn btn-primary" onclick="clearFilters()" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 1rem 2rem; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 1rem;">
                            <i class="fas fa-redo"></i> Clear Filters
                        </button>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Filter functionality
            $('.category-filter, .featured-filter').change(function() {
                applyFilters();
            });

            // Price filter with debounce
            let priceTimeout;
            $('#minPrice, #maxPrice').on('input', function() {
                clearTimeout(priceTimeout);
                priceTimeout = setTimeout(applyFilters, 1000);
            });


            // Quick view functionality
            $('.quick-view').click(function(e) {
                e.preventDefault();
                const productId = $(this).data('id');
                
                if (!productId) {
                    showAlert('Invalid product ID', 'error');
                    return;
                }
                
                showQuickView(productId);
            });

            // Wishlist functionality
            $('.wishlist-btn').click(function(e) {
                e.preventDefault();
                const productId = $(this).data('id');
                
                if (!productId) {
                    showAlert('Invalid product ID', 'error');
                    return;
                }
                
                toggleWishlist(productId);
            });
        });

        function applyFilters() {
            const url = new URL(window.location);
            
            // Clear existing filter params
            url.searchParams.delete('category');
            url.searchParams.delete('featured');
            url.searchParams.delete('min_price');
            url.searchParams.delete('max_price');
            url.searchParams.delete('page');
            
            // Apply category filters
            const selectedCategory = $('.category-filter:checked').val();
            if (selectedCategory) {
                url.searchParams.set('category', selectedCategory);
            }
            
            // Apply featured filter
            if ($('.featured-filter:checked').length) {
                url.searchParams.set('featured', '1');
            }
            
            // Apply price filters
            const minPrice = $('#minPrice').val();
            const maxPrice = $('#maxPrice').val();
            if (minPrice && minPrice > 0) {
                url.searchParams.set('min_price', minPrice);
            }
            if (maxPrice && maxPrice > 0) {
                url.searchParams.set('max_price', maxPrice);
            }
            
            // Show loading state
            $('.products-grid').css('opacity', '0.6');
            
            window.location.href = url.toString();
        }

        function clearFilters() {
            const url = new URL(window.location);
            const search = url.searchParams.get('search');
            url.search = '';
            if (search) {
                url.searchParams.set('search', search);
            }
            window.location.href = url.toString();
        }

        function sortProducts(sortValue) {
            const url = new URL(window.location);
            url.searchParams.set('sort', sortValue);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function toggleFilters() {
            const sidebar = $('#filtersSidebar');
            const button = $('.filter-mobile-toggle');
            
            sidebar.toggleClass('show');
            
            if (sidebar.hasClass('show')) {
                button.html('<i class="fas fa-times"></i> Hide Filters');
                sidebar.slideDown(300);
            } else {
                button.html('<i class="fas fa-filter"></i> Show Filters');
                sidebar.slideUp(300);
            }
        }

        // Add to cart function
        function addToCart(productId, quantity = 1) {
            if (!productId) {
                showAlert('Invalid product ID', 'error');
                return;
            }

            showLoading();
            
            $.ajax({
                url: 'ajax/add_to_cart.php',
                method: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        updateCartCount();
                        showAlert(response.message || 'Product added to cart!', 'success');
                        
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
                    hideLoading();
                    console.error('AJAX Error:', error);
                    showAlert('An error occurred. Please try again.', 'error');
                }
            });
        }

        // Quick view function with corrected image paths
        function showQuickView(productId) {
            showLoading();
            
            $.ajax({
                url: 'ajax/get_product.php',
                method: 'GET',
                data: { id: productId },
                dataType: 'json',
                success: function(product) {
                    hideLoading();
                    
                    if (product && !product.error) {
                        createQuickViewModal(product);
                    } else {
                        showAlert('Product not found', 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    showAlert('Failed to load product details', 'error');
                }
            });
        }

        // Create quick view modal with proper image mapping
        function createQuickViewModal(product) {
            const imageMap = {
                'Smartphone Pro': 's24-ultra.webp',
                'Laptop Ultra': 'asus-ROG.jpg', 
                'Cotton T-Shirt': 't-shirt.jpeg',
                'Programming Book': 'hacking-book.jpg'
            };
            
            let imageSrc = 'images/asus-ROG.jpg'; // Default fallback
            
            if (product.main_image) {
                imageSrc = 'uploads/products/' + product.main_image;
            } else if (imageMap[product.name]) {
                imageSrc = 'images/' + imageMap[product.name];
            }
            
            const salePrice = product.sale_price ? 
                `<span class="price-sale">${parseFloat(product.sale_price).toFixed(2)}</span>
                 <span class="price-original">${parseFloat(product.price).toFixed(2)}</span>` :
                `<span class="price-current">${parseFloat(product.price).toFixed(2)}</span>`;
            
            const modal = `
                <div id="quickViewModal" class="modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                    <div class="modal-content" style="background: white; border-radius: 16px; max-width: 800px; width: 90%; max-height: 90%; overflow-y: auto;">
                        <div class="modal-header" style="padding: 2rem; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
                            <h2>${product.name}</h2>
                            <button class="close-modal" style="background: none; border: none; font-size: 2rem; cursor: pointer;">&times;</button>
                        </div>
                        <div class="modal-body" style="padding: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div class="product-image">
                                <img src="${imageSrc}" 
                                     alt="${product.name}" 
                                     style="width: 100%; height: 300px; object-fit: cover; border-radius: 12px;"
                                     onerror="this.src='images/asus-ROG.jpg'">
                            </div>
                            <div class="product-details">
                                <div class="product-price" style="margin: 1rem 0; font-size: 1.5rem; font-weight: 700;">${salePrice}</div>
                                <p style="color: #6B7280; margin-bottom: 1.5rem;">${product.description || 'No description available'}</p>
                                <div class="quantity-selector" style="margin: 1rem 0;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Quantity:</label>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <button class="qty-decrease" style="width: 40px; height: 40px; border: 1px solid #D1D5DB; background: white; cursor: pointer;">-</button>
                                        <input type="number" class="quantity-input" value="1" min="1" max="${product.stock_quantity || 99}" style="width: 60px; height: 40px; text-align: center; border: 1px solid #D1D5DB;">
                                        <button class="qty-increase" style="width: 40px; height: 40px; border: 1px solid #D1D5DB; background: white; cursor: pointer;">+</button>
                                    </div>
                                </div>
                                <button class="add-to-cart-modal" data-id="${product.id}" style="width: 100%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 1rem; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 1rem;">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            
            // Modal event handlers
            $('.close-modal, #quickViewModal').click(function(e) {
                if (e.target === this) {
                    $('#quickViewModal').remove();
                }
            });
            
            $('.qty-increase').click(function() {
                const input = $('.quantity-input');
                const max = parseInt(input.attr('max'));
                const current = parseInt(input.val());
                if (current < max) {
                    input.val(current + 1);
                }
            });
            
            $('.qty-decrease').click(function() {
                const input = $('.quantity-input');
                const current = parseInt(input.val());
                if (current > 1) {
                    input.val(current - 1);
                }
            });
            
            $('.add-to-cart-modal').click(function() {
                const productId = $(this).data('id');
                const quantity = $('.quantity-input').val();
                addToCart(productId, quantity);
                $('#quickViewModal').remove();
            });
        }

        // Wishlist function
        function toggleWishlist(productId) {
            // Placeholder for wishlist functionality
            showAlert('Wishlist feature coming soon!', 'info');
        }

        // Utility functions
        function showLoading() {
            if (!$('#loadingSpinner').length) {
                $('body').append('<div id="loadingSpinner" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"><div style="width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.3); border-top: 4px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div></div>');
                $('<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
            }
            $('#loadingSpinner').show();
        }

        function hideLoading() {
            $('#loadingSpinner').hide();
        }

        function updateCartCount() {
            $.ajax({
                url: 'ajax/get_cart_count.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#cartCount').text(response.count || 0);
                        if (response.count > 0) {
                            $('#cartCount').addClass('show');
                        }
                    }
                },
                error: function() {
                    console.log('Failed to update cart count');
                }
            });
        }

        function showAlert(message, type = 'info') {
            const alertColors = {
                success: { bg: '#D1FAE5', border: '#10B981', text: '#065F46' },
                error: { bg: '#FEE2E2', border: '#EF4444', text: '#991B1B' },
                info: { bg: '#DBEAFE', border: '#3B82F6', text: '#1E40AF' }
            };
            
            const colors = alertColors[type] || alertColors.info;
            
            const alert = `
                <div class="alert alert-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: ${colors.bg}; border: 1px solid ${colors.border}; color: ${colors.text}; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
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