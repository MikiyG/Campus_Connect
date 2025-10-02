let pendingProfilePicture = null;
let pendingCoverPhoto = null;

document.addEventListener('DOMContentLoaded', () => {
    // Initialize profile completion
    updateProfileCompletion();

    // Hamburger menu toggle
    const hamburger = document.querySelector('.nav-hamburger');
    const nav = document.querySelector('.nav');
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        nav.classList.toggle('active');
    });

    // Drag-and-drop for profile picture
    const profileDragArea = document.getElementById('profileDragArea');
    const profileFileInput = document.getElementById('profilePictureInput');
    profileDragArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        profileDragArea.classList.add('drag-over');
    });
    profileDragArea.addEventListener('dragleave', () => {
        profileDragArea.classList.remove('drag-over');
    });
    profileDragArea.addEventListener('drop', (e) => {
        e.preventDefault();
        profileDragArea.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        handleImage(file, 'profile');
    });
    profileFileInput.addEventListener('change', () => {
        if (profileFileInput.files && profileFileInput.files[0]) {
            handleImage(profileFileInput.files[0], 'profile');
        }
    });

    // Drag-and-drop for cover photo
    const coverDragArea = document.getElementById('coverDragArea');
    const coverFileInput = document.getElementById('coverPhotoInput');
    coverDragArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        coverDragArea.classList.add('drag-over');
    });
    coverDragArea.addEventListener('dragleave', () => {
        coverDragArea.classList.remove('drag-over');
    });
    coverDragArea.addEventListener('drop', (e) => {
        e.preventDefault();
        coverDragArea.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        handleImage(file, 'cover');
    });
    coverFileInput.addEventListener('change', () => {
        if (coverFileInput.files && coverFileInput.files[0]) {
            handleImage(coverFileInput.files[0], 'cover');
        }
    });
});

function openProfilePictureModal() {
    console.log('Opening profile picture modal');
    document.getElementById('profilePictureModal').classList.add('show');
    const profilePicture = document.getElementById('profilePicture').src;
    document.getElementById('profilePicturePreview').src = profilePicture;
    document.getElementById('confirmProfilePicture').disabled = true;
}

function closeProfilePictureModal() {
    console.log('Closing profile picture modal');
    document.getElementById('profilePictureModal').classList.remove('show');
    document.getElementById('profilePictureInput').value = '';
    document.getElementById('profileDragArea').classList.remove('drag-over');
    pendingProfilePicture = null;
    document.getElementById('confirmProfilePicture').disabled = true;
}

function validateImage(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        console.log('Invalid file type:', file.type);
        alert('Please upload a valid image file (JPG, PNG, GIF).');
        return false;
    }
    if (file.size > maxSize) {
        console.log('File size too large:', file.size);
        alert('Image size must be less than 5MB.');
        return false;
    }
    return true;
}

function handleImage(file, type) {
    if (!file) {
        console.log('No file provided for', type);
        alert('No file selected.');
        return;
    }
    if (validateImage(file)) {
        console.log('Handling image for', type, ':', file.name);
        const reader = new FileReader();
        reader.onload = function(e) {
            if (type === 'profile') {
                document.getElementById('profilePicturePreview').src = e.target.result;
                pendingProfilePicture = file;
                document.getElementById('confirmProfilePicture').disabled = false;
            } else {
                document.getElementById('coverPhotoPreview').src = e.target.result;
                pendingCoverPhoto = file;
                document.getElementById('confirmCoverPhoto').disabled = false;
            }
        };
        reader.readAsDataURL(file);
    }
}

function confirmProfilePicture() {
    if (pendingProfilePicture) {
        console.log('Uploading profile picture:', pendingProfilePicture.name, 'Size:', pendingProfilePicture.size);
        const formData = new FormData();
        formData.append('action', 'upload_profile_picture');
        formData.append('profilePicture', pendingProfilePicture);

        fetch('profile_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            if (data.success) {
                document.getElementById('profilePicture').src = data.path;
                alert(data.message);
                updateProfileCompletion();
                closeProfilePictureModal();
            } else {
                alert(data.message || 'Failed to update profile picture');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert(`An error occurred while uploading the profile picture: ${error.message}`);
        });
    } else {
        console.log('No pending profile picture to upload');
        alert('No file selected for upload');
    }
}

function removeProfilePicture() {
    console.log('Removing profile picture');
    fetch('profile_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove_profile_picture'
    })
    .then(response => {
        if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            document.getElementById('profilePicture').src = data.path;
            document.getElementById('profilePicturePreview').src = data.path;
            document.getElementById('profilePictureInput').value = '';
            document.getElementById('profileDragArea').classList.remove('drag-over');
            pendingProfilePicture = null;
            alert(data.message);
            updateProfileCompletion();
            closeProfilePictureModal();
        } else {
            alert(data.message || 'Failed to remove profile picture');
        }
    })
    .catch(error => {
        console.error('Remove error:', error);
        alert(`An error occurred while removing the profile picture: ${error.message}`);
    });
}

function openCoverPhotoModal() {
    console.log('Opening cover photo modal');
    document.getElementById('coverPhotoModal').classList.add('show');
    const coverPhoto = document.getElementById('coverPhoto').style.backgroundImage;
    document.getElementById('coverPhotoPreview').src = coverPhoto ? coverPhoto.slice(5, -2) : '/assets/default_cover.jpg';
    document.getElementById('confirmCoverPhoto').disabled = true;
}

function closeCoverPhotoModal() {
    console.log('Closing cover photo modal');
    document.getElementById('coverPhotoModal').classList.remove('show');
    document.getElementById('coverPhotoInput').value = '';
    document.getElementById('coverDragArea').classList.remove('drag-over');
    pendingCoverPhoto = null;
    document.getElementById('confirmCoverPhoto').disabled = true;
}

function confirmCoverPhoto() {
    if (pendingCoverPhoto) {
        console.log('Uploading cover photo:', pendingCoverPhoto.name, 'Size:', pendingCoverPhoto.size);
        const formData = new FormData();
        formData.append('action', 'upload_cover_photo');
        formData.append('coverPhoto', pendingCoverPhoto);

        fetch('profile_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            if (data.success) {
                document.getElementById('coverPhoto').style.backgroundImage = `url(${data.path})`;
                alert(data.message);
                updateProfileCompletion();
                closeCoverPhotoModal();
            } else {
                alert(data.message || 'Failed to update cover photo');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert(`An error occurred while uploading the cover photo: ${error.message}`);
        });
    } else {
        console.log('No pending cover photo to upload');
        alert('No file selected for upload');
    }
}

function removeCoverPhoto() {
    console.log('Removing cover photo');
    fetch('profile_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove_cover_photo'
    })
    .then(response => {
        if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            document.getElementById('coverPhoto').style.backgroundImage = `url(${data.path})`;
            document.getElementById('coverPhotoPreview').src = data.path;
            document.getElementById('coverPhotoInput').value = '';
            document.getElementById('coverDragArea').classList.remove('drag-over');
            pendingCoverPhoto = null;
            alert(data.message);
            updateProfileCompletion();
            closeCoverPhotoModal();
        } else {
            alert(data.message || 'Failed to remove cover photo');
        }
    })
    .catch(error => {
        console.error('Remove error:', error);
        alert(`An error occurred while removing the cover photo: ${error.message}`);
    });
}

function saveProfileChanges() {
    const name = document.getElementById('editName').value.trim();
    const batch = document.getElementById('editBatch').value.trim();
    const bio = document.getElementById('editBio').value.trim();
    const interests = document.getElementById('editInterests').value.trim();
    const linkedin = document.getElementById('editLinkedin').value.trim();
    const twitter = document.getElementById('editTwitter').value.trim();

    if (!name) {
        console.log('Validation failed: Full name is required');
        alert('Full name is required');
        return;
    }

    console.log('Saving profile changes:', { name, batch, bio, interests, linkedin, twitter });
    const formData = new FormData();
    formData.append('action', 'update_profile');
    formData.append('full_name', name);
    formData.append('batch', batch);
    formData.append('bio', bio);
    formData.append('interests', interests);
    formData.append('linkedin', linkedin);
    formData.append('twitter', twitter);

    fetch('profile_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            document.getElementById('profileName').textContent = name;
            const batchText = batch ? `Student at ${document.getElementById('profileBatch').textContent.split(' | ')[0].replace('Student at ', '')} | Batch ${batch}` : document.getElementById('profileBatch').textContent;
            document.getElementById('profileBatch').textContent = batchText.includes('Administrator') ? 'Administrator' : batchText;
            document.getElementById('profileMajor').textContent = bio || 'No bio specified';
            document.getElementById('linkedinLink').textContent = linkedin || 'Not provided';
            document.getElementById('linkedinLink').href = linkedin || '#';
            document.getElementById('twitterLink').textContent = twitter || 'Not provided';
            document.getElementById('twitterLink').href = twitter || '#';
            alert(data.message);
            updateProfileCompletion();
        } else {
            alert(data.message || 'Failed to update profile');
        }
    })
    .catch(error => {
        console.error('Profile update error:', error);
        alert(`An error occurred while updating the profile: ${error.message}`);
    });
}

function updateProfileCompletion() {
    const fields = [
        document.getElementById('editName').value,
        document.getElementById('editBatch').value,
        document.getElementById('editBio').value,
        document.getElementById('editInterests').value,
        document.getElementById('editLinkedin').value,
        document.getElementById('editTwitter').value,
        document.getElementById('profilePicture').src !== '/assets/default_profile.jpg',
        document.getElementById('coverPhoto').style.backgroundImage !== 'url(/assets/default_cover.jpg)'
    ];
    const filledFields = fields.filter(field => field && field !== true).length + (fields[6] ? 1 : 0) + (fields[7] ? 1 : 0);
    const totalFields = document.getElementById('editBatch').disabled ? 7 : 8; // Exclude batch for admins
    const completion = Math.round((filledFields / totalFields) * 100);
    console.log('Profile completion:', completion, '%');
    document.getElementById('profileCompletion').textContent = `${completion}%`;
    document.getElementById('completionBar').style.width = `${completion}%`;
}

function openDeleteAccountModal() {
    console.log('Opening delete account modal');
    document.getElementById('deleteAccountModal').classList.add('show');
}

function closeDeleteAccountModal() {
    console.log('Closing delete account modal');
    document.getElementById('deleteAccountModal').classList.remove('show');
}

function confirmDeleteAccount() {
    console.log('Confirming account deletion');
    fetch('profile_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete_account'
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            alert(data.message);
            window.location.href = 'login.php';
        } else {
            alert(data.message || 'Failed to delete account');
        }
    })
    .catch(error => {
        console.error('Delete account error:', error);
        alert(`An error occurred while deleting the account: ${error.message}`);
    });
}