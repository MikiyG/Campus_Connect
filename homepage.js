document.addEventListener("DOMContentLoaded", function () {
  // Typing effect for hero h1
  const text = "Thrive in Your School";
  const target = document.querySelector(".hero-content h1");
  target.textContent = "";
  let i = 0;

  function typeEffect() {
    if (i < text.length) {
      target.textContent += text.charAt(i);
      i++;
      setTimeout(typeEffect, 100);
    }
  }

  typeEffect();

  // Feature items animation
  const boxes = document.querySelectorAll('.feature-item');
  boxes.forEach((box, i) => {
    box.style.opacity = 0;
    box.style.transform = 'translateY(30px) scale(0.95)';
    box.style.transition = 'transform 0.6s ease-out, opacity 0.6s ease-out';
    setTimeout(() => {
      box.style.opacity = 1;
      box.style.transform = 'translateY(0) scale(1)';
    }, i * 200);
  });

  // Hamburger menu
  const navHamburger = document.querySelector('.nav-hamburger');
  const nav = document.querySelector('.nav');

  if (navHamburger) {
    navHamburger.addEventListener('click', () => {
      navHamburger.classList.toggle('active');
      nav.classList.toggle('active');
    });

    document.querySelectorAll('.nav a').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          navHamburger.classList.remove('active');
          nav.classList.remove('active');
        }
      });
    });
  }

  // Close menu on resize
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
      if (navHamburger) {
        navHamburger.classList.remove('active');
        nav.classList.remove('active');
      }
    }
  });

  // Mobile search toggle
  const searchToggle = document.querySelector('.search-toggle');
  const searchInput = document.querySelector('.search-input');
  if (searchToggle && searchInput) {
    searchToggle.addEventListener('click', () => {
      searchInput.classList.toggle('active');
      if (searchInput.classList.contains('active')) {
        searchInput.focus();
      }
    });
  }

  // Form submission (stubbed)
  const contactForm = document.getElementById('contactForm');
  const newsletterForm = document.getElementById('newsletterForm');

  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      alert('Contact form submitted! (Backend processing needed)');
      contactForm.reset();
    });
  }

  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      alert('Newsletter subscription submitted! (Backend processing needed)');
      newsletterForm.reset();
    });
  }
});