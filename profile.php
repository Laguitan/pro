<?php
$pageTitle = "My Profile";
include 'includes/header.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=profile.php');
}

$errors = [];
$success = false;

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $zip_code = sanitize($_POST['zip_code'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }

    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validateEmail($email)) {
        $errors[] = "Invalid email format";
    }

    // Check if email is already taken by another user
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Email is already taken by another user";
        }
    }

    // Password change validation
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!verifyPassword($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }

    // Update user if no errors
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = hashPassword($new_password);
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?,
                                       address = ?, city = ?, state = ?, zip_code = ?, country = ?, password = ?,
                                       updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $address, $city, $state,
                               $zip_code, $country, $hashed_password, $_SESSION['user_id']]);
            } else {
                // Update without password change
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?,
                                       address = ?, city = ?, state = ?, zip_code = ?, country = ?,
                                       updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $address, $city, $state,
                               $zip_code, $country, $_SESSION['user_id']]);
            }

            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;

            $success = true;
            setFlashMessage('success', 'Profile updated successfully!');

            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

        } catch (PDOException $e) {
            $errors[] = "Error updating profile. Please try again.";
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user-edit"></i> My Profile</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Profile updated successfully!
                    </div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <!-- Personal Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6><i class="fas fa-user"></i> Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6><i class="fas fa-map-marker-alt"></i> Address Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                           value="<?php echo htmlspecialchars($user['city']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State/Province</label>
                                    <input type="text" class="form-control" id="state" name="state"
                                           value="<?php echo htmlspecialchars($user['state']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code"
                                           value="<?php echo htmlspecialchars($user['zip_code']); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="">Select Country</option>
                                    <option value="US" <?php echo $user['country'] == 'US' ? 'selected' : ''; ?>>United States</option>
                                    <option value="CA" <?php echo $user['country'] == 'CA' ? 'selected' : ''; ?>>Canada</option>
                                    <option value="UK" <?php echo $user['country'] == 'UK' ? 'selected' : ''; ?>>United Kingdom</option>
                                    <option value="AU" <?php echo $user['country'] == 'AU' ? 'selected' : ''; ?>>Australia</option>
                                    <option value="DE" <?php echo $user['country'] == 'DE' ? 'selected' : ''; ?>>Germany</option>
                                    <option value="FR" <?php echo $user['country'] == 'FR' ? 'selected' : ''; ?>>France</option>
                                    <option value="other" <?php echo $user['country'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6><i class="fas fa-lock"></i> Change Password</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Leave password fields empty if you don't want to change your password.
                            </div>

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h6><i class="fas fa-chart-bar"></i> Account Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <?php
                    // Get user statistics
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $total_orders = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND payment_status = 'completed'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $total_spent = $stmt->fetchColumn() ?: 0;

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $total_reviews = $stmt->fetchColumn();
                    ?>

                    <div class="col-md-4">
                        <div class="stat-item">
                            <h4 class="text-primary"><?php echo $total_orders; ?></h4>
                            <p class="text-muted">Total Orders</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-item">
                            <h4 class="text-success"><?php echo formatPrice($total_spent); ?></h4>
                            <p class="text-muted">Total Spent</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-item">
                            <h4 class="text-warning"><?php echo $total_reviews; ?></h4>
                            <p class="text-muted">Reviews Written</p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <p class="text-muted">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
