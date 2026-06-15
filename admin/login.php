<?php
/**
 * Salim Hırdavat - Admin Giriş
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isLoggedIn() && isAdmin()) {
    redirect(SITE_URL . '/admin');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'E-posta ve şifre gereklidir.';
    } else {
        $result = login($email, $password);
        if ($result['success']) {
            if (isAdmin()) {
                redirect(SITE_URL . '/admin');
            } else {
                logout();
                $error = 'Bu alana erişim yetkiniz bulunmamaktadır.';
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1A2332 0%, #0d1520 50%, #1a1a2e 100%);
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-header .logo {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }
        .login-header .logo span { color: #CC0000; }
        .login-header p {
            color: rgba(255,255,255,0.5);
            font-size: 14px;
        }
        .login-card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 36px;
        }
        .login-card h2 {
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 24px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .form-group .input-wrapper {
            position: relative;
        }
        .form-group .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            font-size: 16px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: all 0.3s;
        }
        .form-group input:focus {
            border-color: #CC0000;
            background: rgba(204,0,0,0.05);
            box-shadow: 0 0 0 3px rgba(204,0,0,0.1);
        }
        .form-group input::placeholder { color: rgba(255,255,255,0.3); }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #CC0000;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
        }
        .btn-login:hover { background: #a00; transform: translateY(-1px); box-shadow: 0 4px 15px rgba(204,0,0,0.4); }
        .error-msg {
            background: rgba(220,53,69,0.15);
            border: 1px solid rgba(220,53,69,0.3);
            color: #ff6b7a;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: rgba(255,255,255,0.4);
            font-size: 13px;
            text-decoration: none;
        }
        .back-link:hover { color: rgba(255,255,255,0.7); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">Salim <span>Hırdavat</span></div>
            <p>Yönetim Paneli</p>
        </div>
        
        <div class="login-card">
            <h2><i class="fas fa-lock" style="color: #CC0000; margin-right: 8px;"></i> Admin Giriş</h2>
            
            <?php if ($error): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>E-posta Adresi</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="admin@salimhirdavat.com" required 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Şifre</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Giriş Yap
                </button>
            </form>
        </div>
        
        <a href="<?= SITE_URL ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Siteye Dön
        </a>
    </div>
</body>
</html>
