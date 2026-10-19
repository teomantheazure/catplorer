<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$USERS_BIN_ID = '6ad3c0d3ffd5d16053742348';
$API_KEY = '$2a$10$mhclHkcO6Xxpuk8JaKAE7ewC/qR7pVnfIPlTzKLvrdOWHGh7tqzK6';

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

$context = stream_context_create(['http' => ['method' => 'GET', 'header' => "X-Master-Key: $API_KEY\r\n"]]);
$response = @file_get_contents("https://api.jsonbin.io/v3/b/$USERS_BIN_ID/latest", false, $context);
$users = json_decode($response, true)['record']['users'] ?? [];

foreach ($users as $u) {
    if (strtolower($u['username']) === strtolower($username)) {
        if (password_verify($password, $u['password'])) {
            echo json_encode(['success' => true, 'username' => $u['username']]);
        } else {
            echo json_encode(['error' => 'Şifre yanlış.']);
        }
        exit;
    }
}
echo json_encode(['error' => 'Kullanıcı bulunamadı.']);
?>