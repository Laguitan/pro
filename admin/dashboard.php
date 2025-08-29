<?php
$pageTitle = "Admin Dashboard";
include '../includes/header.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// Get statistics
$stats = [];

// Total products
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$stats['products'] = $stmt->fetchColumn();

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$stats['orders'] = $stmt->fetchColumn();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$stats['users'] = $stmt->fetchColumn();

// Total revenue
$stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'completed'");
$stats['revenue'] = $stmt->fetchColumn() ?: 0;

// Recent orders
$stmt = $pdo->query("SELECT o.*, u.username FROM orders o
                     JOIN users u ON o.user_id = u.id
                     ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();

// Low stock products
$stmt = $pdo->query("SELECT * FROM products WHERE stock_quantity < 10 AND status = 'active' ORDER BY stock_quantity ASC");
$low_stock = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-lg-2">
        <div class="admin-sidebar">
            <nav class="nav flex-column">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box"></i> Products
                </a>
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users"></i> Users
                </a>
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home"></i> Back to Store
                </a>
            </nav>
        </div>
    </div>

    <div class="col-lg-10">
        <h2>Admin Dashboard</h2>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['products']; ?></h4>
                                <p class="mb-0">Products</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['orders']; ?></h4>
                                <p class="mb-0">Orders</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['users']; ?></h4>
                                <p class="mb-0">Users</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo formatPrice($stats['revenue']); ?></h4>
                                <p class="mb-0">Revenue</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Orders -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                                            <td>
                                                <span class="order-status status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="text-warning">Low Stock Alert</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($low_stock)): ?>
                            <p class="text-muted">All products are well stocked!</p>
                        <?php else: ?>
                            <?php foreach ($low_stock as $product): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                                    <span class="badge bg-warning"><?php echo $product['stock_quantity']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
