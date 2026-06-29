<?php
session_start();

require_once 'includes/Database.php';
require_once 'includes/Validator.php';

$formData = [];
$errors = [];
$serverErrors = [];

// Обработка POST (фоллбек без JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $serverErrors[] = 'Ошибка безопасности. Обновите страницу.';
    } else {
        $formData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'dishes' => $_POST['dishes'] ?? [],
            'biography' => trim($_POST['biography'] ?? ''),
            'agreed' => $_POST['agreed'] ?? ''
        ];
        
        $errors = Validator::validate($formData);
        
        if (empty($errors)) {
            try {
                $db = Database::getInstance();
                
                $login = 'user_' . random_int(10000, 99999);
                $password = substr(bin2hex(random_bytes(4)), 0, 8);
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $db->beginTransaction();
                
                $stmt = $db->prepare("INSERT INTO users_auth (login, password_hash) VALUES (?, ?)");
                $stmt->execute([$login, $passwordHash]);
                $authId = $db->lastInsertId();
                
                $stmt = $db->prepare(
                    "INSERT INTO bookings (full_name, phone, email, birth_date, gender, biography, agreed, user_id) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $formData['full_name'], $formData['phone'], $formData['email'],
                    $formData['birth_date'], $formData['gender'],
                    $formData['biography'] ?? '', $formData['agreed'] ? 1 : 0, $authId
                ]);
                $bookingId = $db->lastInsertId();
                
                if (!empty($formData['dishes'])) {
                    $stmtDish = $db->prepare("SELECT id FROM dishes WHERE name = ?");
                    $stmtInsert = $db->prepare("INSERT INTO booking_dishes (booking_id, dish_id) VALUES (?, ?)");
                    foreach ($formData['dishes'] as $dish) {
                        $stmtDish->execute([$dish]);
                        $dishId = $stmtDish->fetchColumn();
                        if ($dishId) {
                            $stmtInsert->execute([$bookingId, $dishId]);
                        }
                    }
                }
                
                $db->commit();
                
                setcookie('login', $login, time() + 3600, '/');
                setcookie('pass', $password, time() + 3600, '/');
                setcookie('save_success', '1', time() + 3600, '/');
                
                header('Location: index.php');
                exit;
                
            } catch (Exception $e) {
                if (isset($db)) $db->rollBack();
                $serverErrors[] = 'Ошибка сохранения данных.';
            }
        }
    }
}

// Загрузка данных авторизованного пользователя
if (isset($_SESSION['login']) && isset($_SESSION['uid'])) {
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$_SESSION['uid']]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            $stmtDish = $db->prepare(
                "SELECT d.name FROM booking_dishes bd 
                 JOIN dishes d ON bd.dish_id = d.id 
                 WHERE bd.booking_id = ?"
            );
            $stmtDish->execute([$booking['id']]);
            $booking['dishes'] = $stmtDish->fetchAll(PDO::FETCH_COLUMN);
            
            $formData = array_merge($formData ?: [], [
                'full_name' => $booking['full_name'],
                'phone' => $booking['phone'],
                'email' => $booking['email'],
                'birth_date' => $booking['birth_date'],
                'gender' => $booking['gender'],
                'dishes' => $booking['dishes'],
                'biography' => $booking['biography'] ?? '',
                'agreed' => $booking['agreed'] ? '1' : ''
            ]);
        }
    } catch (Exception $e) {}
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Простая шаблонизация
function renderHeader($title) {
    $loggedIn = isset($_SESSION['login']) ? 'true' : 'false';
    $uid = $_SESSION['uid'] ?? '';
    ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ресторан «Вкус Востока» | <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-logged-in="<?= $loggedIn ?>" data-user-id="<?= htmlspecialchars($uid) ?>">
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo"><i class="fas fa-utensils"></i> Вкус Востока</a>
            <ul class="nav-menu">
                <li><a href="#about">О ресторане</a></li>
                <li><a href="#menu">Меню</a></li>
                <li><a href="#booking">Бронирование</a></li>
            </ul>
            <button class="mobile-toggle"><span></span><span></span><span></span></button>
        </div>
    </nav>

    <div class="auth-bar">
        <div class="container">
            <?php if (isset($_SESSION['login'])): ?>
                <span>✅ Вы вошли как: <strong><?= htmlspecialchars($_SESSION['login']) ?></strong></span>
                <a href="logout.php" class="btn btn-sm btn-outline">Выйти</a>
            <?php else: ?>
                <span>🔐</span>
                <a href="login.php" class="btn btn-sm btn-outline">Войти для изменения брони</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_COOKIE['save_success'])): ?>
        <?php setcookie('save_success', '', time() - 3600, '/'); ?>
        <div class="message success"><div class="container">✅ Бронирование успешно сохранено!</div></div>
    <?php endif; ?>

    <?php if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])): ?>
        <div class="message success">
            <div class="container">
                🔑 Логин: <strong><?= htmlspecialchars($_COOKIE['login']) ?></strong><br>
                🔒 Пароль: <strong><?= htmlspecialchars($_COOKIE['pass']) ?></strong>
                <?php setcookie('login', '', time() - 3600, '/'); ?>
                <?php setcookie('pass', '', time() - 3600, '/'); ?>
            </div>
        </div>
    <?php endif; ?>
<?php
    return ob_get_clean();
}

function renderFooter() {
    ob_start();
?>
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3><i class="fas fa-utensils"></i> Вкус Востока</h3>
                    <p>Авторская кухня с восточным акцентом с 2015 года.</p>
                </div>
                <div class="footer-col">
                    <h4>Контакты</h4>
                    <p><i class="fas fa-phone"></i> +7 (999) 123-45-67</p>
                    <p><i class="fas fa-envelope"></i> info@vostok.ru</p>
                </div>
                <div class="footer-col">
                    <h4>Часы работы</h4>
                    <p>Пн-Чт: 12:00–23:00</p>
                    <p>Пт-Сб: 12:00–01:00</p>
                </div>
            </div>
            <div class="footer-bottom">© <?= date('Y') ?> Вкус Востока</div>
        </div>
    </footer>
    <script src="public/js/main.js"></script>
</body>
</html>
<?php
    return ob_get_clean();
}

echo renderHeader('Бронирование столика');
?>

<header class="hero">
    <div class="hero-content">
        <h1>Ресторан «Вкус Востока»</h1>
        <p class="hero-subtitle">Авторская кухня с восточным колоритом</p>
        <p class="hero-description">Погрузитесь в атмосферу восточной сказки</p>
        <a href="#booking" class="btn btn-primary btn-lg">Забронировать столик</a>
    </div>
</header>

<section class="section" id="about">
    <div class="container">
        <h2 class="section-title">О нашем ресторане</h2>
        <div class="about-grid">
            <div class="about-text">
                <p>«Вкус Востока» — путешествие в мир восточной гастрономии, где каждое блюдо — произведение искусства.</p>
                <div class="stats">
                    <div class="stat"><span>150+</span><p>блюд</p></div>
                    <div class="stat"><span>8</span><p>лет</p></div>
                    <div class="stat"><span>4.8</span><p>рейтинг</p></div>
                </div>
            </div>
            <div class="about-image"><i class="fas fa-utensils"></i></div>
        </div>
    </div>
</section>

<!-- МЕНЮ С ФОТО -->
<section class="section section-dark" id="menu">
    <div class="container">
        <h2 class="section-title">Наше меню</h2>
        <p class="section-description">Лучшие блюда восточной кухни от нашего шеф-повара</p>
        <div class="menu-grid">
            <!-- Плов -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🍚</div>
                <div class="menu-icon"></div>
                <h3>Плов узбекский</h3>
                <p>Ароматный плов с бараниной, морковью и специями</p>
            </div>
            
            <!-- Лагман -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🍜</div>
                <div class="menu-icon"></div>
                <h3>Лагман</h3>
                <p>Домашняя лапша с мясом и овощами в пряном бульоне</p>
            </div>
            
            <!-- Манты -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🥟</div>
                <div class="menu-icon"></div>
                <h3>Манты</h3>
                <p>Нежные манты с сочной бараниной и луком</p>
            </div>
            
            <!-- Шашлык -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🥩</div>
                <div class="menu-icon"></div>
                <h3>Шашлык из баранины</h3>
                <p>Маринованная баранина на углях с гранатовым соусом</p>
            </div>
            
            <!-- Люля-кебаб -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🍢</div>
                <div class="menu-icon"></div>
                <h3>Люля-кебаб</h3>
                <p>Рубленое мясо на шпажках с зеленью и специями</p>
            </div>
            
            <!-- Шурпа -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🥣</div>
                <div class="menu-icon"></div>
                <h3>Шурпа</h3>
                <p>Наваристый суп с бараниной и овощами</p>
            </div>
            
            <!-- Долма -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🌯</div>
                <div class="menu-icon"></div>
                <h3>Долма</h3>
                <p>Виноградные листья с мясом и рисом</p>
            </div>
            
            <!-- Самса -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🥐</div>
                <div class="menu-icon"></div>
                <h3>Самса</h3>
                <p>Слоёное тесто с мясной начинкой из тандыра</p>
            </div>
            
            <!-- Чебуреки -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🥮</div>
                <div class="menu-icon"></div>
                <h3>Чебуреки</h3>
                <p>Хрустящие чебуреки с сыром и зеленью</p>
            </div>
            
            <!-- Пахлава -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🍯</div>
                <div class="menu-icon"></div>
                <h3>Пахлава</h3>
                <p>Медовая пахлава с орехами и фисташками</p>
            </div>
            
            <!-- Чак-чак -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🍪</div>
                <div class="menu-icon"></div>
                <h3>Чак-чак</h3>
                <p>Обжаренное тесто с медовым сиропом</p>
            </div>
            
            <!-- Чай -->
            <div class="menu-item">
                <div class="menu-photo-placeholder">🍵</div>
                <div class="menu-icon"></div>
                <h3>Чай зелёный</h3>
                <p>Ароматный зелёный чай с восточными сладостями</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="booking">
    <div class="container">
        <h2 class="section-title">Бронирование столика</h2>
        
        <?php if (!empty($serverErrors)): ?>
            <div class="message error"><?= htmlspecialchars(implode(', ', $serverErrors)) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <h4>Исправьте ошибки:</h4>
                <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        
        <div class="booking-container">
            <div class="booking-info">
                <h3>Почему мы?</h3>
                <ul class="features-list">
                    <li><i class="fas fa-check"></i> Живая музыка</li>
                    <li><i class="fas fa-check"></i> Детская комната</li>
                    <li><i class="fas fa-check"></i> Парковка</li>
                    <li><i class="fas fa-check"></i> Скидка 10% в ДР</li>
                </ul>
            </div>
            <div class="booking-form-container">
                <form id="booking-form" action="index.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">ФИО *</label>
                            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>" required>
                            <span class="error-message" data-error="full_name"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Телефон *</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" required>
                            <span class="error-message" data-error="phone"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                            <span class="error-message" data-error="email"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="birth_date">Дата рождения *</label>
                            <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars($formData['birth_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>
                            <span class="error-message" data-error="birth_date"></span>
                        </div>
                        
                        <div class="form-group">
                            <label>Пол *</label>
                            <div class="radio-group">
                                <label class="radio-label"><input type="radio" name="gender" value="male" <?= ($formData['gender'] ?? '') === 'male' ? 'checked' : '' ?> required> Мужской</label>
                                <label class="radio-label"><input type="radio" name="gender" value="female" <?= ($formData['gender'] ?? '') === 'female' ? 'checked' : '' ?>> Женский</label>
                            </div>
                            <span class="error-message" data-error="gender"></span>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="dishes">Любимые блюда *</label>
                            <select id="dishes" name="dishes[]" multiple required size="8">
                                <?php foreach (Validator::getAllowedDishes() as $dish): ?>
                                <option value="<?= $dish ?>" <?= in_array($dish, $formData['dishes'] ?? []) ? 'selected' : '' ?>><?= $dish ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small>Удерживайте Ctrl для выбора нескольких</small>
                            <span class="error-message" data-error="dishes"></span>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="biography">Пожелания</label>
                            <textarea id="biography" name="biography" rows="3"><?= htmlspecialchars($formData['biography'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="checkbox-label">
                                <input type="checkbox" name="agreed" value="1" <?= !empty($formData['agreed']) ? 'checked' : '' ?> required>
                                Согласен с условиями бронирования *
                            </label>
                            <span class="error-message" data-error="agreed"></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                        <i class="fas fa-calendar-check"></i> Забронировать столик
                    </button>
                    <div id="form-response" class="form-response" style="display:none;"></div>
                </form>
            </div>
        </div>
    </div>
</section>

<?= renderFooter() ?>