<?php
$pageTitle = "Checkout";
include 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=checkout.php');
}

// Get cart items
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price FROM cart c
                       JOIN products p ON c.product_id = p.id
                       WHERE c.user_id = ? AND p.status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    redirect('cart.php');
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal >= 50 ? 0 : 9.99;
$tax = $subtotal * 0.08;
$total = $subtotal + $shipping + $tax;

// Process order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, billing_address, payment_method)
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total, $_POST['shipping_address'], $_POST['billing_address'], $_POST['payment_method']]);
        $order_id = $pdo->lastInsertId();

        // Add order items
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        $pdo->commit();

        setFlashMessage('success', 'Order placed successfully!');
        redirect('orders.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage('error', 'Error processing order');
    }
}
?>

<div class="row">
    <div class="col-lg-8">
        <h2>Checkout</h2>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Shipping Address</label>
                <textarea class="form-control" name="shipping_address" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Billing Address</label>
                <textarea class="form-control" name="billing_address" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select class="form-select" name="payment_method" required>
                    <option value="">Select Payment Method</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="paypal">PayPal</option>
                    <option value="cash_on_delivery">Cash on Delivery</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Place Order</button>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Order Summary</div>
            <div class="card-body">
                <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex justify-content-between">
                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                        <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Total:</strong>
                    <strong><?php echo formatPrice($total); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
