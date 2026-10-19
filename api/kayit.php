<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ⭐ Kullanıcılar ayrı Bin'de ⭐
$USERS_BIN_ID = '6ad3c0d3ffd5d16053742348';
$API_KEY = '$2a$10$mhclHkcO6Xxpuk8JaKAE7ewC/qR7pVnfIPlTzKLvrdOWHGh7tqzK6';

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$davetKodu = trim($data['davetKodu'] ?? ''); // ⭐ Davet kodu (opsiyonel) ⭐

if (strlen($username) < 3 || strlen($password) < 4) {
    echo json_encode(['error' => 'Kullanıcı adı en az 3, şifre en az 4 karakter olmalı.']);
    exit;
}

// Mevcut kullanıcıları oku
$context = stream_context_create(['http' => ['method' => 'GET', 'header' => "X-Master-Key: $API_KEY\r\n"]]);
$response = @file_get_contents("https://api.jsonbin.io/v3/b/$USERS_BIN_ID/latest", false, $context);
$users = json_decode($response, true)['record']['users'] ?? [];

// Kullanıcı adı zaten var mı?
foreach ($users as $u) {
    if (strtolower($u['username']) === strtolower($username)) {
        echo json_encode(['error' => 'Bu kullanıcı adı zaten alınmış.']);
        exit;
    }
}

// ⭐ Davet kodunu kontrol et ⭐
$davetEden = null;
if (!empty($davetKodu)) {
    foreach ($users as $u) {
        if (isset($u['davetKodu']) && $u['davetKodu'] === $davetKodu) {
            $davetEden = $u['username'];
            break;
        }
    }
}

// ⭐ Yeni kullanıcıyı oluştur ⭐
$yeniKullanici = [
    'username' => $username,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'avatar' => '🐱',
    'davetKodu' => strtolower($username) . '-' . substr(md5(uniqid()), 0, 4),
    'bonus' => $davetEden ? 500 : 0 // Davet edildiyse +500 bonus
];

if ($davetEden) {
    $yeniKullanici['invitedBy'] = $davetEden;
    $yeniKullanici['scores'] = []; // Skor geçmişi için
    $yeniKullanici['bestScore'] = $davetEden ? 500 : 0;
}

$users[] = $yeniKullanici;

// ⭐ Davet edene de 500 bonus ver ⭐
if ($davetEden) {
    foreach ($users as &$u) {
        if (strtolower($u['username']) === strtolower($davetEden)) {
            $u['bonus'] = ($u['bonus'] ?? 0) + 500;
            $u['davetEdilenSayisi'] = ($u['davetEdilenSayisi'] ?? 0) + 1;
            break;
        }
    }
    unset($u);
}

// Geri yaz
$json = json_encode(['users' => $users]);
$context = stream_context_create(['http' => ['method' => 'PUT', 'header' => "Content-Type: application/json\r\nX-Master-Key: $API_KEY\r\n", 'content' => $json]]);
@file_get_contents("https://api.jsonbin.io/v3/b/$USERS_BIN_ID", false, $context);

echo json_encode([
    'success' => true, 
    'username' => $username,
    'davetEden' => $davetEden,
    'bonus' => $davetEden ? 500 : 0
]);
?>