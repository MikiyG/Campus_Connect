<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once 'config.php';

function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    return $conn;
}

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();

    switch ($action) {
        case 'get_pending_users':
            $stmt = $conn->prepare("SELECT u.id, u.full_name AS name, u.email, uni.name AS university, u.student_id, u.batch, u.id_picture, u.status, u.role
                                    FROM users u JOIN universities uni ON u.university_id = uni.id
                                    WHERE u.status = 'pending'");
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $users]);
            $stmt->close();
            break;

        case 'get_users':
            $stmt = $conn->prepare("SELECT u.id, u.full_name AS name, u.email, uni.name AS university, u.student_id, u.batch, u.id_picture, u.status, u.role
                                    FROM users u JOIN universities uni ON u.university_id = uni.id
                                    WHERE u.status = 'approved'");
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $users]);
            $stmt->close();
            break;

        case 'approve_user':
            $userId = $_POST['userId'] ?? 0;
            $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ? AND status = 'pending'");
            $stmt->bind_param('i', $userId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'User approved']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to approve user']);
            }
            $stmt->close();
            break;

        case 'reject_user':
            $userId = $_POST['userId'] ?? 0;
            $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND status = 'pending'");
            $stmt->bind_param('i', $userId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'User rejected']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reject user']);
            }
            $stmt->close();
            break;

        case 'edit_user':
            $userId = $_POST['userId'] ?? 0;
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $student_id = $_POST['student_id'] ?? '';
            $batch = $_POST['batch'] ?? 0;
            $status = $_POST['status'] ?? '';
            $role = $_POST['role'] ?? '';

            if (empty($name) || empty($email) || empty($student_id) || empty($batch) ||
                !in_array($status, ['pending', 'approved', 'rejected']) ||
                !in_array($role, ['student', 'admin'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                exit;
            }

            // Check for email uniqueness (excluding current user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param('si', $email, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already in use']);
                $stmt->close();
                exit;
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, student_id = ?, batch = ?, status = ?, role = ? WHERE id = ?");
            $stmt->bind_param('sssissi', $name, $email, $student_id, $batch, $status, $role, $userId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'User edited']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to edit user']);
            }
            $stmt->close();
            break;

        case 'delete_user':
            $userId = $_POST['userId'] ?? 0;
            $stmt = $conn->prepare("SELECT id_picture FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $stmt->close();
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param('i', $userId);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    if (file_exists($user['id_picture']) && $user['id_picture'] !== 'Uploads/id_pictures/default.jpg') {
                        unlink($user['id_picture']);
                    }
                    echo json_encode(['success' => true, 'message' => 'User deleted']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            $stmt->close();
            break;

        case 'get_universities':
            $stmt = $conn->prepare("SELECT id, name, code FROM universities");
            $stmt->execute();
            $result = $stmt->get_result();
            $universities = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $universities]);
            $stmt->close();
            break;

        case 'add_university':
            $name = $_POST['name'] ?? '';
            $code = $_POST['code'] ?? '';
            if (empty($name) || empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                exit;
            }
            $stmt = $conn->prepare("SELECT id FROM universities WHERE code = ?");
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'University code already exists']);
                $stmt->close();
                exit;
            }
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO universities (name, code) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $code);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'University added']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add university']);
            }
            $stmt->close();
            break;

        case 'get_university':
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("SELECT name, code FROM universities WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $univ = $result->fetch_assoc();
                echo json_encode(['success' => true, 'data' => $univ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'University not found']);
            }
            $stmt->close();
            break;

        case 'edit_university':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $code = $_POST['code'] ?? '';
            if (empty($name) || empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                exit;
            }
            $stmt = $conn->prepare("SELECT id FROM universities WHERE code = ? AND id != ?");
            $stmt->bind_param('si', $code, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'University code already exists']);
                $stmt->close();
                exit;
            }
            $stmt->close();
            $stmt = $conn->prepare("UPDATE universities SET name = ?, code = ? WHERE id = ?");
            $stmt->bind_param('ssi', $name, $code, $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'University edited']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to edit university']);
            }
            $stmt->close();
            break;

        case 'delete_university':
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM universities WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'University deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete university']);
            }
            $stmt->close();
            break;

        case 'get_groups':
            $stmt = $conn->prepare("SELECT id, name, description FROM groups");
            $stmt->execute();
            $result = $stmt->get_result();
            $groups = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $groups]);
            $stmt->close();
            break;

        case 'get_group':
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("SELECT name, description FROM groups WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $group = $result->fetch_assoc();
                echo json_encode(['success' => true, 'data' => $group]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Group not found']);
            }
            $stmt->close();
            break;

        case 'edit_group':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            if (empty($name) || empty($description)) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE groups SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param('ssi', $name, $description, $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Group edited']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to edit group']);
            }
            $stmt->close();
            break;

        case 'delete_group':
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM groups WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Group deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete group']);
            }
            $stmt->close();
            break;

        case 'get_events':
            $stmt = $conn->prepare("SELECT id, title, description, date FROM events");
            $stmt->execute();
            $result = $stmt->get_result();
            $events = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $events]);
            $stmt->close();
            break;

        case 'get_event':
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("SELECT title, description, date FROM events WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $event = $result->fetch_assoc();
                echo json_encode(['success' => true, 'data' => $event]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
            }
            $stmt->close();
            break;

        case 'edit_event':
            $id = $_POST['id'] ?? 0;
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $date = $_POST['date'] ?? '';
            if (empty($title) || empty($description) || empty($date)) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, date = ? WHERE id = ?");
            $stmt->bind_param('sssi', $title, $description, $date, $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Event edited']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to edit event']);
            }
            $stmt->close();
            break;

        case 'delete_event':
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Event deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete event']);
            }
            $stmt->close();
            break;

        case 'generate_report':
            $type = $_POST['type'] ?? '';
            if ($type === 'user_activity') {
                $stmt = $conn->prepare("SELECT u.full_name, 
                                        COUNT(p.id) as posts, 
                                        COUNT(m.id) as messages, 
                                        COUNT(gm.group_id) as groups_joined, 
                                        COUNT(er.event_id) as events_rsvped 
                                        FROM users u 
                                        LEFT JOIN posts p ON p.user_id = u.id 
                                        LEFT JOIN messages m ON m.sender_id = u.id 
                                        LEFT JOIN group_members gm ON gm.user_id = u.id 
                                        LEFT JOIN event_rsvps er ON er.user_id = u.id 
                                        WHERE u.status = 'approved' 
                                        GROUP BY u.id");
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                $stmt->close();
            } elseif ($type === 'event_participation') {
                $stmt = $conn->prepare("SELECT e.title, COUNT(er.user_id) as participants 
                                        FROM events e 
                                        LEFT JOIN event_rsvps er ON er.event_id = e.id 
                                        GROUP BY e.id");
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                $stmt->close();
            // START OF ADDED CODE: New report types
            } elseif ($type === 'university_distribution') {
                $stmt = $conn->prepare("SELECT uni.name AS university, COUNT(u.id) AS user_count 
                                        FROM universities uni 
                                        LEFT JOIN users u ON u.university_id = uni.id AND u.status = 'approved' 
                                        GROUP BY uni.id");
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                $stmt->close();
            } elseif ($type === 'signup_trends') {
                $stmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(id) AS signups 
                                        FROM users 
                                        GROUP BY month 
                                        ORDER BY month DESC 
                                        LIMIT 12");
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                $stmt->close();
            } elseif ($type === 'popular_groups') {
                $stmt = $conn->prepare("SELECT g.name, g.members_count 
                                        FROM groups g 
                                        ORDER BY g.members_count DESC 
                                        LIMIT 20");
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                $stmt->close();
            } elseif ($type === 'active_users') {
                $stmt = $conn->prepare("SELECT u.full_name, 
                                        (COUNT(p.id) + COUNT(m.id) + COUNT(gm.group_id) + COUNT(er.event_id)) AS total_activity 
                                        FROM users u 
                                        LEFT JOIN posts p ON p.user_id = u.id 
                                        LEFT JOIN messages m ON m.sender_id = u.id 
                                        LEFT JOIN group_members gm ON gm.user_id = u.id 
                                        LEFT JOIN event_rsvps er ON er.user_id = u.id 
                                        WHERE u.status = 'approved' 
                                        GROUP BY u.id 
                                        ORDER BY total_activity DESC 
                                        LIMIT 20");
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                $stmt->close();
            } elseif ($type === 'pending_reports') {
                $stmt = $conn->prepare("SELECT r.id, reporter.full_name AS reporter, reported.full_name AS reported, r.type, r.reason 
                                        FROM reports r 
                                        LEFT JOIN users reporter ON reporter.id = r.reporter_id 
                                        LEFT JOIN users reported ON reported.id = r.reported_id 
                                        WHERE r.status = 'pending' 
                                        ORDER BY r.timestamp DESC 
                                        LIMIT 50");
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                $stmt->close();
            // END OF ADDED CODE
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid report type']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>