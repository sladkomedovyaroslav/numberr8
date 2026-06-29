<?php
session_start();

// Подключаем модули
require_once 'includes/Database.php';
require_once 'includes/Validator.php';
require_once 'includes/Template.php';

$template = new Template();

// Обработка POST-запроса (когда JS отключен - фоллбек)
$formData = [];
$errors = [];
$serverErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $serverErrors[] = 'Ошибка безопасности. Пожалуйста, обновите страницу.';
    } else {
        $formData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'languages' => $_POST['languages'] ?? [],
            'biography' => trim($_POST['biography'] ?? ''),
            'agreed' => $_POST['agreed'] ?? ''
        ];
        
        // Валидация (те же правила, что и в API)
        $errors = Validator::validate($formData);
        
        if (empty($errors)) {
            // Сохраняем через API (внутренний вызов)
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
                    "INSERT INTO users (full_name, phone, email, birth_date, gender, biography, agreed, user_id) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $formData['full_name'], $formData['phone'], $formData['email'],
                    $formData['birth_date'], $formData['gender'],
                    $formData['biography'] ?? '', $formData['agreed'] ? 1 : 0, $authId
                ]);
                $userId = $db->lastInsertId();
                
                if (!empty($formData['languages'])) {
                    $stmtLang = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
                    $stmtInsert = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
                    foreach ($formData['languages'] as $lang) {
                        $stmtLang->execute([$lang]);
                        $langId = $stmtLang->fetchColumn();
                        if ($langId) {
                            $stmtInsert->execute([$userId, $langId]);
                        }
                    }
                }
                
                $db->commit();
                
                // Устанавливаем куки с логином и паролем
                setcookie('login', $login, time() + 3600, '/');
                setcookie('pass', $password, time() + 3600, '/');
                setcookie('save_success', '1', time() + 3600, '/');
                
                // Редирект для предотвращения повторной отправки
                header('Location: /restaurant/');
                exit;
                
            } catch (Exception $e) {
                $db->rollBack();
                $serverErrors[] = 'Ошибка сохранения данных. Попробуйте позже.';
            }
        }
    }
}

// Если пользователь авторизован, загружаем его данные
if (isset($_SESSION['login']) && isset($_SESSION['uid'])) {
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            $stmtLang = $db->prepare(
                "SELECT pl.name FROM user_languages ul 
                 JOIN programming_languages pl ON ul.language_id = pl.id 
                 WHERE ul.user_id = ?"
            );
            $stmtLang->execute([$userData['id']]);
            $userData['languages'] = $stmtLang->fetchAll(PDO::FETCH_COLUMN);
            
            // Заполняем форму данными пользователя
            $formData = array_merge($formData ?: [], [
                'full_name' => $userData['full_name'],
                'phone' => $userData['phone'],
                'email' => $userData['email'],
                'birth_date' => $userData['birth_date'],
                'gender' => $userData['gender'],
                'languages' => $userData['languages'],
                'biography' => $userData['biography'],
                'agreed' => $userData['agreed'] ? '1' : ''
            ]);
        }
    } catch (Exception $e) {
        // Игнорируем ошибку загрузки данных
    }
}

// Рендерим страницу
$headerHtml = $template->render('header', [
    'pageTitle' => 'Бронирование столика'
]);

$footerHtml = $template->render('footer');

// Выводим всё
echo $headerHtml;
?>

<!-- Hero секция -->
<header class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Ресторан «Вкус Востока»</h1>
        <p class="hero-subtitle">Авторская кухня с восточным колоритом</p>
        <p class="hero-description">Погрузитесь в атмосферу восточной сказки и насладитесь изысканными блюдами от шеф-повара</p>
        <a href="#booking" class="btn btn-primary btn-lg">
            <i class="fas fa-calendar-alt"></i> Забронировать столик
        </a>
    </div>
</header>

<!-- О ресторане -->
<section class="section" id="about">
    <div class="container">
        <h2 class="section-title">О нашем ресторане</h2>
        <div class="about-grid">
            <div class="about-text">
                <p>«Вкус Востока» — это больше, чем ресторан. Это путешествие в мир восточной гастрономии, где каждое блюдо — произведение искусства.</p>
                <p>Наш шеф-повар обучался у лучших мастеров восточной кухни и привёз уникальные рецепты, которые вы не найдёте больше нигде в городе.</p>
                <div class="stats">
                    <div class="stat"><span>150+</span> блюд в меню</div>
                    <div class="stat"><span>8</span> лет работы</div>
                    <div class="stat"><span>4.8</span> рейтинг</div>
                </div>
            </div>
            <div class="about-image">
                <img src="public/images/interior.jpg" alt="Интерьер ресторана">
            </div>
        </div>
    </div>
</section>

<!-- Меню -->
<section class="section section-dark" id="menu">
    <div class="container">
        <h2 class="section-title">Наше меню</h2>
        <div class="menu-grid">
            <div class="menu-item">
                <i class="fas fa-utensils menu-icon"></i>
                <h3>Горячие блюда</h3>
                <p>Плов, лагман, манты и другие блюда восточной кухни</p>
            </div>
            <div class="menu-item">
                <i class="fas fa-fish menu-icon"></i>
                <h3>Супы</h3>
                <p>Шурпа, харчо, лапша по-восточному</p>
            </div>
            <div class="menu-item">
                <i class="fas fa-drumstick-bite menu-icon"></i>
                <h3>Мангал</h3>
                <p>Шашлык, люля-кебаб, овощи на гриле</p>
            </div>
            <div class="menu-item">
                <i class="fas fa-cake menu-icon"></i>
                <h3>Десерты</h3>
                <p>Пахлава, чак-чак, восточные сладости</p>
            </div>
        </div>
    </div>
</section>

<!-- Форма бронирования -->
<section class="section" id="booking">
    <div class="container">
        <h2 class="section-title">Бронирование столика</h2>
        <p class="section-description">Заполните форму, и мы забронируем для вас лучший столик</p>
        
        <?php if (!empty($serverErrors)): ?>
            <div class="message error">
                <?php foreach ($serverErrors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <h4>Пожалуйста, исправьте ошибки:</h4>
                <ul>
                    <?php foreach ($errors as $field => $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="booking-container">
            <div class="booking-info">
                <h3>Почему стоит выбрать нас?</h3>
                <ul class="features-list">
                    <li><i class="fas fa-check"></i> Живая музыка каждый вечер</li>
                    <li><i class="fas fa-check"></i> Детская комната с аниматором</li>
                    <li><i class="fas fa-check"></i> Бесплатная парковка</li>
                    <li><i class="fas fa-check"></i> Проведение банкетов</li>
                    <li><i class="fas fa-check"></i> Скидка 10% в день рождения</li>
                </ul>
            </div>
            <div class="booking-form-container">
                <?php $template->render('form', ['formData' => $formData]); ?>
            </div>
        </div>
    </div>
</section>

<?php
echo $footerHtml;