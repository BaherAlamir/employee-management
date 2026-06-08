<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM holidays ORDER BY date ASC");
$stmt->execute();
$holidays = $stmt->get_result();
$stmt->close();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'admin') {
    $name = $_POST['holiday_name'] ?? '';
    $date = $_POST['holiday_date'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if ($name && $date) {
        $stmt = $conn->prepare("
            INSERT INTO holidays (name, date, description, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("sss", $name, $date, $description);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Holiday added successfully!</div>";
            header('Refresh:2');
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holidays - Employee Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <?php include 'topbar.php'; ?>

            <div class="content">
                <div class="page-header">
                    <h1>Company Holidays</h1>
                    <?php if ($role === 'admin'): ?>
                        <button class="btn btn-primary" onclick="openModal('holidayModal')">Add Holiday</button>
                    <?php endif; ?>
                </div>

                <?php echo $message; ?>

                <div class="holidays-container">
                    <div class="holidays-list">
                        <h2>Upcoming Holidays</h2>
                        <?php
                        if ($holidays->num_rows > 0) {
                            while ($holiday = $holidays->fetch_assoc()) {
                                $date = new DateTime($holiday['date']);
                                $is_today = $date->format('Y-m-d') === date('Y-m-d');
                                $is_past = $date < new DateTime();
                                $class = '';
                                if ($is_today) $class = 'holiday-today';
                                elseif ($is_past) $class = 'holiday-past';
                                
                                echo "<div class='holiday-card {$class}'>
                                    <div class='holiday-date'>
                                        <div class='day'>" . $date->format('d') . "</div>
                                        <div class='month'>" . $date->format('M') . "</div>
                                    </div>
                                    <div class='holiday-info'>
                                        <h3>" . htmlspecialchars($holiday['name']) . "</h3>
                                        <p>" . htmlspecialchars($holiday['description']) . "</p>
                                        <small>" . $date->format('l, Y') . "</small>
                                    </div>
                                </div>";
                            }
                        } else {
                            echo "<p class='no-data'>No holidays scheduled</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if ($role === 'admin'): ?>
        <div id="holidayModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add Holiday</h2>
                    <button class="close-btn" onclick="closeModal('holidayModal')">&times;</button>
                </div>
                <form method="POST" class="form">
                    <div class="form-group">
                        <label>Holiday Name *</label>
                        <input type="text" name="holiday_name" required>
                    </div>

                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="holiday_date" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('holidayModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Holiday</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

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