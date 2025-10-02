const searchInput = document.getElementById('searchInput');
const contactSearch = document.getElementById('contactSearch');
const toggleContacts = document.getElementById('toggleContacts');
const contactsList = document.getElementById('contactsList');
const newMessageModal = document.getElementById('newMessageModal');
const newMessageSearch = document.getElementById('newMessageSearch');
const newMessageResults = document.getElementById('newMessageResults');
const newMessageText = document.getElementById('newMessageText');
const messageInput = document.getElementById('messageInput');
const chatWindow = document.getElementById('chatWindow');
const messages = document.getElementById('messages');
const chatHeader = document.getElementById('chatHeader');
const typingIndicator = document.getElementById('typingIndicator');
let selectedContactId = null;
let typingTimeout;

function openNewMessageModal() {
    newMessageModal.classList.remove('hidden');
    newMessageSearch.value = '';
    newMessageText.value = '';
    newMessageResults.innerHTML = '';
    newMessageSearch.focus();
}

function closeNewMessageModal() {
    newMessageModal.classList.add('hidden');
}

function selectContact(contactId, contactName) {
    selectedContactId = contactId;
    chatHeader.textContent = `Chat with ${contactName}`;
    messages.innerHTML = '';
    fetchMessages(contactId);
    updateUnreadCount(contactId);
}

function removeContact(contactId, contactName) {
    if (confirm(`Are you sure you want to remove ${contactName} from your contacts?`)) {
        fetch('messages_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove_contact&contact_id=${contactId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`${contactName} has been removed from your contacts!`);
                location.reload();
            } else {
                alert('Error removing contact: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the contact.');
        });
    }
}

function addContact() {
    const email = newMessageSearch.value.trim();
    if (!email) {
        alert('Please enter an email to add a contact.');
        return;
    }
    fetch('messages_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_contact&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Contact added successfully!');
            closeNewMessageModal();
            location.reload();
        } else {
            alert('Error adding contact: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the contact.');
    });
}

function sendMessage() {
    if (!selectedContactId) {
        alert('Please select a contact to send a message.');
        return;
    }
    const message = messageInput.value.trim();
    if (!message) {
        alert('Please enter a message.');
        return;
    }
    fetch('messages_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=send_message&receiver_id=${selectedContactId}&message=${encodeURIComponent(message)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            fetchMessages(selectedContactId);
            updateUnreadCount(selectedContactId);
        } else {
            alert('Error sending message: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the message.');
    });
}

function sendNewMessage() {
    const email = newMessageSearch.value.trim();
    const message = newMessageText.value.trim();
    if (!email || !message) {
        alert('Please enter an email and a message.');
        return;
    }
    fetch('messages_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_contact&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error adding contact: ' + data.message);
            return;
        }
        return fetch('messages_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&receiver_id=${data.user_id}&message=${encodeURIComponent(message)}`
        });
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Message sent successfully!');
            closeNewMessageModal();
            location.reload();
        } else {
            alert('Error sending message: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the message.');
    });
}

function fetchMessages(contactId) {
    fetch('messages_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=fetch_messages&contact_id=${contactId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messages.innerHTML = '';
            data.messages.forEach(msg => {
                const isSent = msg.sender_id === userId;
                const msgDiv = document.createElement('div');
                msgDiv.className = `p-2 rounded-md ${isSent ? 'bg-[#FFD700] text-black ml-auto' : 'bg-gray-700 text-white mr-auto'} max-w-xs`;
                msgDiv.innerHTML = `
                    <p>${msg.message}</p>
                    <p class="text-xs text-gray-400">${new Date(msg.timestamp).toLocaleString()}</p>
                    ${isSent ? '' : (msg.seen ? '<p class="text-xs text-green-400">Seen</p>' : '')}
                `;
                messages.appendChild(msgDiv);
            });
            chatWindow.scrollTop = chatWindow.scrollHeight;
        } else {
            alert('Error fetching messages: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching messages.');
    });
}

function updateUnreadCountOnLoad() {
    fetch('messages_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_unread_count`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const navBadge = document.querySelector('#messagesNav .unread-badge');
            if (data.unread_conversations > 0) {
                if (navBadge) {
                    navBadge.textContent = data.unread_conversations;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'unread-badge inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-black bg-[#FFD700] rounded-full absolute -top-2 -right-2';
                    newBadge.textContent = data.unread_conversations;
                    const messagesNav = document.querySelector('#messagesNav');
                    if (messagesNav) messagesNav.appendChild(newBadge);
                }
            } else if (navBadge) {
                navBadge.remove();
            }
        }
    })
    .catch(error => {
        console.error('Error updating unread count on load:', error);
    });
}

function updateUnreadCount(contactId = null) {
    fetch('messages_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_unread_count${contactId ? `&contact_id=${contactId}` : ''}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (contactId) {
                const badge = document.querySelector(`.contact-card[data-contact-id="${contactId}"] .unread-contact-badge`);
                if (badge) badge.remove();
            }
            const navBadge = document.querySelector('#messagesNav .unread-badge');
            if (data.unread_conversations > 0) {
                if (navBadge) {
                    navBadge.textContent = data.unread_conversations;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'unread-badge inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-black bg-[#FFD700] rounded-full absolute -top-2 -right-2';
                    newBadge.textContent = data.unread_conversations;
                    const messagesNav = document.querySelector('#messagesNav');
                    if (messagesNav) messagesNav.appendChild(newBadge);
                }
            } else if (navBadge) {
                navBadge.remove();
            }
        }
    })
    .catch(error => {
        console.error('Error updating unread count:', error);
    });
}

document.addEventListener('DOMContentLoaded', () => {
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

    newMessageSearch.addEventListener('input', () => {
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            const search = newMessageSearch.value.trim();
            if (search.length < 2) {
                newMessageResults.innerHTML = '<p class="text-gray-400">Enter at least 2 characters to search</p>';
                return;
            }
            fetch('messages_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=search_users&search=${encodeURIComponent(search)}`
            })
            .then(response => response.json())
            .then(data => {
                newMessageResults.innerHTML = '';
                if (data.success && data.users.length > 0) {
                    data.users.forEach(user => {
                        const userDiv = document.createElement('div');
                        userDiv.className = 'p-2 bg-gray-800 rounded-md cursor-pointer hover:bg-gray-700';
                        userDiv.innerHTML = `
                            <p class="font-bold">${user.full_name}</p>
                            <p class="text-sm text-gray-400">${user.email}</p>
                        `;
                        userDiv.onclick = () => {
                            newMessageSearch.value = user.email;
                            newMessageResults.innerHTML = '';
                        };
                        newMessageResults.appendChild(userDiv);
                    });
                } else {
                    newMessageResults.innerHTML = '<p class="text-gray-400">No users found</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                newMessageResults.innerHTML = '<p class="text-red-400">Error searching users</p>';
            });
        }, 500);
    });

    contactSearch.addEventListener('input', () => {
        const search = contactSearch.value.toLowerCase();
        document.querySelectorAll('.contact-card').forEach(card => {
            const name = card.querySelector('p.font-bold').textContent.toLowerCase();
            card.style.display = name.includes(search) ? 'flex' : 'none';
        });
    });

    toggleContacts.addEventListener('click', () => {
        contactsList.classList.toggle('hidden');
    });

    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    updateUnreadCountOnLoad();
});