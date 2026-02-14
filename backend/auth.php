<?php
require 'config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'register') {
            register($pdo, $input);
        } elseif ($_GET['action'] === 'login') {
            login($pdo, $input);
        } elseif ($_GET['action'] === 'logout') {
            logout();
        }
    }
} elseif ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check_session') {
    check_session();
}

function register($pdo, $input)
{
    if (!isset($input['username'], $input['email'], $input['password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        return;
    }

    $username = $input['username'];
    $email = $input['email'];
    $password = password_hash($input['password'], PASSWORD_DEFAULT);
    $role = isset($input['role']) ? $input['role'] : 'tourist'; // Default to tourist

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        echo json_encode(['message' => 'User registered successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Registration failed: ' . $e->getMessage()]);
    }
}

function login($pdo, $input)
{
    if (!isset($input['email'], $input['password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing email or password']);
        return;
    }

    $email = $input['email'];
    $password = $input['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            echo json_encode([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Login failed: ' . $e->getMessage()]);
    }
}

function logout()
{
    session_destroy();
    echo json_encode(['message' => 'Logged out successfully']);
}

function check_session()
{
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'loggedIn' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role']
            ]
        ]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
}
?>