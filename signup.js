// signup.js
let pendingIdPicture = null;

function validateImage(file) {
  const maxSize = 5 * 1024 * 1024; // 5MB
  if (!file.type.startsWith('image/')) {
    alert('Please upload a valid image file (e.g., JPG, PNG).');
    return false;
  }
  if (file.size > maxSize) {
    alert('Image size must be less than 5MB.');
    return false;
  }
  return true;
}

function handleImage(file) {
  if (validateImage(file)) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('idPicturePreview');
      preview.src = e.target.result;
      preview.classList.remove('hidden');
      pendingIdPicture = e.target.result;
      document.getElementById('submitButton').disabled = false;
    };
    reader.readAsDataURL(file);
  }
}

const dragArea = document.getElementById('dragArea');
dragArea.addEventListener('dragover', (e) => {
  e.preventDefault();
  dragArea.classList.add('drag-over');
});
dragArea.addEventListener('dragleave', () => {
  dragArea.classList.remove('drag-over');
});
dragArea.addEventListener('drop', (e) => {
  e.preventDefault();
  dragArea.classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  handleImage(file);
});

document.getElementById('idPicture').addEventListener('change', () => {
  if (document.getElementById('idPicture').files && document.getElementById('idPicture').files[0]) {
    handleImage(document.getElementById('idPicture').files[0]);
  }
});

document.getElementById('signupForm').addEventListener('submit', function(event) {
  event.preventDefault();
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirmPassword').value;
  if (password !== confirmPassword) {
    alert('Passwords do not match!');
    return;
  }
  if (!pendingIdPicture) {
    alert('Please upload a student ID picture.');
    return;
  }

  const formData = new FormData(document.getElementById('signupForm'));

  // Use relative path for PHP development server
  fetch('signup_process.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log('Response Status:', response.status);
    console.log('Response Headers:', [...response.headers.entries()]);
    return response.text().then(text => {
      console.log('Raw Response:', text);
      try {
        return JSON.parse(text);
      } catch (e) {
        throw new Error('Invalid JSON: ' + text);
      }
    });
  })
  .then(data => {
    if (data.success) {
      document.getElementById('confirmationModal').classList.add('show');
      document.getElementById('signupForm').reset();
      document.getElementById('idPicturePreview').classList.add('hidden');
      document.getElementById('submitButton').disabled = true;
      pendingIdPicture = null;
    } else {
      alert(data.message);
    }
  })
  .catch(error => {
    console.error('Fetch Error:', error);
    alert('An error occurred: ' + error.message);
  });
});

function closeConfirmationModal() {
  document.getElementById('confirmationModal').classList.remove('show');
}

function openTermsModal() {
  document.getElementById('termsModal').classList.add('show');
}

function closeTermsModal() {
  document.getElementById('termsModal').classList.remove('show');
}

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

window.addEventListener('resize', () => {
  if (window.innerWidth > 768) {
    if (navHamburger) {
      navHamburger.classList.remove('active');
      nav.classList.remove('active');
    }
  }
});