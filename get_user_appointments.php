<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
if (!isset($_SESSION['admin_logged_in'])) {
    exit('Unauthorized');
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    
    // Get user details
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = get_stmt_result($stmt)->fetch_assoc();
    
    // Get user's appointments
    $appointments_query = "SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC";
    $stmt = $db->prepare($appointments_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $appointments = get_stmt_result($stmt);
    ?>
    
    <div class="mb-3">
        <h6>User Details:</h6>
        <p>
            <strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?><br>
            <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
            <strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?>
        </p>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>User Notes</th>
                    <th>Admin Comments</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($appointment = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?></td>
                    <td><?php echo $appointment['appointment_time']; ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $appointment['status'] == 'confirmed' ? 'success' : 
                                ($appointment['status'] == 'pending' ? 'warning' : 'danger'); 
                        ?>">
                            <?php echo ucfirst($appointment['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($appointment['notes'])): ?>
                            <button type="button" class="btn btn-sm btn-info" 
                                    data-bs-toggle="popover" 
                                    data-bs-content="<?php echo htmlspecialchars($appointment['notes']); ?>"
                                    data-bs-trigger="focus">
                                <i class="fas fa-sticky-note me-1"></i>View Notes
                            </button>
                        <?php else: ?>
                            <span class="text-muted">No notes</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($appointment['admin_comments'])): ?>
                            <button type="button" class="btn btn-sm btn-secondary" 
                                    data-bs-toggle="popover" 
                                    data-bs-content="<?php echo htmlspecialchars($appointment['admin_comments']); ?>"
                                    data-bs-trigger="focus">
                                <i class="fas fa-comment me-1"></i>View Comments
                            </button>
                        <?php else: ?>
                            <span class="text-muted">No comments</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($appointment['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?> 

<script>
$(document).ready(function(){
    $('[data-bs-toggle="popover"]').popover();
});
</script> 