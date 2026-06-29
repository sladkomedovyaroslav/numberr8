<?php
session_start();

// ПРОСТАЯ ЗАЩИТА: админский логин/пароль
$ADMIN_LOGIN = 'admin';
$ADMIN_PASSWORD = 'admin123'; // ПОМЕНЯЙ НА СВОЙ!

// Если не авторизован как админ
if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['login'] === $ADMIN_LOGIN && $_POST['password'] === $ADMIN_PASSWORD) {
            $_SESSION['admin'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
    
    // Показываем форму входа
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Админ-панель | Вкус Востока</title>
        <link rel="stylesheet" href="public/css/style.css">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
        <style>
            .admin-login {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #1a1a1a;
            }
            .admin-login-card {
                background: white;
                padding: 40px;
                border-radius: 8px;
                width: 100%;
                max-width: 400px;
            }
        </style>
    </head>
    <body>
        <div class="admin-login">
            <div class="admin-login-card">
                <h1 style="text-align:center;">🔐 Админ-панель</h1>
                <?php if (isset($error)): ?>
                    <div class="message error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Логин</label>
                        <input type="text" name="login" required>
                    </div>
                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Войти</button>
                </form>
                <p style="text-align:center;margin-top:15px;"><a href="index.php">← На сайт</a></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============ АДМИН-ПАНЕЛЬ (после входа) ============

require_once 'includes/Database.php';

// Удаление записи
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT user_id FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if ($booking) {
            $db->beginTransaction();
            $db->exec("DELETE FROM booking_dishes WHERE booking_id = $id");
            $db->exec("DELETE FROM bookings WHERE id = $id");
            $db->exec("DELETE FROM users_auth WHERE id = " . (int)$booking['user_id']);
            $db->commit();
        }
        header('Location: admin.php?deleted=1');
        exit;
    } catch (Exception $e) {}
}

// Получаем все бронирования
try {
    $db = Database::getInstance();
    $stmt = $db->query("
        SELECT b.*, ua.login 
        FROM bookings b 
        JOIN users_auth ua ON b.user_id = ua.id 
        ORDER BY b.id DESC
    ");
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    $bookings = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель | Бронирования</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .admin-header {
            background: #1a1a1a;
            color: white;
            padding: 20px 0;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        .admin-table th {
            background: #c62828;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .admin-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        .admin-table tr:hover {
            background: #f5f5f5;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-delete:hover {
            background: #c82333;
            color: white;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            background: #d4a853;
            color: white;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container" style="display:flex;justify-content:space-between;align-items:center;">
            <h2>🔐 Админ-панель | Бронирования</h2>
            <div>
                <a href="index.php" class="btn btn-sm btn-outline">На сайт</a>
                <a href="admin.php?logout=1" class="btn btn-sm btn-outline" style="margin-left:10px;">Выйти</a>
            </div>
        </div>
    </div>

    <div class="container" style="margin-top:30px;margin-bottom:50px;">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="message success" style="margin-bottom:20px;">✅ Запись удалена</div>
        <?php endif; ?>
        
        <?php if (empty($bookings)): ?>
            <p style="text-align:center;padding:50px;">Нет бронирований</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Дата рождения</th>
                            <th>Пол</th>
                            <th>Блюда</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): 
                            // Получаем блюда для этого бронирования
                            $stmtD = $db->prepare("SELECT d.name FROM booking_dishes bd JOIN dishes d ON bd.dish_id = d.id WHERE bd.booking_id = ?");
                            $stmtD->execute([$b['id']]);
                            $dishes = $stmtD->fetchAll(PDO::FETCH_COLUMN);
                        ?>
                        <tr>
                            <td><?= $b['id'] ?></td>
                            <td><?= htmlspecialchars($b['login']) ?></td>
                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                            <td><?= htmlspecialchars($b['phone']) ?></td>
                            <td><?= htmlspecialchars($b['email']) ?></td>
                            <td><?= $b['birth_date'] ?></td>
                            <td><?= $b['gender'] === 'male' ? 'М' : 'Ж' ?></td>
                            <td>
                                <?php foreach ($dishes as $dish): ?>
                                    <span class="badge"><?= htmlspecialchars($dish) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <a href="admin.php?delete=<?= $b['id'] ?>" class="btn-delete" onclick="return confirm('Удалить запись?')">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// Выход из админки
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    header('Location: admin.php');
    exit;
}