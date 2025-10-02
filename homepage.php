<?php
session_start();
require_once 'config.php';

// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['status']) && $_SESSION['status'] === 'approved') {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="homepage.css">
</head>
<body>
  <!-- Top Social Media + Search -->
  <div class="top-bar">
    <div class="social-icons">
      <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
      <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
      <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
      <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
    </div>
    <div class="search-bar">
      <button class="search-toggle md:hidden" aria-label="Toggle search"><i class="fas fa-search"></i></button>
      <input type="text" placeholder="Search..." class="search-input">
    </div>
  </div>

  <!-- Header -->
  <header class="header">
    <div class="logo">Campus Connect</div>
    <nav class="nav">
      <a href="homepage.php" aria-label="Home">Home</a>
      <a href="#about" aria-label="About">About</a>
      <a href="signup.php" aria-label="Sign Up">Sign-Up</a>
      <a href="login.php" aria-label="Login">Login</a>
      <a href="#contact" aria-label="Contact">Contact</a>
    </nav>
    <div class="nav-hamburger">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <h1>Thrive in Your School</h1>
      <p>Discover Your Community, Ignite Your Future</p>
      <a href="signup.php" class="cta-button" aria-label="Join Now">Join Your Campus Now</a>
    </div>
  </section>

  <!-- Why Join Section -->
  <section class="why-join" id="about">
    <h2>Why Join Campus Connect?!</h2>
    <p class="intro">Campus Connect is your gateway to a vibrant student community where you can network, collaborate, and grow. Whether you're seeking academic support, social connections, or career opportunities, our platform empowers you to make the most of your campus experience.</p>
    <div class="features-grid">
      <div class="feature-item">
        <img src="/assets/group_of_workers_networking.jpg" alt="Networking with Peers" loading="lazy">
        <h3>Networking with Peers</h3>
        <p>Connect with classmates, seniors, and students from other departments.<br>Share resources, study tips, and experiences.</p>
      </div>
      <div class="feature-item">
        <img src="/assets/studygroups.jpg" alt="Academic Collaboration" loading="lazy">
        <h3>Academic Collaboration</h3>
        <p>Form study groups or project teams.<br>Exchange notes, assignments, or past exam questions.<br>Ask and answer subject-specific questions quickly.</p>
      </div>
      <div class="feature-item">
        <img src="/assets/resources.png" alt="Access to Resources" loading="lazy">
        <h3>Access to Resources</h3>
        <p>Find information about courses, events, and campus activities.<br>Access student-run content like tutorials, guides, or e-books.</p>
      </div>
      <div class="feature-item">
        <img src="/assets/opportunity.jpg" alt="Opportunities & Events" loading="lazy">
        <h3>Opportunities & Events</h3>
        <p>Stay updated on workshops, competitions, and internships.<br>Get alerts about campus clubs, events, or job fairs.</p>
      </div>
      <div class="feature-item">
        <img src="/assets/communitybuilding.jpg" alt="Social & Community Building" loading="lazy">
        <h3>Social & Community Building</h3>
        <p>Build friendships in a safe, student-centered environment.<br>Discuss common interests like hobbies, sports, or tech.</p>
      </div>
      <div class="feature-item">
        <img src="/assets/fishjumping.jpg" alt="Career & Skill Development" loading="lazy">
        <h3>Career & Skill Development</h3>
        <p>Connect with alumni for mentorship.<br>Discover opportunities for skill-building like coding contests, hackathons, or online courses.</p>
      </div>
    </div>
  </section>

  <!-- Quote Section -->
  <section class="quote-section">
    <div class="quote-content">
      <p>Learning thrives when connections are made—students grow faster when they share, collaborate, and engage with peers.</p>
    </div>
  </section>

  <!-- Footer Section -->
  <footer class="bg-black py-12 px-4 md:px-12 border-t-2 border-[#FFD700]" id="contact">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
      <div class="text-center md:text-left">
        <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Get in Touch</h3>
        <p class="text-white mb-4">Email: <a href="mailto:support@campusconnect.com" class="text-white hover:text-[#FFD700] transition">support@campusconnect.com</a></p>
        <p class="text-white mb-4">Phone: <a href="tel:+1234567890" class="text-white hover:text-[#FFD700] transition">+251*******</a></p>
        <form id="contactForm">
          <input type="text" name="name" placeholder="Your Name" class="custom-input w-full px-4 py-2 mb-4 border border-[#FFD700] rounded-md bg-gray-800 text-white" required>
          <input type="email" name="email" placeholder="Your Email" class="custom-input w-full px-4 py-2 mb-4 border border-[#FFD700] rounded-md bg-gray-800 text-white" required>
          <textarea name="message" placeholder="Your Message" class="custom-input w-full px-4 py-2 mb-4 border border-[#FFD700] rounded-md bg-gray-800 text-white h-24 resize-y" required></textarea>
          <button type="submit" class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] hover:border-2 hover:border-[#FFD700] transition" aria-label="Send Message">Send Message</button>
        </form>
      </div>
      <div class="text-center">
        <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Explore</h3>
        <ul class="space-y-2">
          <li><a href="homepage.php" class="text-white hover:text-[#FFD700] transition">Home</a></li>
          <li><a href="#about" class="text-white hover:text-[#FFD700] transition">About</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">Events</a></li>
          <li><a href="#" class="text-white hover:text-[#FFD700] transition">Resources</a></li>
          <li><a href="#contact" class="text-white hover:text-[#FFD700] transition">Contact</a></li>
        </ul>
      </div>
      <div class="text-center md:text-left">
        <h3 class="text-2xl font-bold text-[#FFD700] mb-4">Stay Connected</h3>
        <p class="text-white mb-4">Join our newsletter for the latest updates and events!</p>
        <form id="newsletterForm">
          <input type="email" name="email" placeholder="Enter your email" class="custom-input w-full px-4 py-2 mb-4 border border-[#FFD700] rounded-md bg-gray-800 text-white" required>
          <button type="submit" class="w-full bg-[#FFD700] text-black py-2 px-4 rounded-md hover:bg-black hover:text-[#FFD700] hover:border-2 hover:border-[#FFD700] transition" aria-label="Subscribe">Subscribe</button>
        </form>
      </div>
    </div>
    <div class="text-center mt-8 pt-6 border-t border-[#FFD700]">
      <div class="social-icons flex justify-center gap-4 mb-4">
        <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i><span style="display:none;">FB</span></a>
        <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i><span style="display:none;">TW</span></a>
        <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i><span style="display:none;">IG</span></a>
        <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i><span style="display:none;">YT</span></a>
      </div>
      <p class="text-[#FFD700] text-lg">Campus Connect: Building Bridges, Igniting Futures</p>
    </div>
  </footer>

  <script src="homepage.js"></script>
</body>
</html>