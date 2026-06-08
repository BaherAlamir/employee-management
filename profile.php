<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$message = '';

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    
    if ($name && $email) {
        $stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, email = ?, department = ?, position = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssi", $name, $email, $department, $position, $user_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Profile updated successfully!</div>";
            $_SESSION['name'] = $name;
        } else {
            $message = "<div class='alert alert-error'>Error updating profile!</div>";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$current_password || !$new_password || !$confirm_password) {
        $message = "<div class='alert alert-error'>All fields are required!</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-error'>New passwords do not match!</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='alert alert-error'>Password must be at least 6 characters!</div>";
    } else {
        if (password_verify($current_password, $user['password'])) {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Password changed successfully!</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='alert alert-error'>Current password is incorrect!</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Employee Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

            <div class="content">
                <div class="page-header">
                    <h1>Settings & Profile</h1>
                </div>

                <?php echo $message; ?>

                <div class="settings-container">
                    <div class="settings-card">
                        <h2>Profile Information</h2>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Employee ID</div>
                                <div class="info-value">#<?php echo $user['id']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Role</div>
                                <div class="info-value"><?php echo ucfirst($user['role']); ?></div>
                            </div>
                        </div>

                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="update">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Department</label>
                                    <input type="text" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Position</label>
                                    <input type="text" name="position" value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="modal-actions">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>

                    <div class="settings-card">
                        <h2>Leave Balances</h2>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Leave Balance</div>
                                <div class="info-value" style="color: #667eea;"><?php echo $user['leave_balance']; ?> days</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Used Leave</div>
                                <div class="info-value" style="color: #f57c00;"><?php echo $user['total_leave_days']; ?> days</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Overtime Hours</div>
                                <div class="info-value" style="color: #2e7d32;"><?php echo round($user['overtime_hours'], 2); ?> hrs</div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <h2>Change Password</h2>
                        
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>

                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required>
                            </div>

                            <div class="modal-actions">
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .settings-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .settings-card h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
    </style>
</body>
</html>