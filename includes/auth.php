<?php
/**
 * Salim Hırdavat - Kimlik Doğrulama Sistemi
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Kullanıcı girişi
 */
function login($email, $password) {
    // Rate limiting kontrolü
    if (isLoginLocked()) {
        return ['success' => false, 'message' => 'Çok fazla hatalı giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.'];
    }

    $user = db()->fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        incrementLoginAttempts();
        return ['success' => false, 'message' => 'E-posta veya şifre hatalı.'];
    }
    
    // Oturum oluştur
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    // Son giriş güncelle
    db()->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    
    // Giriş denemelerini sıfırla
    resetLoginAttempts();
    
    // Session sepetini veritabanına aktar
    mergeSessionCartToDb($user['id']);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Çıkış
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * Kullanıcı kaydı
 */
function register($data) {
    $errors = [];
    
    // Validasyon
    if (empty($data['first_name'])) $errors[] = 'Ad alanı zorunludur.';
    if (empty($data['last_name'])) $errors[] = 'Soyad alanı zorunludur.';
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi giriniz.';
    }
    if (empty($data['phone']) || !preg_match('/^0[5][0-9]{9}$/', $data['phone'])) {
        $errors[] = 'Geçerli bir telefon numarası giriniz (05XXXXXXXXX).';
    }
    if (empty($data['password']) || strlen($data['password']) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    }
    if ($data['password'] !== ($data['password_confirm'] ?? '')) {
        $errors[] = 'Şifreler eşleşmiyor.';
    }
    
    // Email benzersizlik kontrolü
    if (empty($errors)) {
        $exists = db()->count('users', 'email = ?', [$data['email']]);
        if ($exists > 0) {
            $errors[] = 'Bu e-posta adresi zaten kayıtlı.';
        }
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Kayıt
    $userId = db()->insert('users', [
        'first_name' => sanitize($data['first_name']),
        'last_name' => sanitize($data['last_name']),
        'email' => sanitize($data['email']),
        'phone' => sanitize($data['phone']),
        'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
        'role' => 'customer',
        'is_active' => 1,
        'email_verified' => 0
    ]);
    
    // Otomatik giriş
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = 'customer';
    $_SESSION['user_name'] = sanitize($data['first_name']) . ' ' . sanitize($data['last_name']);
    
    mergeSessionCartToDb($userId);
    
    return ['success' => true, 'user_id' => $userId];
}

/**
 * Giriş zorunluluğu
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = currentUrl();
        flashMessage('warning', 'Bu sayfayı görüntülemek için giriş yapmalısınız.');
        redirect(SITE_URL . '/giris');
    }
}

/**
 * Admin zorunluluğu
 */
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        redirect(SITE_URL . '/admin/login.php');
    }
}

/**
 * Şifre güncelleme
 */
function updatePassword($userId, $oldPassword, $newPassword) {
    $user = db()->fetch("SELECT password_hash FROM users WHERE id = ?", [$userId]);
    
    if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Mevcut şifre hatalı.'];
    }
    
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Yeni şifre en az 6 karakter olmalıdır.'];
    }
    
    db()->update('users', [
        'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])
    ], 'id = ?', [$userId]);
    
    return ['success' => true, 'message' => 'Şifreniz başarıyla güncellendi.'];
}

/**
 * Şifre sıfırlama talebi
 */
function resetPasswordRequest($email) {
    $user = db()->fetch("SELECT id FROM users WHERE email = ? AND is_active = 1", [$email]);
    
    if (!$user) {
        return ['success' => true, 'message' => 'E-posta adresinize şifre sıfırlama bağlantısı gönderildi.'];
    }
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    db()->update('users', [
        'reset_token' => $token,
        'reset_token_expires' => $expires
    ], 'id = ?', [$user['id']]);
    
    // TODO: E-posta gönderimi (SMTP kurulumu gerekli)
    // sendResetEmail($email, $token);
    
    return ['success' => true, 'message' => 'E-posta adresinize şifre sıfırlama bağlantısı gönderildi.'];
}

/**
 * Rate limiting - giriş denemeleri
 */
function isLoginLocked() {
    if (!isset($_SESSION['login_attempts'])) return false;
    if ($_SESSION['login_attempts'] < MAX_LOGIN_ATTEMPTS) return false;
    
    $lockTime = $_SESSION['login_lock_time'] ?? 0;
    if (time() - $lockTime > LOGIN_LOCKOUT_TIME) {
        resetLoginAttempts();
        return false;
    }
    
    return true;
}

function incrementLoginAttempts() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_lock_time'] = time();
    }
}

function resetLoginAttempts() {
    unset($_SESSION['login_attempts'], $_SESSION['login_lock_time']);
}

/**
 * Session sepetini veritabanına aktar
 */
function mergeSessionCartToDb($userId) {
    if (empty($_SESSION['cart'])) return;
    
    foreach ($_SESSION['cart'] as $item) {
        $exists = db()->fetch(
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
            [$userId, $item['product_id']]
        );
        
        if ($exists) {
            db()->update('cart', 
                ['quantity' => $exists['quantity'] + $item['quantity']], 
                'id = ?', [$exists['id']]
            );
        } else {
            db()->insert('cart', [
                'user_id' => $userId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity']
            ]);
        }
    }
    
    unset($_SESSION['cart']);
}
