<?php
// signup_process.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Set JSON header early

require_once 'config.php'; // Include config for DB constants

// Function to connect to DB
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    return $conn;
}

// Handle only POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullName = trim($_POST['fullName'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $studentId = trim($_POST['studentId'] ?? '');
    $batch = trim($_POST['batch'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');
    $terms = isset($_POST['terms']);

    // Validate required fields
    if (empty($fullName) || empty($university) || empty($studentId) || empty($batch) || empty($email) || empty($password) || empty($confirmPassword) || !$terms) {
        echo json_encode(['success' => false, 'message' => 'All fields are required, and you must agree to the terms.']);
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    // Validate passwords match
    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    // Handle file upload
    if (!isset($_FILES['idPicture']) || $_FILES['idPicture']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Student ID picture is required.']);
        exit;
    }

    $file = $_FILES['idPicture'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    if (!str_starts_with($file['type'], 'image/')) {
        echo json_encode(['success' => false, 'message' => 'Please upload a valid image file (e.g., JPG, PNG).']);
        exit;
    }
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'Image size must be less than 5MB.']);
        exit;
    }

    // Create upload directory if not exists
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
            exit;
        }
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('id_', true) . '.' . $ext;
    $uploadPath = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Connect to DB
    $conn = connectDB();

    // Handle university: Map to ID, insert if 'other'
    $uniCode = strtoupper($university);
    $uniQuery = "SELECT id FROM universities WHERE code = ?";
    $stmt = $conn->prepare($uniQuery);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare university query: ' . $conn->error]);
        unlink($uploadPath);
        $conn->close();
        exit;
    }
    $stmt->bind_param('s', $uniCode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $universityId = $row['id'];
    } else {
        // Insert new university if not found (e.g., 'other')
        $uniName = ucfirst($university);
        $insertUni = "INSERT INTO universities (name, code) VALUES (?, ?)";
        $stmtInsert = $conn->prepare($insertUni);
        if (!$stmtInsert) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare university insert: ' . $conn->error]);
            unlink($uploadPath);
            $conn->close();
            exit;
        }
        $stmtInsert->bind_param('ss', $uniName, $uniCode);
        if ($stmtInsert->execute()) {
            $universityId = $conn->insert_id;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to insert university: ' . $stmtInsert->error]);
            unlink($uploadPath);
            $conn->close();
            exit;
        }
        $stmtInsert->close();
    }
    $stmt->close();

    // Check if email already exists
    $emailQuery = "SELECT id FROM users WHERE email = ?";
    $stmtEmail = $conn->prepare($emailQuery);
    if (!$stmtEmail) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare email check: ' . $conn->error]);
        unlink($uploadPath);
        $conn->close();
        exit;
    }
    $stmtEmail->bind_param('s', $email);
    $stmtEmail->execute();
    $resultEmail = $stmtEmail->get_result();
    if ($resultEmail->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
        unlink($uploadPath);
        $conn->close();
        exit;
    }
    $stmtEmail->close();

    // Insert user
    $insertQuery = "INSERT INTO users (full_name, email, password, university_id, student_id, batch, status, id_picture, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, 'student')";
    $stmtInsert = $conn->prepare($insertQuery);
    if (!$stmtInsert) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare user insert: ' . $conn->error]);
        unlink($uploadPath);
        $conn->close();
        exit;
    }
    $stmtInsert->bind_param('sssisis', $fullName, $email, $hashedPassword, $universityId, $studentId, $batch, $uploadPath);
    if ($stmtInsert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Signup request submitted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account: ' . $stmtInsert->error]);
        unlink($uploadPath);
    }
    $stmtInsert->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>