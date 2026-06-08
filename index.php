<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$pending_count = 0;
if ($role === 'admin' || $role === 'manager') {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'");
    $stmt->execute();
    $pending_count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management System - Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

            <div class="content">
                <div class="page-header">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</p>
                </div>

                <div class="dashboard-grid">
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #e3f2fd;">
                                <span>🎉</span>
                            </div>
                            <div class="stat-content">
                                <p class="stat-label">Holiday Balance</p>
                                <h3 class="stat-value"><?php echo $user['holiday_balance']; ?> days</h3>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background: #f3e5f5;">
                                <span>📋</span>
                            </div>
                            <div class="stat-content">
                                <p class="stat-label">Leave Balance</p>
                                <h3 class="stat-value"><?php echo $user['leave_balance']; ?> days</h3>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background: #fff3e0;">
                                <span>⏰</span>
                            </div>
                            <div class="stat-content">
                                <p class="stat-label">Overtime Hours</p>
                                <h3 class="stat-value"><?php echo round($user['overtime_hours'], 1); ?> hrs</h3>
                            </div>
                        </div>

                        <?php if ($role === 'admin' || $role === 'manager'): ?>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: #e8f5e9;">
                                    <span>✅</span>
                                </div>
                                <div class="stat-content">
                                    <p class="stat-label">Pending Requests</p>
                                    <h3 class="stat-value"><?php echo $pending_count; ?></h3>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="recent-section">
                        <div class="section-header">
                            <h2>Recent Requests</h2>
                            <a href="leave.php" class="view-all">View All</a>
                        </div>
                        <div class="requests-list">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT * FROM requests 
                                WHERE employee_id = ? 
                                ORDER BY created_at DESC 
                                LIMIT 5
                            ");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($req = $result->fetch_assoc()) {
                                    $status_class = 'status-' . strtolower($req['status']);
                                    echo "<div class='request-item'>
                                        <div class='request-info'>
                                            <p class='request-type'>" . ucfirst($req['type']) . "</p>
                                            <p class='request-date'>" . date('M d, Y', strtotime($req['start_date'])) . "</p>
                                        </div>
                                        <span class='status-badge {$status_class}'>" . ucfirst($req['status']) . "</span>
                                    </div>";
                                }
                            } else {
                                echo "<p class='no-data'>No requests yet</p>";
                            }
                            $stmt->close();
                            ?>
                        </div>
                    </div>

                    <div class="actions-section">
                        <h2>Quick Actions</h2>
                        <div class="action-buttons">
                            <a href="leave.php" class="action-btn btn-primary">
                                <span>➕</span>
                                Request Leave
                            </a>
                            <a href="overtime.php" class="action-btn btn-secondary">
                                <span>➕</span>
                                Log Overtime
                            </a>
                            <a href="holidays.php" class="action-btn btn-secondary">
                                <span>📅</span>
                                View Holidays
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>