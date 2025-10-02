<?php
session_start();
require_once 'config.php';

// Redirect if not logged in or status not approved
if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
    header('Location: login.php');
    exit;
}

// Fetch user data
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('An error occurred. Please try again later.');
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
if (!$stmt) {
    error_log('Prepare failed for user query: ' . $conn->error);
    die('An error occurred. Please try again later.');
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_user) {
    error_log('User not found for ID: ' . $user_id);
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch contacts with latest message and unread count
$query = "
    SELECT u.id, u.full_name, u.profile_picture, u.email,
           (SELECT message FROM messages m 
            WHERE (m.sender_id = u.id AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = u.id) 
            ORDER BY m.timestamp DESC LIMIT 1) AS last_message,
           (SELECT COUNT(*) FROM messages m 
            WHERE m.receiver_id = ? AND m.sender_id = u.id AND m.seen = FALSE) AS unread_count
    FROM users u
    LEFT JOIN connections c ON (u.id = c.user_id2 AND c.user_id1 = ? AND c.status = 'accepted') 
                            OR (u.id = c.user_id1 AND c.user_id2 = ? AND c.status = 'accepted')
    WHERE u.id != ? AND u.status = 'approved' 
    AND (c.user_id1 IS NOT NULL OR c.user_id2 IS NOT NULL)
    ORDER BY u.full_name ASC
";
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log('Prepare failed for contacts query: ' . $conn->error . ' | Query: ' . $query);
    die('An error occurred while loading contacts: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('iiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
if (!$stmt->execute()) {
    error_log('Execute failed for contacts query: ' . $stmt->error);
    $stmt->close();
    die('An error occurred while executing contacts query: ' . htmlspecialchars($stmt->error));
}
$contacts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect - Messages</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="messages.css">
</head>
<body class="bg-black text-white">
  <!-- Top Social Media + Search -->
  <div class="top-bar flex justify-end items-center p-4 bg-black border-b-2 border-[#FFD700]">
    <div class="social-icons flex gap-4">
      <a href="#"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
      <a href="#"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
      <a href="#"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
      <a href="#"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
    </div>
    <div class="ml-4">
      <input type="text" id="searchInput" placeholder="Search messages..." class="custom-input px-4 py-2 rounded-full border-none outline-none bg-gray-800 text-white focus:ring-2 focus:ring-[#FFD700]">
    </div>
  </div>

  <!-- Header with logo + navigation -->
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

  <!-- Welcome Banner -->
  <section class="welcome-banner py-8 px-4 text-center bg-black text-white">
    <h1 class="text-4xl font-bold mb-2">Stay Connected</h1>
    <p class="text-lg text-gray-200">Chat with peers and build your network!</p>
    <button onclick="openNewMessageModal()" class="mt-4 bg-black text-[#FFD700] py-2 px-6 rounded-md hover:bg-[#FFD700] hover:text-black transition border border-[#FFD700]">New Message</button>
  </section>

  <!-- Messages Section -->
  <section class="py-8 px-4 messages-section">
    <div class="w-full max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Sidebar: Contact List -->
      <aside class="sidebar col-span-1 p-6 rounded-lg">
        <h2 class="text-xl font-bold text-[#FFD700] mb-4">Contacts</h2>
        <input type="text" id="contactSearch" placeholder="Search contacts..." class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800 mb-4">
        <button id="toggleContacts" class="md:hidden mb-4 bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Toggle Contacts</button>
        <div id="contactsList" class="space-y-4">
          <?php foreach ($contacts as $contact): ?>
            <div class="contact-card cursor-pointer transition relative" data-contact-id="<?php echo $contact['id']; ?>">
              <div class="flex items-center gap-4 contact-info" onclick="selectContact(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['full_name']); ?>')">
                <img src="<?php echo htmlspecialchars($contact['profile_picture'] ?: '/assets/user_profile.jpg'); ?>" alt="Contact" class="w-10 h-10 rounded-full object-cover">
                <div class="flex-1 min-w-0">
                  <p class="font-bold text-[#FFD700]"><?php echo htmlspecialchars($contact['full_name']); ?></p>
                  <p class="text-sm text-gray-400 truncate"><?php echo htmlspecialchars($contact['last_message'] ?: 'No messages yet'); ?></p>
                </div>
                <?php if ($contact['unread_count'] > 0): ?>
                  <span class="unread-contact-badge inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-black bg-[#FFD700] rounded-full absolute top-2 right-8"><?php echo $contact['unread_count']; ?></span>
                <?php endif; ?>
              </div>
              <button class="remove-contact-btn" onclick="removeContact(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['full_name']); ?>')"><i class="fas fa-times"></i></button>
            </div>
          <?php endforeach; ?>
          <?php if (empty($contacts)): ?>
            <p class="text-gray-400">No contacts yet. Add a contact or start a new message!</p>
          <?php endif; ?>
        </div>
      </aside>

      <!-- Main Content: Chat Window -->
      <main class="col-span-3 p-6 bg-black rounded-lg">
        <h2 id="chatHeader" class="text-xl font-bold text-[#FFD700] mb-4">Select a contact to chat</h2>
        <div id="chatWindow" class="h-96 overflow-y-auto bg-gray-800 rounded-md p-4 mb-4">
          <div id="messages" class="space-y-4"></div>
          <div id="typingIndicator" class="typing-indicator hidden">
            <span>.</span><span>.</span><span>.</span>
          </div>
        </div>
        <div class="flex gap-2">
          <input id="messageInput" type="text" placeholder="Type a message..." class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800">
          <button onclick="sendMessage()" class="bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Send</button>
        </div>
      </main>
    </div>
  </section>

  <!-- New Message Modal -->
  <div id="newMessageModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-black p-6 rounded-lg shadow-lg w-full max-w-md border border-[#FFD700]">
      <h2 class="text-lg font-bold text-[#FFD700] mb-4">New Message / Add Contact</h2>
      <div class="space-y-4">
        <input type="text" id="newMessageSearch" placeholder="Search by name or email..." class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800">
        <div id="newMessageResults" class="space-y-2 max-h-40 overflow-y-auto"></div>
        <textarea id="newMessageText" placeholder="Type your message (optional)..." class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800" rows="4"></textarea>
        <div class="flex gap-4">
          <button onclick="addContact()" class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Add Contact</button>
          <button onclick="sendNewMessage()" class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Send Message</button>
          <button onclick="closeNewMessageModal()" class="w-full bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-700 transition">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer Section -->
  <footer class="bg-black py-8 px-4 text-white border-t-2 border-[#FFD700]">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-6xl mx-auto">
      <div class="text-center md:text-left">
        <h3 class="text-xl font-bold text-[#FFD700] mb-2">Get in Touch</h3>
        <p class="mb-2">Email: <a href="mailto:support@campusconnect.com" class="text-white hover:text-[#FFD700] transition">support@campusconnect.com</a></p>
        <p class="mb-2">Phone: <a href="tel:+1234567890" class="text-white hover:text-[#FFD700] transition">+251*******</a></p>
        <div>
          <input type="text" placeholder="Your Name" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white">
          <input type="email" placeholder="Your Email" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white">
          <textarea placeholder="Your Message" class="w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white h-20 resize-y"></textarea>
          <button class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Send Message</button>
        </div>
      </div>
      <div class="text-center">
        <h3 class="text-xl font-bold text-[#FFD700] mb-2">Explore</h3>
        <ul class="space-y-2">
          <li><a href="index.php" class="text-white hover:text-[#FFD700] transition">Home</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">About</a></li>
          <li><a href="events.php" class="text-white hover:text-[#FFD700] transition">Events</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">Resources</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">Contact</a></li>
        </ul>
      </div>
      <div class="text-center md:text-left">
        <h3 class="text-xl font-bold text-[#FFD700] mb-2">Stay Connected</h3>
        <p class="mb-2">Join our newsletter for the latest updates!</p>
        <input type="email" placeholder="Enter your email" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white">
        <button class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Subscribe</button>
      </div>
    </div>
    <div class="text-center mt-8 pt-4 border-t border-[#FFD700]">
      <div class="social-icons flex justify-center gap-4 mb-2">
        <a href="#"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
        <a href="#"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
        <a href="#"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
        <a href="#"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
      </div>
      <p class="text-[#FFD700] text-lg">Campus Connect: Building Bridges, Igniting Futures</p>
    </div>
  </footer>

  <script>
    const userId = <?php echo json_encode($user_id); ?>;
    document.addEventListener('DOMContentLoaded', () => {
      updateUnreadCountOnLoad();
    });
  </script>
  <script src="messages.js"></script>
</body>
</html>