<?php
/**
 * Tek seferlik admin şifre sıfırlama
 * Bu dosyayı çalıştırdıktan sonra silin!
 */
require_once __DIR__ . '/config/config.php';

$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

$stmt = db()->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->execute([$hash, 'admin@salimhirdavat.com']);

echo "✅ Admin şifresi sıfırlandı!\n";
echo "E-posta: admin@salimhirdavat.com\n";
echo "Şifre: admin123\n";
echo "\n⚠️ Bu dosyayı hemen silin!";
