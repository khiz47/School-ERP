<?php
session_start();

// in common/functions.php
include __DIR__ . '/../includes/conn.php';
include __DIR__ . '/../includes/helpers.php';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'login_user':
            loginUser($conn);
            break;

        case 'register_user':
            registerUser($conn);
            break;

        default:
            sendResponse('error', null, 'Invalid action.');
    }
}



// function check_login($role = null){
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login");
//     exit();
// }
// if ($role !== null && $_SESSION['role_name'] !== $role) {
//     header("Location: /login.php");
//     exit();
// }}

function loginUser($conn)
{
    if (empty($_POST['email']) || empty($_POST['password'])) {
        sendResponse('error', null, 'Email and password are required.');
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT u.*, r.role_name 
                            FROM users u
                            JOIN roles r ON u.role_id = r.role_id
                            WHERE u.email = ? LIMIT 1");
    if (!$stmt) {
        sendResponse('error', null, 'Something went wrong. Please try again later. ' . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            if ($user['status'] !== 'active') {
                sendResponse('error', null, 'Your account has been suspended. Please contact support.');
            }

            // Reset login attempts on successful login
            $_SESSION['login_attempts'] = 0;

            // Save session
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role_id']   = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['username']  = $user['username'];

            $updateStmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user['user_id']);
            $updateStmt->execute();

            $redirectUrl = '/';
            switch ($user['role_name']) {
                case 'Admin':
                    $redirectUrl = BASE_URL . 'admin/';
                    break;
                case 'Teacher':
                    $redirectUrl = BASE_URL . 'teacher/';
                    break;
                case 'Student':
                    $redirectUrl = BASE_URL . 'student/';
                    break;
                case 'Parent':
                    $redirectUrl = BASE_URL . 'parent/';
                    break;
                case 'Accountant':
                    $redirectUrl = BASE_URL . 'fees/';
                    break;
            }

            sendResponse('success', ['redirect' => $redirectUrl], 'Login successful! Redirecting...');
        }
    }

    // Increment login attempts on failure
    $_SESSION['login_attempts']++;

    $showReset = $_SESSION['login_attempts'] >= 1 ? true : false;
    sendResponse('error', ['show_reset' => $showReset], 'Invalid email or password.');
}


function registerUser($conn)
{
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
        sendResponse('error', null, 'Please fill in all fields.');
    }

    if (!preg_match("/^[a-zA-Z\s]{1,30}$/", $fullname)) {
        sendResponse('error', null, 'Full name must be alphabetics.');
    }

    if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        sendResponse('error', null, 'Username can only contain letters, numbers, and underscores.');
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        sendResponse('error', null, 'Phone number must be exactly 10 digits.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse('error', null, 'Invalid email format.');
    }

    if (!preg_match("/^[^@]+@[^@]+\.[a-z]{2,}$/i", $email)) {
        sendResponse('error', null, 'Email must have a valid top-level domain (e.g., .com, .org).');
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        sendResponse('error', null, 'Password must be 8+ characters with uppercase, lowercase, number, and symbol.');
    }

    if ($password !== $confirm_password) {
        sendResponse('error', null, 'Passwords do not match.');
    }

    // Check if email or username already exists using prepared statement
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ? OR phone = ?");
    $stmt->bind_param("sss", $email, $username, $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        sendResponse('error', null, 'Email or username already exists.');
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Default values for self-registration
    $role_id = 3;    // Student
    $branch_id = 1;  // Main branch
    $status = 'active';

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (fullname, role_id, branch_id, username, password, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisssss", $fullname, $role_id, $branch_id,  $username, $hashedPassword, $email, $phone, $status);
    $result = $stmt->execute();

    if ($result) {
        // 🔒 Security: prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role_id']   = $role_id;
        $_SESSION['role_name'] = 'Student';

        sendResponse('success', null, 'Registration successful! Redirecting...');
    } else {
        sendResponse('error', null, 'Registration failed. Please try again.');
    }
}

function logoutUser()
{
    // Ensure session started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // If no active user session, just redirect
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login");
        exit;
    }

    // Clear session variables
    $_SESSION = [];

    // Destroy the session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    // Start a new session and regenerate ID
    session_start();
    session_regenerate_id(true);

    // Redirect
    header("Location: /login");
    exit;
}
