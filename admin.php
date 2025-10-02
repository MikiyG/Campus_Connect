<?php
session_start();
require_once 'config.php';

// Check if user is logged in, approved, and an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="admin.css">
</head>
<body class="bg-black text-white">
    <!-- Top Bar -->
    <div class="top-bar flex justify-end items-center p-4 md:p-6 bg-[#1a1a1a] border-b-2 border-[#FFD700] gap-4">
        <div class="social-icons flex gap-4">
            <a href="#"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
            <a href="#"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
            <a href="#"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
            <a href="#"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
        </div>
        <div>
            <input type="text" id="searchInput" placeholder="Search..." class="custom-input px-4 py-2 rounded-full border-none outline-none bg-gray-800 text-white focus:ring-2 focus:ring-[#FFD700]">
        </div>
    </div>

    <!-- Header -->
    <div class="header flex justify-between items-center p-4 md:p-6 bg-black text-white border-b-2 border-[#FFD700] shadow-md">
        <div class="logo text-3xl md:text-4xl font-bold text-[#FFD700]">Campus Connect</div>
        <div class="nav-hamburger flex-col justify-between w-8 h-5 cursor-pointer md:hidden z-[1000]">
            <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
            <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
            <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
        </div>
        <div class="flex gap-4 md:gap-8 items-center">
            <span class="welcome-message text-sm md:text-base text-gray-400 hidden md:inline-block"><span id="typedWelcome" data-username="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"></span><span class="cursor">|</span></span>
            <span class="timestamp text-sm md:text-base text-gray-400 md:block"><?php echo date('D, M d, Y, h:i A'); ?></span>
            <nav class="nav flex gap-4 md:gap-8">
                <a href="homepage.php" class="font-medium text-white hover:text-[#FFD700] transition">Home</a>
                <a href="admin.php" class="font-medium text-[#FFD700] transition">Admin</a>
                <a href="events.php" class="font-medium text-white hover:text-[#FFD700] transition">Events</a>
                <a href="logout.php" class="font-medium text-white hover:text-[#FFD700] transition">Logout</a>
            </nav>
        </div>
    </div>

    <!-- Admin Panel -->
    <section class="min-h-screen pt-16 pb-12 px-4 md:px-12">
        <h1 class="text-3xl font-bold text-center text-[#FFD700] mb-4">Admin Panel</h1>
        <span class="welcome-message text-sm text-gray-400 md:hidden text-center block mb-4"><span id="typedWelcomeMobile" data-username="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"></span><span class="cursor">|</span></span>
        <!-- Tabs -->
        <div class="tab-buttons flex flex-wrap gap-4 border-b border-gray-700 mb-6">
            <button class="tab-button px-4 py-2 text-white font-medium active" onclick="switchTab('pending')">Users Pending</button>
            <button class="tab-button px-4 py-2 text-white font-medium" onclick="switchTab('users')">Users</button>
            <button class="tab-button px-4 py-2 text-white font-medium" onclick="switchTab('universities')">Universities</button>
            <button class="tab-button px-4 py-2 text-white font-medium" onclick="switchTab('groups')">Groups</button>
            <button class="tab-button px-4 py-2 text-white font-medium" onclick="switchTab('events')">Events</button>
            <button class="tab-button px-4 py-2 text-white font-medium" onclick="switchTab('reports')">Reports</button>
        </div>

        <!-- Users Pending Tab -->
        <div id="pending" class="tab-content">
            <div class="dashboard-card p-4 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-[#FFD700]">Pending Users: <span id="pendingCount">0</span></h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-800">
                            <th class="p-3">ID</th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">University</th>
                            <th class="p-3">Student ID</th>
                            <th class="p-3">Batch</th>
                            <th class="p-3">ID Picture</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Role</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pendingTable">
                        <!-- Data populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="users" class="tab-content hidden">
            <div class="dashboard-card p-4 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-[#FFD700]">Total Users: <span id="userCount">0</span></h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-800">
                            <th class="p-3">ID</th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">University</th>
                            <th class="p-3">Student ID</th>
                            <th class="p-3">Batch</th>
                            <th class="p-3">ID Picture</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Role</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <!-- Data populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Universities Tab -->
        <div id="universities" class="tab-content hidden">
            <div class="dashboard-card p-4 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-[#FFD700]">Total Universities: <span id="universityCount">0</span></h3>
            </div>
            <div class="flex justify-end mb-4">
                <button class="glow-button bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-500 transition" onclick="addUniversity()">Add University</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-800">
                            <th class="p-3">ID</th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Code</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="universitiesTable">
                        <!-- Data populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Groups Tab -->
        <div id="groups" class="tab-content hidden">
            <div class="dashboard-card p-4 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-[#FFD700]">Total Groups: <span id="groupCount">0</span></h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-800">
                            <th class="p-3">ID</th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Description</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="groupsTable">
                        <!-- Data populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Events Tab -->
        <div id="events" class="tab-content hidden">
            <div class="dashboard-card p-4 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-[#FFD700]">Total Events: <span id="eventCount">0</span></h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-800">
                            <th class="p-3">ID</th>
                            <th class="p-3">Title</th>
                            <th class="p-3">Description</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="eventsTable">
                        <!-- Data populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reports" class="tab-content hidden">
            <div class="dashboard-card p-4 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-[#FFD700]">Reports</h3>
            </div>
            <div class="report-options flex flex-col gap-4">
                <button class="glow-button bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition" onclick="generateReport('user_activity')">Generate User Activity Report</button>
                <button class="glow-button bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition" onclick="generateReport('event_participation')">Generate Event Participation Report</button>
                <!-- START OF ADDED CODE: New report buttons -->
                <button class="glow-button bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition" onclick="generateReport('university_distribution')">Generate University User Distribution Report</button>
                <button class="glow-button bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition" onclick="generateReport('signup_trends')">Generate Signup Trends Report</button>
                <button class="glow-button bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition" onclick="generateReport('popular_groups')">Generate Popular Groups Report</button>
                <button class="glow-button bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition" onclick="generateReport('active_users')">Generate Active Users Report</button>
                <button class="glow-button bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition" onclick="generateReport('pending_reports')">Generate Pending Reports Report</button>
                <!-- END OF ADDED CODE -->
            </div>
            <div class="report-content mt-6 overflow-x-auto"></div>
        </div>

        <!-- Confirmation Modal -->
        <div id="confirmationModal" class="modal hidden flex items-center justify-center">
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-sm border border-[#FFD700]">
                <h2 class="text-lg font-bold text-[#FFD700] mb-4" id="modalTitle">Action Confirmed</h2>
                <p class="text-gray-400 mb-4" id="modalMessage">Action completed successfully.</p>
                <button onclick="closeConfirmationModal()" class="glow-button w-full bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition">Close</button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black py-8 px-4 md:px-12 border-t-2 border-[#FFD700]">
        <div class="text-center">
            <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Explore</h3>
            <ul class="space-y-2 mb-6">
                <li><a href="homepage.php" class="text-white hover:text-[#FFD700] transition">Home</a></li>
                <li><a href="admin.php" class="text-white hover:text-[#FFD700] transition">Admin</a></li>
                <li><a href="logout.php" class="text-white hover:text-[#FFD700] transition">Logout</a></li>
            </ul>
            <div class="social-icons flex justify-center gap-4 mb-4">
                <a href="#"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
                <a href="#"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
                <a href="#"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
                <a href="#"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
            </div>
            <p class="text-[#FFD700] text-lg">Campus Connect: Building Bridges, Igniting Futures</p>
        </div>
    </footer>

    <script src="admin.js"></script>
</body>
</html>