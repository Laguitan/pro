<?php
$pageTitle = "My Orders";
include 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=orders.php');
}

// Get user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<h2><i class="fas fa-box"></i> My Orders</h2>

<?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <i class="fas fa-box text-muted" style="font-size: 4rem;"></i>
        <h4 class="mt-3">No orders yet</h4>
        <p class="text-muted">Start shopping to see your orders here.</p>
        <a href="products.php" class="btn btn-primary">Browse Products</a>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($orders as $order): ?>
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Order #<?php echo $order['id']; ?></h6>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                        </div>
                        <div>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Order Details</h6>
                                <p class="mb-1"><strong>Total:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
                                <p class="mb-1"><strong>Payment:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                                <p class="mb-0"><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Shipping Address</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <?php
                        $items_stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi
                                                     JOIN products p ON oi.product_id = p.id
                                                     WHERE oi.order_id = ?");
                        $items_stmt->execute([$order['id']]);
                        $items = $items_stmt->fetchAll();
                        ?>

                        <div class="mt-3">
                            <h6>Items Ordered</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                            <?php if ($order['status'] == 'delivered'): ?>
                                                <th>Review</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td>
                                                    <a href="product.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><?php echo formatPrice($item['price']); ?></td>
                                                <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                                <?php if ($order['status'] == 'delivered'): ?>
                                                    <td>
                                                        <?php
                                                        // Check if user has reviewed this product
                                                        $review_check = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
                                                        $review_check->execute([$item['product_id'], $_SESSION['user_id']]);
                                                        $has_reviewed = $review_check->fetch();
                                                        ?>

                                                        <?php if ($has_reviewed): ?>
                                                            <small class="text-success">
                                                                <i class="fas fa-check"></i> Reviewed
                                                            </small>
                                                        <?php else: ?>
                                                            <a href="product.php?id=<?php echo $item['product_id']; ?>#reviewForm"
                                                               class="btn btn-sm btn-outline-primary">
                                                                Write Review
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
