<?php
session_start();
require_once 'config.php';

// Redirect unauthenticated users to login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
    header('Location: login.php');
    exit;
}

// Generate CSRF token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
$csrf_token = generateCsrfToken();

// Initialize database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    error_log("Connection failed: " . $db->connect_error);
    die("Connection failed: " . $db->connect_error);
}

// Fetch all groups
$query = "SELECT g.id, g.name, g.description, g.category, g.privacy, g.image, g.members_count, g.creator_id, u.full_name AS creator_name
          FROM groups g
          LEFT JOIN users u ON g.creator_id = u.id";
$groups_result = $db->query($query);
if (!$groups_result) {
    error_log("Groups query failed: " . $db->error);
    $groups_result = false;
}

// Check if user is a member of each group
$user_id = $_SESSION['user_id'];
$group_membership = [];
if ($groups_result && $groups_result->num_rows > 0) {
    while ($group = $groups_result->fetch_assoc()) {
        $group_id = $group['id'];
        $membership_query = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($membership_query);
        if ($stmt) {
            $stmt->bind_param("ii", $group_id, $user_id);
            $stmt->execute();
            $group['is_member'] = $stmt->get_result()->num_rows > 0;
            $group_membership[$group_id] = $group;
            $stmt->close();
        } else {
            error_log("Membership query preparation failed: " . $db->error);
        }
    }
}

// Fetch suggested groups (most members, limit 2)
$suggested_query = "SELECT id, name, members_count, description, image FROM groups ORDER BY members_count DESC LIMIT 2";
$suggested_result = $db->query($suggested_query);
if (!$suggested_result) {
    error_log("Suggested groups query failed: " . $db->error);
}

// Close database connection
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect - Groups</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="groups.css">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white" data-user-id="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
  <!-- Top Social Media + Search -->
  <div class="top-bar flex justify-end items-center p-4 bg-[#111] border-b-2 border-[#FFD700]">
    <div class="social-icons flex gap-4">
      <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
      <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
      <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
      <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
    </div>
    <div class="search-bar ml-4 flex items-center gap-2">
      <button class="search-toggle md:hidden text-[#FFD700]" aria-label="Toggle search"><i class="fas fa-search"></i></button>
      <input type="text" id="groupSearch" placeholder="Search for groups..." class="search-input px-4 py-2 rounded-full border-none outline-none bg-gray-800 text-white focus:ring-2 focus:ring-[#FFD700]">
    </div>
  </div>

  <!-- Header -->
  <header class="header flex justify-between items-center p-4 bg-[#111] border-b-2 border-[#FFD700] sticky top-0 z-[1000]">
    <div class="logo text-3xl font-bold text-[#FFD700]">Campus Connect</div>
    <div class="nav-hamburger flex flex-col justify-between w-8 h-5 cursor-pointer md:hidden z-[1001]">
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
    </div>
    <nav class="nav hidden md:flex gap-4">
      <a href="homepage.php" class="font-medium text-white hover:text-[#FFD700] transition" aria-label="Home">Home</a>
      <a href="dashboard.php" class="font-medium text-white hover:text-[#FFD700] transition" aria-label="Dashboard">Dashboard</a>
      <a href="profile.php" class="font-medium text-white hover:text-[#FFD700] transition" aria-label="Profile">Profile</a>
      <a href="messages.php" class="font-medium text-white hover:text-[#FFD700] transition" aria-label="Messages">Messages</a>
      <a href="events.php" class="font-medium text-white hover:text-[#FFD700] transition" aria-label="Events">Events</a>
      <a href="groups.php" class="font-medium text-white hover:text-[#FFD700] transition" aria-label="Groups">Groups</a>
      <a href="logout.php" class="font-medium text-white hover:text-[#FFD700] transition" aria-label="Logout">Logout</a>
    </nav>
  </header>

  <!-- Welcome Banner -->
  <section class="welcome-banner py-8 px-4 text-center bg-gradient-to-r from-[#FFD700] to-[#DAA520]">
    <h1 class="text-4xl font-bold mb-2 text-black">Discover Groups</h1>
    <p class="text-lg text-gray-800">Connect with peers in study groups, clubs, and more!</p>
    <button onclick="openCreateGroupModal()" class="mt-4 bg-black text-[#FFD700] py-2 px-6 rounded-md hover:bg-[#FFD700] hover:text-black transition border border-[#FFD700]" aria-label="Create New Group">Create New Group</button>
  </section>

  <!-- Groups Section -->
  <section class="groups-section py-8 px-4">
    <div class="w-full max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Sidebar: Filters and Suggested Groups -->
      <aside class="sidebar col-span-1 p-6 rounded-lg bg-[#111] border border-[#FFD700]">
        <h2 class="text-xl font-bold text-[#FFD700] mb-4">Filters</h2>
        <button id="toggleFilters" class="md:hidden mb-4 bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Toggle Filters">Toggle Filters</button>
        <div id="filtersContent" class="space-y-4 hidden md:block">
          <div>
            <label class="block text-sm text-gray-400 mb-1" for="filterCategory">Category</label>
            <select id="filterCategory" class="custom-select w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800 text-white">
              <option value="" selected>All Categories</option>
              <option value="study">Study Groups</option>
              <option value="sports">Sports</option>
              <option value="hobbies">Hobbies</option>
              <option value="clubs">Clubs</option>
              <option value="professional">Professional</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1" for="filterMembers">Member Count</label>
            <select id="filterMembers" class="custom-select w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800 text-white">
              <option value="" selected>All Sizes</option>
              <option value="small">Less than 50</option>
              <option value="medium">50-100</option>
              <option value="large">100+</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1" for="filterPrivacy">Privacy</label>
            <select id="filterPrivacy" class="custom-select w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800 text-white">
              <option value="" selected>All</option>
              <option value="public">Public</option>
              <option value="private">Private</option>
            </select>
          </div>
          <button onclick="applyFilters()" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Apply Filters">Apply Filters</button>
        </div>
        <h2 class="text-xl font-bold text-[#FFD700] mt-6 mb-4">Suggested Groups</h2>
        <div id="suggestedGroups" class="space-y-4">
          <?php
          if ($suggested_result && $suggested_result->num_rows > 0) {
              while ($group = $suggested_result->fetch_assoc()) {
                  echo '<div class="group-card p-4 rounded-md bg-[#222] border border-[#FFD700]">';
                  echo '<img src="' . htmlspecialchars($group['image'] ?: '/assets/default_group.jpg') . '" alt="' . htmlspecialchars($group['name']) . '" class="w-full h-32 object-cover rounded-md mb-2" loading="lazy">';
                  echo '<h3 class="font-bold text-[#FFD700]">' . htmlspecialchars($group['name']) . '</h3>';
                  echo '<p class="text-sm text-gray-400">Members: ' . $group['members_count'] . ' | ' . htmlspecialchars(substr($group['description'], 0, 50)) . '...</p>';
                  echo '<button onclick="joinGroup(' . $group['id'] . ')" class="mt-2 bg-[#FFD700] text-black py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Join ' . htmlspecialchars($group['name']) . '">Join</button>';
                  echo '</div>';
              }
          } else {
              echo '<p class="text-gray-400">No suggested groups available.</p>';
          }
          ?>
        </div>
      </aside>

      <!-- Main Content: Groups List -->
      <main class="col-span-3 p-6 bg-[#111] rounded-lg border border-[#FFD700]">
        <h2 class="text-xl font-bold text-[#FFD700] mb-4">Groups List</h2>
        <div id="groupsList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <?php
          if ($groups_result && $groups_result->num_rows > 0) {
              foreach ($group_membership as $group) {
                  $joinButtonText = $group['is_member'] ? 'Leave' : ($group['privacy'] === 'private' ? 'Private Group' : 'Join');
                  $joinFunction = $group['is_member'] ? 'leaveGroup' : ($group['privacy'] === 'private' ? '' : 'joinGroup');
                  $joinButtonDisabled = $group['privacy'] === 'private' && !$group['is_member'] ? 'disabled' : '';
                  echo '<div class="group-card p-4 rounded-md bg-[#222] border border-[#FFD700]" data-group-id="' . $group['id'] . '" data-creator-id="' . $group['creator_id'] . '" data-category="' . strtolower($group['category']) . '" data-privacy="' . strtolower($group['privacy']) . '" data-members="' . $group['members_count'] . '">';
                  echo '<img src="' . htmlspecialchars($group['image'] ?: '/assets/default_group.jpg') . '" alt="' . htmlspecialchars($group['name']) . '" class="w-full h-32 object-cover rounded-md mb-2" loading="lazy">';
                  echo '<h3 class="font-bold text-[#FFD700]">' . htmlspecialchars($group['name']) . '</h3>';
                  echo '<p class="text-sm text-gray-400">Members: ' . $group['members_count'] . ' | ' . htmlspecialchars(substr($group['description'], 0, 50)) . '...</p>';
                  echo '<button onclick="viewGroup(' . $group['id'] . ')" class="mt-2 bg-[#FFD700] text-black py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="View ' . htmlspecialchars($group['name']) . '">View</button>';
                  echo '<button ' . $joinButtonDisabled . ' onclick="' . $joinFunction . ($joinFunction ? '(' . $group['id'] . ')' : '') . '" class="mt-2 ml-2 bg-' . ($group['is_member'] ? 'gray-600' : ($group['privacy'] === 'private' ? 'gray-600' : '[#FFD700]')) . ' text-' . ($group['is_member'] ? 'white' : 'black') . ' py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="' . $joinButtonText . ' ' . htmlspecialchars($group['name']) . '">' . $joinButtonText . '</button>';
                  if ($group['is_member']) {
                      echo '<button onclick="openGroupChat(' . $group['id'] . ')" class="mt-2 ml-2 bg-[#FFD700] text-black py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Chat in ' . htmlspecialchars($group['name']) . '">Chat</button>';
                  }
                  if ($group['creator_id'] == $user_id) {
                      echo '<button onclick="removeGroup(' . $group['id'] . ')" class="mt-2 ml-2 remove-button py-1 px-3 rounded-md transition" aria-label="Remove ' . htmlspecialchars($group['name']) . '">Remove</button>';
                  }
                  echo '</div>';
              }
          } else {
              echo '<p class="text-gray-400">No groups found. Create one to get started!</p>';
          }
          ?>
        </div>
      </main>
    </div>
  </section>

  <!-- Create Group Modal -->
  <div id="createGroupModal" class="modal hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
    <div class="bg-[#111] p-6 rounded-lg shadow-lg w-full max-w-sm border border-[#FFD700]">
      <h2 class="text-lg font-bold text-[#FFD700] mb-4">Create New Group</h2>
      <form id="createGroupForm" action="groups_process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create_group">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
        <input type="text" name="name" placeholder="Group Name" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
        <textarea name="description" placeholder="Group Description" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" rows="3" required></textarea>
        <select name="category" class="custom-select w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
          <option value="" disabled selected>Select Category</option>
          <option value="study">Study Groups</option>
          <option value="sports">Sports</option>
          <option value="hobbies">Hobbies</option>
          <option value="clubs">Clubs</option>
          <option value="professional">Professional</option>
        </select>
        <select name="privacy" class="custom-select w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
          <option value="" disabled selected>Select Privacy</option>
          <option value="public">Public</option>
          <option value="private">Private</option>
        </select>
        <input type="file" name="image" accept="image/*" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white">
        <input type="hidden" name="image_path" value="/assets/default_group.jpg">
        <div class="flex gap-4">
          <button type="button" onclick="closeCreateGroupModal()" class="w-full bg-gray-800 text-white py-1 px-4 rounded-md hover:bg-gray-700 transition" aria-label="Cancel">Cancel</button>
          <button type="submit" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Create Group">Create</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Group Details Modal -->
  <div id="groupDetailsModal" class="modal hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
    <div class="bg-[#111] p-6 rounded-lg shadow-lg w-full max-w-md border border-[#FFD700]">
      <h2 id="groupDetailsTitle" class="text-lg font-bold text-[#FFD700] mb-4"></h2>
      <img id="groupDetailsImage" src="" alt="Group Image" class="w-full h-48 object-cover rounded-md mb-4">
      <p id="groupDetailsDescription" class="text-gray-400 mb-4"></p>
      <p id="groupDetailsCreator" class="text-sm text-gray-400 mb-2"></p>
      <p id="groupDetailsMembers" class="text-sm text-gray-400 mb-2"></p>
      <p id="groupDetailsCategory" class="text-sm text-gray-400 mb-2"></p>
      <p id="groupDetailsPrivacy" class="text-sm text-gray-400 mb-4"></p>
      <button id="addUserBtn" class="hidden mb-4 bg-blue-600 text-white py-1 px-3 rounded-md hover:bg-blue-700 transition" onclick="openAddUserModal(currentGroupId)" aria-label="Add User">Add User</button>
      <div id="groupChatSection" class="hidden mb-4">
        <h3 class="text-md font-bold text-[#FFD700] mb-2">Group Chat</h3>
        <div id="chatMessages" class="bg-gray-800 p-4 rounded-md h-48 overflow-y-auto mb-4"></div>
        <form id="chatForm" action="groups_process.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="send_message">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
          <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
          <input type="hidden" name="group_id" id="chatGroupId">
          <input type="text" name="message" placeholder="Type your message..." class="custom-input w-full px-4 py-2 mb-2 rounded-md border border-[#FFD700] bg-gray-800 text-white">
          <input type="file" name="file" accept="image/*,application/pdf" class="custom-input w-full px-4 py-2 mb-2 rounded-md border border-[#FFD700] bg-gray-800 text-white">
          <button type="submit" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Send Message">Send</button>
        </form>
      </div>
      <div class="flex gap-4">
        <button onclick="closeGroupDetailsModal()" class="w-full bg-gray-800 text-white py-1 px-4 rounded-md hover:bg-gray-700 transition" aria-label="Close">Close</button>
        <button id="groupDetailsRemove" class="hidden w-full remove-button py-1 px-4 rounded-md transition" aria-label="Remove Group">Remove</button>
      </div>
    </div>
  </div>

  <!-- Add User Modal -->
  <div id="addUserModal" class="modal hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
    <div class="bg-[#111] p-6 rounded-lg shadow-lg w-full max-w-sm border border-[#FFD700]">
      <h2 class="text-lg font-bold text-[#FFD700] mb-4">Add User to Group</h2>
      <form id="addUserForm">
        <input type="hidden" id="addUserGroupId" name="group_id">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="email" name="email" id="userEmail" placeholder="Enter user email" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
        <div class="flex gap-4">
          <button type="button" onclick="closeAddUserModal()" class="w-full bg-gray-800 text-white py-1 px-4 rounded-md hover:bg-gray-700 transition" aria-label="Cancel">Cancel</button>
          <button type="submit" onclick="addUserToGroup(document.getElementById('addUserGroupId').value)" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Add User">Add</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-[#111] py-8 px-4 text-white border-t-2 border-[#FFD700]">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-6xl mx-auto">
      <div class="text-center md:text-left">
        <h3 class="text-xl font-bold text-[#FFD700] mb-2">Get in Touch</h3>
        <p class="mb-2">Email: <a href="mailto:support@campusconnect.com" class="text-white hover:text-[#FFD700] transition">support@campusconnect.com</a></p>
        <p class="mb-2">Phone: <a href="tel:+1234567890" class="text-white hover:text-[#FFD700] transition">+251*******</a></p>
        <form id="contactForm">
          <input type="text" name="name" placeholder="Your Name" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white" required>
          <input type="email" name="email" placeholder="Your Email" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white" required>
          <textarea name="message" placeholder="Your Message" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white h-20 resize-y" required></textarea>
          <button type="submit" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Send Message">Send Message</button>
        </form>
      </div>
      <div class="text-center">
        <h3 class="text-xl font-bold text-[#FFD700] mb-2">Explore</h3>
        <ul class="space-y-2">
          <li><a href="homepage.php" class="text-white hover:text-[#FFD700] transition">Home</a></li>
          <li><a href="homepage.php#about" class="text-white hover:text-[#FFD700] transition">About</a></li>
          <li><a href="events.php" class="text-white hover:text-[#FFD700] transition">Events</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">Resources</a></li>
          <li><a href="homepage.php#contact" class="text-white hover:text-[#FFD700] transition">Contact</a></li>
        </ul>
      </div>
      <div class="text-center md:text-left">
        <h3 class="text-xl font-bold text-[#FFD700] mb-2">Stay Connected</h3>
        <p class="mb-2">Join our newsletter for the latest updates!</p>
        <form id="newsletterForm">
          <input type="email" name="email" placeholder="Enter your email" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white" required>
          <button type="submit" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Subscribe">Subscribe</button>
        </form>
      </div>
    </div>
    <div class="text-center mt-8 pt-4 border-t border-[#FFD700]">
      <div class="social-icons flex justify-center gap-4 mb-2">
        <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
        <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
        <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
        <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
      </div>
      <p class="text-[#FFD700] text-lg">Campus Connect: Building Bridges, Igniting Futures</p>
    </div>
  </footer>

  <script src="groups.js"></script>
</body>
</html>