<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ресторан «Вкус Востока» | <?= htmlspecialchars($pageTitle ?? '') ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <noscript>
        <style>.js-only { display: none !important; }</style>
    </noscript>
</head>
<body data-logged-in="<?= isset($_SESSION['login']) ? 'true' : 'false' ?>" data-user-id="<?= htmlspecialchars($_SESSION['uid'] ?? '') ?>">
    <!-- Навигация -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-utensils"></i> Вкус Востока
            </a>
            <ul class="nav-menu">
                <li><a href="#about">О ресторане</a></li>
                <li><a href="#menu">Меню</a></li>
                <li><a href="#booking">Бронирование</a></li>
                <li><a href="#contacts">Контакты</a></li>
            </ul>
            <button class="mobile-toggle" aria-label="Меню">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <!-- Статус авторизации -->
    <div class="auth-bar">
        <div class="container">
            <?php if (isset($_SESSION['login'])): ?>
                <span class="auth-status">
                    ✅ Вы вошли как: <strong><?= htmlspecialchars($_SESSION['login']) ?></strong>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline">Выйти</a>
            <?php else: ?>
                <span class="auth-status">🔐</span>
                <a href="login.php" class="btn btn-sm btn-outline">Войти для изменения брони</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Сообщения -->
    <?php if (!empty($_COOKIE['save_success'])): ?>
        <?php setcookie('save_success', '', time() - 3600, '/'); ?>
        <div class="message success">
            <div class="container">
                <i class="fas fa-check-circle"></i> Бронирование успешно сохранено!
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])): ?>
        <div class="message success">
            <div class="container">
                🔑 Ваш логин: <strong><?= htmlspecialchars($_COOKIE['login']) ?></strong><br>
                🔒 Пароль: <strong><?= htmlspecialchars($_COOKIE['pass']) ?></strong><br>
                <small>Сохраните эти данные для входа и редактирования брони</small>
                <?php setcookie('login', '', time() - 3600, '/'); ?>
                <?php setcookie('pass', '', time() - 3600, '/'); ?>
            </div>
        </div>
    <?php endif; ?>