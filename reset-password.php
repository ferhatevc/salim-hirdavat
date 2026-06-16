<?php
require_once __DIR__ . '/config/config.php';

$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

db()->query("UPDATE users SET password_hash = ? WHERE email = ?", [$hash, 'admin@salimhirdavat.com']);

echo "✅ Admin şifresi sıfırlandı!<br>";
echo "E-posta: admin@salimhirdavat.com<br>";
echo "Şifre: admin123<br>";
echo "<br>⚠️ Bu dosyayı hemen silin!";
