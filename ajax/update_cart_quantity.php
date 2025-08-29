<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$cart_id = (int)($_POST['cart_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);
$csrf_token = $_POST['csrf_token'] ?? '';

if (!verifyCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($cart_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Check stock availability
    $stmt = $pdo->prepare("SELECT p.stock_quantity FROM cart c
                           JOIN products p ON c.product_id = p.id
                           WHERE c.id = ? AND c.user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    $result = $stmt->fetch();

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }

    if ($result['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        exit;
    }

    // Update quantity
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Quantity updated']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
