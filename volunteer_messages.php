<?php
session_start();
require_once 'config/db_conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'User';

// Check if this is a reply to an existing message
$reply_subject = "";
$reply_body = "";
$reply_to = null;

if (isset($_GET['reply_to'])) {
    $reply_id = intval($_GET['reply_to']);
    $stmt = $conn->prepare("
        SELECT m.subject, m.body, u.fullname AS sender_name 
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
<link rel="stylesheet" href="css/volunteer_messages.css">
   
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
            <a href="volunteer_dashboard.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-home"></i> Dashboard
            </a> 
            / 
            <a href="volunteer_inbox.php" style="color: var(--primary-color); text-decoration: none;">
                <i class="fas fa-inbox"></i> Inbox
            </a> 
            / 
            <span style="color: var(--dark-color);">Compose Message</span>
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
        
        <form method="post" action="compose.php" class="compose-form">
            <div class="form-group">
                <label for="receiver_id">To:</label>
                <select name="receiver_id" id="receiver_id" class="form-control" required>
                    <option value="">-- Select Recipient --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                            <?php echo (isset($_POST['receiver_id']) && $_POST['receiver_id'] == $user['id']) ? 'selected' : ''; ?>>
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
                </div>
                <textarea id="body" name="body" class="form-control" required oninput="updateCharCount()"><?php 
                    echo isset($_POST['body']) ? htmlspecialchars($_POST['body']) : $reply_body; 
                ?></textarea>
                <div class="character-count">
                    <span id="charCount">0</span> characters
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_draft" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Save Draft
                </button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-danger" onclick="location.href='volunteer_dashboard.php'">
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
        
        switch(format) {
            case 'bold':
                formattedText = '**' + selectedText + '**';
                break;
            case 'italic':
                formattedText = '_' + selectedText + '_';
                break;
            case 'underline':
                formattedText = '<u>' + selectedText + '</u>';
                break;
        }
        
        textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + formattedText.length, start + formattedText.length);
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
    
    function updateCharCount() {
        const textarea = document.getElementById('body');
        document.getElementById('charCount').textContent = textarea.value.length;
    }
    
    // Initialize character count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCharCount();
        
        // If this is a reply, try to pre-select the original sender
        <?php if ($reply_to): ?>
            const originalSenderId = <?php echo $reply_to; ?>;
            // This would need additional logic to determine the original sender's ID
            // For now, we'll just focus on the message body
            document.getElementById('body').focus();
        <?php endif; ?>
    });
</script>
</body>
</html>