<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'employee';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
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

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>EMS</h2>
        <p class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></p>
    </div>
    
    <nav class="nav-menu">
        <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <span class="icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="holidays.php" class="nav-link <?php echo $current_page === 'holidays.php' ? 'active' : ''; ?>">
            <span class="icon">🎉</span>
            <span>Holidays</span>
        </a>
        <a href="leave.php" class="nav-link <?php echo $current_page === 'leave.php' ? 'active' : ''; ?>">
            <span class="icon">📋</span>
            <span>Leave Requests</span>
        </a>
        <a href="overtime.php" class="nav-link <?php echo $current_page === 'overtime.php' ? 'active' : ''; ?>">
            <span class="icon">⏰</span>
            <span>Overtime</span>
        </a>
        
        <?php if ($role === 'admin' || $role === 'manager'): ?>
            <hr>
            <a href="approvals.php" class="nav-link <?php echo $current_page === 'approvals.php' ? 'active' : ''; ?>">
                <span class="icon">✅</span>
                <span>Approvals</span>
                <?php if ($pending_count > 0): ?>
                    <span class="badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        
        <hr>
        <a href="profile.php" class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <span class="icon">⚙️</span>
            <span>Settings</span>
        </a>
        <a href="logout.php" class="nav-link logout">
            <span class="icon">🚪</span>
            <span>Logout</span>
        </a>
    </nav>
</aside>