<?php
$pageTitle = "Products";
include 'includes/header.php';

// Get filters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'name';
$order = isset($_GET['order']) ? sanitize($_GET['order']) : 'ASC';

// Build query
$where_conditions = ["p.status = 'active'"];
$params = [];

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Valid sort options
$valid_sorts = ['name', 'price', 'created_at'];
$sort = in_array($sort, $valid_sorts) ? $sort : 'name';
$order = in_array(strtoupper($order), ['ASC', 'DESC']) ? strtoupper($order) : 'ASC';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = "SELECT COUNT(*) FROM products p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products
$query = "SELECT p.*, c.name as category_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE $where_clause
          ORDER BY p.$sort $order
          LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

// Get current category name
$current_category = '';
if ($category_id > 0) {
    $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_stmt->execute([$category_id]);
    $current_category = $cat_stmt->fetchColumn();
}
?>

<div class="row">
    <!-- Sidebar Filters -->
    <div class="col-lg-3">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
            </div>
            <div class="card-body">
                <!-- Search -->
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control"
                               placeholder="Search products..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <?php if ($category_id): ?>
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                </form>

                <!-- Categories -->
                <h6>Categories</h6>
                <div class="list-group list-group-flush">
                    <a href="products.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>"
                       class="list-group-item list-group-item-action <?php echo $category_id == 0 ? 'active' : ''; ?>">
                        All Categories
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="products.php?category=<?php echo $category['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                           class="list-group-item list-group-item-action <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="col-lg-9">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>
                    <?php if ($current_category): ?>
                        <?php echo htmlspecialchars($current_category); ?>
                    <?php elseif ($search): ?>
                        Search Results for "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        All Products
                    <?php endif; ?>
                </h2>
                <p class="text-muted"><?php echo $total_products; ?> products found</p>
            </div>

            <!-- Sort Options -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                    Sort by
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => 'ASC'])); ?>">Name A-Z</a></li>
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => 'DESC'])); ?>">Name Z-A</a></li>
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price', 'order' => 'ASC'])); ?>">Price Low-High</a></li>
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price', 'order' => 'DESC'])); ?>">Price High-Low</a></li>
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => 'DESC'])); ?>">Newest First</a></li>
                </ul>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3">No products found</h4>
                <p class="text-muted">Try adjusting your search criteria or browse all categories.</p>
                <a href="products.php" class="btn btn-primary">View All Products</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-xl-4 mb-4">
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

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                </div>
                                <div class="mt-auto">
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);

                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add CSRF token for AJAX requests -->
<meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">

<?php include 'includes/footer.php'; ?>
