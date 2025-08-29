<?php
include 'includes/header.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    redirect('products.php');
}

// Get product details
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.id = ? AND p.status = 'active'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('products.php');
}

$pageTitle = $product['name'];

// Get product ratings and reviews
$reviews_stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name
                               FROM reviews r
                               JOIN users u ON r.user_id = u.id
                               WHERE r.product_id = ?
                               ORDER BY r.created_at DESC");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate average rating
$rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
                              FROM reviews WHERE product_id = ?");
$rating_stmt->execute([$product_id]);
$rating_data = $rating_stmt->fetch();
$avg_rating = round($rating_data['avg_rating'], 1);
$total_reviews = $rating_data['total_reviews'];

// Check if current user has reviewed this product
$user_review = null;
if (isLoggedIn()) {
    $user_review_stmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? AND user_id = ?");
    $user_review_stmt->execute([$product_id, $_SESSION['user_id']]);
    $user_review = $user_review_stmt->fetch();
}

// Get related products
$related_stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                               FROM products p
                               LEFT JOIN categories c ON p.category_id = c.id
                               WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
                               ORDER BY RAND() LIMIT 4");
$related_stmt->execute([$product['category_id'], $product['id']]);
$related_products = $related_stmt->fetchAll();

// Function to display star rating
function displayStars($rating, $maxStars = 5) {
    $output = '';
    for ($i = 1; $i <= $maxStars; $i++) {
        if ($i <= $rating) {
            $output .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $output .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $output .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $output;
}
?>

<div class="row">
    <!-- Product Images -->
    <div class="col-lg-6">
        <div class="product-gallery">
            <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/500x500?text=' . urlencode($product['name']); ?>"
                 class="img-fluid rounded shadow"
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 style="width: 100%; height: 400px; object-fit: cover;">
        </div>
    </div>

    <!-- Product Details -->
    <div class="col-lg-6">
        <div class="product-details">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <?php if ($product['category_name']): ?>
                        <li class="breadcrumb-item">
                            <a href="products.php?category=<?php echo $product['category_id']; ?>">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>

            <h1 class="h2 mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

            <!-- Rating Display -->
            <?php if ($total_reviews > 0): ?>
                <div class="mb-3">
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <?php echo displayStars($avg_rating); ?>
                        </div>
                        <span class="me-2"><?php echo $avg_rating; ?>/5</span>
                        <span class="text-muted">({<?php echo $total_reviews; ?> review<?php echo $total_reviews > 1 ? 's' : ''; ?>)</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <span class="h3 text-success me-3"><?php echo formatPrice($product['price']); ?></span>
                <?php if ($product['category_name']): ?>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Stock Status -->
            <div class="mb-3">
                <?php if ($product['stock_quantity'] > 0): ?>
                    <span class="text-success">
                        <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)
                    </span>
                <?php else: ?>
                    <span class="text-danger">
                        <i class="fas fa-times-circle"></i> Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <h5>Description</h5>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <!-- Add to Cart Form -->
            <?php if ($product['stock_quantity'] > 0): ?>
                <?php if (isLoggedIn()): ?>
                    <form class="mb-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label for="quantity" class="form-label">Quantity:</label>
                            </div>
                            <div class="col-auto">
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-btn decrease">-</button>
                                    <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                    <button type="button" class="quantity-btn increase">+</button>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex mt-3">
                            <button type="button" class="btn btn-cart btn-lg flex-fill add-to-cart"
                                    data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-heart"></i> Wishlist
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <a href="login.php" class="alert-link">Login</a> to purchase this product.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Product Features -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Product Features</h6>
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-shipping-fast text-primary me-2"></i> Free shipping on orders over $50</li>
                        <li><i class="fas fa-undo text-primary me-2"></i> 30-day return policy</li>
                        <li><i class="fas fa-shield-alt text-primary me-2"></i> 1-year warranty</li>
                        <li><i class="fas fa-headset text-primary me-2"></i> 24/7 customer support</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reviews Section -->
<div class="mt-5">
    <div class="row">
        <div class="col-12">
            <h3>Customer Reviews</h3>

            <!-- Write Review Section -->
            <?php if (isLoggedIn()): ?>
                <?php if (!$user_review): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-star"></i> Write a Review</h5>
                        </div>
                        <div class="card-body">
                            <form id="reviewForm">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating-input">
                                        <input type="radio" name="rating" value="5" id="star5">
                                        <label for="star5"><i class="fas fa-star"></i></label>
                                        <input type="radio" name="rating" value="4" id="star4">
                                        <label for="star4"><i class="fas fa-star"></i></label>
                                        <input type="radio" name="rating" value="3" id="star3">
                                        <label for="star3"><i class="fas fa-star"></i></label>
                                        <input type="radio" name="rating" value="2" id="star2">
                                        <label for="star2"><i class="fas fa-star"></i></label>
                                        <input type="radio" name="rating" value="1" id="star1">
                                        <label for="star1"><i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="review_text" class="form-label">Your Review</label>
                                    <textarea class="form-control" id="review_text" name="review_text" rows="4"
                                              placeholder="Share your experience with this product..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Review
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You have already reviewed this product.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <a href="login.php" class="alert-link">Login</a> to write a review.
                </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <?php if (!empty($reviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                        <div class="mb-2">
                                            <?php echo displayStars($review['rating']); ?>
                                            <span class="ms-2 text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($review['review_text']): ?>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-comments text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">No reviews yet</h5>
                    <p class="text-muted">Be the first to review this product!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Related Products -->
<?php if (!empty($related_products)): ?>
<div class="mt-5">
    <h3 class="mb-4">Related Products</h3>
    <div class="row">
        <?php foreach ($related_products as $related): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card product-card h-100">
                    <img src="<?php echo $related['image'] ?: 'https://via.placeholder.com/300x200?text=' . urlencode($related['name']); ?>"
                         class="card-img-top product-image"
                         alt="<?php echo htmlspecialchars($related['name']); ?>"
                         style="height: 200px;">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">
                            <a href="product.php?id=<?php echo $related['id']; ?>" class="product-title">
                                <?php echo htmlspecialchars($related['name']); ?>
                            </a>
                        </h6>
                        <p class="card-text text-muted flex-grow-1 small">
                            <?php echo htmlspecialchars(substr($related['description'], 0, 80)) . '...'; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="product-price"><?php echo formatPrice($related['price']); ?></span>
                            <?php if ($related['stock_quantity'] > 0 && isLoggedIn()): ?>
                                <button class="btn btn-sm btn-cart add-to-cart"
                                        data-product-id="<?php echo $related['id']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Add CSRF token for AJAX requests -->
<meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">

<style>
/* Rating Input Styles */
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label {
    color: #ddd;
    font-size: 1.5rem;
    padding: 0 0.2rem;
    cursor: pointer;
    transition: color 0.3s ease;
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input[type="radio"]:checked ~ label {
    color: #ffc107;
}
</style>

<script>
// Update add to cart button to use quantity
document.addEventListener('DOMContentLoaded', function() {
    const addToCartBtn = document.querySelector('.add-to-cart[data-product-id="<?php echo $product['id']; ?>"]');
    const quantityInput = document.querySelector('#quantity');

    if (addToCartBtn && quantityInput) {
        addToCartBtn.addEventListener('click', function() {
            this.dataset.quantity = quantityInput.value;
        });
    }

    // Review form submission
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('product_id', '<?php echo $product_id; ?>');
            formData.append('csrf_token', getCsrfToken());

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;

            fetch('ajax/submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Review submitted successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Error submitting review', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error submitting review', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
