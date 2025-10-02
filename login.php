<?php
session_start();
require_once 'config.php';

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['status']) && $_SESSION['status'] === 'approved') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
        exit;
    } else {
        header('Location: dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect - Log In</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="login.css">
</head>
<body class="bg-black text-white">
  <!-- Top Social Media + Search -->
  <div class="top-bar flex justify-end items-center p-4 md:p-6 bg-black gap-4">
    <div class="social-icons flex gap-4">
      <a href="#"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
      <a href="#"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
      <a href="#"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
      <a href="#"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
    </div>
    <div>
      <input type="text" placeholder="Search..." class="custom-input px-3 py-1 rounded-full border-none outline-none bg-gray-800 text-white focus:ring-2 focus:ring-[#FFD700]">
    </div>
  </div>

  <!-- Header with logo + navigation -->
  <div class="header flex justify-between items-center p-4 md:p-6 bg-black border-b-2 border-[#FFD700]">
    <div class="logo text-3xl font-bold text-[#FFD700]">Campus Connect</div>
    <div class="nav-hamburger flex-col justify-between w-8 h-5 cursor-pointer md:hidden z-[1000]">
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
      <span class="w-full h-[3px] bg-[#FFD700] transition-all"></span>
    </div>
    <nav class="nav flex gap-4">
      <a href="homepage.php" class="font-medium text-white hover:text-[#FFD700] transition">Home</a>
      <a href="#" class="font-medium text-white hover:text-[#FFD700] transition">About</a>
      <a href="#" class="font-medium text-white hover:text-[#FFD700] transition">Contact</a>
    </nav>
  </div>

  <!-- Login Section -->
  <section class="login-section relative min-h-[600px] bg-[url('/assets/login_background.jpg')] bg-center bg-cover flex items-center justify-center text-center py-12 md:py-16">
    <div class="relative z-10 bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
      <h2 class="text-3xl font-bold text-center text-[#FFD700] mb-6">Log In to Campus Connect</h2>
      <?php if (isset($_SESSION['login_error'])): ?>
        <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?></p>
      <?php endif; ?>
      <form id="login-form" action="login_process.php" method="POST" class="space-y-6">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
          <input type="email" id="email" name="email" required
                 class="custom-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:border-[#FFD700]"
                 placeholder="Enter your email">
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
          <input type="password" id="password" name="password" required
                 class="custom-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:border-[#FFD700]"
                 placeholder="Enter your password">
        </div>
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input type="checkbox" id="remember" name="remember"
                   class="h-4 w-4 text-[#FFD700] focus:ring-[#FFD700] border-gray-300 rounded">
            <label for="remember" class="ml-2 block text-sm text-gray-600">Remember me</label>
          </div>
          <a href="#" class="text-sm text-[#FFD700] hover:underline">Forgot Password?</a>
        </div>
        <button type="submit"
                class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] hover:border-2 hover:border-[#FFD700] focus:outline-none focus:ring-2 focus:ring-[#FFD700] focus:ring-offset-2 transition">
          Log In
        </button>
      </form>
      <p class="mt-6 text-center text-sm text-gray-600">
        Don't have an account? <a href="signup.php" class="text-[#FFD700] hover:underline">Sign Up</a>
      </p>
      <p class="mt-2 text-center text-sm text-gray-600">
        <a href="homepage.php" class="text-[#FFD700] hover:underline">Back to Home</a>
      </p>
    </div>
  </section>

  <!-- Footer Section -->
  <footer class="bg-black py-12 px-4 md:px-12 border-t-2 border-[#FFD700]">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
      <div class="text-center md:text-left">
        <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Get in Touch</h3>
        <p class="text-white mb-4">Email: <a href="mailto:support@campusconnect.com" class="text-white hover:text-[#FFD700] transition">support@campusconnect.com</a></p>
        <p class="text-white mb-4">Phone: <a href="tel:+1234567890" class="text-white hover:text-[#FFD700] transition">+251********</a></p>
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
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">About</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">Contact</a></li>
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

  <script src="login.js"></script>
</body>
</html>