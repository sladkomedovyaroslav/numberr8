<?php
session_start();

require_once 'includes/Database.php';
require_once 'includes/Validator.php';
require_once 'includes/Template.php';

$template = new Template();
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
            'languages' => $_POST['languages'] ?? [],
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
                
                setcookie('login', $login, time() + 3600, '/');
                setcookie('pass', $password, time() + 3600, '/');
                setcookie('save_success', '1', time() + 3600, '/');
                
                header('Location: index.php');
                exit;
                
            } catch (Exception $e) {
                $db->rollBack();
                $serverErrors[] = 'Ошибка сохранения данных.';
            }
        }
    }
}

// Загрузка данных авторизованного пользователя
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
            
            $formData = array_merge($formData ?: [], [
                'full_name' => $userData['full_name'],
                'phone' => $userData['phone'],
                'email' => $userData['email'],
                'birth_date' => $userData['birth_date'],
                'gender' => $userData['gender'],
                'languages' => $userData['languages'],
                'biography' => $userData['biography'] ?? '',
                'agreed' => $userData['agreed'] ? '1' : ''
            ]);
        }
    } catch (Exception $e) {}
}

// Генерируем CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Рендерим шапку
echo $template->render('header', ['pageTitle' => 'Бронирование столика']);
?>

<!-- HERO -->
<header class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Ресторан «Вкус Востока»</h1>
        <p class="hero-subtitle">Авторская кухня с восточным колоритом</p>
        <p class="hero-description">Погрузитесь в атмосферу восточной сказки и насладитесь изысканными блюдами</p>
        <a href="#booking" class="btn btn-primary btn-lg">
            <i class="fas fa-calendar-alt"></i> Забронировать столик
        </a>
    </div>
</header>

<!-- О РЕСТОРАНЕ -->
<section class="section" id="about">
    <div class="container">
        <h2 class="section-title">О нашем ресторане</h2>
        <div class="about-grid">
            <div class="about-text">
                <p>«Вкус Востока» — это больше, чем ресторан. Это путешествие в мир восточной гастрономии, где каждое блюдо — произведение искусства.</p>
                <p>Наш шеф-повар обучался у лучших мастеров восточной кухни и привёз уникальные рецепты.</p>
                <div class="stats">
                    <div class="stat"><span>150+</span><p>блюд в меню</p></div>
                    <div class="stat"><span>8</span><p>лет работы</p></div>
                    <div class="stat"><span>4.8</span><p>рейтинг</p></div>
                </div>
            </div>
            <div class="about-image">
                <i class="fas fa-utensils"></i>
            </div>
        </div>
    </div>
</section>

<!-- МЕНЮ -->
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

<!-- ФОРМА БРОНИРОВАНИЯ -->
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
                <form id="booking-form" action="index.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-grid">
                        <!-- ФИО -->
                        <div class="form-group">
                            <label for="full_name">ФИО *</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>"
                                   placeholder="Иванов Иван Иванович" required maxlength="150">
                            <span class="error-message" data-error="full_name"></span>
                        </div>
                        
                        <!-- Телефон -->
                        <div class="form-group">
                            <label for="phone">Телефон *</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($formData['phone'] ?? '') ?>"
                                   placeholder="+7 (999) 123-45-67" required>
                            <span class="error-message" data-error="phone"></span>
                        </div>
                        
                        <!-- Email -->
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email"
                                   value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                                   placeholder="example@mail.ru" required>
                            <span class="error-message" data-error="email"></span>
                        </div>
                        
                        <!-- Дата рождения -->
                        <div class="form-group">
                            <label for="birth_date">Дата рождения *</label>
                            <input type="date" id="birth_date" name="birth_date"
                                   value="<?= htmlspecialchars($formData['birth_date'] ?? '') ?>"
                                   max="<?= date('Y-m-d') ?>" required>
                            <span class="error-message" data-error="birth_date"></span>
                        </div>
                        
                        <!-- Пол -->
                        <div class="form-group">
                            <label>Пол *</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="gender" value="male" 
                                           <?= ($formData['gender'] ?? '') === 'male' ? 'checked' : '' ?> required>
                                    Мужской
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="gender" value="female"
                                           <?= ($formData['gender'] ?? '') === 'female' ? 'checked' : '' ?>>
                                    Женский
                                </label>
                            </div>
                            <span class="error-message" data-error="gender"></span>
                        </div>
                        
                        <!-- Любимые языки -->
                        <div class="form-group full-width">
                            <label for="languages">Любимые языки программирования *</label>
                            <select id="languages" name="languages[]" multiple required size="6">
                                <?php 
                                $languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                                foreach ($languages as $lang):
                                    $selected = in_array($lang, $formData['languages'] ?? []) ? 'selected' : '';
                                ?>
                                    <option value="<?= $lang ?>" <?= $selected ?>><?= $lang ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small>Удерживайте Ctrl/Cmd для выбора нескольких</small>
                            <span class="error-message" data-error="languages"></span>
                        </div>
                        
                        <!-- О себе -->
                        <div class="form-group full-width">
                            <label for="biography">О себе</label>
                            <textarea id="biography" name="biography" rows="4"
                                      placeholder="Расскажите о вашем опыте..."><?= htmlspecialchars($formData['biography'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Согласие -->
                        <div class="form-group full-width">
                            <label class="checkbox-label">
                                <input type="checkbox" name="agreed" value="1"
                                       <?= !empty($formData['agreed']) ? 'checked' : '' ?> required>
                                Я согласен с условиями бронирования *
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

<?php
echo $template->render('footer');
?>