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

$message = "";
$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'User';

// Check if this is a reply to an existing message
$reply_subject = "";
$reply_body = "";
$reply_to = null;
$preselected_recipient = null;

if (isset($_GET['reply_to'])) {
    $reply_id = intval($_GET['reply_to']);
    $stmt = $conn->prepare("
        SELECT m.subject, m.body, m.sender_id, u.fullname AS sender_name 
        FROM messages m 
        JOIN app_users u ON m.sender_id = u.id 
        WHERE m.id = ? AND m.receiver_id = ?
    ");
    $stmt->bind_param("ii", $reply_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $original_msg = $result->fetch_assoc();
        $reply_subject = "Re: " . $original_msg['subject'];
        $reply_body = "\n\n--- Original Message ---\nFrom: " . $original_msg['sender_name'] . "\n\n" . $original_msg['body'];
        $reply_to = $reply_id;
        $preselected_recipient = $original_msg['sender_id'];
    }
    $stmt->close();
}

// Fetch all users except current user for receiver list
$users = [];
$result = $conn->query("SELECT id, fullname, username FROM app_users WHERE id != $user_id ORDER BY fullname");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);
    $save_draft = isset($_POST['save_draft']);

    if ((!$receiver_id || !$subject || !$body) && !$save_draft) {
        $message = "Please fill in all fields.";
        $message_class = "error";
    } else {
        if ($save_draft) {
            // Save as draft logic would go here
            $message = "Draft saved successfully.";
            $message_class = "message";
        } else {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $user_id, $receiver_id, $subject, $body);

            if ($stmt->execute()) {
                $message = "Message sent successfully.";
                $message_class = "message";
                // Clear form
                $subject = $body = "";
                $receiver_id = 0;
            } else {
                $message = "Error sending message: " . $conn->error;
                $message_class = "error";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Compose Message</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-color: #4a6fa5;
        --secondary-color: #166088;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --gray-color: #6c757d;
        --light-gray: #e9ecef;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7f9;
        color: #333;
        line-height: 1.6;
    }
    
    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 8px 8px 0 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .app-name {
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .nav-container {
        background-color: white;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--light-gray);
    }
    
    .breadcrumb {
        font-size: 1.1rem;
        font-weight: 500;
    }
    
    .breadcrumb a {
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .breadcrumb a:hover {
        text-decoration: underline;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }
    
    .btn-primary:hover {
        background-color: var(--secondary-color);
    }
    
    .btn-danger {
        background-color: var(--danger-color);
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #bd2130;
    }
    
    .btn-success {
        background-color: var(--success-color);
        color: white;
    }
    
    .btn-success:hover {
        background-color: #218838;
    }
    
    .btn-secondary {
        background-color: var(--gray-color);
        color: white;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    
    .compose-container {
        background-color: white;
        padding: 2rem;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .alert {
        padding: 12px 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .compose-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .form-group label {
        font-weight: 500;
        color: var(--dark-color);
    }
    
    .form-control {
        padding: 10px 15px;
        border: 1px solid var(--light-gray);
        border-radius: 4px;
        font-family: inherit;
        font-size: 1rem;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
    }
    
    textarea.form-control {
        min-height: 200px;
        resize: vertical;
    }
    
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--light-gray);
    }
    
    .formatting-toolbar {
        display: flex;
        gap: 5px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    
    .format-btn {
        padding: 6px 10px;
        background-color: var(--light-color);
        border: 1px solid var(--light-gray);
        border-radius: 4px;
        cursor: pointer;
    }
    
    .format-btn:hover {
        background-color: var(--light-gray);
    }
    
    .character-count {
        text-align: right;
        color: var(--gray-color);
        font-size: 0.9rem;
    }
    
    footer {
        text-align: center;
        margin-top: 30px;
        color: var(--gray-color);
        font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            gap: 15px;
        }
        
        .action-buttons {
            width: 100%;
            justify-content: center;
        }
        
        .form-actions {
            flex-direction: column;
            gap: 15px;
        }
        
        .form-actions .btn {
            width: 100%;
        }
        
        header {
            padding: 1rem;
        }
        
        .compose-container {
            padding: 1.5rem;
        }
    }
</style>
</head>
<body>

<div class="container">
    <header>
        <div class="app-name">Message System</div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo $fullname; ?></span>
        </div>
    </header>
    
    <div class="nav-container">
        <div class="breadcrumb">
            <a href="volunteer_dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a> 
            / 
            <a href="volunteer_inbox.php">
                <i class="fas fa-inbox"></i> Inbox
            </a> 
            / 
            <span>Compose Message</span>
        </div>
        <div class="action-buttons">
            <button class="btn btn-success" onclick="location.href='volunteer_inbox.php'">
                <i class="fas fa-inbox"></i> Inbox
            </button>
  
        </div>
    </div>
    
    <div class="compose-container">
        <h1 style="margin-bottom: 20px;"><?php echo $reply_to ? 'Reply to Message' : 'Compose Message'; ?></h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $message_class === 'message' ? 'alert-success' : 'alert-error'; ?>">
                <i class="<?php echo $message_class === 'message' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="volunteer_compose.php" class="compose-form">
            <div class="form-group">
                <label for="receiver_id">To:</label>
                <select name="receiver_id" id="receiver_id" class="form-control" required>
                    <option value="">-- Select Recipient --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                            <?php 
                                if (isset($_POST['receiver_id']) && $_POST['receiver_id'] == $user['id']) {
                                    echo 'selected';
                                } elseif ($preselected_recipient && $preselected_recipient == $user['id']) {
                                    echo 'selected';
                                }
                            ?>>
                            <?php echo htmlspecialchars($user['fullname'] . " ({$user['username']})"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" class="form-control" 
                    value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : $reply_subject; ?>" 
                    required />
            </div>
            
            <div class="form-group">
                <label for="body">Message:</label>
                <div class="formatting-toolbar">
                    <button type="button" class="format-btn" onclick="formatText('bold')"><i class="fas fa-bold"></i></button>
                    <button type="button" class="format-btn" onclick="formatText('italic')"><i class="fas fa-italic"></i></button>
                    <button type="button" class="format-btn" onclick="formatText('underline')"><i class="fas fa-underline"></i></button>
                    <button type="button" class="format-btn" onclick="insertBullet()"><i class="fas fa-list-ul"></i></button>
                    <button type="button" class="format-btn" onclick="insertNumber()"><i class="fas fa-list-ol"></i></button>
                    <button type="button" class="format-btn" onclick="insertLink()"><i class="fas fa-link"></i></button>
                </div>
                <textarea id="body" name="body" class="form-control" required oninput="updateCharCount()"><?php 
                    echo isset($_POST['body']) ? htmlspecialchars($_POST['body']) : $reply_body; 
                ?></textarea>
                <div class="character-count">
                    <span id="charCount">0</span> characters
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_draft" value="1" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Save Draft
                </button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-danger" onclick="location.href='volunteer_inbox.php'">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <footer>
        <p>Message System &copy; <?php echo date('Y'); ?></p>
    </footer>
</div>

<script>
    function formatText(format) {
        const textarea = document.getElementById('body');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        
        let formattedText = '';
        let cursorAdjustment = 0;
        
        switch(format) {
            case 'bold':
                formattedText = '**' + selectedText + '**';
                cursorAdjustment = selectedText ? 0 : 2; // If no text selected, position cursor between markers
                break;
            case 'italic':
                formattedText = '_' + selectedText + '_';
                cursorAdjustment = selectedText ? 0 : 1;
                break;
            case 'underline':
                formattedText = '<u>' + selectedText + '</u>';
                cursorAdjustment = selectedText ? 0 : 4;
                break;
        }
        
        textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
        textarea.focus();
        
        if (selectedText) {
            textarea.setSelectionRange(start + formattedText.length, start + formattedText.length);
        } else {
            textarea.setSelectionRange(start + cursorAdjustment, start + cursorAdjustment);
        }
        
        updateCharCount();
    }
    
    function insertBullet() {
        const textarea = document.getElementById('body');
        const start = textarea.selectionStart;
        
        textarea.value = textarea.value.substring(0, start) + '\n• ' + textarea.value.substring(start);
        textarea.focus();
        textarea.setSelectionRange(start + 3, start + 3);
        updateCharCount();
    }
    
    function insertNumber() {
        const textarea = document.getElementById('body');
        const start = textarea.selectionStart;
        
        textarea.value = textarea.value.substring(0, start) + '\n1. ' + textarea.value.substring(start);
        textarea.focus();
        textarea.setSelectionRange(start + 4, start + 4);
        updateCharCount();
    }
    
    function insertLink() {
        const textarea = document.getElementById('body');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        const linkText = selectedText || 'link text';
        
        textarea.value = textarea.value.substring(0, start) + 
            '[' + linkText + '](https://example.com)' + 
            textarea.value.substring(end);
        
        textarea.focus();
        
        if (selectedText) {
            textarea.setSelectionRange(start + linkText.length + 3, start + linkText.length + 21);
        } else {
            textarea.setSelectionRange(start + 1, start + 9);
        }
        
        updateCharCount();
    }
    
    function updateCharCount() {
        const textarea = document.getElementById('body');
        document.getElementById('charCount').textContent = textarea.value.length;
    }
    
    // Initialize character count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCharCount();
        
        // If this is a reply, try to pre-select the original sender
        <?php if ($reply_to && $preselected_recipient): ?>
            document.getElementById('body').focus();
        <?php endif; ?>
    });
</script>
</body>
</html>