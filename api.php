<?php
/**
 * api.php - Веб-сервис для работы с формой
 * Использование:
 * POST api.php - создание новой записи
 * PUT api.php?id=123 - обновление (авторизованный)
 * GET api.php?id=123 - получение (авторизованный)
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'includes/Database.php';
require_once 'includes/Validator.php';

$method = $_SERVER['REQUEST_METHOD'];

// Получаем ID из разных источников
$resourceId = null;

// Пробуем получить ID из PATH_INFO (если сервер поддерживает)
if (!empty($_SERVER['PATH_INFO'])) {
    $resourceId = trim($_SERVER['PATH_INFO'], '/');
}

// Если нет PATH_INFO, пробуем GET-параметр
if (!$resourceId && isset($_GET['id'])) {
    $resourceId = $_GET['id'];
}

// Для PUT-запросов (форма не умеет PUT, но можно через скрытое поле _method)
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    $method = 'PUT';
    // Данные берём из JSON тела запроса
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
        unset($input['_method']);
    }
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input && $method === 'POST') {
        $input = $_POST;
    }
}

// Подключаемся к БД
try {
    $db = Database::getInstance();
} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера']);
    exit;
}

// Маршрутизация
switch ($method) {
    case 'POST':
        handlePost($db, $input);
        break;
    case 'GET':
        handleGet($db, $resourceId);
        break;
    case 'PUT':
        handlePut($db, $resourceId, $input);
        break;
    case 'OPTIONS':
        http_response_code(200);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается']);
}

function handlePost($db, $input) {
    $errors = Validator::validate($input);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        return;
    }
    
    // Генерируем логин и пароль
    $login = 'user_' . random_int(10000, 99999);
    $password = substr(bin2hex(random_bytes(4)), 0, 8);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("INSERT INTO users_auth (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $passwordHash]);
        $authId = $db->lastInsertId();
        
        $stmt = $db->prepare(
            "INSERT INTO users (full_name, phone, email, birth_date, gender, biography, agreed, user_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $input['full_name'], $input['phone'], $input['email'],
            $input['birth_date'], $input['gender'],
            $input['biography'] ?? '', $input['agreed'] ? 1 : 0, $authId
        ]);
        $userId = $db->lastInsertId();
        
        if (!empty($input['languages'])) {
            $stmtLang = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $stmtInsert = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            foreach ($input['languages'] as $lang) {
                $stmtLang->execute([$lang]);
                $langId = $stmtLang->fetchColumn();
                if ($langId) {
                    $stmtInsert->execute([$userId, $langId]);
                }
            }
        }
        
        $db->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'login' => $login,
            'password' => $password,
            'profile_url' => 'http://u82683.kubsu-dev.ru/number8/'
        ]);
        
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка сохранения']);
    }
}

function handleGet($db, $id) {
    session_start();
    if (!isset($_SESSION['uid'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Необходима авторизация']);
        return;
    }
    
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Не указан ID']);
        return;
    }
    
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([(int)$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Данные не найдены']);
        return;
    }
    
    $stmtLang = $db->prepare(
        "SELECT pl.name FROM user_languages ul 
         JOIN programming_languages pl ON ul.language_id = pl.id 
         WHERE ul.user_id = ?"
    );
    $stmtLang->execute([$user['id']]);
    $user['languages'] = $stmtLang->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($user, JSON_UNESCAPED_UNICODE);
}

function handlePut($db, $id, $input) {
    session_start();
    if (!isset($_SESSION['uid'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Необходима авторизация']);
        return;
    }
    
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Не указан ID']);
        return;
    }
    
    $errors = Validator::validate($input);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([(int)$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Данные не найдены']);
            return;
        }
        
        $stmt = $db->prepare(
            "UPDATE users SET full_name=?, phone=?, email=?, birth_date=?, 
             gender=?, biography=?, agreed=? WHERE id=?"
        );
        $stmt->execute([
            $input['full_name'], $input['phone'], $input['email'],
            $input['birth_date'], $input['gender'],
            $input['biography'] ?? '', $input['agreed'] ? 1 : 0,
            $user['id']
        ]);
        
        $db->exec("DELETE FROM user_languages WHERE user_id = " . (int)$user['id']);
        
        if (!empty($input['languages'])) {
            $stmtLang = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $stmtInsert = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            foreach ($input['languages'] as $lang) {
                $stmtLang->execute([$lang]);
                $langId = $stmtLang->fetchColumn();
                if ($langId) {
                    $stmtInsert->execute([$user['id'], $langId]);
                }
            }
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Данные обновлены']);
        
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка обновления']);
    }
}