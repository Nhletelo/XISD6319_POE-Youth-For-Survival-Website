<?php
session_start();
require_once 'config/db_conn.php';



$volunteer_id = $_SESSION['volunteer_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: volunteers_inbox.php");
    exit();
}

$message_id = $_GET['id'];

try {
    // Get the message and mark it as read
    $stmt = $pdo->prepare("
        SELECT m.*, u.username AS sender_name 
        FROM messages m 
        LEFT JOIN users u ON m.sender_id = u.id 
        WHERE m.id = ? AND m.receiver_id = ?
    ");
    $stmt->execute([$message_id, $volunteer_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        header("Location: volunteers_inbox.php");
        exit();
    }

    // Mark as read if not already read
    if (!$message['is_read']) {
        $update_stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $update_stmt->execute([$message_id]);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Message</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .message-container { max-width: 800px; margin: 0 auto; }
        .message-header { background: #f8f9fa; padding: 20px; border-radius: 5px; }
        .message-body { padding: 20px; background: white; border: 1px solid #ddd; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 4px; }
        .btn-primary { background: #007bff; color: white; }
    </style>
</head>
<body>
    <div class="message-container">
        <a href="volunteers_inbox.php" class="btn btn-primary">← Back to Inbox</a>
        
        <div class="message-header">
            <h1><?php echo htmlspecialchars($message['subject']); ?></h1>
            <p><strong>From:</strong> <?php echo htmlspecialchars($message['sender_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($message['sent_at'])); ?></p>
        </div>
        
        <div class="message-body">
            <?php echo nl2br(htmlspecialchars($message['body'])); ?>
        </div>
    </div>
</body>
</html>