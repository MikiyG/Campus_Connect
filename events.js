document.addEventListener("DOMContentLoaded", function () {
  console.log('events.js loaded');

  // Animate event cards
  const eventCards = document.querySelectorAll('.event-card');
  eventCards.forEach((card, i) => {
    setTimeout(() => {
      card.classList.add('slideIn');
    }, i * 100);
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

  // Mobile search toggle
  const searchToggle = document.querySelector('.search-toggle');
  const searchInput = document.querySelector('.search-input');
  if (searchToggle && searchInput) {
    searchToggle.addEventListener('click', () => {
      console.log('Search toggle clicked');
      searchInput.classList.toggle('active');
      if (searchInput.classList.contains('active')) {
        searchInput.focus();
      }
    });
  } else {
    console.error('Search toggle or input not found');
  }

  // Toggle filters in mobile view
  const toggleFilters = document.getElementById('toggleFilters');
  const filtersContent = document.getElementById('filtersContent');
  if (toggleFilters && filtersContent) {
    toggleFilters.addEventListener('click', () => {
      console.log('Filters toggled');
      filtersContent.classList.toggle('hidden');
      toggleFilters.textContent = filtersContent.classList.contains('hidden') ? 'Show Filters' : 'Hide Filters';
    });
  } else {
    console.error('Toggle filters or filters content not found');
  }

  // Client-side search and filter
  const eventSearch = document.getElementById('eventSearch');
  const filterCategory = document.getElementById('filterCategory');
  const filterDate = document.getElementById('filterDate');
  const filterLocation = document.getElementById('filterLocation');
  const filterAttendance = document.getElementById('filterAttendance');
  const listView = document.getElementById('listView');

  function applyFilters() {
    console.log('Applying filters');
    if (!listView) {
      console.error('List view not found');
      return;
    }
    const searchTerm = eventSearch?.value.toLowerCase() || '';
    const category = filterCategory?.value.toLowerCase() || '';
    const dateFilter = filterDate?.value.toLowerCase() || '';
    const location = filterLocation?.value.toLowerCase() || '';
    const attendance = filterAttendance?.value.toLowerCase() || '';

    const cards = listView.querySelectorAll('.event-card');
    cards.forEach(card => {
      const eventName = card.querySelector('h3')?.textContent.toLowerCase() || '';
      const eventCategory = card.dataset.category || '';
      const eventDate = new Date(card.dataset.date || '');
      const eventLocation = card.dataset.location || '';
      const eventAttendance = card.dataset.attendance || '';

      let show = true;
      if (searchTerm && !eventName.includes(searchTerm)) show = false;
      if (category && eventCategory !== category) show = false;
      if (dateFilter) {
        const now = new Date();
        if (dateFilter === 'upcoming' && eventDate < now) show = false;
        if (dateFilter === 'past' && eventDate >= now) show = false;
      }
      if (location && eventLocation !== location) show = false;
      if (attendance && eventAttendance !== attendance) show = false;

      card.classList.toggle('hidden', !show);
    });
  }

  if (eventSearch) eventSearch.addEventListener('input', applyFilters);
  if (filterCategory) filterCategory.addEventListener('change', applyFilters);
  if (filterDate) filterDate.addEventListener('change', applyFilters);
  if (filterLocation) filterLocation.addEventListener('change', applyFilters);
  if (filterAttendance) filterAttendance.addEventListener('change', applyFilters);

  // Handle create event form submission
  const createEventForm = document.getElementById('createEventForm');
  if (createEventForm) {
    createEventForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      console.log('Create event form submitted');
      const formData = new FormData(createEventForm);
      for (let [key, value] of formData.entries()) {
        console.log(`FormData: ${key}=${value}`);
      }
      try {
        const response = await fetch('events_process.php', {
          method: 'POST',
          body: formData
        });
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Create event response:', data);
        if (data.success) {
          alert('Event created successfully!');
          closeCreateEventModal();
          window.location.reload();
        } else {
          console.error('Error creating event:', data.message);
          alert('Error creating event: ' + data.message);
        }
      } catch (error) {
        console.error('Error submitting create event form:', error);
        alert('Error submitting form: ' + error.message);
      }
    });
  } else {
    console.error('Create event form not found');
  }

  // Form submission for contact and newsletter
  const contactForm = document.getElementById('contactForm');
  const newsletterForm = document.getElementById('newsletterForm');

  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      console.log('Contact form submitted');
      alert('Contact form submitted! (Backend processing needed)');
      contactForm.reset();
    });
  }

  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      console.log('Newsletter form submitted');
      alert('Newsletter subscription submitted! (Backend processing needed)');
      newsletterForm.reset();
    });
  }

  // Handle placeholder links
  document.querySelectorAll('a[href="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      console.log('Placeholder link clicked');
      alert('This feature is coming soon!');
    });
  });

  // Countdown for featured event
  const countdownElement = document.getElementById('countdown');
  if (countdownElement) {
    const eventDate = new Date(countdownElement.dataset.eventDate);
    function updateCountdown() {
      const now = new Date();
      const timeLeft = eventDate - now;
      if (timeLeft <= 0) {
        countdownElement.textContent = 'Event started!';
        return;
      }
      const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
      const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
      countdownElement.textContent = `Starts in ${days}d ${hours}h ${minutes}m`;
    }
    updateCountdown();
    setInterval(updateCountdown, 60000);
  }
});

function openCreateEventModal() {
  console.log('Opening create event modal');
  const modal = document.getElementById('createEventModal');
  if (modal) {
    modal.classList.remove('hidden');
  } else {
    console.error('Create event modal not found');
    alert('Error: Create event modal not found');
  }
}

function closeCreateEventModal() {
  console.log('Closing create event modal');
  const modal = document.getElementById('createEventModal');
  if (modal) {
    modal.classList.add('hidden');
    const form = document.getElementById('createEventForm');
    if (form) form.reset();
  }
}

function openEventDetailsModal(eventId) {
  console.log('Fetching event details for ID:', eventId);
  const modal = document.getElementById('eventDetailsModal');
  const removeButton = document.getElementById('eventDetailsRemove');
  if (!modal || !removeButton) {
    console.error('Event details modal or remove button not found');
    alert('Error: Event details modal not found');
    return;
  }
  fetch(`events_process.php?action=get_event&event_id=${eventId}`, {
    method: 'GET',
    headers: { 'Accept': 'application/json' }
  })
  .then(response => {
    console.log('Event details response status:', response.status);
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    return response.json();
  })
  .then(data => {
    console.log('Event details response:', data);
    if (data.success) {
      const userId = document.body.dataset.userId;
      const fields = ['Title', 'Image', 'Description', 'Date', 'Time', 'Location', 'Organizer'];
      fields.forEach(field => {
        const element = document.getElementById(`eventDetails${field}`);
        if (!element) {
          console.error(`Element eventDetails${field} not found`);
        } else {
          if (field === 'Image') {
            element.src = data.event[field.toLowerCase()] || '/assets/default_event.jpg';
          } else if (field === 'Date') {
            element.textContent = `Date: ${data.event[field.toLowerCase()] ? new Date(data.event[field.toLowerCase()]).toLocaleDateString() : 'N/A'}`;
          } else if (field === 'Time') {
            element.textContent = `Time: ${data.event[field.toLowerCase()] ? new Date(`1970-01-01T${data.event[field.toLowerCase()]}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'N/A'}`;
          } else {
            element.textContent = field === 'Organizer' ? `Organized by: ${data.event[field.toLowerCase()] || 'Unknown'}` : data.event[field.toLowerCase()] || 'N/A';
          }
        }
      });
      removeButton.classList.toggle('hidden', data.event.creator_id != userId);
      if (data.event.creator_id == userId) {
        removeButton.onclick = () => removeEvent(eventId);
      }
      modal.classList.remove('hidden');
    } else {
      console.error('Error loading event details:', data.message);
      alert('Error loading event details: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error fetching event details:', error);
    alert('Error fetching event details: ' + error.message);
  });
}

function closeEventDetailsModal() {
  console.log('Closing event details modal');
  const modal = document.getElementById('eventDetailsModal');
  if (modal) {
    modal.classList.add('hidden');
  }
}

function rsvpEvent(eventId) {
  console.log('RSVPing to event ID:', eventId);
  const userId = document.body.dataset.userId;
  if (!userId || isNaN(userId)) {
    console.error('User ID not found or invalid in document.body.dataset');
    alert('Error: Please log in again');
    return;
  }
  const csrfToken = document.querySelector('input[name="csrf_token"]').value;
  if (!csrfToken) {
    console.error('CSRF token not found');
    alert('Error: CSRF token missing');
    return;
  }
  const formData = new FormData();
  formData.append('action', 'rsvp_event');
  formData.append('event_id', eventId);
  formData.append('user_id', userId);
  formData.append('csrf_token', csrfToken);
  fetch('events_process.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log('RSVP response status:', response.status);
    return response.json();
  })
  .then(data => {
    console.log('RSVP event response:', data);
    if (data.success) {
      alert('RSVP successful!');
      window.location.reload();
    } else {
      console.error('Error RSVPing:', data.message);
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error RSVPing to event:', error);
    alert('Error: ' + error.message);
  });
}

function cancelRsvp(eventId) {
  console.log('Cancelling RSVP for event ID:', eventId);
  if (!confirm('Are you sure you want to cancel your RSVP for this event?')) return;
  const userId = document.body.dataset.userId;
  if (!userId || isNaN(userId)) {
    console.error('User ID not found or invalid in document.body.dataset');
    alert('Error: Please log in again');
    return;
  }
  const csrfToken = document.querySelector('input[name="csrf_token"]').value;
  if (!csrfToken) {
    console.error('CSRF token not found');
    alert('Error: CSRF token missing');
    return;
  }
  const formData = new FormData();
  formData.append('action', 'cancel_rsvp');
  formData.append('event_id', eventId);
  formData.append('user_id', userId);
  formData.append('csrf_token', csrfToken);
  fetch('events_process.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log('Cancel RSVP response status:', response.status);
    return response.json();
  })
  .then(data => {
    console.log('Cancel RSVP response:', data);
    if (data.success) {
      alert('RSVP cancelled successfully!');
      window.location.reload();
    } else {
      console.error('Error cancelling RSVP:', data.message);
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error cancelling RSVP:', error);
    alert('Error: ' + error.message);
  });
}

function removeEvent(eventId) {
  console.log('Removing event ID:', eventId);
  if (!confirm('Are you sure you want to remove this event? This action cannot be undone.')) return;
  const userId = document.body.dataset.userId;
  if (!userId || isNaN(userId)) {
    console.error('User ID not found or invalid in document.body.dataset');
    alert('Error: Please log in again');
    return;
  }
  const csrfToken = document.querySelector('input[name="csrf_token"]').value;
  if (!csrfToken) {
    console.error('CSRF token not found');
    alert('Error: CSRF token missing');
    return;
  }
  const formData = new FormData();
  formData.append('action', 'remove_event');
  formData.append('event_id', eventId);
  formData.append('user_id', userId);
  formData.append('csrf_token', csrfToken);
  fetch('events_process.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log('Remove event response status:', response.status);
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    return response.json();
  })
  .then(data => {
    console.log('Remove event response:', data);
    if (data.success) {
      alert('Event removed successfully!');
      window.location.reload();
    } else {
      console.error('Error removing event:', data.message);
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error removing event:', error);
    alert('Error: ' + error.message);
  });
}

function viewEvent(eventId) {
  console.log('View event called for ID:', eventId);
  openEventDetailsModal(eventId);
}

function showListView() {
  console.log('Showing list view');
  const listView = document.getElementById('listView');
  const calendarView = document.getElementById('calendarView');
  if (listView && calendarView) {
    listView.classList.remove('hidden');
    calendarView.classList.add('hidden');
  }
}

function showCalendarView() {
  console.log('Showing calendar view');
  const listView = document.getElementById('listView');
  const calendarView = document.getElementById('calendarView');
  if (listView && calendarView) {
    listView.classList.add('hidden');
    calendarView.classList.remove('hidden');
  }
}