<?php
// users_management.php
session_start();
require_once 'config/db_conn.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'] ?? 'Admin';

// Handle user actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        
        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM app_users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Username or email already exists!";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO app_users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fullname, $email, $username, $password, $role);
            
            if ($stmt->execute()) {
                $message = "User added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding user: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    if (isset($_POST['edit_user'])) {
        // Edit existing user
        $user_id = $_POST['user_id'];
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $role = $_POST['role'];
        
        // Check if username or email already exists (excluding current user)
        $check_stmt = $conn->prepare("SELECT id FROM app_users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->bind_param("ssi", $username, $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Username or email already exists!";
            $message_type = "error";
        } else {
            // If password is provided, update it too
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE app_users SET fullname = ?, email = ?, username = ?, password = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $fullname, $email, $username, $password, $role, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE app_users SET fullname = ?, email = ?, username = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $fullname, $email, $username, $role, $user_id);
            }
            
            if ($stmt->execute()) {
                $message = "User updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating user: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    if (isset($_POST['delete_user'])) {
        // Delete user (prevent self-deletion)
        $user_id = $_POST['user_id'];
        
        if ($user_id == $admin_id) {
            $message = "You cannot delete your own account!";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("DELETE FROM app_users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $message = "User deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting user: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? '';

// Build query based on filters
$query = "SELECT id, fullname, email, username, role, created_at FROM app_users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (fullname LIKE ? OR email LIKE ? OR username LIKE ?)";
    $search_term = "%$search%";
    $params = array_fill(0, 3, $search_term);
    $types = "sss";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();

// Get role counts for filters
$all_count = $conn->query("SELECT COUNT(*) as count FROM app_users")->fetch_assoc()['count'];
$admin_count = $conn->query("SELECT COUNT(*) as count FROM app_users WHERE role = 'admin'")->fetch_assoc()['count'];
$volunteer_count = $conn->query("SELECT COUNT(*) as count FROM app_users WHERE role = 'volunteer'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Youth For Survival</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/user_management.css">
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <img src="images/logo_large.webp" alt="Youth For Survival Logo">
            <span>YouthFor<span style="color: var(--accent-color);">Survival</span></span>
        </div>
        <div class="admin-info">
            <span class="admin-name">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="admin_dashboard.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <div class="content-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>User Management</h2>
            </div>
            
            <div class="filters">
                <h3>Filters</h3>
                <ul class="filter-list">
                    <li>
                        <a href="users_management.php" class="<?php echo empty($role_filter) ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> All Users
                            <span class="badge"><?php echo $all_count; ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="users_management.php?role_filter=admin" class="<?php echo $role_filter === 'admin' ? 'active' : ''; ?>">
                            <i class="fas fa-user-shield"></i> Administrators
                            <span class="badge"><?php echo $admin_count; ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="users_management.php?role_filter=volunteer" class="<?php echo $role_filter === 'volunteer' ? 'active' : ''; ?>">
                            <i class="fas fa-hands-helping"></i> Volunteers
                            <span class="badge"><?php echo $volunteer_count; ?></span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <button class="btn btn-primary" style="width: 100%;" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New User
            </button>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>User Management</h1>
                <div class="content-actions">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </div>
            
            <!-- Message Alert -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Search and Filter Box -->
            <form method="GET" class="search-filter-box">
                <input type="text" name="search" class="search-input" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="role_filter" class="filter-select">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="volunteer" <?php echo $role_filter === 'volunteer' ? 'selected' : ''; ?>>Volunteer</option>
                </select>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($search) || !empty($role_filter)): ?>
                    <a href="users_management.php" class="btn btn-danger">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
            
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="far fa-user"></i>
                    <h3>No users found</h3>
                    <p>There are no users matching your criteria.</p>
                </div>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <span class="user-role role-<?php echo $user['role']; ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="user-actions">
                                    <button class="action-btn btn-edit" onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['fullname']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo $user['role']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add New User</h2>
            <button class="close-modal" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="volunteer">Volunteer</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" name="add_user" class="btn btn-success">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit User</h2>
            <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" id="edit_fullname" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="edit_email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="edit_username" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" id="edit_role" class="form-select" required>
                    <option value="volunteer">Volunteer</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="edit_user" class="btn btn-success">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Open add user modal
    function openAddModal() {
        document.getElementById('addModal').style.display = 'flex';
    }
    
    // Open edit user modal
    function openEditModal(id, fullname, email, username, role) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_fullname').value = fullname;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_role').value = role;
        document.getElementById('editModal').style.display = 'flex';
    }
    
    // Close modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    };
</script>
</body>
</html>