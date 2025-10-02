<?php
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in and approved
if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
  header("Location: login.php");
  exit();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, profile_picture, student_id, batch, bio FROM users WHERE id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch recent posts
$posts_query = "SELECT p.content, p.timestamp, u.full_name FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.timestamp DESC LIMIT 3";
$posts_result = $conn->query($posts_query);

// Fetch recent notifications
$notif_query = "SELECT content, timestamp FROM notifications WHERE user_id = ? AND seen = FALSE ORDER BY timestamp DESC LIMIT 3";
$stmt = $conn->prepare($notif_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notif_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect - Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>
  <!-- Top Bar -->
  <div class="top-bar flex justify-end items-center p-4 bg-[#1a1a1a] border-b-2 border-[#FFD700]">
    <div class="flex items-center gap-4">
      <input type="text" placeholder="Search for peers, events, or groups..." class="custom-input px-4 py-2 rounded-full w-64">
      <div class="social-icons flex gap-3">
        <a href="#"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
        <a href="#"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
        <a href="#"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
        <a href="#"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="header py-4 px-6">
    <div class="container flex justify-between items-center">
      <div class="logo text-4xl font-bold text-[#FFD700]">Campus Connect</div>
      <div class="nav-hamburger flex-col justify-between w-8 h-5 cursor-pointer md:hidden z-[1000]">
        <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
        <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
        <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
      </div>
      <nav class="nav flex gap-6">
        <a href="dashboard.php" class="text-lg text-white hover:text-[#FFD700] transition">Dashboard</a>
        <a href="profile.php" class="text-lg text-white hover:text-[#FFD700] transition">Profile</a>
        <a href="messages.php" class="text-lg text-white hover:text-[#FFD700] transition">Messages</a>
        <a href="events.php" class="text-lg text-white hover:text-[#FFD700] transition">Events</a>
        <a href="groups.php" class="text-lg text-white hover:text-[#FFD700] transition">Groups</a>
        <a href="logout.php" class="text-lg text-white hover:text-[#FFD700] transition">Logout</a>
      </nav>
    </div>
  </header>

  <!-- Welcome Banner -->
  <section class="welcome-banner container">
    <h1 class="text-5xl font-bold mb-3">Welcome, <span id="typedWelcome" data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"></span><span class="cursor">|</span>!</h1>
    <p class="text-xl text-gray-800">Join your AAU community and spark new connections!</p>
    <button class="button-gold mt-6">Get Started</button>
  </section>

  <!-- Dashboard Section -->
  <section class="container py-12">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <!-- Left Sidebar: Profile -->
      <aside class="sidebar-left col-span-3">
        <button id="toggleLeftSidebar" class="md:hidden mb-4 bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Toggle Sidebar</button>
        <div id="leftSidebarContent" class="space-y-8">
          <div class="text-center">
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?: '/assets/user_profile.jpg'); ?>" alt="User Profile" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-[#FFD700] shadow-lg">
            <h2 class="text-2xl font-bold text-[#FFD700]"><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p class="text-gray-400">Student ID: <?php echo htmlspecialchars($user['student_id']); ?> | Batch <?php echo htmlspecialchars($user['batch']); ?></p>
            <p class="text-gray-400 mt-2"><?php echo htmlspecialchars($user['bio'] ?: 'No bio available.'); ?></p>
<a href="profile.php" class="button-gold mt-4 w-full text-center block">Edit Profile</a>
          </div>
          <div>
            <h3 class="text-xl font-bold text-[#FFD700] mb-4">Recent Notifications</h3>
            <div class="space-y-3">
              <?php if ($notif_result->num_rows > 0): ?>
                <?php while ($notif = $notif_result->fetch_assoc()): ?>
                  <div class="text-sm text-gray-400 flex items-center gap-2">
                    <i class="fas fa-bell"></i>
                    <p><?php echo htmlspecialchars($notif['content']); ?> <span class="text-xs ml-auto"><?php echo date('g:i a', strtotime($notif['timestamp'])); ?></span></p>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <p class="text-sm text-gray-400">No new notifications.</p>
              <?php endif; ?>
              <a href="#notifications" class="block text-[#FFD700] hover:underline text-sm">View All Notifications</a>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="col-span-6">
        <div class="mb-8">
          <textarea class="custom-textarea w-full h-24" placeholder="What's on your mind, <?php echo htmlspecialchars($user['full_name']); ?>?"></textarea>
          <button onclick="postUpdate()" class="button-gold mt-3">Post Update</button>
        </div>

        <h2 class="text-2xl font-bold text-[#FFD700] mb-6">Recent Posts</h2>
        <div class="space-y-6">
          <?php if ($posts_result->num_rows > 0): ?>
            <?php while ($post = $posts_result->fetch_assoc()): ?>
              <div class="feed-card p-6 card-animation">
                <div class="flex items-center gap-4 mb-3">
                  <img src="/assets/user_profile.jpg" alt="User" class="w-12 h-12 rounded-full">
                  <div>
                    <p class="font-bold text-[#FFD700]"><?php echo htmlspecialchars($post['full_name']); ?></p>
                    <p class="text-sm text-gray-400"><?php echo date('F j, Y, g:i a', strtotime($post['timestamp'])); ?></p>
                  </div>
                </div>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <div class="flex gap-4 mt-3">
                  <button class="text-[#FFD700] hover:underline">Like (15)</button>
                  <button class="text-[#FFD700] hover:underline">Comment (7)</button>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="text-gray-400">No recent posts available.</p>
          <?php endif; ?>
        </div>

        <h2 class="text-2xl font-bold text-[#FFD700] mt-8 mb-6">Recent Events</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="event-card p-6 card-animation">
            <img src="/assets/event1.jpg" alt="Event" class="w-full h-40 object-cover rounded-lg mb-3">
            <h3 class="font-bold text-[#FFD700] text-lg">Hackathon 2025</h3>
            <p class="text-sm text-gray-400">September 15, 2025 | Main Hall</p>
            <button class="button-gold mt-3">RSVP</button>
          </div>
          <div class="event-card p-6 card-animation">
            <img src="/assets/event2.jpg" alt="Event" class="w-full h-40 object-cover rounded-lg mb-3">
            <h3 class="font-bold text-[#FFD700] text-lg">Career Fair</h3>
            <p class="text-sm text-gray-400">October 20, 2025 | Auditorium</p>
            <button class="button-gold mt-3">RSVP</button>
          </div>
          <div class="event-card p-6 card-animation">
            <img src="/assets/event4.jpg" alt="Event" class="w-full h-40 object-cover rounded-lg mb-3">
            <h3 class="font-bold text-[#FFD700] text-lg">AI Workshop</h3>
            <p class="text-sm text-gray-400">September 5, 2025 | Tech Lab</p>
            <button class="button-gold mt-3">RSVP</button>
          </div>
        </div>

        <h2 class="text-2xl font-bold text-[#FFD700] mt-8 mb-6">Recent Groups</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="group-card p-6 card-animation">
            <img src="/assets/group4.jpg" alt="Group" class="w-full h-40 object-cover rounded-lg mb-3">
            <h3 class="font-bold text-[#FFD700] text-lg">Coding Club</h3>
            <p class="text-sm text-gray-400">Members: 150 | Learn to code together!</p>
            <button class="button-gold mt-3">View Group</button>
          </div>
          <div class="group-card p-6 card-animation">
            <img src="/assets/studygroups.jpg" alt="Group" class="w-full h-40 object-cover rounded-lg mb-3">
            <h3 class="font-bold text-[#FFD700] text-lg">Debate Team</h3>
            <p class="text-sm text-gray-400">Members: 80 | Debate and discuss topics</p>
            <button class="button-gold mt-3">View Group</button>
          </div>
          <div class="group-card p-6 card-animation">
            <img src="/assets/group3.jpeg" alt="Group" class="w-full h-40 object-cover rounded-lg mb-3">
            <h3 class="font-bold text-[#FFD700] text-lg">Photography Club</h3>
            <p class="text-sm text-gray-400">Members: 50 | Photo contest announced</p>
            <button class="button-gold mt-3">View Group</button>
          </div>
        </div>
      </main>

      <!-- Right Sidebar: Messages and Connections -->
      <aside class="sidebar-right col-span-3">
        <button id="toggleRightSidebar" class="md:hidden mb-4 bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Toggle Sidebar</button>
        <div id="rightSidebarContent" class="space-y-8">
          <div>
            <h2 class="text-2xl font-bold text-[#FFD700] mb-6">Messages</h2>
            <div class="space-y-4">
              <div class="border-b border-gray-700 pb-3 cursor-pointer" onclick="openMessageModal('Tamirat Negus')">
                <p class="font-bold text-[#FFD700]">Tamirat Negus</p>
                <p class="text-sm text-gray-400">Hey, want to join the study group?</p>
                <p class="text-xs text-gray-500">1h ago</p>
              </div>
              <div class="border-b border-gray-700 pb-3 cursor-pointer" onclick="openMessageModal('Kirubel Abera')">
                <p class="font-bold text-[#FFD700]">Kirubel Abera</p>
                <p class="text-sm text-gray-400">Check out this event!</p>
                <p class="text-xs text-gray-500">3h ago</p>
              </div>
              <div class="border-b border-gray-700 pb-3 cursor-pointer" onclick="openMessageModal('Kidist Alemu')">
                <p class="font-bold text-[#FFD700]">Kidist Alemu</p>
                <p class="text-sm text-gray-400">Got any tips for the quiz?</p>
                <p class="text-xs text-gray-500">5h ago</p>
              </div>
<a href="messages.php" class="button-gold w-full text-center block">View All Messages</a>
            </div>
          </div>
          <div>
            <h2 class="text-2xl font-bold text-[#FFD700] mt-8 mb-6">Recent Connections</h2>
            <div class="space-y-4">
              <div class="flex items-center gap-4">
                <img src="/assets/user_profile.jpg" alt="User" class="w-12 h-12 rounded-full">
                <div>
                  <p class="font-bold text-[#FFD700]">Kidist Alemu</p>
                  <p class="text-sm text-gray-400">Engineering | Batch 2024</p>
                </div>
                <button class="ml-auto text-[#FFD700] hover:underline"><i class="fas fa-user-plus"></i> Connect</button>
              </div>
              <div class="flex items-center gap-4">
                <img src="/assets/user_profile.jpg" alt="User" class="w-12 h-12 rounded-full">
                <div>
                  <p class="font-bold text-[#FFD700]">Kirubel Abera</p>
                  <p class="text-sm text-gray-400">Sports Club Member</p>
                </div>
                <button class="ml-auto text-[#FFD700] hover:underline"><i class="fas fa-user-plus"></i> Connect</button>
              </div>
              <div class="flex items-center gap-4">
                <img src="/assets/user_profile.jpg" alt="User" class="w-12 h-12 rounded-full">
                <div>
                  <p class="font-bold text-[#FFD700]">Sarah Tariq</p>
                  <p class="text-sm text-gray-400">Photography Club Member</p>
                </div>
                <button class="ml-auto text-[#FFD700] hover:underline"><i class="fas fa-user-plus"></i> Connect</button>
              </div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <!-- Message Modal -->
  <div id="messageModal" class="modal hidden">
    <div class="bg-[#1a1a1a] p-6 rounded-lg shadow-xl w-full max-w-md border border-[#FFD700]">
      <h2 class="text-xl font-bold text-[#FFD700] mb-4">Message <span id="messageRecipient"></span></h2>
      <textarea class="custom-textarea w-full h-32" placeholder="Type your message..."></textarea>
      <div class="flex gap-4 mt-4">
        <button onclick="closeMessageModal()" class="w-full bg-gray-800 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition">Cancel</button>
        <button onclick="sendMessage()" class="w-full button-gold">Send</button>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-[#1a1a1a] py-12 px-6 border-t-2 border-[#FFD700]">
    <div class="container grid grid-cols-1 md:grid-cols-3 gap-8">
      <div>
        <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Get in Touch</h3>
        <p class="mb-3">Email: <a href="mailto:support@campusconnect.com" class="text-white hover:text-[#FFD700] transition">support@campusconnect.com</a></p>
        <p class="mb-3">Phone: <a href="tel:+1234567890" class="text-white hover:text-[#FFD700] transition">+251********</a></p>
        <input type="text" placeholder="Your Name" class="custom-input w-full mb-3">
        <input type="email" placeholder="Your Email" class="custom-input w-full mb-3">
        <textarea placeholder="Your Message" class="custom-textarea w-full h-24 mb-3"></textarea>
        <button class="button-gold w-full">Send Message</button>
      </div>
      <div class="text-center">
        <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Explore</h3>
        <ul class="space-y-3">
          <li><a href="index.php" class="text-white hover:text-[#FFD700] transition">Home</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">About</a></li>
          <li><a href="events.php" class="text-white hover:text-[#FFD700] transition">Events</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">Resources</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">Contact</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Stay Connected</h3>
        <p class="mb-3">Join our newsletter for the latest updates!</p>
        <input type="email" placeholder="Enter your email" class="custom-input w-full mb-3">
        <button class="button-gold w-full">Subscribe</button>
      </div>
    </div>
    <div class="text-center mt-12 pt-6 border-t border-[#FFD700]">
      <div class="social-icons flex justify-center gap-4 mb-3">
        <a href="#"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
        <a href="#"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
        <a href="#"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
        <a href="#"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
      </div>
      <p class="text-[#FFD700] text-lg">Campus Connect: Building Bridges, Igniting Futures</p>
    </div>
  </footer>

  <script src="dashboard.js"></script>
</body>
</html>