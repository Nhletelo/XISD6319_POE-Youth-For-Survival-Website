<?php
session_start();
require_once 'config/db_conn.php';

// Check if volunteer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    header("Location: login.php");
    exit;
}

$volunteer_id = $_SESSION['user_id'];
$volunteerName = $_SESSION['fullname'] ?? 'Volunteer';

// Handle status update (if form submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['task_status'])) {
    $task_id = intval($_POST['task_id']);
    $new_status = $_POST['task_status'];

    // Validate status value
    $valid_statuses = ['pending', 'in_progress', 'completed'];
    if (in_array($new_status, $valid_statuses)) {
        $completed_date_sql = ($new_status === 'completed') ? ", completed_date = NOW()" : ", completed_date = NULL";

        $stmt = $conn->prepare("UPDATE volunteer_tasks SET task_status = ? $completed_date_sql, updated_at = NOW() WHERE id = ? AND volunteer_id = ?");
        $stmt->bind_param("sii", $new_status, $task_id, $volunteer_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: volunteer_tasks.php");
    exit;
}

// Fetch tasks for this volunteer
$stmt = $conn->prepare("SELECT * FROM volunteer_tasks WHERE volunteer_id = ? ORDER BY 
    CASE 
        WHEN task_status = 'pending' THEN 1 
        WHEN task_status = 'in_progress' THEN 2 
        WHEN task_status = 'completed' THEN 3 
    END,
    due_date ASC");
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Volunteer Tasks - Youth For Survival</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/volunteer_tasks.css" rel="stylesheet">
        
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="brand">
                <h1>Youth For Survival</h1>
                <div class="welcome">Welcome back, <strong><?php echo htmlspecialchars($volunteerName); ?></strong></div>
            </div>
            
            <div class="menu">
                <a href="volunteer_dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="volunteer_tasks.php" class="menu-item active">
                    <i class="fas fa-tasks"></i>
                    <span>My Tasks</span>
                </a>
                <a href="volunteer_messages.php" class="menu-item">
                    <i class="fas fa-comment"></i>
                    <span>Send Message</span>
                </a>
                <a href="volunteer_inbox.php" class="menu-item">
                    <i class="fas fa-inbox"></i>
                    <span>Inbox</span>
                </a>
            </div>
            
            <div class="logout-container">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="dashboard-header">
                <h1>My Volunteer Tasks</h1>
                <p>View and manage your assigned tasks. Update your progress to keep the team informed.</p>
            </div>
            
            <!-- Task Filters -->
            <div class="task-filters">
                <button class="filter-btn active" data-filter="all">All Tasks</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="in_progress">In Progress</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
            </div>
            
            <!-- Task Grid -->
            <div class="task-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($task = $result->fetch_assoc()): ?>
                        <div class="task-card <?php echo htmlspecialchars($task['task_status']); ?>" data-status="<?php echo htmlspecialchars($task['task_status']); ?>">
                            <div class="task-header">
                                <h3 class="task-title"><?php echo htmlspecialchars($task['task_title']); ?></h3>
                                <span class="task-status status-<?php echo htmlspecialchars($task['task_status']); ?>">
                                    <?php 
                                    $status = htmlspecialchars($task['task_status']);
                                    echo str_replace('_', ' ', ucfirst($status)); 
                                    ?>
                                </span>
                            </div>
                            
                            <p class="task-description"><?php echo nl2br(htmlspecialchars($task['task_description'])); ?></p>
                            
                            <div class="task-details">
                                <div class="detail-item">
                                    <span class="detail-label">Assigned Date</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($task['assigned_date'])); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Due Date</span>
                                    <span class="detail-value <?php echo (strtotime($task['due_date']) < time() && $task['task_status'] != 'completed') ? 'text-danger' : ''; ?>">
                                        <?php echo date('M j, Y', strtotime($task['due_date'])); ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Priority</span>
                                    <span class="detail-value"><?php echo isset($task['priority']) ? ucfirst($task['priority']) : 'Normal'; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Completed Date</span>
                                    <span class="detail-value"><?php echo !empty($task['completed_date']) ? date('M j, Y', strtotime($task['completed_date'])) : '-'; ?></span>
                                </div>
                            </div>
                            
                            <div class="task-actions">
                                <form method="post" action="volunteer_tasks.php" class="status-form">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <select name="task_status" class="status-select">
                                        <option value="pending" <?php if ($task['task_status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                        <option value="in_progress" <?php if ($task['task_status'] === 'in_progress') echo 'selected'; ?>>In Progress</option>
                                        <option value="completed" <?php if ($task['task_status'] === 'completed') echo 'selected'; ?>>Completed</option>
                                    </select>
                                    <button type="submit" class="update-btn">Update</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-tasks">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No tasks assigned yet</h3>
                        <p>You don't have any tasks assigned to you at the moment. Check back later or contact your coordinator.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Filter tasks by status
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const taskCards = document.querySelectorAll('.task-card');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show/hide tasks based on filter
                    taskCards.forEach(card => {
                        if (filter === 'all' || card.getAttribute('data-status') === filter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
            
            // Highlight overdue tasks
            taskCards.forEach(card => {
                const dueDateElem = card.querySelector('.detail-value.text-danger');
                if (dueDateElem) {
                    card.style.boxShadow = '0 0 0 2px rgba(220, 53, 69, 0.3)';
                }
            });
        });
    </script>
</body>
</html>