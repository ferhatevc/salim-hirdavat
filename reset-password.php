<?php
require_once __DIR__ . '/config/config.php';

// 1. Şifreyi sıfırla
$hash = password_hash('admin123', PASSWORD_BCRYPT);
db()->query("UPDATE users SET password_hash = ?, is_active = 1 WHERE email = ?", [$hash, 'admin@salimhirdavat.com']);

// 2. Rate limiting temizle
if (isset($_SESSION['login_attempts'])) unset($_SESSION['login_attempts']);
if (isset($_SESSION['login_locked_until'])) unset($_SESSION['login_locked_until']);

// 3. Kontrol
$user = db()->fetch("SELECT id, email, role, is_active, password_hash FROM users WHERE email = ?", ['admin@salimhirdavat.com']);

echo "<h2>✅ Admin Şifresi Sıfırlandı!</h2>";
echo "<p><strong>E-posta:</strong> admin@salimhirdavat.com</p>";
echo "<p><strong>Şifre:</strong> admin123</p>";
echo "<p><strong>Aktif:</strong> " . ($user['is_active'] ? 'Evet' : 'Hayır') . "</p>";
echo "<p><strong>Rol:</strong> " . $user['role'] . "</p>";

// 4. Test
$test = password_verify('admin123', $user['password_hash']);
echo "<p><strong>Şifre Doğrulama Testi:</strong> " . ($test ? '✅ BAŞARILI' : '❌ BAŞARISIZ') . "</p>";

echo "<br><a href='/admin/login.php' style='background:#CC0000;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;'>→ Admin Paneline Git</a>";
