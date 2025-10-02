<?php
// Include config.php to access database constants if needed
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect - Signup</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="signup.css">
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

  <!-- Header -->
  <div class="header flex items-center p-5 bg-[#111] border-b-2 border-[#FFD700] relative">
    <div class="logo text-4xl font-bold text-[#FFD700] text-shadow-md">Campus Connect</div>
    <div class="nav-hamburger flex-col justify-between w-8 h-5 cursor-pointer z-[1000]">
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
    </div>
    <nav class="nav flex gap-8">
      <a href="homepage.php" class="font-medium text-white hover:text-[#FFD700] transition">Home</a>
      <a href="homepage.php#about" class="font-medium text-white hover:text-[#FFD700] transition">About</a>
      <a href="#contact" class="font-medium text-white hover:text-[#FFD700] transition">Contact</a>
    </nav>
  </div>

  <!-- Signup Section -->
  <section class="signup-section relative pt-16 pb-12">
    <div class="relative z-10 bg-white p-8 rounded-lg shadow-lg w-full max-w-md mx-auto">
      <h1 class="text-3xl font-bold text-center text-[#FFD700] mb-6">Join Campus Connect</h1>
      <form id="signupForm" action="signup_process.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
          <label for="fullName" class="block text-sm font-medium text-gray-700">Full Name</label>
          <input type="text" id="fullName" name="fullName" required
                 class="custom-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:border-[#FFD700]"
                 placeholder="Full Name">
        </div>
        <div>
          <label for="university" class="block text-sm font-medium text-gray-700">University</label>
          <select id="university" name="university" required
                  class="custom-select mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-[#FFD700] focus:border-[#FFD700] text-black text-base">
            <option value="" disabled selected>Select your university</option>
            <?php
            // Connect to the database
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                echo "<option value='' disabled>Error loading universities</option>";
            } else {
                // Fetch universities from the database
                $query = "SELECT code, name FROM universities ORDER BY name";
                $result = $conn->query($query);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . htmlspecialchars($row['code']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                    }
                } else {
                    echo "<option value='' disabled>No universities found</option>";
                }
                $result->free();
                $conn->close();
            }
            ?>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label for="studentId" class="block text-sm font-medium text-gray-700">Student ID</label>
          <input type="text" id="studentId" name="studentId" required
                 class="custom-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:border-[#FFD700]"
                 placeholder="e.g., S123456">
        </div>
        <div>
          <label for="batch" class="block text-sm font-medium text-gray-700">Batch/Year</label>
          <select id="batch" name="batch" required
                  class="custom-select mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-[#FFD700] focus:border-[#FFD700] text-black text-base">
            <option value="" disabled selected>Select your batch</option>
            <option value="2023">2023</option>
            <option value="2024">2024</option>
            <option value="2025">2025</option>
            <option value="2026">2026</option>
            <option value="2027">2027</option>
          </select>
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Your Email</label>
          <input type="email" id="email" name="email" required
                 class="custom-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:border-[#FFD700]"
                 placeholder="you@gmail.com">
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
          <input type="password" id="password" name="password" required
                 class="custom-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:border-[#FFD700]"
                 placeholder="Create a password">
        </div>
        <div>
          <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
          <input type="password" id="confirmPassword" name="confirmPassword" required
                 class="custom-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:border-[#FFD700]"
                 placeholder="Confirm your password">
        </div>
        <div>
          <label for="idPicture" class="block text-sm font-medium text-gray-700">Student ID Picture</label>
          <div id="dragArea" class="drag-area mt-1 rounded-md">
            <p class="text-gray-700">Drag & drop your student ID image here or click to upload</p>
            <input type="file" id="idPicture" name="idPicture" accept="image/*" required class="custom-input w-full mt-2">
            <img id="idPicturePreview" src="/assets/default_id.jpg" alt="ID Preview" class="id-preview w-full h-40 object-contain mt-2 hidden">
          </div>
        </div>
        <div class="flex items-center">
          <input type="checkbox" id="terms" name="terms" required
                 class="h-4 w-4 text-[#FFD700] focus:ring-[#FFD700] border-gray-300 rounded">
          <label for="terms" class="ml-2 block text-sm text-gray-900">
            I agree to the <a href="#" onclick="openTermsModal(); return false;" class="text-[#FFD700] hover:underline">Terms of Service</a>
          </label>
        </div>
        <button type="submit" id="submitButton" disabled
                class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] hover:border-2 hover:border-[#FFD700] focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:ring-offset-2 transition">
          Sign Up
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-gray-600">
        Already have an account? <a href="login.php" class="text-[#FFD700] hover:underline">Log in</a>
      </p>
    </div>
  </section>

  <!-- Confirmation Modal -->
  <div id="confirmationModal" class="modal hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-sm">
      <h2 class="text-lg font-bold text-[#FFD700] mb-4">Submission Received</h2>
      <p class="text-gray-700 mb-4">Your signup request has been submitted! We will review your student ID and get back to you within 48 hours.</p>
      <div class="flex gap-4">
        <button onclick="closeConfirmationModal()" class="w-full bg-gray-300 text-black py-2 px-4 rounded-md hover:bg-gray-400 transition">Close</button>
        <button onclick="window.location.href='login.php'" class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Go to Login</button>
      </div>
    </div>
  </div>

  <!-- Terms of Service Modal -->
  <div id="termsModal" class="modal hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg max-h-[80vh] overflow-y-auto">
      <h2 class="text-xl font-bold text-[#FFD700] mb-4">Terms of Service</h2>
      <div class="text-gray-700 mb-4">
        <p class="mb-2"><strong>1. Acceptance of Terms</strong></p>
        <p>By accessing or using Campus Connect, you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.</p>
        <p class="mt-2 mb-2"><strong>2. User Conduct</strong></p>
        <p>You agree to use Campus Connect for lawful purposes only. You will not post or share content that is defamatory, obscene, or violates the rights of others.</p>
        <p class="mt-2 mb-2"><strong>3. Account Security</strong></p>
        <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
        <p class="mt-2 mb-2"><strong>4. Privacy</strong></p>
        <p>Your use of Campus Connect is also governed by our Privacy Policy, which outlines how we collect, use, and protect your personal information.</p>
        <p class="mt-2 mb-2"><strong>5. Termination</strong></p>
        <p>We reserve the right to suspend or terminate your account if you violate these terms or engage in conduct that we deem harmful to the platform.</p>
        <p class="mt-2"><strong>6. Contact</strong></p>
        <p>For questions about these Terms, contact us at <a href="mailto:support@campusconnect.com" class="text-[#FFD700] hover:underline">support@campusconnect.com</a>.</p>
      </div>
      <button onclick="closeTermsModal()" class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] transition border border-[#FFD700]">Close</button>
    </div>
  </div>

  <!-- Footer Section -->
  <div id="contact">
    <footer class="bg-black py-12 px-4 md:px-12 border-t-2 border-[#FFD700]">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
        <div class="text-center md:text-left">
          <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Get in Touch</h3>
          <p class="text-white mb-4">Email: <a href="mailto:support@campusconnect.com" class="text-white hover:text-[#FFD700] transition">support@campusconnect.com</a></p>
          <p class="text-white mb-4">Phone: <a href="tel:+1234567890" class="text-white hover:text-[#FFD700] transition">+251*******</a></p>
          <div>
            <input type="text" placeholder="Your Name" class="custom-input w-full px-4 py-2 mb-4 border border-[#FFD700] rounded-md bg-gray-800 text-white">
            <input type="email" placeholder="Your Email" class="custom-input w-full px-4 py-2 mb-4 border border-[#FFD700] rounded-md bg-gray-800 text-white">
            <textarea placeholder="Your Message" class="custom-input w-full px-4 py-2 mb-4 border border-[#FFD700] rounded-md bg-gray-800 text-white h-24 resize-y"></textarea>
            <button class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] hover:border-2 hover:border-[#FFD700] transition">
              Send Message
            </button>
          </div>
        </div>
        <div class="text-center">
          <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Explore</h3>
          <ul class="space-y-2">
            <li><a href="homepage.php" class="text-white hover:text-[#FFD700] transition">Home</a></li>
            <li><a href="homepage.php#about" class="text-white hover:text-[#FFD700] transition">About</a></li>
            <li><a href="#" class="text-white hover:text-[#FFD700] transition">Events</a></li>
            <li><a href="#" class="text-white hover:text-[#FFD700] transition">Resources</a></li>
            <li><a href="#contact" class="text-white hover:text-[#FFD700] transition">Contact</a></li>
          </ul>
        </div>
        <div class="text-center md:text-left">
          <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Stay Connected</h3>
          <p class="text-white mb-4">Join our newsletter for the latest updates and events!</p>
          <input type="email" placeholder="Enter your email" class="custom-input w-full px-4 py-2 mb-4 border border-[#FFD700] rounded-md bg-gray-800 text-white">
          <button class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] hover:border-2 hover:border-[#FFD700] transition">
            Subscribe
          </button>
        </div>
      </div>
      <div class="text-center mt-8 pt-6 border-t border-[#FFD700]">
        <div class="social-icons flex justify-center gap-4 mb-4">
          <a href="#"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
          <a href="#"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
          <a href="#"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
          <a href="#"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
        </div>
        <p class="text-[#FFD700] text-lg">Campus Connect: Building Bridges, Igniting Futures</p>
      </div>
    </footer>
  </div>

  <script src="signup.js"></script>
</body>
</html>