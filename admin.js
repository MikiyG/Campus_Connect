function typeText() {
    const typedWelcome = document.getElementById('typedWelcome');
    const typedWelcomeMobile = document.getElementById('typedWelcomeMobile');
    const text = `Welcome, ${typedWelcome ? typedWelcome.getAttribute('data-username') : 'Admin'}`;

    function type(element) {
        let i = 0;
        function typeChar() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(typeChar, 100);
            } else {
                const cursor = element.nextElementSibling;
                if (cursor && cursor.classList.contains('cursor')) {
                    cursor.remove();
                }
            }
        }
        typeChar();
    }

    if (typedWelcome) type(typedWelcome);
    if (typedWelcomeMobile) type(typedWelcomeMobile);
}

// Hamburger menu
const navHamburger = document.querySelector('.nav-hamburger');
const nav = document.querySelector('.nav');

function updateNavLinks() {
    if (window.innerWidth <= 768) {
        nav.innerHTML = `
            <a href="homepage.php" class="font-medium text-white hover:text-[#FFD700] transition" onclick="closeNav()">Home</a>
            <a href="admin.php" class="font-medium text-[#FFD700] transition" onclick="closeNav()">Admin</a>
            <a href="events.php" class="font-medium text-white hover:text-[#FFD700] transition" onclick="closeNav()">Events</a>
            <a href="logout.php" class="font-medium text-white hover:text-[#FFD700] transition" onclick="closeNav()">Logout</a>
        `;
    } else {
        nav.innerHTML = `
            <a href="homepage.php" class="font-medium text-white hover:text-[#FFD700] transition">Home</a>
            <a href="admin.php" class="font-medium text-[#FFD700] transition">Admin</a>
            <a href="events.php" class="font-medium text-white hover:text-[#FFD700] transition">Events</a>
            <a href="logout.php" class="font-medium text-white hover:text-[#FFD700] transition">Logout</a>
        `;
    }
}

function closeNav() {
    if (window.innerWidth <= 768) {
        navHamburger.classList.remove('active');
        nav.classList.remove('active');
    }
}

if (navHamburger && nav) {
    navHamburger.addEventListener('click', () => {
        navHamburger.classList.toggle('active');
        nav.classList.toggle('active');
    });
}

window.addEventListener('resize', () => {
    updateNavLinks();
    if (window.innerWidth > 768) {
        if (navHamburger && nav) {
            navHamburger.classList.remove('active');
            nav.classList.remove('active');
        }
    }
});

// Placeholder links
document.querySelectorAll('a[href="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        if (!link.getAttribute('onclick')) {
            alert('This feature is coming soon!');
        }
    });
});

// Tab switching
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabId).classList.remove('hidden');
    document.querySelector(`button[onclick="switchTab('${tabId}')"]`)?.classList.add('active');
    if (tabId === 'pending') fetchPendingUsers();
    if (tabId === 'users') fetchUsers();
    if (tabId === 'universities') fetchUniversities();
    if (tabId === 'groups') fetchGroups();
    if (tabId === 'events') fetchEvents();
    if (tabId === 'reports') {} // Reports are static
}

// Modal
function showModal(title, message) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('confirmationModal').classList.add('show');
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').classList.remove('show');
}

// Fetch pending users
function fetchPendingUsers() {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_pending_users'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populatePendingTable(data.data);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to fetch pending users');
    });
}

function populatePendingTable(data) {
    const table = document.getElementById('pendingTable');
    table.innerHTML = '';
    document.getElementById('pendingCount').textContent = data.length;
    data.forEach(user => {
        const row = document.createElement('tr');
        row.classList.add('table-row', 'bg-gray-900', 'hover:bg-gray-700');
        row.innerHTML = `
            <td class="p-3">${user.id}</td>
            <td class="p-3">${user.name}</td>
            <td class="p-3">${user.email}</td>
            <td class="p-3">${user.university}</td>
            <td class="p-3">${user.student_id}</td>
            <td class="p-3">${user.batch}</td>
            <td class="p-3"><img src="${user.id_picture}" alt="ID" class="id-preview w-16 h-16 object-cover rounded cursor-pointer" onclick="window.open('${user.id_picture}', '_blank')"></td>
            <td class="p-3">${user.status}</td>
            <td class="p-3">${user.role}</td>
            <td class="p-3 flex gap-2">
                <button onclick="approveUser(${user.id})" class="glow-button bg-green-600 text-white px-3 py-1 rounded hover:bg-green-500">Approve</button>
                <button onclick="rejectUser(${user.id})" class="glow-button bg-red-600 text-white px-3 py-1 rounded hover:bg-red-500">Reject</button>
                <button onclick="editUser(${user.id}, '${user.name}', '${user.email}', '${user.university}', '${user.student_id}', ${user.batch}, '${user.role}', 'pending')" class="glow-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-500">Edit</button>
            </td>
        `;
        table.appendChild(row);
    });
}

function approveUser(id) {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=approve_user&userId=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showModal('User Approved', data.message);
            fetchPendingUsers();
            fetchUsers();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to approve user');
    });
}

function rejectUser(id) {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=reject_user&userId=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showModal('User Rejected', data.message);
            fetchPendingUsers();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to reject user');
    });
}

// Fetch approved users
function fetchUsers() {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_users'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateUsersTable(data.data);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to fetch users');
    });
}

function populateUsersTable(data) {
    const table = document.getElementById('usersTable');
    table.innerHTML = '';
    document.getElementById('userCount').textContent = data.length;
    data.forEach(user => {
        const row = document.createElement('tr');
        row.classList.add('table-row', 'bg-gray-900', 'hover:bg-gray-700');
        row.innerHTML = `
            <td class="p-3">${user.id}</td>
            <td class="p-3">${user.name}</td>
            <td class="p-3">${user.email}</td>
            <td class="p-3">${user.university}</td>
            <td class="p-3">${user.student_id}</td>
            <td class="p-3">${user.batch}</td>
            <td class="p-3"><img src="${user.id_picture}" alt="ID" class="id-preview w-16 h-16 object-cover rounded cursor-pointer" onclick="window.open('${user.id_picture}', '_blank')"></td>
            <td class="p-3">${user.status}</td>
            <td class="p-3">${user.role}</td>
            <td class="p-3 flex gap-2">
                <button onclick="editUser(${user.id}, '${user.name}', '${user.email}', '${user.university}', '${user.student_id}', ${user.batch}, '${user.role}', 'approved')" class="glow-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-500">Edit</button>
                <button onclick="deleteUser(${user.id})" class="glow-button bg-red-600 text-white px-3 py-1 rounded hover:bg-red-500">Delete</button>
            </td>
        `;
        table.appendChild(row);
    });
}

function editUser(id, name, email, university, student_id, batch, role, status) {
    const newName = prompt('Enter new name:', name);
    const newEmail = prompt('Enter new email:', email);
    const newStudentId = prompt('Enter new student ID:', student_id);
    const newBatch = prompt('Enter new batch:', batch);
    const newRole = prompt('Enter new role (student, admin):', role);

    if (newName && newEmail && newStudentId && newBatch && newRole &&
        ['student', 'admin'].includes(newRole)) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=edit_user&userId=${id}&name=${encodeURIComponent(newName)}&email=${encodeURIComponent(newEmail)}&student_id=${encodeURIComponent(newStudentId)}&batch=${newBatch}&status=${status}&role=${newRole}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showModal('User Edited', data.message);
                fetchPendingUsers();
                fetchUsers();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to edit user');
        });
    } else {
        alert('Invalid input. No changes made.');
    }
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_user&userId=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showModal('User Deleted', data.message);
                fetchPendingUsers();
                fetchUsers();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete user');
        });
    }
}

// Fetch universities
function fetchUniversities() {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_universities'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateUniversitiesTable(data.data);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to fetch universities');
    });
}

function populateUniversitiesTable(data) {
    const table = document.getElementById('universitiesTable');
    table.innerHTML = '';
    document.getElementById('universityCount').textContent = data.length;
    data.forEach(university => {
        const row = document.createElement('tr');
        row.classList.add('table-row', 'bg-gray-900', 'hover:bg-gray-700');
        row.innerHTML = `
            <td class="p-3">${university.id}</td>
            <td class="p-3">${university.name}</td>
            <td class="p-3">${university.code}</td>
            <td class="p-3 flex gap-2">
                <button onclick="editUniversity(${university.id})" class="glow-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-500">Edit</button>
                <button onclick="deleteUniversity(${university.id})" class="glow-button bg-red-600 text-white px-3 py-1 rounded hover:bg-red-500">Delete</button>
            </td>
        `;
        table.appendChild(row);
    });
}

function addUniversity() {
    const name = prompt('Enter university name:');
    const code = prompt('Enter university code:');
    if (name && code) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add_university&name=${encodeURIComponent(name)}&code=${encodeURIComponent(code)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showModal('University Added', data.message);
                fetchUniversities();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add university');
        });
    } else {
        alert('Invalid input. No changes made.');
    }
}

function editUniversity(id) {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_university&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const univ = data.data;
            const newName = prompt('Enter new name:', univ.name);
            const newCode = prompt('Enter new code:', univ.code);
            if (newName && newCode) {
                fetch('admin_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=edit_university&id=${id}&name=${encodeURIComponent(newName)}&code=${encodeURIComponent(newCode)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showModal('University Edited', data.message);
                        fetchUniversities();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to edit university');
                });
            } else {
                alert('Invalid input. No changes made.');
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to fetch university details');
    });
}

function deleteUniversity(id) {
    if (confirm('Are you sure you want to delete this university?')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_university&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showModal('University Deleted', data.message);
                fetchUniversities();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete university');
        });
    }
}

// Fetch groups
function fetchGroups() {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_groups'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateGroupsTable(data.data);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to fetch groups');
    });
}

function populateGroupsTable(data) {
    const table = document.getElementById('groupsTable');
    table.innerHTML = '';
    document.getElementById('groupCount').textContent = data.length;
    data.forEach(group => {
        const row = document.createElement('tr');
        row.classList.add('table-row', 'bg-gray-900', 'hover:bg-gray-700');
        row.innerHTML = `
            <td class="p-3">${group.id}</td>
            <td class="p-3">${group.name}</td>
            <td class="p-3">${group.description}</td>
            <td class="p-3 flex gap-2">
                <button onclick="editGroup(${group.id})" class="glow-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-500">Edit</button>
                <button onclick="deleteGroup(${group.id})" class="glow-button bg-red-600 text-white px-3 py-1 rounded hover:bg-red-500">Delete</button>
            </td>
        `;
        table.appendChild(row);
    });
}

function editGroup(id) {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_group&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const group = data.data;
            const newName = prompt('Enter new name:', group.name);
            const newDesc = prompt('Enter new description:', group.description);
            if (newName && newDesc) {
                fetch('admin_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=edit_group&id=${id}&name=${encodeURIComponent(newName)}&description=${encodeURIComponent(newDesc)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showModal('Group Edited', data.message);
                        fetchGroups();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to edit group');
                });
            } else {
                alert('Invalid input. No changes made.');
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to fetch group details');
    });
}

function deleteGroup(id) {
    if (confirm('Are you sure you want to delete this group?')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_group&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showModal('Group Deleted', data.message);
                fetchGroups();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete group');
        });
    }
}

// Fetch events
function fetchEvents() {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_events'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateEventsTable(data.data);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to fetch events');
    });
}

function populateEventsTable(data) {
    const table = document.getElementById('eventsTable');
    table.innerHTML = '';
    document.getElementById('eventCount').textContent = data.length;
    data.forEach(event => {
        const row = document.createElement('tr');
        row.classList.add('table-row', 'bg-gray-900', 'hover:bg-gray-700');
        row.innerHTML = `
            <td class="p-3">${event.id}</td>
            <td class="p-3">${event.title}</td>
            <td class="p-3">${event.description}</td>
            <td class="p-3">${event.date}</td>
            <td class="p-3 flex gap-2">
                <button onclick="editEvent(${event.id})" class="glow-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-500">Edit</button>
                <button onclick="deleteEvent(${event.id})" class="glow-button bg-red-600 text-white px-3 py-1 rounded hover:bg-red-500">Delete</button>
            </td>
        `;
        table.appendChild(row);
    });
}

function editEvent(id) {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_event&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const event = data.data;
            const newTitle = prompt('Enter new title:', event.title);
            const newDesc = prompt('Enter new description:', event.description);
            const newDate = prompt('Enter new date (YYYY-MM-DD):', event.date);
            if (newTitle && newDesc && newDate) {
                fetch('admin_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=edit_event&id=${id}&title=${encodeURIComponent(newTitle)}&description=${encodeURIComponent(newDesc)}&date=${encodeURIComponent(newDate)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showModal('Event Edited', data.message);
                        fetchEvents();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to edit event');
                });
            } else {
                alert('Invalid input. No changes made.');
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to fetch event details');
    });
}

function deleteEvent(id) {
    if (confirm('Are you sure you want to delete this event?')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_event&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showModal('Event Deleted', data.message);
                fetchEvents();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete event');
        });
    }
}

function generateReport(type) {
    fetch('admin_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=generate_report&type=${type}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const reportContent = document.querySelector('#reports .report-content');
            reportContent.innerHTML = '';
            let html = `<h3 class="text-lg font-bold text-[#FFD700] mb-4">${getReportTitle(type)} (Generated on ${new Date().toLocaleString()})</h3>`;
            html += '<table class="w-full text-left border-collapse"><thead><tr class="bg-gray-800">';
            // START OF ADDED CODE: Handle table headers and rows for new report types
            if (type === 'user_activity') {
                html += '<th class="p-3">Name</th><th class="p-3">Posts</th><th class="p-3">Messages</th><th class="p-3">Groups Joined</th><th class="p-3">Events RSVPed</th>';
            } else if (type === 'event_participation') {
                html += '<th class="p-3">Title</th><th class="p-3">Participants</th>';
            } else if (type === 'university_distribution') {
                html += '<th class="p-3">University</th><th class="p-3">User Count</th>';
            } else if (type === 'signup_trends') {
                html += '<th class="p-3">Month</th><th class="p-3">Signups</th>';
            } else if (type === 'popular_groups') {
                html += '<th class="p-3">Group Name</th><th class="p-3">Members</th>';
            } else if (type === 'active_users') {
                html += '<th class="p-3">User Name</th><th class="p-3">Total Activity</th>';
            } else if (type === 'pending_reports') {
                html += '<th class="p-3">Report ID</th><th class="p-3">Reporter</th><th class="p-3">Reported</th><th class="p-3">Type</th><th class="p-3">Reason</th>';
            }
            html += '</tr></thead><tbody>';
            data.data.forEach(item => {
                html += '<tr class="bg-gray-900 hover:bg-gray-700">';
                if (type === 'user_activity') {
                    html += `<td class="p-3">${item.full_name}</td><td class="p-3">${item.posts}</td><td class="p-3">${item.messages}</td><td class="p-3">${item.groups_joined}</td><td class="p-3">${item.events_rsvped}</td>`;
                } else if (type === 'event_participation') {
                    html += `<td class="p-3">${item.title}</td><td class="p-3">${item.participants}</td>`;
                } else if (type === 'university_distribution') {
                    html += `<td class="p-3">${item.university}</td><td class="p-3">${item.user_count}</td>`;
                } else if (type === 'signup_trends') {
                    html += `<td class="p-3">${item.month}</td><td class="p-3">${item.signups}</td>`;
                } else if (type === 'popular_groups') {
                    html += `<td class="p-3">${item.name}</td><td class="p-3">${item.members_count}</td>`;
                } else if (type === 'active_users') {
                    html += `<td class="p-3">${item.full_name}</td><td class="p-3">${item.total_activity}</td>`;
                } else if (type === 'pending_reports') {
                    html += `<td class="p-3">${item.id}</td><td class="p-3">${item.reporter}</td><td class="p-3">${item.reported}</td><td class="p-3">${item.type}</td><td class="p-3">${item.reason}</td>`;
                }
                html += '</tr>';
            });
            // END OF ADDED CODE
            html += '</tbody></table>';
            reportContent.innerHTML = html;
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to generate report');
    });
}

// START OF ADDED CODE: Helper function for report titles
function getReportTitle(type) {
    const titles = {
        'user_activity': 'User Activity Report',
        'event_participation': 'Event Participation Report',
        'university_distribution': 'University User Distribution Report',
        'signup_trends': 'Signup Trends Report',
        'popular_groups': 'Popular Groups Report',
        'active_users': 'Active Users Report',
        'pending_reports': 'Pending Reports Report'
    };
    return titles[type] || 'Unknown Report';
}
// END OF ADDED CODE

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    if (!document.getElementById('pending').classList.contains('hidden')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_pending_users'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const filtered = data.data.filter(u => 
                    u.name.toLowerCase().includes(query) || 
                    u.email.toLowerCase().includes(query) ||
                    u.student_id.toLowerCase().includes(query)
                );
                populatePendingTable(filtered);
            }
        });
    } else if (!document.getElementById('users').classList.contains('hidden')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_users'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const filtered = data.data.filter(u => 
                    u.name.toLowerCase().includes(query) || 
                    u.email.toLowerCase().includes(query) ||
                    u.student_id.toLowerCase().includes(query)
                );
                populateUsersTable(filtered);
            }
        });
    } else if (!document.getElementById('universities').classList.contains('hidden')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_universities'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const filtered = data.data.filter(u => 
                    u.name.toLowerCase().includes(query) || 
                    u.code.toLowerCase().includes(query)
                );
                populateUniversitiesTable(filtered);
            }
        });
    } else if (!document.getElementById('groups').classList.contains('hidden')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_groups'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const filtered = data.data.filter(g => 
                    g.name.toLowerCase().includes(query) || 
                    g.description.toLowerCase().includes(query)
                );
                populateGroupsTable(filtered);
            }
        });
    } else if (!document.getElementById('events').classList.contains('hidden')) {
        fetch('admin_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_events'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const filtered = data.data.filter(e => 
                    e.title.toLowerCase().includes(query) || 
                    e.description.toLowerCase().includes(query)
                );
                populateEventsTable(filtered);
            }
        });
    }
});

// Initialize
updateNavLinks();
window.addEventListener('load', () => {
    typeText();
    switchTab('pending');
});