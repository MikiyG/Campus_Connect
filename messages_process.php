<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Get current user's name for notifications
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$action = $_POST['action'] ?? '';

if ($action === 'remove_contact') {
    $contact_id = (int)($_POST['contact_id'] ?? 0);
    if ($contact_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM connections WHERE ((user_id1 = ? AND user_id2 = ?) OR (user_id1 = ? AND user_id2 = ?)) AND status = 'accepted'");
    if (!$stmt) {
        error_log('Prepare failed for remove contact: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit;
    }
    $stmt->bind_param('iiii', $user_id, $contact_id, $contact_id, $user_id);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    if ($affected_rows > 0) {
        // Add notification for removed contact
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content) VALUES (?, 'connection_request', ?)");
        if ($stmt) {
            $notification_content = "Your connection with " . $current_user['full_name'] . " has been removed.";
            $stmt->bind_param('is', $contact_id, $notification_content);
            if (!$stmt->execute()) {
                error_log('Failed to insert notification: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log('Prepare failed for notification: ' . $conn->error);
        }
        echo json_encode(['success' => true, 'message' => 'Contact removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No connection found to remove']);
    }
} elseif ($action === 'add_contact') {
    $contact_email = trim($_POST['email'] ?? '');
    if (empty($contact_email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND status = 'approved' AND id != ?");
    if (!$stmt) {
        error_log('Prepare failed for add contact: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit;
    }
    $stmt->bind_param('si', $contact_email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contact = $result->fetch_assoc();
    $stmt->close();
    if (!$contact) {
        echo json_encode(['success' => false, 'message' => 'User not found or not approved']);
        exit;
    }
    $contact_id = $contact['id'];
    $stmt = $conn->prepare("INSERT IGNORE INTO connections (user_id1, user_id2, status) VALUES (?, ?, 'accepted')");
    if (!$stmt) {
        error_log('Prepare failed for insert connection: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit;
    }
    $stmt->bind_param('ii', $user_id, $contact_id);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    if ($affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Contact added successfully', 'user_id' => $contact_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Connection already exists']);
    }
} elseif ($action === 'send_message') {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if ($receiver_id <= 0 || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Invalid receiver or message']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log('Prepare failed for send message: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit;
    }
    $stmt->bind_param('iis', $user_id, $receiver_id, $message);
    $stmt->execute();
    $stmt->close();
    // Add notification for new message
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content) VALUES (?, 'message', ?)");
    if ($stmt) {
        $notification_content = "New message from " . $current_user['full_name'];
        $stmt->bind_param('is', $receiver_id, $notification_content);
        if (!$stmt->execute()) {
            error_log('Failed to insert message notification: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log('Prepare failed for message notification: ' . $conn->error);
    }
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} elseif ($action === 'fetch_messages') {
    $contact_id = (int)($_POST['contact_id'] ?? 0);
    if ($contact_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
        exit;
    }
    $stmt = $conn->prepare("
        SELECT id, sender_id, receiver_id, message, timestamp, seen 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
        ORDER BY timestamp ASC
    ");
    if (!$stmt) {
        error_log('Prepare failed for fetch messages: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit;
    }
    $stmt->bind_param('iiii', $user_id, $contact_id, $contact_id, $user_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // Mark messages as seen
    $stmt = $conn->prepare("UPDATE messages SET seen = TRUE WHERE sender_id = ? AND receiver_id = ? AND seen = FALSE");
    if ($stmt) {
        $stmt->bind_param('ii', $contact_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log('Prepare failed for update seen: ' . $conn->error);
    }
    echo json_encode(['success' => true, 'messages' => $messages]);
} elseif ($action === 'update_unread_count') {
    $contact_id = (int)($_POST['contact_id'] ?? 0);
    if ($contact_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
        exit;
    }
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT sender_id) as unread_conversations FROM messages WHERE receiver_id = ? AND seen = FALSE");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_conversations = $result->fetch_assoc()['unread_conversations'] ?? 0;
    $stmt->close();
    echo json_encode(['success' => true, 'unread_conversations' => $unread_conversations]);
} elseif ($action === 'search_users') {
    $search = '%' . trim($_POST['search'] ?? '') . '%';
    $stmt = $conn->prepare("
        SELECT id, full_name, email, profile_picture 
        FROM users 
        WHERE (LOWER(full_name) LIKE LOWER(?) OR LOWER(email) LIKE LOWER(?)) 
        AND status = 'approved' AND id != ?
    ");
    if (!$stmt) {
        error_log('Prepare failed for search users: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit;
    }
    $stmt->bind_param('ssi', $search, $search, $user_id);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode(['success' => true, 'users' => $users]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>