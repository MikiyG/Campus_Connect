<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('An error occurred. Please try again later.');
}

$user_id = (int)$_SESSION['user_id'];

// Fetch total unread message count from distinct senders
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT sender_id) as unread_conversations 
    FROM messages 
    WHERE receiver_id = ? AND seen = FALSE
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['unread_conversations'] ?? 0;
$stmt->close();

$conn->close();
?>

<div class="sticky top-0 z-10 flex justify-between items-center p-4 bg-black text-white border-b-2 border-[#FFD700] shadow-md">
    <div class="text-3xl font-bold text-[#FFD700] logo">Campus Connect</div>
    <div class="nav-hamburger flex-col justify-between w-8 h-5 cursor-pointer z-[1000]">
        <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
        <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
        <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
    </div>
    <div class="nav flex gap-4 items-center">
        <a href="dashboard.php" class="font-medium text-white hover:text-[#FFD700] transition">Dashboard</a>
        <a href="profile.php" class="font-medium text-white hover:text-[#FFD700] transition">Profile</a>
        <a href="messages.php" class="font-medium text-white hover:text-[#FFD700] transition relative" id="messagesNav">
            Messages
            <?php if ($unread_count > 0): ?>
                <span class="unread-badge inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-black bg-[#FFD700] rounded-full absolute -top-2 -right-2"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="events.php" class="font-medium text-white hover:text-[#FFD700] transition">Events</a>
        <a href="groups.php" class="font-medium text-white hover:text-[#FFD700] transition">Groups</a>
        <a href="logout.php" class="font-medium text-white hover:text-[#FFD700] transition">Logout</a>
    </div>
</div>

<script>
    const userId = <?php echo json_encode($user_id); ?>;
    document.addEventListener('DOMContentLoaded', () => {
        updateUnreadCountOnLoad();
    });
</script>