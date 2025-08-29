<?php
$pageTitle = "Home";
include 'includes/header.php';

// Get featured products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.featured = 1 AND p.status = 'active'
                     ORDER BY p.created_at DESC LIMIT 6");
$featuredProducts = $stmt->fetchAll();

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title">Welcome to Our Store</h1>
                <p class="hero-subtitle">Discover amazing products at unbeatable prices. Quality guaranteed!</p>
                <a href="products.php" class="btn btn-light btn-lg">Shop Now</a>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-shopping-bag" style="font-size: 15rem; opacity: 0.1;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Categories Section -->
<section class="mb-5">
    <div class="container">
        <h2 class="text-center mb-5">Shop by Category</h2>
        <div class="row">
            <?php foreach ($categories as $category): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <a href="products.php?category=<?php echo $category['id']; ?>" class="category-card d-block">
                        <div class="category-icon">
                            <?php
                            $icons = [
                                'Electronics' => 'fas fa-laptop',
                                'Clothing' => 'fas fa-tshirt',
                                'Books' => 'fas fa-book',
                                'Home & Garden' => 'fas fa-home',
                                'Sports' => 'fas fa-dumbbell'
                            ];
                            echo '<i class="' . ($icons[$category['name']] ?? 'fas fa-tag') . '"></i>';
                            ?>
                        </div>
                        <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<?php if (!empty($featuredProducts)): ?>
<section class="mb-5">
    <div class="container">
        <h2 class="text-center mb-5">Featured Products</h2>
        <div class="row">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/300x250?text=' . urlencode($product['name']); ?>"
                             class="card-img-top product-image"
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-title">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                            </p>

                            <!-- Product Rating -->
                            <?php
                            // Get rating for this product
                            $rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
                            $rating_stmt->execute([$product['id']]);
                            $rating_data = $rating_stmt->fetch();
                            $avg_rating = round($rating_data['avg_rating'], 1);
                            $review_count = $rating_data['review_count'];
                            ?>

                            <?php if ($review_count > 0): ?>
                                <div class="mb-2">
                                    <span class="star-rating">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $avg_rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - 0.5 <= $avg_rating) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </span>
                                    <small class="text-muted ms-1">(<?php echo $review_count; ?>)</small>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                            <div class="mt-3">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <?php if (isLoggedIn()): ?>
                                        <button class="btn btn-cart w-100 add-to-cart"
                                                data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-cart w-100">
                                            <i class="fas fa-sign-in-alt"></i> Login to Buy
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-times"></i> Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center">
            <a href="products.php" class="btn btn-primary btn-lg">View All Products</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-shipping-fast text-primary" style="font-size: 3rem;"></i>
                </div>
                <h5>Fast Shipping</h5>
                <p class="text-muted">Free shipping on orders over $50. Fast and reliable delivery.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-shield-alt text-primary" style="font-size: 3rem;"></i>
                </div>
                <h5>Secure Payment</h5>
                <p class="text-muted">Your payment information is safe and secure with us.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-icon mb-3">
                    <i class="fas fa-undo text-primary" style="font-size: 3rem;"></i>
                </div>
                <h5>Easy Returns</h5>
                <p class="text-muted">Not satisfied? Return your purchase within 30 days.</p>
            </div>
        </div>
    </div>
</section>

<!-- Add CSRF token for AJAX requests -->
<meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">

<?php include 'includes/footer.php'; ?>
