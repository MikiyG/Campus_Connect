function openMessageModal(recipient) {
  document.getElementById('messageModal').classList.add('show');
  document.getElementById('messageRecipient').textContent = recipient;
}

function closeMessageModal() {
  document.getElementById('messageModal').classList.remove('show');
}

function sendMessage() {
  alert('Message sent! (This is a front-end demo.)');
  closeMessageModal();
}

function postUpdate() {
  // Check if tour is active to prevent posting during tour
  if (document.querySelector('.tour-overlay.show')) {
    return;
  }
  const postContent = document.querySelector('.custom-textarea').value;
  if (!postContent.trim()) {
    alert('Please enter some content to post!');
    return;
  }

  fetch('dashboard_process.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `content=${encodeURIComponent(postContent)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Post shared successfully!');
      document.querySelector('.custom-textarea').value = ''; // Clear textarea
      // Optionally, refresh posts dynamically here
    } else {
      alert('Error posting update: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred while posting.');
  });
}

// Typing animation for welcome message
function typeText(fullName) {
  const typedWelcome = document.getElementById('typedWelcome');
  let i = 0;

  function type() {
    if (i < fullName.length) {
      typedWelcome.textContent += fullName.charAt(i);
      i++;
      setTimeout(type, 350); // Adjust speed (100ms per character)
    } else {
      document.querySelector('.cursor').style.display = 'none'; // Hide cursor when done
    }
  }

  type();
}

// Hamburger menu functionality
const navHamburger = document.querySelector('.nav-hamburger');
const nav = document.querySelector('.nav');

if (navHamburger && nav) {
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

window.addEventListener('resize', () => {
  if (window.innerWidth > 768) {
    if (navHamburger && nav) {
      console.log('Window resized, resetting menu');
      navHamburger.classList.remove('active');
      nav.classList.remove('active');
    }
  }
});

// Toggle sidebars in mobile view
document.getElementById('toggleLeftSidebar').addEventListener('click', () => {
  const leftSidebarContent = document.getElementById('leftSidebarContent');
  leftSidebarContent.classList.toggle('hidden');
  document.getElementById('toggleLeftSidebar').textContent = leftSidebarContent.classList.contains('hidden') ? 'Show Sidebar' : 'Hide Sidebar';
});

document.getElementById('toggleRightSidebar').addEventListener('click', () => {
  const rightSidebarContent = document.getElementById('rightSidebarContent');
  rightSidebarContent.classList.toggle('hidden');
  document.getElementById('toggleRightSidebar').textContent = rightSidebarContent.classList.contains('hidden') ? 'Show Sidebar' : 'Hide Sidebar';
});

// Handle placeholder links
document.querySelectorAll('a[href="#"]').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    alert('This feature is coming soon! (This is a front-end demo.)');
  });
});

// Start typing animation on page load with dynamic full_name
window.addEventListener('load', () => {
  const fullName = document.getElementById('typedWelcome').getAttribute('data-fullname');
  typeText(fullName);
});

// Guided Tour Functionality
const tourSteps = [
  {
    element: document.querySelector('.custom-textarea + .button-gold'),
    text: 'Share your thoughts with the community by posting an update here.'
  },
  {
    element: document.querySelector('#leftSidebarContent .button-gold'),
    text: 'Edit your profile to update your personal information.'
  },
  {
    element: document.querySelector('.event-card .button-gold'),
    text: 'RSVP to events to stay updated and participate.'
  },
  {
    element: document.querySelector('.group-card .button-gold'),
    text: 'Join groups to connect with peers sharing similar interests.'
  },
  {
    element: document.querySelector('#rightSidebarContent .button-gold'),
    text: 'View all your messages to stay in touch with connections.'
  },
  {
    element: document.querySelector('#rightSidebarContent .fa-user-plus').parentElement,
    text: 'Connect with peers to expand your network.'
  }
];

let currentTourStep = 0;

function startTour() {
  // Create overlay
  let overlay = document.querySelector('.tour-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.classList.add('tour-overlay');
    document.body.appendChild(overlay);
  }
  overlay.classList.add('show');

  // Create tooltip
  let tooltip = document.querySelector('.tour-tooltip');
  if (!tooltip) {
    tooltip = document.createElement('div');
    tooltip.classList.add('tour-tooltip');
    document.body.appendChild(tooltip);
  }

  // Create arrow
  let arrow = document.querySelector('.tour-arrow');
  if (!arrow) {
    arrow = document.createElement('div');
    arrow.classList.add('tour-arrow');
    document.body.appendChild(arrow);
  }

  currentTourStep = 0;
  showTourStep();
}

function showTourStep() {
  const step = tourSteps[currentTourStep];
  if (!step.element) {
    endTour();
    return;
  }

  // Highlight the current element
  tourSteps.forEach((s, i) => {
    if (s.element) {
      s.element.classList.remove('tour-highlight');
    }
  });
  step.element.classList.add('tour-highlight');

  // Position tooltip and arrow
  const rect = step.element.getBoundingClientRect();
  const tooltip = document.querySelector('.tour-tooltip');
  const arrow = document.querySelector('.tour-arrow');

  tooltip.innerHTML = `
    <p>${step.text}</p>
    <button onclick="nextTourStep()">OK</button>
  `;

  // Position tooltip below the element with a small offset
  const tooltipTop = rect.bottom + window.scrollY + 10;
  const tooltipLeft = rect.left + window.scrollX + (rect.width - tooltip.offsetWidth) / 2;
  tooltip.style.top = `${tooltipTop}px`;
  tooltip.style.left = `${Math.max(10, tooltipLeft)}px`;

  // Position arrow
  const arrowTop = rect.bottom + window.scrollY;
  const arrowLeft = rect.left + window.scrollX + rect.width / 2 - 10; // Center arrow
  arrow.style.top = `${arrowTop}px`;
  arrow.style.left = `${arrowLeft}px`;

  // Ensure element is visible
  step.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function nextTourStep() {
  currentTourStep++;
  if (currentTourStep < tourSteps.length) {
    showTourStep();
  } else {
    endTour();
  }
}

function endTour() {
  const overlay = document.querySelector('.tour-overlay');
  const tooltip = document.querySelector('.tour-tooltip');
  const arrow = document.querySelector('.tour-arrow');

  if (overlay) overlay.classList.remove('show');
  if (tooltip) tooltip.remove();
  if (arrow) arrow.remove();

  tourSteps.forEach(step => {
    if (step.element) {
      step.element.classList.remove('tour-highlight');
    }
  });
}

// Attach tour to Get Started button
document.querySelector('.welcome-banner .button-gold').addEventListener('click', startTour);