<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// CSRF token validation
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Check CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf_token)) {
        $response['message'] = 'Invalid CSRF token';
        error_log('Invalid CSRF token in events_process.php');
        http_response_code(403);
        echo json_encode($response);
        exit;
    }
}

// Check user authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
    $response['message'] = 'Unauthorized access. Please log in.';
    error_log('Unauthorized access attempt in events_process.php: ' . print_r($_SESSION, true));
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// Ensure user_id is valid
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if ($user_id <= 0) {
    $response['message'] = 'Invalid user ID in session';
    error_log('Invalid user ID in session: ' . print_r($_SESSION, true));
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Initialize database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    error_log("Database connection failed: " . $db->connect_error);
    $response['message'] = 'Database connection failed';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_event':
        $event_id = intval($_GET['event_id'] ?? 0);
        if ($event_id <= 0) {
            $response['message'] = 'Invalid event ID';
            error_log('Invalid event ID: ' . $event_id);
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        $query = "SELECT id, title, description, date, time, category, location, attendance, image, organizer, creator_id 
                  FROM events WHERE id = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($event = $result->fetch_assoc()) {
                $response['success'] = true;
                $response['event'] = $event;
            } else {
                $response['message'] = 'Event not found';
                error_log('Event not found for ID: ' . $event_id);
                http_response_code(404);
            }
            $stmt->close();
        } else {
            error_log("Get event query preparation failed: " . $db->error);
            $response['message'] = 'Failed to fetch event';
            http_response_code(500);
        }
        break;

    case 'rsvp_event':
        $event_id = intval($_POST['event_id'] ?? 0);
        $rsvp_user_id = intval($_POST['user_id'] ?? $user_id);

        if ($event_id <= 0 || $rsvp_user_id <= 0) {
            $response['message'] = 'Invalid event or user ID';
            error_log('Invalid event ID: ' . $event_id . ' or user ID: ' . $rsvp_user_id);
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        $check_query = "SELECT * FROM event_rsvps WHERE event_id = ? AND user_id = ?";
        $stmt = $db->prepare($check_query);
        if ($stmt) {
            $stmt->bind_param("ii", $event_id, $rsvp_user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $response['message'] = 'You have already RSVP\'d to this event';
                error_log('User ' . $rsvp_user_id . ' already RSVP\'d to event ' . $event_id);
                http_response_code(400);
                echo json_encode($response);
                exit;
            }

            $insert_query = "INSERT INTO event_rsvps (event_id, user_id) VALUES (?, ?)";
            $stmt = $db->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param("ii", $event_id, $rsvp_user_id);
                if ($stmt->execute()) {
                    $event_query = "SELECT title FROM events WHERE id = ?";
                    $stmt = $db->prepare($event_query);
                    $stmt->bind_param("i", $event_id);
                    $stmt->execute();
                    $event_title = $stmt->get_result()->fetch_assoc()['title'] ?? 'Unknown Event';

                    $notify_query = "INSERT INTO notifications (user_id, type, content) VALUES (?, 'event_rsvp', ?)";
                    $stmt = $db->prepare($notify_query);
                    if ($stmt) {
                        $content = "You RSVP'd to the event: " . $event_title;
                        $stmt->bind_param("is", $rsvp_user_id, $content);
                        if (!$stmt->execute()) {
                            error_log("Notification insert failed: " . $stmt->error);
                        }
                    } else {
                        error_log("Notification query preparation failed: " . $db->error);
                    }

                    $response['success'] = true;
                } else {
                    error_log("RSVP event failed: " . $db->error);
                    $response['message'] = 'Failed to RSVP to event';
                    http_response_code(500);
                }
                $stmt->close();
            } else {
                error_log("RSVP query preparation failed: " . $db->error);
                $response['message'] = 'Failed to RSVP to event';
                http_response_code(500);
            }
        } else {
            error_log("Check RSVP query preparation failed: " . $db->error);
            $response['message'] = 'Failed to check RSVP status';
            http_response_code(500);
        }
        break;

    case 'cancel_rsvp':
        $event_id = intval($_POST['event_id'] ?? 0);
        $rsvp_user_id = intval($_POST['user_id'] ?? $user_id);

        if ($event_id <= 0 || $rsvp_user_id <= 0) {
            $response['message'] = 'Invalid event or user ID';
            error_log('Invalid event ID: ' . $event_id . ' or user ID: ' . $rsvp_user_id);
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        $check_query = "SELECT * FROM event_rsvps WHERE event_id = ? AND user_id = ?";
        $stmt = $db->prepare($check_query);
        if ($stmt) {
            $stmt->bind_param("ii", $event_id, $rsvp_user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $response['message'] = 'You have not RSVP\'d to this event';
                error_log('User ' . $rsvp_user_id . ' not RSVP\'d to event ' . $event_id);
                http_response_code(400);
                echo json_encode($response);
                exit;
            }

            $delete_query = "DELETE FROM event_rsvps WHERE event_id = ? AND user_id = ?";
            $stmt = $db->prepare($delete_query);
            if ($stmt) {
                $stmt->bind_param("ii", $event_id, $rsvp_user_id);
                if ($stmt->execute()) {
                    $event_query = "SELECT title FROM events WHERE id = ?";
                    $stmt = $db->prepare($event_query);
                    $stmt->bind_param("i", $event_id);
                    $stmt->execute();
                    $event_title = $stmt->get_result()->fetch_assoc()['title'] ?? 'Unknown Event';

                    $notify_query = "INSERT INTO notifications (user_id, type, content) VALUES (?, 'event_rsvp', ?)";
                    $stmt = $db->prepare($notify_query);
                    if ($stmt) {
                        $content = "You cancelled your RSVP for the event: " . $event_title;
                        $stmt->bind_param("is", $rsvp_user_id, $content);
                        if (!$stmt->execute()) {
                            error_log("Notification insert failed: " . $stmt->error);
                        }
                    } else {
                        error_log("Notification query preparation failed: " . $db->error);
                    }

                    $response['success'] = true;
                } else {
                    error_log("Cancel RSVP failed: " . $db->error);
                    $response['message'] = 'Failed to cancel RSVP';
                    http_response_code(500);
                }
                $stmt->close();
            } else {
                error_log("Cancel RSVP query preparation failed: " . $db->error);
                $response['message'] = 'Failed to cancel RSVP';
                http_response_code(500);
            }
        } else {
            error_log("Check RSVP query preparation failed: " . $db->error);
            $response['message'] = 'Failed to check RSVP status';
            http_response_code(500);
        }
        break;

    case 'create_event':
        $required_fields = ['title', 'description', 'date', 'time', 'category', 'location', 'attendance', 'organizer', 'user_id'];
        $errors = [];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                $errors[] = "Missing or empty $field";
            }
        }
        if ($errors) {
            $response['message'] = 'Missing required fields: ' . implode(', ', $errors);
            error_log('Missing required fields in create_event: ' . implode(', ', $errors));
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = trim($_POST['date']);
        $time = trim($_POST['time']);
        $category = trim($_POST['category']);
        $location = trim($_POST['location']);
        $attendance = trim($_POST['attendance']);
        $organizer = trim($_POST['organizer']);
        $creator_id = intval($_POST['user_id']);

        // Verify creator_id matches session user_id
        if ($creator_id !== $user_id) {
            $response['message'] = 'Unauthorized: User ID mismatch';
            error_log('User ID mismatch: session user_id=' . $user_id . ', provided user_id=' . $creator_id);
            http_response_code(403);
            echo json_encode($response);
            exit;
        }

        // Validate inputs
        if (!in_array($category, ['academic', 'social', 'sports', 'cultural', 'professional'])) {
            $response['message'] = 'Invalid category';
            error_log('Invalid category: ' . $category);
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
        if (!in_array($attendance, ['in-person', 'virtual'])) {
            $response['message'] = 'Invalid attendance type';
            error_log('Invalid attendance: ' . $attendance);
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        // Validate event date
        try {
            $event_datetime = new DateTime("$date $time");
            $now = new DateTime();
            if ($event_datetime < $now) {
                $response['message'] = 'Event date and time must be in the future';
                error_log('Invalid event date/time: ' . $date . ' ' . $time);
                http_response_code(400);
                echo json_encode($response);
                exit;
            }
        } catch (Exception $e) {
            $response['message'] = 'Invalid date or time format';
            error_log('Date/time format error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        // Handle image upload
        $image_path = $_POST['image_path'] ?? '/assets/default_event.jpg';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $response['message'] = 'Invalid image type. Allowed: JPEG, PNG, GIF';
                error_log('Invalid image type: ' . $_FILES['image']['type']);
                http_response_code(400);
                echo json_encode($response);
                exit;
            }
            if ($_FILES['image']['size'] > $max_size) {
                $response['message'] = 'Image size exceeds 5MB';
                error_log('Image size too large: ' . $_FILES['image']['size']);
                http_response_code(400);
                echo json_encode($response);
                exit;
            }
            $target_dir = __DIR__ . "/assets/uploads/";
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0755, true)) {
                    $response['message'] = 'Failed to create upload directory';
                    error_log('Failed to create upload directory: ' . $target_dir);
                    http_response_code(500);
                    echo json_encode($response);
                    exit;
                }
            }
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $target_dir . $image_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = '/assets/uploads/' . $image_name;
            } else {
                error_log("Image upload failed: " . $_FILES['image']['error']);
                $response['message'] = 'Failed to upload image';
                http_response_code(500);
                echo json_encode($response);
                exit;
            }
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            error_log("Image upload error: " . $_FILES['image']['error']);
            $response['message'] = 'Image upload error';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        // Insert event
        $query = "INSERT INTO events (title, description, date, time, category, location, attendance, image, organizer, creator_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("sssssssssi", $title, $description, $date, $time, $category, $location, $attendance, $image_path, $organizer, $creator_id);
            if ($stmt->execute()) {
                $event_id = $db->insert_id;
                $rsvp_query = "INSERT INTO event_rsvps (event_id, user_id) VALUES (?, ?)";
                $stmt = $db->prepare($rsvp_query);
                if ($stmt) {
                    $stmt->bind_param("ii", $event_id, $creator_id);
                    if (!$stmt->execute()) {
                        error_log("RSVP insert failed: " . $stmt->error);
                    }
                } else {
                    error_log("RSVP query preparation failed: " . $db->error);
                }

                $notify_query = "INSERT INTO notifications (user_id, type, content) VALUES (?, 'event_create', ?)";
                $stmt = $db->prepare($notify_query);
                if ($stmt) {
                    $content = "You created and RSVP'd to the event: " . $title;
                    $stmt->bind_param("is", $creator_id, $content);
                    if (!$stmt->execute()) {
                        error_log("Notification insert failed: " . $stmt->error);
                    }
                } else {
                    error_log("Notification query preparation failed: " . $db->error);
                }

                $response['success'] = true;
            } else {
                error_log("Create event failed: " . $db->error);
                $response['message'] = 'Failed to create event: ' . $db->error;
                http_response_code(500);
            }
            $stmt->close();
        } else {
            error_log("Create event query preparation failed: " . $db->error);
            $response['message'] = 'Failed to create event: Query preparation error';
            http_response_code(500);
        }
        break;

    case 'remove_event':
        $event_id = intval($_POST['event_id'] ?? 0);
        $remove_user_id = intval($_POST['user_id'] ?? $user_id);

        if ($event_id <= 0 || $remove_user_id <= 0) {
            $response['message'] = 'Invalid event or user ID';
            error_log('Invalid event ID: ' . $event_id . ' or user ID: ' . $remove_user_id);
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        // Verify user is the creator
        $check_query = "SELECT creator_id, image FROM events WHERE id = ?";
        $stmt = $db->prepare($check_query);
        if (!$stmt) {
            error_log("Check event creator query preparation failed: " . $db->error);
            $response['message'] = 'Failed to verify event ownership';
            http_response_code(500);
            echo json_encode($response);
            exit;
        }

        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $response['message'] = 'Event not found';
            error_log('Event not found for ID: ' . $event_id);
            http_response_code(404);
            echo json_encode($response);
            exit;
        }

        $event = $result->fetch_assoc();
        if ($event['creator_id'] !== $remove_user_id) {
            $response['message'] = 'Unauthorized: Only the event creator can delete this event';
            error_log('Unauthorized delete attempt by user ' . $remove_user_id . ' for event ' . $event_id);
            http_response_code(403);
            echo json_encode($response);
            exit;
        }

        // Delete associated RSVPs
        $delete_rsvp_query = "DELETE FROM event_rsvps WHERE event_id = ?";
        $stmt = $db->prepare($delete_rsvp_query);
        if ($stmt) {
            $stmt->bind_param("i", $event_id);
            if (!$stmt->execute()) {
                error_log("Delete RSVPs failed: " . $db->error);
                $response['message'] = 'Failed to delete event RSVPs';
                http_response_code(500);
                echo json_encode($response);
                exit;
            }
            $stmt->close();
        } else {
            error_log("Delete RSVPs query preparation failed: " . $db->error);
            $response['message'] = 'Failed to prepare RSVP deletion';
            http_response_code(500);
            echo json_encode($response);
            exit;
        }

        // Delete associated notifications
        $delete_notify_query = "DELETE FROM notifications WHERE type IN ('event_create', 'event_rsvp') AND content LIKE ?";
        $stmt = $db->prepare($delete_notify_query);
        if ($stmt) {
            $like_pattern = "%event: %";
            $stmt->bind_param("s", $like_pattern);
            if (!$stmt->execute()) {
                error_log("Delete notifications failed: " . $db->error);
                $response['message'] = 'Failed to delete event notifications';
                http_response_code(500);
                echo json_encode($response);
                exit;
            }
            $stmt->close();
        } else {
            error_log("Delete notifications query preparation failed: " . $db->error);
            $response['message'] = 'Failed to prepare notification deletion';
            http_response_code(500);
            echo json_encode($response);
            exit;
        }

        // Delete event image if not default
        if ($event['image'] && $event['image'] !== '/assets/default_event.jpg') {
            $image_path = __DIR__ . $event['image'];
            if (file_exists($image_path)) {
                if (!unlink($image_path)) {
                    error_log("Failed to delete event image: " . $image_path);
                }
            }
        }

        // Delete event
        $delete_event_query = "DELETE FROM events WHERE id = ?";
        $stmt = $db->prepare($delete_event_query);
        if ($stmt) {
            $stmt->bind_param("i", $event_id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Event deleted successfully';
            } else {
                error_log("Delete event failed: " . $db->error);
                $response['message'] = 'Failed to delete event';
                http_response_code(500);
            }
            $stmt->close();
        } else {
            error_log("Delete event query preparation failed: " . $db->error);
            $response['message'] = 'Failed to prepare event deletion';
            http_response_code(500);
        }
        break;

    default:
        $response['message'] = 'Invalid action';
        error_log('Invalid action: ' . $action);
        http_response_code(400);
}
$db->close();
echo json_encode($response);
exit;
?>