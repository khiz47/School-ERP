<?php
function sendResponse($status, $payload, $message)
{
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'data' => $payload,
        'message' => $message
    ]);
    exit;
}

function check_login($roles = [])
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login");
        exit();
    }
    if (!empty($roles) && !in_array($_SESSION['role_name'], (array)$roles)) {
        header("Location: /login");
        exit();
    }
}