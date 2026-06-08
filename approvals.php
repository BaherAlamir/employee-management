<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role !== 'admin' && $role !== 'manager') {
    header('Location: index.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $comment = $_POST['comment'] ?? '';
    
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $stmt = $conn->prepare("
        UPDATE requests 
        SET status = ?, reviewed_by = ?, reviewed_at = NOW(), reviewer_comment = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sisi", $status, $user_id, $comment, $request_id);
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Request " . $status . " successfully!</div>";
    } else {
        $message = "<div class='alert alert-error'>Error updating request!</div>";
    }
    $stmt->close();
}

$stmt = $conn->prepare("
    SELECT r.*, u.name as employee_name, u.email 
    FROM requests r
    JOIN users u ON r.employee_id = u.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
");
$stmt->execute();
$pending_requests = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("
    SELECT r.*, u.name as employee_name, u.email 
    FROM requests r
    JOIN users u ON r.employee_id = u.id
    WHERE r.status = 'approved'
    ORDER BY r.reviewed_at DESC
    LIMIT 10
");
$stmt->execute();
$approved_requests = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvals - Employee Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

            <div class="content">
                <div class="page-header">
                    <h1>Request Approvals</h1>
                </div>

                <?php echo $message; ?>

                <div class="approval-section">
                    <h2>Pending Requests (<?php echo $pending_requests->num_rows; ?>)</h2>
                    
                    <?php
                    if ($pending_requests->num_rows > 0) {
                        while ($req = $pending_requests->fetch_assoc()) {
                            $start = new DateTime($req['start_date']);
                            $end = new DateTime($req['end_date']);
                            $days = $end->diff($start)->days + 1;
                            
                            echo "<div class='approval-card'>
                                <div class='card-header'>
                                    <div class='emp-info'>
                                        <h3>" . htmlspecialchars($req['employee_name']) . "</h3>
                                        <p>" . htmlspecialchars($req['email']) . "</p>
                                    </div>
                                    <span class='type-badge'>" . ucfirst($req['type']) . "</span>
                                </div>
                                
                                <div class='card-body'>
                                    <div class='req-details'>
                                        <div class='detail-item'>
                                            <label>Period:</label>
                                            <span>" . date('M d, Y', strtotime($req['start_date'])) . " - " . date('M d, Y', strtotime($req['end_date'])) . "</span>
                                        </div>
                                        <div class='detail-item'>
                                            <label>Duration:</label>
                                            <span>{$days} days</span>
                                        </div>
                                        <div class='detail-item'>
                                            <label>Reason:</label>
                                            <span>" . htmlspecialchars($req['reason']) . "</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class='card-actions'>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='request_id' value='" . $req['id'] . "'>
                                        <input type='hidden' name='action' value='approve'>
                                        <textarea name='comment' placeholder='Add comment (optional)' rows='2'></textarea>
                                        <button type='submit' class='btn btn-success'>Approve</button>
                                    </form>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='request_id' value='" . $req['id'] . "'>
                                        <input type='hidden' name='action' value='reject'>
                                        <textarea name='comment' placeholder='Reason for rejection (optional)' rows='2'></textarea>
                                        <button type='submit' class='btn btn-danger'>Reject</button>
                                    </form>
                                </div>
                            </div>";
                        }
                    } else {
                        echo "<p class='no-data'>No pending requests</p>";
                    }
                    ?>
                </div>

                <div class="approval-section" style="margin-top: 40px;">
                    <h2>Recently Approved (<?php echo $approved_requests->num_rows; ?>)</h2>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Period</th>
                                    <th>Approved Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($approved_requests->num_rows > 0) {
                                    while ($req = $approved_requests->fetch_assoc()) {
                                        echo "<tr>
                                            <td>" . htmlspecialchars($req['employee_name']) . "</td>
                                            <td><span class='type-badge'>" . ucfirst($req['type']) . "</span></td>
                                            <td>" . date('M d', strtotime($req['start_date'])) . " - " . date('M d, Y', strtotime($req['end_date'])) . "</td>
                                            <td>" . date('M d, Y', strtotime($req['reviewed_at'])) . "</td>
                                            <td><span class='status-badge status-approved'>Approved</span></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>No approved requests</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>