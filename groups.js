document.addEventListener("DOMContentLoaded", function () {
  console.log('groups.js loaded');

  // Hamburger menu toggle
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

  // Close menu on resize
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
      if (navHamburger && nav) {
        console.log('Window resized, resetting menu');
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
      console.log('Search toggle clicked');
      searchInput.classList.toggle('hidden');
      if (!searchInput.classList.contains('hidden')) {
        searchInput.focus();
      }
    });
  }

  // Toggle filters in mobile view
  const toggleFilters = document.getElementById('toggleFilters');
  const filtersContent = document.getElementById('filtersContent');
  if (toggleFilters && filtersContent) {
    toggleFilters.addEventListener('click', () => {
      console.log('Filters toggled');
      filtersContent.classList.toggle('hidden');
      toggleFilters.textContent = filtersContent.classList.contains('hidden') ? 'Toggle Filters' : 'Hide Filters';
    });
  }

  // Client-side search and filter
  const groupSearch = document.getElementById('groupSearch');
  const filterCategory = document.getElementById('filterCategory');
  const filterMembers = document.getElementById('filterMembers');
  const filterPrivacy = document.getElementById('filterPrivacy');
  const groupsList = document.getElementById('groupsList');

  window.applyFilters = function() {
    console.log('Applying filters');
    const searchTerm = groupSearch.value.toLowerCase();
    const category = filterCategory.value.toLowerCase();
    const members = filterMembers.value;
    const privacy = filterPrivacy.value.toLowerCase();

    const cards = groupsList.querySelectorAll('.group-card');
    cards.forEach(card => {
      const groupName = card.querySelector('h3').textContent.toLowerCase();
      const groupCategory = card.dataset.category;
      const groupMembers = parseInt(card.dataset.members);
      const groupPrivacy = card.dataset.privacy;

      let show = true;
      if (searchTerm && !groupName.includes(searchTerm)) {
        show = false;
      }
      if (category && groupCategory !== category) {
        show = false;
      }
      if (members) {
        if (members === 'small' && groupMembers >= 50) show = false;
        if (members === 'medium' && (groupMembers < 50 || groupMembers > 100)) show = false;
        if (members === 'large' && groupMembers <= 100) show = false;
      }
      if (privacy && groupPrivacy !== privacy) {
        show = false;
      }

      card.style.display = show ? 'block' : 'none';
    });
  };

  if (groupSearch) groupSearch.addEventListener('input', applyFilters);
  if (filterCategory) filterCategory.addEventListener('change', applyFilters);
  if (filterMembers) filterMembers.addEventListener('change', applyFilters);
  if (filterPrivacy) filterPrivacy.addEventListener('change', applyFilters);

  // Form submission for contact and newsletter (stubbed)
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

  // Handle create group form submission
  const createGroupForm = document.getElementById('createGroupForm');
  if (createGroupForm) {
    createGroupForm.addEventListener('submit', (e) => {
      e.preventDefault();
      console.log('Create group form submitted');
      
      const formData = new FormData(createGroupForm);
      
      fetch('groups_process.php', {
        method: 'POST',
        body: formData
      })
        .then(response => {
          if (!response.ok) throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
          return response.json();
        })
        .then(data => {
          console.log('Create group response:', data);
          if (data.success) {
            alert('Group created successfully!');
            closeCreateGroupModal();
            window.location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error creating group:', error);
          alert('Error: Failed to create group - ' + error.message);
        });
    });
  }

  // Handle chat form submission
  const chatForm = document.getElementById('chatForm');
  if (chatForm) {
    chatForm.addEventListener('submit', (e) => {
      e.preventDefault();
      console.log('Chat form submitted');
      
      const formData = new FormData(chatForm);
      
      fetch('groups_process.php', {
        method: 'POST',
        body: formData
      })
        .then(response => {
          if (!response.ok) throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
          return response.json();
        })
        .then(data => {
          console.log('Send message response:', data);
          if (data.success) {
            appendMessage(data.message_data);
            chatForm.reset();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error sending message:', error);
          alert('Error: Failed to send message - ' + error.message);
        });
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
});

// Modal functions
function openCreateGroupModal() {
  console.log('Opening create group modal');
  const modal = document.getElementById('createGroupModal');
  if (modal) {
    modal.classList.remove('hidden');
  } else {
    console.error('Create group modal not found');
  }
}

function closeCreateGroupModal() {
  console.log('Closing create group modal');
  const modal = document.getElementById('createGroupModal');
  if (modal) {
    modal.classList.add('hidden');
    document.getElementById('createGroupForm').reset();
  }
}

let messagePollingInterval = null;
let currentGroupId = null;

function openGroupChat(groupId) {
  console.log('Opening group chat for ID:', groupId);
  if (!groupId || isNaN(groupId)) {
    console.error('Invalid group ID:', groupId);
    alert('Error: Invalid group ID');
    return;
  }
  openGroupDetailsModal(groupId, true);
}

function openGroupDetailsModal(groupId, openChat = false) {
  console.log('Fetching group details for ID:', groupId);
  if (!groupId || isNaN(groupId)) {
    console.error('Invalid group ID:', groupId);
    alert('Error: Invalid group ID');
    return;
  }
  currentGroupId = groupId;

  fetch(`groups_process.php?action=get_group&group_id=${groupId}`, {
    method: 'GET',
    headers: {
      'Accept': 'application/json'
    }
  })
    .then(response => {
      console.log('Response status:', response.status, response.statusText);
      if (!response.ok) {
        return response.text().then(text => {
          console.error('Response text:', text);
          throw new Error(`Network response was not ok: ${response.status} ${response.statusText} - Response: ${text}`);
        });
      }
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        return response.text().then(text => {
          console.error('Non-JSON response:', text);
          throw new Error('Received non-JSON response from server');
        });
      }
      return response.json();
    })
    .then(data => {
      console.log('Group details response:', data);
      if (data.success) {
        const modal = document.getElementById('groupDetailsModal');
        if (!modal) {
          console.error('Group details modal not found');
          alert('Error: Modal not found');
          return;
        }
        const title = document.getElementById('groupDetailsTitle');
        const image = document.getElementById('groupDetailsImage');
        const description = document.getElementById('groupDetailsDescription');
        const creator = document.getElementById('groupDetailsCreator');
        const members = document.getElementById('groupDetailsMembers');
        const category = document.getElementById('groupDetailsCategory');
        const privacy = document.getElementById('groupDetailsPrivacy');
        const removeButton = document.getElementById('groupDetailsRemove');
        const chatSection = document.getElementById('groupChatSection');
        const chatGroupId = document.getElementById('chatGroupId');
        const addUserBtn = document.getElementById('addUserBtn');

        if (!title || !image || !description || !creator || !members || !category || !privacy || !removeButton || !chatSection || !chatGroupId || !addUserBtn) {
          console.error('One or more modal elements not found');
          alert('Error: Modal elements missing');
          return;
        }

        title.textContent = data.group.name || 'Unnamed Group';
        image.src = data.group.image || '/assets/default_group.jpg';
        description.textContent = data.group.description || 'No description';
        creator.textContent = `Created by: ${data.group.creator_name || 'Unknown'}`;
        members.textContent = `Members: ${data.group.members_count || 0}`;
        category.textContent = `Category: ${data.group.category || 'None'}`;
        privacy.textContent = `Privacy: ${data.group.privacy || 'Unknown'}`;
        const userId = document.body.dataset.userId;
        if (data.group.creator_id == userId) {
          removeButton.classList.remove('hidden');
          removeButton.onclick = () => removeGroup(groupId);
          if (data.group.privacy === 'private') {
            addUserBtn.classList.remove('hidden');
            addUserBtn.onclick = () => openAddUserModal(groupId);
          } else {
            addUserBtn.classList.add('hidden');
          }
        } else {
          removeButton.classList.add('hidden');
          addUserBtn.classList.add('hidden');
        }
        if (data.group.is_member) {
          chatSection.classList.remove('hidden');
          chatGroupId.value = groupId;
          loadMessages(groupId);
          if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
          }
          messagePollingInterval = setInterval(() => loadMessages(groupId), 5000);
        } else {
          chatSection.classList.add('hidden');
        }
        modal.classList.remove('hidden');
        if (openChat && data.group.is_member) {
          chatSection.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        console.error('Error loading group details:', data.message);
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error fetching group details:', error);
      alert(`Error: Failed to load group details - ${error.message}`);
    });
}

function closeGroupDetailsModal() {
  console.log('Closing group details modal');
  const modal = document.getElementById('groupDetailsModal');
  if (modal) {
    modal.classList.add('hidden');
    document.getElementById('chatMessages').innerHTML = '';
    if (messagePollingInterval) {
      clearInterval(messagePollingInterval);
      messagePollingInterval = null;
    }
  }
}

function openAddUserModal(groupId) {
  console.log('Opening add user modal for group ID:', groupId);
  if (!groupId || isNaN(groupId)) {
    console.error('Invalid group ID:', groupId);
    alert('Error: Invalid group ID');
    return;
  }
  const modal = document.getElementById('addUserModal');
  const addUserForm = document.getElementById('addUserForm');
  const addUserGroupId = document.getElementById('addUserGroupId');
  if (!modal || !addUserForm || !addUserGroupId) {
    console.error('Add user modal or elements not found');
    alert('Error: Modal not found');
    return;
  }
  addUserGroupId.value = groupId;
  modal.classList.remove('hidden');
}

function closeAddUserModal() {
  console.log('Closing add user modal');
  const modal = document.getElementById('addUserModal');
  if (modal) {
    modal.classList.add('hidden');
    document.getElementById('addUserForm').reset();
  }
}

function addUserToGroup(groupId) {
  console.log('Adding user to group ID:', groupId);
  if (!groupId || isNaN(groupId)) {
    console.error('Invalid group ID:', groupId);
    alert('Error: Invalid group ID');
    return;
  }
  const emailInput = document.getElementById('userEmail');
  const email = emailInput.value.trim();
  if (!email) {
    alert('Please enter an email address');
    return;
  }
  const formData = new FormData();
  formData.append('action', 'add_user_to_group');
  formData.append('group_id', groupId);
  formData.append('email', email);
  const csrfToken = document.querySelector('input[name="csrf_token"]').value;
  if (!csrfToken) {
    console.error('CSRF token not found');
    alert('Error: CSRF token missing');
    return;
  }
  formData.append('csrf_token', csrfToken);

  fetch('groups_process.php', {
    method: 'POST',
    body: formData
  })
    .then(response => {
      console.log('Add user response status:', response.status, response.statusText);
      if (!response.ok) {
        return response.text().then(text => {
          console.error('Response text:', text);
          throw new Error(`Network response was not ok: ${response.status} ${response.statusText} - Response: ${text}`);
        });
      }
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        return response.text().then(text => {
          console.error('Non-JSON response:', text);
          throw new Error('Received non-JSON response from server');
        });
      }
      return response.json();
    })
    .then(data => {
      console.log('Add user response:', data);
      if (data.success) {
        alert(data.message);
        closeAddUserModal();
        setTimeout(() => {
          openGroupDetailsModal(parseInt(groupId));
        }, 500);
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error adding user:', error);
      alert(`Error: Failed to add user - ${error.message}`);
    });
}

function loadMessages(groupId) {
  console.log('Loading messages for group ID:', groupId);
  if (!groupId || isNaN(groupId)) {
    console.error('Invalid group ID for messages:', groupId);
    return;
  }
  fetch(`groups_process.php?action=get_messages&group_id=${groupId}`)
    .then(response => {
      if (!response.ok) throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
      return response.json();
    })
    .then(data => {
      console.log('Get messages response:', data);
      if (data.success) {
        const chatMessages = document.getElementById('chatMessages');
        if (!chatMessages) {
          console.error('Chat messages container not found');
          return;
        }
        chatMessages.innerHTML = '';
        data.messages.forEach(message => appendMessage(message));
        chatMessages.scrollTop = chatMessages.scrollHeight;
      } else {
        console.error('Error loading messages:', data.message);
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error fetching messages:', error);
      alert('Error: Failed to load messages - ' + error.message);
    });
}

function appendMessage(message) {
  console.log('Appending message:', message);
  const chatMessages = document.getElementById('chatMessages');
  if (!chatMessages) {
    console.error('Chat messages container not found');
    return;
  }
  const messageElement = document.createElement('div');
  messageElement.classList.add('p-2', 'border-b', 'border-gray-700');
  const time = new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  let content = `<strong>${message.full_name}</strong> (${time}): `;
  if (message.message) {
    content += message.message;
  }
  if (message.file_path) {
    const fileExt = message.file_path.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
      content += `<br><a href="${message.file_path}" target="_blank"><img src="${message.file_path}" alt="Uploaded image" class="max-w-[100px] mt-2 rounded" loading="lazy"></a>`;
    } else if (fileExt === 'pdf') {
      content += `<br><a href="${message.file_path}" target="_blank" class="text-[#FFD700] underline">View PDF</a>`;
    }
  }
  messageElement.innerHTML = content;
  chatMessages.appendChild(messageElement);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

function viewGroup(groupId) {
  console.log('Viewing group ID:', groupId);
  openGroupDetailsModal(groupId);
}

function joinGroup(groupId) {
  console.log('Joining group ID:', groupId);
  if (!groupId || isNaN(groupId)) {
    console.error('Invalid group ID:', groupId);
    alert('Error: Invalid group ID');
    return;
  }
  const card = document.querySelector(`.group-card[data-group-id="${groupId}"]`);
  if (!card) {
    console.error('Group card not found for ID:', groupId);
    alert('Error: Group not found');
    return;
  }
  const privacy = card.dataset.privacy;
  if (privacy === 'private') {
    alert('This is a private group. The creator must add you.');
    return;
  }
  const formData = new FormData();
  formData.append('action', 'join_group');
  formData.append('group_id', groupId);
  formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

  fetch('groups_process.php', {
    method: 'POST',
    body: formData
  })
    .then(response => {
      if (!response.ok) throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
      return response.json();
    })
    .then(data => {
      console.log('Join group response:', data);
      if (data.success) {
        alert('Joined group successfully!');
        window.location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error joining group:', error);
      alert('Error: Failed to join group - ' + error.message);
    });
}

function leaveGroup(groupId) {
  console.log('Leaving group ID:', groupId);
  if (!groupId || isNaN(groupId)) {
    console.error('Invalid group ID:', groupId);
    alert('Error: Invalid group ID');
    return;
  }
  if (!confirm('Are you sure you want to leave this group?')) {
    return;
  }
  const formData = new FormData();
  formData.append('action', 'leave_group');
  formData.append('group_id', groupId);
  formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

  fetch('groups_process.php', {
    method: 'POST',
    body: formData
  })
    .then(response => {
      if (!response.ok) throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
      return response.json();
    })
    .then(data => {
      console.log('Leave group response:', data);
      if (data.success) {
        alert('Left group successfully!');
        window.location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error leaving group:', error);
      alert('Error: Failed to leave group - ' + error.message);
    });
}

function removeGroup(groupId) {
  console.log('Removing group ID:', groupId);
  if (!groupId || isNaN(groupId)) {
    console.error('Invalid group ID:', groupId);
    alert('Error: Invalid group ID');
    return;
  }
  if (!confirm('Are you sure you want to delete this group? This action cannot be undone.')) {
    return;
  }
  const formData = new FormData();
  formData.append('action', 'remove_group');
  formData.append('group_id', groupId);
  formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

  fetch('groups_process.php', {
    method: 'POST',
    body: formData
  })
    .then(response => {
      if (!response.ok) throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
      return response.json();
    })
    .then(data => {
      console.log('Remove group response:', data);
      if (data.success) {
        alert('Group deleted successfully!');
        window.location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error removing group:', error);
      alert('Error: Failed to delete group - ' + error.message);
    });
}