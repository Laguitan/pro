<?php
$pageTitle = "Shopping Cart";
include 'includes/header.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=cart.php');
}

// Get cart items
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.stock_quantity
                       FROM cart c
                       JOIN products p ON c.product_id = p.id
                       WHERE c.user_id = ? AND p.status = 'active'
                       ORDER BY c.added_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping = $subtotal >= 50 ? 0 : 9.99;
$tax = $subtotal * 0.08;
$total = $subtotal + $shipping + $tax;
?>

<div class="row">
    <div class="col-lg-8">
        <h2><i class="fas fa-shopping-cart"></i> Shopping Cart (<?php echo $total_items; ?> items)</h2>

        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart text-muted" style="font-size: 5rem;"></i>
                <h4 class="mt-3">Your cart is empty</h4>
                <a href="products.php" class="btn btn-primary btn-lg">Continue Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <img src="<?php echo $item['image'] ?: 'https://via.placeholder.com/100x100'; ?>"
                                 class="img-fluid rounded" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                        <div class="col-md-4">
                            <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                            <small class="text-muted"><?php echo formatPrice($item['price']); ?></small>
                        </div>
                        <div class="col-md-3">
                            <div class="quantity-selector">
                                <button type="button" class="quantity-btn decrease" data-cart-id="<?php echo $item['id']; ?>">-</button>
                                <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1">
                                <button type="button" class="quantity-btn increase" data-cart-id="<?php echo $item['id']; ?>">+</button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <strong><?php echo formatPrice($item['price'] * $item['quantity']); ?></strong>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-sm btn-outline-danger remove-cart-item" data-cart-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($cart_items)): ?>
    <div class="col-lg-4">
        <div class="cart-summary">
            <h5>Order Summary</h5>
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span><?php echo formatPrice($subtotal); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Shipping:</span>
                <span><?php echo $shipping == 0 ? 'FREE' : formatPrice($shipping); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Tax:</span>
                <span><?php echo formatPrice($tax); ?></span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
                <strong>Total:</strong>
                <strong><?php echo formatPrice($total); ?></strong>
            </div>
            <div class="d-grid">
                <a href="checkout.php" class="btn btn-success btn-lg">Proceed to Checkout</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">

<?php include 'includes/footer.php'; ?>
