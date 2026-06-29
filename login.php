<?php
session_start();

if (isset($_SESSION['login'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/Database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($login) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM users_auth WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['login'] = $user['login'];
                $_SESSION['uid'] = $user['id'];
                header('Location: index.php');
                exit;
            }
            
            // Проверка старого формата md5
            if ($user && $user['password_hash'] === md5($password)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users_auth SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newHash, $user['id']]);
                
                $_SESSION['login'] = $user['login'];
                $_SESSION['uid'] = $user['id'];
                header('Location: index.php');
                exit;
            }
            
            $error = 'Неверный логин или пароль';
        } catch (Exception $e) {
            $error = 'Ошибка сервера. Попробуйте позже.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Ресторан «Вкус Востока»</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <h1><i class="fas fa-utensils"></i> Вкус Востока</h1>
            <p class="login-subtitle">Войдите для редактирования бронирования</p>
            
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" required 
                           placeholder="user_12345" autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="••••••••" autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary">Войти</button>
            </form>
            
            <p class="login-link">
                <a href="index.php">← Вернуться на главную</a>
            </p>
        </div>
    </div>
</body>
</html>