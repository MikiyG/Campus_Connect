document.addEventListener('DOMContentLoaded', () => {
    console.log('login.js loaded at', new Date().toLocaleString()); // Debug: Confirm script load time

    // Login form submission
    document.getElementById('login-form').addEventListener('submit', (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // Basic client-side validation
        if (!email || !password) {
            alert('Please fill in both email and password.');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            alert('Please enter a valid email address.');
            return;
        }

        // Submit form via fetch
        fetch('login_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.role === 'admin') {
                    window.location.href = 'admin.php';
                } else {
                    window.location.href = 'dashboard.php';
                }
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });

    // Hamburger menu
    const navHamburger = document.querySelector('.nav-hamburger');
    const nav = document.querySelector('.nav');
    if (!navHamburger || !nav) {
        console.error('Hamburger or nav not found');
    } else {
        navHamburger.addEventListener('click', () => {
            console.log('Hamburger clicked');
            navHamburger.classList.toggle('active');
            nav.classList.toggle('active');
        });

        document.querySelectorAll('.nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    console.log('Nav link clicked, closing menu');
                    navHamburger.classList.remove('active');
                    nav.classList.remove('active');
                }
            });
        });
    }

    // Close menu on resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && nav && navHamburger) {
            console.log('Window resized, resetting menu');
            navHamburger.classList.remove('active');
            nav.classList.remove('active');
        }
    });

    // Forgot Password link
    const forgotPasswordLink = document.querySelector('#login-form a[href="#"]');
    if (forgotPasswordLink) {
        console.log('Forgot Password link found:', forgotPasswordLink); // Debug: Confirm link is found
        forgotPasswordLink.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Forgot Password link clicked at', new Date().toLocaleString());
            alert('Please contact your school\'s admin.');
        });
    } else {
        console.error('Forgot Password link not found in #login-form');
        // Debug: Log all links in login form to diagnose
        const allLinks = document.querySelectorAll('#login-form a');
        console.log('All links in #login-form:', allLinks);
    }
});