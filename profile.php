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

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.full_name, u.email, u.university_id, u.batch, u.bio, u.interests, u.linkedin, u.twitter, u.profile_picture, u.cover_photo, u.role, uni.name AS university_name 
                        FROM users u 
                        LEFT JOIN universities uni ON u.university_id = uni.id 
                        WHERE u.id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    error_log('User not found for ID: ' . $user_id);
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch recent activity
$activities = [];
// Posts
$stmt = $conn->prepare("SELECT content, timestamp FROM posts WHERE user_id = ? ORDER BY timestamp DESC LIMIT 3");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $activities[] = ['type' => 'post', 'content' => $row['content'], 'timestamp' => $row['timestamp']];
}
$stmt->close();

// Event RSVPs
$stmt = $conn->prepare("SELECT e.title, er.rsvp_at AS timestamp 
                        FROM event_rsvps er 
                        JOIN events e ON er.event_id = e.id 
                        WHERE er.user_id = ? ORDER BY er.rsvp_at DESC LIMIT 3");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $activities[] = ['type' => 'rsvp', 'content' => "RSVP'd to {$row['title']}", 'timestamp' => $row['timestamp']];
}
$stmt->close();

// Group memberships
$stmt = $conn->prepare("SELECT g.name, gm.joined_at AS timestamp 
                        FROM group_members gm 
                        JOIN groups g ON gm.group_id = g.id 
                        WHERE gm.user_id = ? ORDER BY gm.joined_at DESC LIMIT 3");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $activities[] = ['type' => 'group', 'content' => "Joined {$row['name']}", 'timestamp' => $row['timestamp']];
}
$stmt->close();

// Sort activities by timestamp
usort($activities, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});
$activities = array_slice($activities, 0, 3);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect - Profile</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="profile.css">
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
      <input type="text" placeholder="Search for peers, events, or groups..." class="custom-input px-4 py-2 rounded-full border-none outline-none bg-gray-800 text-white focus:ring-2 focus:ring-[#FFD700]">
    </div>
  </div>

  <!-- Header with logo + navigation -->
  <div class="header sticky top-0 z-10 flex justify-between items-center p-4 bg-black text-white border-b-2 border-[#FFD700] shadow-md">
    <div class="logo text-3xl font-bold text-[#FFD700]">Campus Connect</div>
    <div class="nav-hamburger">
      <span></span>
      <span></span>
      <span></span>
    </div>
    <div class="nav flex gap-4">
      <a href="dashboard.php" class="font-medium text-white hover:text-[#FFD700] transition">Dashboard</a>
      <a href="profile.php" class="font-medium text-white hover:text-[#FFD700] transition">Profile</a>
      <a href="messages.php" class="font-medium text-white hover:text-[#FFD700] transition">Messages</a>
      <a href="events.php" class="font-medium text-white hover:text-[#FFD700] transition">Events</a>
      <a href="groups.php" class="font-medium text-white hover:text-[#FFD700] transition">Groups</a>
      <a href="logout.php" class="font-medium text-white hover:text-[#FFD700] transition">Logout</a>
    </div>
  </div>

  <!-- Profile Section -->
  <section class="py-8 px-4">
    <div class="w-full max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Sidebar: Profile Summary -->
      <aside class="sidebar col-span-1 p-6 rounded-lg">
        <div class="text-center mb-6">
          <img id="profilePicture" src="<?php echo htmlspecialchars($user['profile_picture'] ?: '/assets/default_profile.jpg'); ?>" alt="User Profile" class="w-24 h-24 rounded-full mx-auto mb-2 border-2 border-[#FFD700]">
          <h2 id="profileName" class="text-xl font-bold text-[#FFD700]"><?php echo htmlspecialchars($user['full_name']); ?></h2>
          <p id="profileBatch" class="text-sm text-gray-400">
            <?php 
            if ($user['role'] === 'student' && $user['university_name'] && $user['batch']) {
                echo 'Student at ' . htmlspecialchars($user['university_name']) . ' | Batch ' . htmlspecialchars($user['batch']);
            } elseif ($user['role'] === 'admin') {
                echo 'Administrator';
            } else {
                echo 'Student';
            }
            ?>
          </p>
          <p id="profileMajor" class="text-sm text-gray-400"><?php echo htmlspecialchars($user['bio'] ?: 'No bio specified'); ?></p>
          <div class="mt-2">
            <p class="text-sm text-gray-400">Profile Completion: <span id="profileCompletion">0%</span></p>
            <div class="w-full bg-gray-800 rounded-full h-2.5">
              <div id="completionBar" class="bg-[#FFD700] h-2.5 rounded-full" style="width: 0%"></div>
            </div>
          </div>
          <button onclick="openProfilePictureModal()" class="mt-2 bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Edit Profile Picture</button>
          <button onclick="openDeleteAccountModal()" class="mt-2 bg-red-600 text-white py-1 px-4 rounded-md hover:bg-red-700 transition border border-red-600">Delete Account</button>
        </div>
        <h3 class="text-lg font-bold text-[#FFD700] mb-2">Social Links</h3>
        <div id="socialLinks" class="space-y-2">
          <div class="flex items-center gap-2">
            <i class="fab fa-linkedin text-[#FFD700]"></i>
            <a id="linkedinLink" href="<?php echo htmlspecialchars($user['linkedin'] ?: '#'); ?>" class="text-white hover:text-[#FFD700] transition"><?php echo htmlspecialchars($user['linkedin'] ?: 'Not provided'); ?></a>
          </div>
          <div class="flex items-center gap-2">
            <i class="fab fa-twitter text-[#FFD700]"></i>
            <a id="twitterLink" href="<?php echo htmlspecialchars($user['twitter'] ?: '#'); ?>" class="text-white hover:text-[#FFD700] transition"><?php echo htmlspecialchars($user['twitter'] ?: 'Not provided'); ?></a>
          </div>
        </div>
      </aside>

      <!-- Main Content: Cover Photo, Profile Form, Recent Activity -->
      <main class="col-span-3 p-6 bg-black rounded-lg">
        <!-- Cover Photo -->
        <div id="coverPhoto" class="cover-photo mb-6" style="background-image: url('<?php echo htmlspecialchars($user['cover_photo'] ?: '/assets/default_cover.jpg'); ?>');">
          <div class="cover-photo-overlay">
            <button onclick="openCoverPhotoModal()" class="bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Edit Cover Photo</button>
          </div>
        </div>

        <!-- Profile Form -->
        <h2 class="text-xl font-bold text-[#FFD700] mb-4">Edit Profile</h2>
        <div class="bg-gray-800 p-6 rounded-md border border-[#FFD700]">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-400 mb-1">Full Name</label>
              <input id="editName" type="text" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800">
            </div>
            <div>
              <label class="block text-sm text-gray-400 mb-1">Batch</label>
              <input id="editBatch" type="text" value="<?php echo htmlspecialchars($user['batch'] ?: ''); ?>" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800" <?php echo $user['role'] === 'admin' ? 'disabled' : ''; ?>>
            </div>
            <div>
              <label class="block text-sm text-gray-400 mb-1">Bio</label>
              <input id="editMajor" type="text" value="<?php echo htmlspecialchars($user['bio'] ?: ''); ?>" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800">
            </div>
            <div>
              <label class="block text-sm text-gray-400 mb-1">Interests</label>
              <input id="editInterests" type="text" value="<?php echo htmlspecialchars($user['interests'] ?: ''); ?>" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800">
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm text-gray-400 mb-1">Bio (Detailed)</label>
              <textarea id="editBio" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800" rows="4"><?php echo htmlspecialchars($user['bio'] ?: ''); ?></textarea>
            </div>
            <div>
              <label class="block text-sm text-gray-400 mb-1">LinkedIn</label>
              <input id="editLinkedin" type="text" value="<?php echo htmlspecialchars($user['linkedin'] ?: ''); ?>" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800">
            </div>
            <div>
              <label class="block text-sm text-gray-400 mb-1">Twitter</label>
              <input id="editTwitter" type="text" value="<?php echo htmlspecialchars($user['twitter'] ?: ''); ?>" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800">
            </div>
          </div>
          <button onclick="saveProfileChanges()" class="mt-4 bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Save Changes</button>
        </div>

        <!-- Recent Activity -->
        <h2 class="text-xl font-bold text-[#FFD700] mt-6 mb-4">Recent Activity</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?php if (empty($activities)): ?>
            <p class="text-gray-400">No recent activity.</p>
          <?php else: ?>
            <?php foreach ($activities as $activity): ?>
              <div class="activity-card p-4 rounded-md">
                <div class="flex items-center gap-4 mb-2">
                  <img src="<?php echo htmlspecialchars($user['profile_picture'] ?: '/assets/default_profile.jpg'); ?>" alt="User" class="w-10 h-10 rounded-full">
                  <div>
                    <p class="font-bold text-[#FFD700]"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p class="text-sm text-gray-400"><?php echo date('F j, Y, g:i a', strtotime($activity['timestamp'])); ?></p>
                  </div>
                </div>
                <p><?php echo htmlspecialchars($activity['content']); ?></p>
                <div class="flex gap-4 mt-2">
                  <button class="text-[#FFD700] hover:underline">Like (0)</button>
                  <button class="text-[#FFD700] hover:underline">Comment (0)</button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </section>

  <!-- Profile Picture Edit Modal -->
  <div id="profilePictureModal" class="modal hidden flex items-center justify-center">
    <div class="bg-black p-4 rounded-lg shadow-lg w-full max-w-sm border border-[#FFD700]">
      <h2 class="text-lg font-bold text-[#FFD700] mb-2">Edit Profile Picture</h2>
      <div class="flex justify-center mb-4">
        <img id="profilePicturePreview" src="<?php echo htmlspecialchars($user['profile_picture'] ?: '/assets/default_profile.jpg'); ?>" alt="Profile Picture Preview" class="w-24 h-24 rounded-full border-2 border-[#FFD700] profile-picture-preview">
      </div>
      <div id="profileDragArea" class="drag-area mb-4">
        <p class="text-gray-400">Drag & drop an image here or click to select</p>
        <input type="file" id="profilePictureInput" accept="image/*" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800" style="opacity: 0; position: absolute; width: 100%; height: 100%; cursor: pointer;">
      </div>
      <div class="flex gap-4 mt-4">
        <button onclick="closeProfilePictureModal()" class="w-full bg-gray-800 text-white py-1 px-4 rounded-md hover:bg-gray-700 transition">Cancel</button>
        <button onclick="removeProfilePicture()" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Remove</button>
        <button id="confirmProfilePicture" onclick="confirmProfilePicture()" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" disabled>Confirm</button>
      </div>
    </div>
  </div>

  <!-- Cover Photo Edit Modal -->
  <div id="coverPhotoModal" class="modal hidden flex items-center justify-center">
    <div class="bg-black p-4 rounded-lg shadow-lg w-full max-w-sm border border-[#FFD700]">
      <h2 class="text-lg font-bold text-[#FFD700] mb-2">Edit Cover Photo</h2>
      <div class="flex justify-center mb-4">
        <img id="coverPhotoPreview" src="<?php echo htmlspecialchars($user['cover_photo'] ?: '/assets/default_cover.jpg'); ?>" alt="Cover Photo Preview" class="w-full h-32 object-cover rounded-md border-2 border-[#FFD700]">
      </div>
      <div id="coverDragArea" class="drag-area mb-4">
        <p class="text-gray-400">Drag & drop an image here or click to select</p>
        <input type="file" id="coverPhotoInput" accept="image/*" class="custom-input w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800" style="opacity: 0; position: absolute; width: 100%; height: 100%; cursor: pointer;">
      </div>
      <div class="flex gap-4 mt-4">
        <button onclick="closeCoverPhotoModal()" class="w-full bg-gray-800 text-white py-1 px-4 rounded-md hover:bg-gray-700 transition">Cancel</button>
        <button onclick="removeCoverPhoto()" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Remove</button>
        <button id="confirmCoverPhoto" onclick="confirmCoverPhoto()" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" disabled>Confirm</button>
      </div>
    </div>
  </div>

  <!-- Delete Account Modal -->
  <div id="deleteAccountModal" class="modal hidden flex items-center justify-center">
    <div class="bg-black p-4 rounded-lg shadow-lg w-full max-w-sm border border-[#FFD700]">
      <h2 class="text-lg font-bold text-[#FFD700] mb-2">Delete Account</h2>
      <p class="text-gray-400 mb-4">Are you sure you want to delete your account? This action is permanent and cannot be undone.</p>
      <div class="flex gap-4 mt-4">
        <button onclick="closeDeleteAccountModal()" class="w-full bg-gray-800 text-white py-1 px-4 rounded-md hover:bg-gray-700 transition">Cancel</button>
        <button onclick="confirmDeleteAccount()" class="w-full bg-red-600 text-white py-1 px-4 rounded-md hover:bg-red-700 transition border border-red-600">Confirm Delete</button>
      </div>
    </div>
  </div>

  <!-- Footer Section -->
  <footer class="footer bg-black py-8 px-4 text-white border-t-2 border-[#FFD700]">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-6xl mx-auto">
      <div class="text-center md:text-left">
        <h3 class="text-xl font-bold text-[#FFD700] mb-2">Get in Touch</h3>
        <p class="mb-2">Email: <a href="mailto:support@campusconnect.com" class="text-white hover:text-[#FFD700] transition">support@campusconnect.com</a></p>
        <p class="mb-2">Phone: <a href="tel:+1234567890" class="text-white hover:text-[#FFD700] transition">+251*******</a></p>
        <div>
          <input type="text" placeholder="Your Name" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white">
          <input type="email" placeholder="Your Email" class="custom-input w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white">
          <textarea placeholder="Your Message" class="w-full px-4 py-2 mb-2 border border-[#FFD700] rounded-md bg-gray-800 text-white h-20 resize-y"></textarea>
          <button class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Send Message</button>
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
        <button class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Subscribe</button>
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

  <script src="profile.js"></script>
</body>
</html>