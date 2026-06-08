<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $type = $_POST['type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    if ($type && $start_date && $end_date && $reason) {
        $stmt = $conn->prepare("
            INSERT INTO requests (employee_id, type, start_date, end_date, reason, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("issss", $user_id, $type, $start_date, $end_date, $reason);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Leave request submitted successfully!</div>";
        } else {
            $message = "<div class='alert alert-error'>Error submitting request!</div>";
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT * FROM requests 
    WHERE employee_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - Employee Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

            <div class="content">
                <div class="page-header">
                    <h1>Leave Requests</h1>
                    <button class="btn btn-primary" onclick="openModal('leaveModal')">Request New Leave</button>
                </div>

                <?php echo $message; ?>

                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Leave Balance</h3>
                        <p class="summary-value"><?php echo $user['leave_balance']; ?> days</p>
                    </div>
                    <div class="summary-card">
                        <h3>Used Leave</h3>
                        <p class="summary-value"><?php echo $user['total_leave_days']; ?> days</p>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($requests->num_rows > 0) {
                                while ($req = $requests->fetch_assoc()) {
                                    $start = new DateTime($req['start_date']);
                                    $end = new DateTime($req['end_date']);
                                    $days = $end->diff($start)->days + 1;
                                    $status_class = 'status-' . strtolower($req['status']);
                                    
                                    echo "<tr>
                                        <td><span class='type-badge'>" . ucfirst($req['type']) . "</span></td>
                                        <td>" . date('M d, Y', strtotime($req['start_date'])) . "</td>
                                        <td>" . date('M d, Y', strtotime($req['end_date'])) . "</td>
                                        <td>{$days}</td>
                                        <td>" . substr($req['reason'], 0, 30) . "...</td>
                                        <td><span class='status-badge {$status_class}'>" . ucfirst($req['status']) . "</span></td>
                                        <td>
                                            <button class='btn-small'>View</button>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>No leave requests yet</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="leaveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Request Leave</h2>
                <button class="close-btn" onclick="closeModal('leaveModal')">&times;</button>
            </div>
            <form method="POST" class="form">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Leave Type *</label>
                    <select name="type" required>
                        <option value="">Select Type</option>
                        <option value="annual">Annual Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="emergency">Emergency Leave</option>
                        <option value="unpaid">Unpaid Leave</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date *</label>
                        <input type="date" name="end_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Reason *</label>
                    <textarea name="reason" rows="4" required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('leaveModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>