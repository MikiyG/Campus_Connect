<?php
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in and approved
if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
  echo json_encode(['success' => false, 'message' => 'You must be logged in and approved to post.']);
  exit();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
  exit();
}

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
  $user_id = $_SESSION['user_id'];
  $content = trim($_POST['content']);

  if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Post content cannot be empty.']);
    exit();
  }

  // Insert post into database
  $stmt = $conn->prepare("INSERT INTO posts (user_id, content, timestamp) VALUES (?, ?, NOW())");
  $stmt->bind_param("is", $user_id, $content);

  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Post created successfully.']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to create post.']);
  }

  $stmt->close();
} else {
  echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>