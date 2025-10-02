<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['status']) || $_SESSION['status'] !== 'approved') {
    $response['message'] = 'Unauthorized access';
    error_log("Unauthorized access attempt: " . ($_SESSION['user_id'] ?? 'No user_id'));
    echo json_encode($response);
    exit;
}

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid CSRF token';
        error_log("Invalid CSRF token: $csrf_token");
        echo json_encode($response);
        exit;
    }
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    error_log("Database connection failed: " . $db->connect_error);
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_group':
        $group_id = intval($_GET['group_id'] ?? 0);
        error_log("Processing get_group for group_id: $group_id, user_id: " . ($_SESSION['user_id'] ?? 'No user_id'));
        if ($group_id <= 0) {
            error_log("Invalid group ID: $group_id");
            $response['message'] = 'Invalid group ID';
            echo json_encode($response);
            exit;
        }

        $query = "SELECT g.id, g.name, g.description, g.category, g.privacy, g.image, g.members_count, u.full_name AS creator_name, g.creator_id
                  FROM groups g
                  LEFT JOIN users u ON g.creator_id = u.id
                  WHERE g.id = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $group_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($group = $result->fetch_assoc()) {
                    $user_id = $_SESSION['user_id'];
                    $group['is_member'] = false;
                    $membership_query = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
                    $member_stmt = $db->prepare($membership_query);
                    if ($member_stmt) {
                        $member_stmt->bind_param("ii", $group_id, $user_id);
                        $member_stmt->execute();
                        $group['is_member'] = $member_stmt->get_result()->num_rows > 0;
                        $member_stmt->close();
                        error_log("Membership check for group_id: $group_id, user_id: $user_id, is_member: " . ($group['is_member'] ? 'true' : 'false'));
                    } else {
                        error_log("Membership query preparation failed: " . $db->error);
                    }
                    // Allow creators to access group details regardless of membership
                    if ($group['privacy'] === 'private' && !$group['is_member'] && $group['creator_id'] != $user_id) {
                        error_log("Access denied: User $user_id is not a member or creator of private group $group_id");
                        $response['message'] = 'You are not a member of this group';
                        echo json_encode($response);
                        exit;
                    }
                    $response['success'] = true;
                    $response['group'] = $group;
                    error_log("Successfully fetched group details for group_id: $group_id");
                } else {
                    error_log("Group not found for ID: $group_id");
                    $response['message'] = 'Group not found';
                }
            } else {
                error_log("Get group query execution failed: " . $stmt->error);
                $response['message'] = 'Failed to fetch group';
            }
            $stmt->close();
        } else {
            error_log("Get group query preparation failed: " . $db->error);
            $response['message'] = 'Failed to prepare group query';
        }
        break;

    case 'join_group':
        $group_id = intval($_POST['group_id'] ?? 0);
        $user_id = $_SESSION['user_id'];

        if ($group_id <= 0) {
            $response['message'] = 'Invalid group ID';
            echo json_encode($response);
            exit;
        }

        $group_query = "SELECT privacy, name FROM groups WHERE id = ?";
        $stmt = $db->prepare($group_query);
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $group_data = $stmt->get_result()->fetch_assoc();
        if (!$group_data) {
            $response['message'] = 'Group not found';
            echo json_encode($response);
            exit;
        }
        $privacy = $group_data['privacy'];
        $group_name = $group_data['name'];

        if ($privacy !== 'public') {
            $response['message'] = 'This is a private group. The creator must add you.';
            echo json_encode($response);
            exit;
        }

        $check_query = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($check_query);
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response['message'] = 'You are already a member of this group';
            echo json_encode($response);
            exit;
        }

        $insert_query = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
        $stmt = $db->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("ii", $group_id, $user_id);
            if ($stmt->execute()) {
                $update_query = "UPDATE groups SET members_count = members_count + 1 WHERE id = ?";
                $stmt = $db->prepare($update_query);
                $stmt->bind_param("i", $group_id);
                $stmt->execute();

                $notify_type = 'group_join';
                $notify_content = "You joined the group: $group_name";
                $notify_query = "INSERT INTO notifications (user_id, type, content) VALUES (?, ?, ?)";
                $stmt = $db->prepare($notify_query);
                $stmt->bind_param("iss", $user_id, $notify_type, $notify_content);
                $stmt->execute();

                $response['success'] = true;
            } else {
                error_log("Join group failed: " . $db->error);
                $response['message'] = 'Failed to join group';
            }
            $stmt->close();
        }
        break;

    case 'add_user_to_group':
        $group_id = intval($_POST['group_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $creator_id = $_SESSION['user_id'];

        if ($group_id <= 0 || empty($email)) {
            $response['message'] = 'Invalid group ID or email';
            echo json_encode($response);
            exit;
        }

        $creator_check = "SELECT creator_id, name, privacy FROM groups WHERE id = ?";
        $stmt = $db->prepare($creator_check);
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0 || $result->fetch_assoc()['creator_id'] != $creator_id) {
            $response['message'] = 'Not authorized to add users';
            echo json_encode($response);
            exit;
        }
        $stmt->close();

        $user_query = "SELECT id, full_name FROM users WHERE email = ?";
        $stmt = $db->prepare($user_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $response['message'] = 'User not found';
            echo json_encode($response);
            exit;
        }
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $user_name = $user['full_name'];
        $stmt->close();

        $check_query = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($check_query);
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response['message'] = 'User is already a member of this group';
            echo json_encode($response);
            exit;
        }
        $stmt->close();

        $insert_query = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
        $stmt = $db->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("ii", $group_id, $user_id);
            if ($stmt->execute()) {
                $update_query = "UPDATE groups SET members_count = members_count + 1 WHERE id = ?";
                $stmt = $db->prepare($update_query);
                $stmt->bind_param("i", $group_id);
                $stmt->execute();

                $group_query = "SELECT name FROM groups WHERE id = ?";
                $stmt = $db->prepare($group_query);
                $stmt->bind_param("i", $group_id);
                $stmt->execute();
                $group_name = $stmt->get_result()->fetch_assoc()['name'];
                $notify_type = 'group_added';
                $notify_content = "You were added to the group: $group_name";
                $notify_query = "INSERT INTO notifications (user_id, type, content) VALUES (?, ?, ?)";
                $stmt = $db->prepare($notify_query);
                $stmt->bind_param("iss", $user_id, $notify_type, $notify_content);
                $stmt->execute();

                $response['success'] = true;
                $response['message'] = "User $user_name added successfully";
            } else {
                error_log("Add user to group failed: " . $db->error);
                $response['message'] = 'Failed to add user';
            }
            $stmt->close();
        } else {
            error_log("Add user query preparation failed: " . $db->error);
            $response['message'] = 'Failed to prepare add user query';
        }
        break;

    case 'leave_group':
        $group_id = intval($_POST['group_id'] ?? 0);
        $user_id = $_SESSION['user_id'];

        if ($group_id <= 0) {
            $response['message'] = 'Invalid group ID';
            echo json_encode($response);
            exit;
        }

        $check_query = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($check_query);
        if ($stmt) {
            $stmt->bind_param("ii", $group_id, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $response['message'] = 'You are not a member of this group';
                echo json_encode($response);
                exit;
            }

            $delete_query = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
            $stmt = $db->prepare($delete_query);
            if ($stmt) {
                $stmt->bind_param("ii", $group_id, $user_id);
                if ($stmt->execute()) {
                    $update_query = "UPDATE groups SET members_count = members_count - 1 WHERE id = ?";
                    $stmt = $db->prepare($update_query);
                    $stmt->bind_param("i", $group_id);
                    $stmt->execute();

                    $group_query = "SELECT name FROM groups WHERE id = ?";
                    $stmt = $db->prepare($group_query);
                    $stmt->bind_param("i", $group_id);
                    $stmt->execute();
                    $group_name = $stmt->get_result()->fetch_assoc()['name'];

                    $notify_type = 'group_leave';
                    $notify_content = "You left the group: $group_name";
                    $notify_query = "INSERT INTO notifications (user_id, type, content) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($notify_query);
                    $stmt->bind_param("iss", $user_id, $notify_type, $notify_content);
                    $stmt->execute();

                    $response['success'] = true;
                } else {
                    error_log("Leave group failed: " . $db->error);
                    $response['message'] = 'Failed to leave group';
                }
                $stmt->close();
            } else {
                error_log("Leave group query preparation failed: " . $db->error);
                $response['message'] = 'Failed to leave group';
            }
        } else {
            error_log("Check membership query preparation failed: " . $db->error);
            $response['message'] = 'Failed to check membership';
        }
        break;

    case 'create_group':
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? '';
        $privacy = $_POST['privacy'] ?? 'public';
        $user_id = $_SESSION['user_id'];
        $members_count = 1;

        if (empty($name) || empty($description) || empty($category)) {
            $response['message'] = 'All fields are required';
            echo json_encode($response);
            exit;
        }

        $image_path = $_POST['image_path'] ?? '/assets/default_group.jpg';
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "assets/uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $target_dir . $image_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = '/' . $target_file;
            } else {
                error_log("Image upload failed: " . $_FILES['image']['error']);
                $response['message'] = 'Failed to upload image';
                echo json_encode($response);
                exit;
            }
        }

        $query = "INSERT INTO groups (name, description, category, privacy, image, creator_id, members_count) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("sssssii", $name, $description, $category, $privacy, $image_path, $user_id, $members_count);
            if ($stmt->execute()) {
                $group_id = $db->insert_id;
                $join_query = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
                $stmt = $db->prepare($join_query);
                if ($stmt) {
                    $stmt->bind_param("ii", $group_id, $user_id);
                    $stmt->execute();
                } else {
                    error_log("Join creator query preparation failed: " . $db->error);
                    $response['message'] = 'Failed to auto-join creator';
                    echo json_encode($response);
                    exit;
                }

                $notify_type = 'group_create';
                $notify_content = "You created and joined the group: $name";
                $notify_query = "INSERT INTO notifications (user_id, type, content) VALUES (?, ?, ?)";
                $stmt = $db->prepare($notify_query);
                if ($stmt) {
                    $stmt->bind_param("iss", $user_id, $notify_type, $notify_content);
                    $stmt->execute();
                } else {
                    error_log("Create group notification query preparation failed: " . $db->error);
                    $response['message'] = 'Failed to create notification';
                    echo json_encode($response);
                    exit;
                }

                $response['success'] = true;
                $response['group'] = [
                    'id' => $group_id,
                    'name' => $name,
                    'description' => $description,
                    'category' => $category,
                    'privacy' => $privacy,
                    'image' => $image_path,
                    'members_count' => $members_count,
                    'creator_id' => $user_id
                ];
            } else {
                error_log("Create group failed: " . $db->error);
                $response['message'] = 'Failed to create group';
            }
            $stmt->close();
        } else {
            error_log("Create group query preparation failed: " . $db->error);
            $response['message'] = 'Failed to create group';
        }
        break;

    case 'remove_group':
        $group_id = intval($_POST['group_id'] ?? 0);
        $user_id = $_SESSION['user_id'];

        if ($group_id <= 0) {
            $response['message'] = 'Invalid group ID';
            echo json_encode($response);
            exit;
        }

        $check_query = "SELECT creator_id FROM groups WHERE id = ?";
        $stmt = $db->prepare($check_query);
        if ($stmt) {
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0 || $result->fetch_assoc()['creator_id'] != $user_id) {
                $response['message'] = 'You are not authorized to remove this group';
                echo json_encode($response);
                exit;
            }

            $delete_members_query = "DELETE FROM group_members WHERE group_id = ?";
            $stmt = $db->prepare($delete_members_query);
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $delete_messages_query = "DELETE FROM group_messages WHERE group_id = ?";
            $stmt = $db->prepare($delete_messages_query);
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $delete_notifications_query = "DELETE FROM notifications WHERE type IN ('group_join', 'group_leave', 'group_create', 'group_added', 'message') AND content LIKE ?";
            $stmt = $db->prepare($delete_notifications_query);
            $like_pattern = "%group: %";
            $stmt->bind_param("s", $like_pattern);
            $stmt->execute();

            $delete_group_query = "DELETE FROM groups WHERE id = ?";
            $stmt = $db->prepare($delete_group_query);
            if ($stmt) {
                $stmt->bind_param("i", $group_id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                } else {
                    error_log("Remove group failed: " . $db->error);
                    $response['message'] = 'Failed to remove group';
                }
                $stmt->close();
            } else {
                error_log("Remove group query preparation failed: " . $db->error);
                $response['message'] = 'Failed to remove group';
            }
        } else {
            error_log("Check creator query preparation failed: " . $db->error);
            $response['message'] = 'Failed to verify creator';
        }
        break;

    case 'get_messages':
        $group_id = intval($_GET['group_id'] ?? 0);
        if ($group_id <= 0) {
            $response['message'] = 'Invalid group ID';
            echo json_encode($response);
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $check_query = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($check_query);
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $response['message'] = 'You are not a member of this group';
            echo json_encode($response);
            exit;
        }

        $query = "SELECT gm.id, gm.message, gm.file_path, gm.created_at, u.full_name
                  FROM group_messages gm
                  LEFT JOIN users u ON gm.user_id = u.id
                  WHERE gm.group_id = ?
                  ORDER BY gm.created_at ASC";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $group_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $messages = [];
                while ($row = $result->fetch_assoc()) {
                    $messages[] = $row;
                }
                $response['success'] = true;
                $response['messages'] = $messages;
            } else {
                error_log("Get messages query execution failed: " . $stmt->error);
                $response['message'] = 'Failed to fetch messages';
            }
            $stmt->close();
        } else {
            error_log("Get messages query preparation failed: " . $db->error);
            $response['message'] = 'Failed to prepare messages query';
        }
        break;

    case 'send_message':
        $group_id = intval($_POST['group_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        $message = trim($_POST['message'] ?? '');
        $file_path = '';

        if ($group_id <= 0) {
            $response['message'] = 'Invalid group ID';
            echo json_encode($response);
            exit;
        }

        $check_query = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($check_query);
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $response['message'] = 'You are not a member of this group';
            echo json_encode($response);
            exit;
        }

        if (!empty($_FILES['file']['name'])) {
            $target_dir = "assets/uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_name = time() . '_' . basename($_FILES['file']['name']);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $file_path = '/' . $target_file;
            } else {
                error_log("File upload failed: " . $_FILES['file']['error']);
                $response['message'] = 'Failed to upload file';
                echo json_encode($response);
                exit;
            }
        }

        if (empty($message) && empty($file_path)) {
            $response['message'] = 'Message or file is required';
            echo json_encode($response);
            exit;
        }

        $query = "INSERT INTO group_messages (group_id, user_id, message, file_path) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iiss", $group_id, $user_id, $message, $file_path);
            if ($stmt->execute()) {
                $message_id = $db->insert_id;
                $group_query = "SELECT name FROM groups WHERE id = ?";
                $stmt = $db->prepare($group_query);
                $stmt->bind_param("i", $group_id);
                $stmt->execute();
                $group_name = $stmt->get_result()->fetch_assoc()['name'];

                $user_query = "SELECT full_name FROM users WHERE id = ?";
                $stmt = $db->prepare($user_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $full_name = $stmt->get_result()->fetch_assoc()['full_name'];

                $notify_type = 'message';
                $notify_content = "New message in group: $group_name";
                $notify_query = "INSERT INTO notifications (user_id, type, content) VALUES (?, ?, ?)";
                $stmt = $db->prepare($notify_query);
                $stmt->bind_param("iss", $user_id, $notify_type, $notify_content);
                $stmt->execute();

                $response['success'] = true;
                $response['message_data'] = [
                    'id' => $message_id,
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'message' => $message,
                    'file_path' => $file_path,
                    'created_at' => date('Y-m-d H:i:s'),
                    'full_name' => $full_name
                ];
            } else {
                error_log("Send message failed: " . $db->error);
                $response['message'] = 'Failed to send message';
            }
            $stmt->close();
        } else {
            error_log("Send message query preparation failed: " . $db->error);
            $response['message'] = 'Failed to prepare message query';
        }
        break;

    default:
        $response['message'] = 'Invalid action';
        break;
}

$db->close();
echo json_encode($response);
?>