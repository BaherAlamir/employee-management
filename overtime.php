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
    $date = $_POST['overtime_date'] ?? '';
    $hours = $_POST['hours'] ?? '';
    $description = $_POST['description'] ?? '';
    $approved_by = $_POST['approved_by'] ?? '';
    
    if ($date && $hours && $description) {
        $stmt = $conn->prepare("
            INSERT INTO overtime (employee_id, date, hours, description, approved_by, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("isdss", $user_id, $date, $hours, $description, $approved_by);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Overtime entry submitted successfully!</div>";
        } else {
            $message = "<div class='alert alert-error'>Error submitting overtime entry!</div>";
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
    SELECT * FROM overtime 
    WHERE employee_id = ? 
    ORDER BY date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$overtimes = $stmt->get_result();
$stmt->close();

$total_hours = 0;
$approved_hours = 0;
while ($ot = $overtimes->fetch_assoc()) {
    $total_hours += $ot['hours'];
    if ($ot['status'] === 'approved') {
        $approved_hours += $ot['hours'];
    }
}
$overtimes->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime - Employee Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

            <div class="content">
                <div class="page-header">
                    <h1>Overtime Management</h1>
                    <button class="btn btn-primary" onclick="openModal('overtimeModal')">Log Overtime</button>
                </div>

                <?php echo $message; ?>

                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Overtime Hours</h3>
                        <p class="summary-value"><?php echo $total_hours; ?> hrs</p>
                    </div>
                    <div class="summary-card">
                        <h3>Approved Hours</h3>
                        <p class="summary-value"><?php echo $approved_hours; ?> hrs</p>
                    </div>
                    <div class="summary-card">
                        <h3>Pending Hours</h3>
                        <p class="summary-value"><?php echo $total_hours - $approved_hours; ?> hrs</p>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Hours</th>
                                <th>Description</th>
                                <th>Approved By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($overtimes->num_rows > 0) {
                                while ($ot = $overtimes->fetch_assoc()) {
                                    $status_class = 'status-' . strtolower($ot['status']);
                                    echo "<tr>
                                        <td>" . date('M d, Y', strtotime($ot['date'])) . "</td>
                                        <td>{$ot['hours']} hours</td>
                                        <td>" . substr($ot['description'], 0, 40) . "...</td>
                                        <td>" . htmlspecialchars($ot['approved_by']) . "</td>
                                        <td><span class='status-badge {$status_class}'>" . ucfirst($ot['status']) . "</span></td>
                                        <td>
                                            <button class='btn-small'>View</button>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No overtime entries yet</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="overtimeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Log Overtime</h2>
                <button class="close-btn" onclick="closeModal('overtimeModal')">&times;</button>
            </div>
            <form method="POST" class="form">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="overtime_date" required>
                </div>

                <div class="form-group">
                    <label>Hours *</label>
                    <input type="number" name="hours" min="0.5" step="0.5" required>
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label>Approved By</label>
                    <input type="text" name="approved_by" placeholder="Manager Name (Optional)">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('overtimeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
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