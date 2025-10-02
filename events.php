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

// Get selected year and month from query parameters or default to current
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
if ($current_month < 1 || $current_month > 12) {
    $current_month = date('m');
}
if ($current_year < 2000 || $current_year > 9999) {
    $current_year = date('Y');
}

// Calculate previous and next month/year for navigation
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Initialize database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    error_log("Connection failed: " . $db->connect_error);
    die("Connection failed: " . $db->connect_error);
}

// Fetch events for the selected month
$start_date = "$current_year-$current_month-01";
$end_date = date('Y-m-t', strtotime($start_date));
$query = "SELECT id, title, description, date, time, category, location, attendance, image, organizer, creator_id 
          FROM events 
          WHERE date BETWEEN ? AND ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$events_result = $stmt->get_result();
if (!$events_result) {
    error_log("Events query failed: " . $db->error);
    $events_result = false;
}

// Check if user has RSVP'd to each event
$user_id = $_SESSION['user_id'];
$event_rsvps = [];
if ($events_result && $events_result->num_rows > 0) {
    while ($event = $events_result->fetch_assoc()) {
        $event_id = $event['id'];
        $rsvp_query = "SELECT * FROM event_rsvps WHERE event_id = ? AND user_id = ?";
        $rsvp_stmt = $db->prepare($rsvp_query);
        if ($rsvp_stmt) {
            $rsvp_stmt->bind_param("ii", $event_id, $user_id);
            $rsvp_stmt->execute();
            $event['has_rsvped'] = $rsvp_stmt->get_result()->num_rows > 0;
            $event_rsvps[$event_id] = $event;
            $rsvp_stmt->close();
        } else {
            error_log("RSVP query preparation failed: " . $db->error);
        }
    }
}

// Fetch universities for filter
$universities_query = "SELECT id, name, code FROM universities";
$universities_result = $db->query($universities_query);
$universities = $universities_result ? $universities_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch featured event (earliest upcoming event)
$featured_query = "SELECT id, title, description, date, time, location, image, creator_id 
                  FROM events WHERE date >= CURDATE() 
                  ORDER BY date ASC, time ASC LIMIT 1";
$featured_result = $db->query($featured_query);
if (!$featured_result) {
    error_log("Featured event query failed: " . $db->error);
}

// Close database connection
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect - Events</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="events.css">
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
      <input type="text" id="eventSearch" placeholder="Search for events..." class="search-input px-4 py-2 rounded-full border-none outline-none bg-gray-800 text-white focus:ring-2 focus:ring-[#FFD700]">
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
    <h1 class="text-4xl font-bold mb-2 text-black">Explore Events</h1>
    <p class="text-lg text-gray-800">Join exciting campus events and connect with your community!</p>
    <button onclick="openCreateEventModal()" class="mt-4 bg-black text-[#FFD700] py-2 px-6 rounded-md hover:bg-[#FFD700] hover:text-black transition border border-[#FFD700]" aria-label="Create New Event">Create New Event</button>
  </section>

  <!-- Events Section -->
  <section class="events-section py-8 px-4">
    <div class="w-full max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Sidebar: Filters and Featured Event -->
      <aside class="sidebar col-span-1 p-6 rounded-lg bg-[#111] border border-[#FFD700]">
        <h2 class="text-xl font-bold text-[#FFD700] mb-4">Filters</h2>
        <button id="toggleFilters" class="md:hidden mb-4 bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Toggle Filters">Toggle Filters</button>
        <div id="filtersContent" class="space-y-4 hidden md:block">
          <div>
            <label class="block text-sm text-gray-400 mb-1" for="filterCategory">Category</label>
            <select id="filterCategory" class="custom-select w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800 text-white">
              <option value="" selected>All Categories</option>
              <option value="academic">Academic</option>
              <option value="social">Social</option>
              <option value="sports">Sports</option>
              <option value="cultural">Cultural</option>
              <option value="professional">Professional</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1" for="filterDate">Date</label>
            <select id="filterDate" class="custom-select w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800 text-white">
              <option value="" selected>All Dates</option>
              <option value="upcoming">Upcoming</option>
              <option value="past">Past</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1" for="filterLocation">Location</label>
            <select id="filterLocation" class="custom-select w-full px-4 py-2 rounded-md border border-[#FFD700] bg-gray-800 text-white">
              <option value="" selected>All Locations</option>
              <?php foreach ($universities as $uni): ?>
                <option value="<?php echo htmlspecialchars($uni['code']); ?>"><?php echo htmlspecialchars($uni['name']); ?></option>
              <?php endforeach; ?>
              <option value="online">Online</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1" for="filterAttendance">Attendance</label>
            <select id="filterAttendance" class="custom-select w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white">
              <option value="" selected>All Types</option>
              <option value="in-person">In-Person</option>
              <option value="virtual">Virtual</option>
            </select>
          </div>
          <button onclick="applyFilters()" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Apply Filters">Apply Filters</button>
        </div>
        <h2 class="text-xl font-bold text-[#FFD700] mt-6 mb-4">Featured Event</h2>
        <div id="featuredEvent" class="space-y-4">
          <?php
          if ($featured_result && $featured_result->num_rows > 0) {
              $event = $featured_result->fetch_assoc();
              $event_datetime = date('M d, Y H:i', strtotime($event['date'] . ' ' . $event['time']));
              echo '<div class="event-card p-4 rounded-md bg-[#222] border border-[#FFD700]" data-event-id="' . $event['id'] . '" data-creator-id="' . $event['creator_id'] . '">';
              echo '<img src="' . htmlspecialchars($event['image'] ?: '/assets/event1.jpg') . '" alt="' . htmlspecialchars($event['title']) . '" class="w-full h-40 object-cover rounded-md mb-2" loading="lazy">';
              echo '<h3 class="font-bold text-[#FFD700]">' . htmlspecialchars($event['title']) . '</h3>';
              echo '<p class="text-sm text-gray-400">Date: ' . $event_datetime . ' | ' . htmlspecialchars($event['location']) . '</p>';
              echo '<p class="text-sm text-gray-400">' . htmlspecialchars(substr($event['description'], 0, 50)) . '...</p>';
              echo '<p class="countdown mt-2" id="countdown" data-event-date="' . $event['date'] . ' ' . $event['time'] . '">Starting soon...</p>';
              echo '<button onclick="rsvpEvent(' . $event['id'] . ')" class="mt-2 bg-[#FFD700] text-black py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="RSVP to ' . htmlspecialchars($event['title']) . '">RSVP</button>';
              if ($event['creator_id'] == $user_id) {
                  echo '<button onclick="removeEvent(' . $event['id'] . ')" class="mt-2 ml-2 remove-button py-1 px-3 rounded-md transition" aria-label="Remove ' . htmlspecialchars($event['title']) . '">Remove</button>';
              }
              echo '</div>';
          } else {
              echo '<p class="text-gray-400">No featured events available.</p>';
          }
          ?>
        </div>
      </aside>

      <!-- Main Content: Events List -->
      <main class="col-span-3 p-6 bg-[#111] rounded-lg border border-[#FFD700]">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold text-[#FFD700]">Events List</h2>
          <div class="flex gap-2">
            <button onclick="showListView()" class="bg-[#FFD700] text-black py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="List View">List View</button>
            <button onclick="showCalendarView()" class="bg-gray-800 text-white py-1 px-3 rounded-md hover:bg-[#FFD700] hover:text-black transition border border-[#FFD700]" aria-label="Calendar View">Calendar View</button>
          </div>
        </div>
        <div id="listView" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <?php
          if ($events_result && $events_result->num_rows > 0) {
              foreach ($event_rsvps as $event) {
                  $event_datetime = date('M d, Y H:i', strtotime($event['date'] . ' ' . $event['time']));
                  echo '<div class="event-card p-4 rounded-md bg-[#222] border border-[#FFD700]" data-event-id="' . $event['id'] . '" data-creator-id="' . $event['creator_id'] . '" data-category="' . strtolower($event['category']) . '" data-location="' . strtolower($event['location']) . '" data-attendance="' . strtolower($event['attendance']) . '" data-date="' . $event['date'] . ' ' . $event['time'] . '">';
                  echo '<img src="' . htmlspecialchars($event['image'] ?: '/assets/event2.jpg') . '" alt="' . htmlspecialchars($event['title']) . '" class="w-full h-32 object-cover rounded-md mb-2" loading="lazy">';
                  echo '<h3 class="font-bold text-[#FFD700]">' . htmlspecialchars($event['title']) . '</h3>';
                  echo '<p class="text-sm text-gray-400">Date: ' . $event_datetime . ' | ' . htmlspecialchars($event['location']) . '</p>';
                  echo '<p class="text-sm text-gray-400">' . htmlspecialchars(substr($event['description'], 0, 50)) . '...</p>';
                  echo '<button onclick="viewEvent(' . $event['id'] . ')" class="mt-2 bg-[#FFD700] text-black py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="View ' . htmlspecialchars($event['title']) . '">View</button>';
                  echo '<button onclick="' . ($event['has_rsvped'] ? 'cancelRsvp' : 'rsvpEvent') . '(' . $event['id'] . ')" class="mt-2 ml-2 bg-' . ($event['has_rsvped'] ? 'gray-600' : '[#FFD700]') . ' text-' . ($event['has_rsvped'] ? 'white' : 'black') . ' py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="' . ($event['has_rsvped'] ? 'Cancel RSVP' : 'RSVP') . ' ' . htmlspecialchars($event['title']) . '">' . ($event['has_rsvped'] ? 'Cancel RSVP' : 'RSVP') . '</button>';
                  if ($event['creator_id'] == $user_id) {
                      echo '<button onclick="removeEvent(' . $event['id'] . ')" class="mt-2 ml-2 remove-button py-1 px-3 rounded-md transition" aria-label="Remove ' . htmlspecialchars($event['title']) . '">Remove</button>';
                  }
                  echo '</div>';
              }
          } else {
              echo '<p class="text-gray-400">No events found. Create one to get started!</p>';
          }
          ?>
        </div>
        <div id="calendarView" class="hidden">
          <div class="flex justify-between items-center mb-4">
            <a href="?year=<?php echo $prev_year; ?>&month=<?php echo $prev_month; ?>" class="bg-[#FFD700] text-black py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Previous Month">Previous</a>
            <h3 class="text-lg font-bold text-[#FFD700]"><?php echo date('F Y', strtotime("$current_year-$current_month-01")); ?></h3>
            <a href="?year=<?php echo $next_year; ?>&month=<?php echo $next_month; ?>" class="bg-[#FFD700] text-black py-1 px-3 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Next Month">Next</a>
          </div>
          <div class="calendar-grid grid grid-cols-7 gap-2">
            <?php
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
            $first_day = date('N', strtotime("$current_year-$current_month-01")) - 1; // 0-based (0 = Monday)
            $event_dates = [];
            foreach ($event_rsvps as $event) {
                $event_date = date('Y-m-d', strtotime($event['date']));
                $day = date('j', strtotime($event['date']));
                if (date('Y-m', strtotime($event_date)) === "$current_year-$current_month") {
                    $event_dates[$day][] = $event;
                }
            }
            // Render day headers (Mon-Sun)
            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            foreach ($days as $day) {
                echo '<div class="calendar-day font-bold text-[#FFD700]">' . $day . '</div>';
            }
            // Render empty cells before the first day
            for ($i = 0; $i < $first_day; $i++) {
                echo '<div class="calendar-day bg-gray-800"></div>';
            }
            // Render calendar days
            for ($day = 1; $day <= $days_in_month; $day++) {
                $is_event = isset($event_dates[$day]);
                echo '<div class="calendar-day ' . ($is_event ? 'event' : '') . '" data-day="' . $day . '">' . $day;
                if ($is_event) {
                    echo '<div class="event-tooltip">';
                    foreach ($event_dates[$day] as $event) {
                        echo '<p class="cursor-pointer" onclick="viewEvent(' . $event['id'] . ')">' . htmlspecialchars($event['title']) . ' (' . date('H:i', strtotime($event['time'])) . ')</p>';
                    }
                    echo '</div>';
                }
                echo '</div>';
            }
            // Fill remaining cells
            $total_cells = $first_day + $days_in_month;
            $remaining_cells = (7 - ($total_cells % 7)) % 7;
            for ($i = 0; $i < $remaining_cells; $i++) {
                echo '<div class="calendar-day bg-gray-800"></div>';
            }
            ?>
          </div>
        </div>
      </main>
    </div>
  </section>

  <!-- Create Event Modal -->
  <div id="createEventModal" class="modal hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
    <div class="bg-[#111] p-6 rounded-lg shadow-lg w-full max-w-sm border border-[#FFD700]">
      <h2 class="text-lg font-bold text-[#FFD700] mb-4">Create New Event</h2>
      <form id="createEventForm" action="events_process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create_event">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
        <input type="text" name="title" placeholder="Event Title" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
        <textarea name="description" placeholder="Event Description" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" rows="3" required></textarea>
        <input type="date" name="date" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
        <input type="time" name="time" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
        <select name="category" class="custom-select w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
          <option value="" disabled selected>Select Category</option>
          <option value="academic">Academic</option>
          <option value="social">Social</option>
          <option value="sports">Sports</option>
          <option value="cultural">Cultural</option>
          <option value="professional">Professional</option>
        </select>
        <select name="location" class="custom-select w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
          <option value="" disabled selected>Select Location</option>
          <?php foreach ($universities as $uni): ?>
            <option value="<?php echo htmlspecialchars($uni['name']); ?>"><?php echo htmlspecialchars($uni['name']); ?></option>
          <?php endforeach; ?>
          <option value="Online">Online</option>
        </select>
        <select name="attendance" class="custom-select w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
          <option value="" disabled selected>Select Attendance</option>
          <option value="in-person">In-Person</option>
          <option value="virtual">Virtual</option>
        </select>
        <input type="text" name="organizer" placeholder="Organizer Name" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white" required>
        <input type="file" name="image" accept="image/*" class="custom-input w-full px-4 py-2 mb-4 rounded-md border border-[#FFD700] bg-gray-800 text-white">
        <input type="hidden" name="image_path" value="/assets/default_event.jpg">
        <div class="flex gap-4">
          <button type="button" onclick="closeCreateEventModal()" class="w-full bg-gray-800 text-white py-1 px-4 rounded-md hover:bg-gray-700 transition" aria-label="Cancel">Cancel</button>
          <button type="submit" class="w-full bg-[#FFD700] text-black py-1 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]" aria-label="Create Event">Create</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Event Details Modal -->
  <div id="eventDetailsModal" class="modal hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
    <div class="bg-[#111] p-6 rounded-lg shadow-lg w-full max-w-md border border-[#FFD700]">
      <h2 id="eventDetailsTitle" class="text-lg font-bold text-[#FFD700] mb-4"></h2>
      <img id="eventDetailsImage" src="" alt="Event Image" class="w-full h-48 object-cover rounded-md mb-4">
      <p id="eventDetailsDescription" class="text-gray-400 mb-4"></p>
      <p id="eventDetailsDate" class="text-sm text-gray-400 mb-2"></p>
      <p id="eventDetailsTime" class="text-sm text-gray-400 mb-2"></p>
      <p id="eventDetailsLocation" class="text-sm text-gray-400 mb-2"></p>
      <p id="eventDetailsOrganizer" class="text-sm text-gray-400 mb-4"></p>
      <div class="flex gap-4">
        <button onclick="closeEventDetailsModal()" class="w-full bg-gray-800 text-white py-1 px-4 rounded-md hover:bg-gray-700 transition" aria-label="Close">Close</button>
        <button id="eventDetailsRemove" class="hidden w-full remove-button py-1 px-4 rounded-md transition" aria-label="Remove Event">Remove</button>
      </div>
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

  <script src="events.js"></script>
</body>
</html>