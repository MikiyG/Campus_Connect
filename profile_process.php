<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
    error_log('Unauthorized access attempt. Session user_id: ' . ($_SESSION['user_id'] ?? 'not set') . ', status: ' . ($_SESSION['status'] ?? 'not set'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check user role
$stmt = $conn->prepare("SELECT role, university_id, student_id, batch FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    error_log('User not found for ID: ' . $user_id);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch user data
    $stmt = $conn->prepare("SELECT full_name, email, university_id, batch, bio, interests, linkedin, twitter, profile_picture, cover_photo, role 
                            FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch university name
    $university_name = null;
    if ($user_data['university_id']) {
        $stmt = $conn->prepare("SELECT name FROM universities WHERE id = ?");
        $stmt->bind_param('i', $user_data['university_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $university_name = $row['name'];
        }
        $stmt->close();
    }

    $user_data['university_name'] = $university_name;

    // Fetch recent activity
    $activities = [];
    // Posts
    $stmt = $conn->prepare("SELECT content, timestamp FROM posts WHERE user_id = ? ORDER BY timestamp DESC LIMIT 3");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $activities[] = ['type' => 'post', 'content' => $row['content'], 'timestamp' => $row['timestamp']];
    }
    $stmt->close();

    // Event RSVPs
    $stmt = $conn->prepare("SELECT e.title, er.rsvp_at AS timestamp 
                            FROM event_rsvps er 
                            JOIN events e ON er.event_id = e.id 
                            WHERE er.user_id = ? ORDER BY er.rsvp_at DESC LIMIT 3");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $activities[] = ['type' => 'rsvp', 'content' => "RSVP'd to {$row['title']}", 'timestamp' => $row['timestamp']];
    }
    $stmt->close();

    // Group memberships
    $stmt = $conn->prepare("SELECT g.name, gm.joined_at AS timestamp 
                            FROM group_members gm 
                            JOIN groups g ON gm.group_id = g.id 
                            WHERE gm.user_id = ? ORDER BY gm.joined_at DESC LIMIT 3");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $activities[] = ['type' => 'group', 'content' => "Joined {$row['name']}", 'timestamp' => $row['timestamp']];
    }
    $stmt->close();

    usort($activities, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    $activities = array_slice($activities, 0, 3);

    echo json_encode(['success' => true, 'user' => $user_data, 'activities' => $activities]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $batch = trim($_POST['batch'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $interests = trim($_POST['interests'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');
        $twitter = trim($_POST['twitter'] ?? '');

        if (empty($full_name)) {
            error_log('Profile update failed: Full name is required');
            echo json_encode(['success' => false, 'message' => 'Full name is required']);
            exit;
        }

        // For students, ensure batch is provided if role is student
        if ($user['role'] === 'student' && (empty($batch) || empty($user['university_id']) || empty($user['student_id']))) {
            error_log('Profile update failed for user ID ' . $user_id . ': Students must provide batch, university, and student ID');
            echo json_encode(['success' => false, 'message' => 'Students must provide batch, university, and student ID']);
            exit;
        }

        // For admins, set batch to NULL
        $batch = $user['role'] === 'admin' ? null : $batch;

        $stmt = $conn->prepare("UPDATE users SET full_name = ?, batch = ?, bio = ?, interests = ?, linkedin = ?, twitter = ? WHERE id = ?");
        $stmt->bind_param('ssssssi', $full_name, $batch, $bio, $interests, $linkedin, $twitter, $user_id);
        if ($stmt->execute()) {
            error_log('Profile updated successfully for user ID ' . $user_id);
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            error_log('Profile update failed for user ID ' . $user_id . ': ' . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
        $stmt->close();
    } elseif ($action === 'upload_profile_picture' || $action === 'upload_cover_photo') {
        $field = $action === 'upload_profile_picture' ? 'profile_picture' : 'cover_photo';
        $input_name = $action === 'upload_profile_picture' ? 'profilePicture' : 'coverPhoto';
        $upload_dir = 'Uploads/';
        
        // Ensure upload directory exists and is writable
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                error_log('Failed to create Uploads directory');
                echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
                exit;
            }
        }
        if (!is_writable($upload_dir)) {
            error_log('Uploads directory is not writable');
            echo json_encode(['success' => false, 'message' => 'Upload directory is not writable']);
            exit;
        }

        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$input_name];
            $max_size = 5 * 1024 * 1024; // 5MB
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

            if (!in_array($file['type'], $allowed_types)) {
                error_log('Invalid file type for ' . $input_name . ': ' . $file['type']);
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, or GIF allowed.']);
                exit;
            }
            if ($file['size'] > $max_size) {
                error_log('File size too large for ' . $input_name . ': ' . $file['size']);
                echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
                exit;
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_') . '.' . $ext;
            $destination = $upload_dir . $filename;

            // Delete old image if it exists and is not default
            $stmt = $conn->prepare("SELECT $field FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old_image = $result->fetch_assoc()[$field];
            $stmt->close();
            
            if ($old_image && $old_image !== ($field === 'profile_picture' ? '/assets/default_profile.jpg' : '/assets/default_cover.jpg')) {
                if (file_exists($old_image)) {
                    if (!unlink($old_image)) {
                        error_log('Failed to delete old image: ' . $old_image);
                    }
                }
            }

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $conn->prepare("UPDATE users SET $field = ? WHERE id = ?");
                $stmt->bind_param('si', $destination, $user_id);
                if ($stmt->execute()) {
                    error_log(ucfirst(str_replace('_', ' ', $field)) . ' updated successfully for user ID ' . $user_id . ': ' . $destination);
                    echo json_encode(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated successfully', 'path' => $destination]);
                } else {
                    error_log('Failed to update ' . $field . ' for user ID ' . $user_id . ': ' . $conn->error);
                    echo json_encode(['success' => false, 'message' => 'Failed to update database']);
                }
                $stmt->close();
            } else {
                error_log('Failed to move uploaded file to ' . $destination . ': ' . error_get_last()['message']);
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            }
        } else {
            $error_code = $_FILES[$input_name]['error'] ?? UPLOAD_ERR_NO_FILE;
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $error_message = $error_messages[$error_code] ?? 'Unknown upload error';
            error_log('File upload error for ' . $input_name . ': ' . $error_message);
            echo json_encode(['success' => false, 'message' => $error_message]);
        }
    } elseif ($action === 'remove_profile_picture' || $action === 'remove_cover_photo') {
        $field = $action === 'remove_profile_picture' ? 'profile_picture' : 'cover_photo';
        $default = $field === 'profile_picture' ? '/assets/default_profile.jpg' : '/assets/default_cover.jpg';
        $stmt = $conn->prepare("SELECT $field FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_image = $result->fetch_assoc()[$field];
        $stmt->close();

        if ($old_image && $old_image !== $default && file_exists($old_image)) {
            if (!unlink($old_image)) {
                error_log('Failed to delete old image: ' . $old_image);
            }
        }

        $stmt = $conn->prepare("UPDATE users SET $field = ? WHERE id = ?");
        $stmt->bind_param('si', $default, $user_id);
        if ($stmt->execute()) {
            error_log(ucfirst(str_replace('_', ' ', $field)) . ' removed successfully for user ID ' . $user_id);
            echo json_encode(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' removed successfully', 'path' => $default]);
        } else {
            error_log('Failed to remove ' . $field . ' for user ID ' . $user_id . ': ' . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Failed to remove ' . str_replace('_', ' ', $field)]);
        }
        $stmt->close();
    } elseif ($action === 'delete_account') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            error_log('Account deleted successfully for user ID ' . $user_id);
            session_unset();
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
        } else {
            error_log('Failed to delete account for user ID ' . $user_id . ': ' . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
        }
        $stmt->close();
    } else {
        error_log('Invalid action received: ' . $action);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

$conn->close();
?>