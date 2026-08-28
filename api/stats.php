<?php
// Отключаем вывод ошибок в браузер
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'config.php';

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['message' => 'Метод не поддерживается'], 405);
}

// Получаем ID аккаунта из URL
$accountId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($accountId <= 0) {
    jsonResponse(['message' => 'Некорректный аккаунт.'], 400);
}

try {
    $db = getDbConnection();
    if (!$db) {
        jsonResponse(['message' => 'Ошибка подключения к базе данных.'], 500);
    }

    // Получаем статистику аккаунта
    $stmt = $db->prepare("
        SELECT
            id, exp, level, last_login, game_for_hour, game_for_day, game_for_day_prev,
            car_slots, biz_slot, home_slot, garage_slot, bp_level, business, house, house_type, garage
        FROM accounts
        WHERE id = :id
        LIMIT 1
    ");
    
    $stmt->execute(['id' => $accountId]);
    $account = $stmt->fetch();

    if (!$account) {
        jsonResponse(['message' => 'Аккаунт не найден.'], 404);
    }

    // Получаем бизнесы
    $stmt = $db->prepare("
        SELECT id, name, type, products, balance, price, rent_price, city
        FROM business
        WHERE owner_id = :owner_id
        ORDER BY id ASC
        LIMIT 20
    ");
    $stmt->execute(['owner_id' => $accountId]);
    $businesses = $stmt->fetchAll();

    // Получаем дома
    $stmt = $db->prepare("
        SELECT id, name, type, price, rent_price, city, improvements
        FROM houses
        WHERE owner_id = :owner_id
        ORDER BY id ASC
        LIMIT 20
    ");
    $stmt->execute(['owner_id' => $accountId]);
    $houses = $stmt->fetchAll();

    // Формируем ответ
    $response = [
        'exp' => (int)$account['exp'],
        'level' => (int)$account['level'],
        'lastLogin' => (int)$account['last_login'],
        'gameForHour' => (int)$account['game_for_hour'],
        'gameForDay' => (int)$account['game_for_day'],
        'gameForDayPrev' => (int)$account['game_for_day_prev'],
        'carSlots' => (int)$account['car_slots'],
        'bizSlot' => (int)$account['biz_slot'],
        'homeSlot' => (int)$account['home_slot'],
        'garageSlot' => (int)$account['garage_slot'],
        'bpLevel' => (int)$account['bp_level'],
        'businessId' => (int)$account['business'],
        'houseId' => (int)$account['house'],
        'houseType' => (int)$account['house_type'],
        'garageId' => (int)$account['garage'],
        'businesses' => array_map(function($biz) {
            return [
                'id' => (int)$biz['id'],
                'name' => trim($biz['name']) ?: "Бизнес #{$biz['id']}",
                'type' => (int)$biz['type'],
                'products' => (int)$biz['products'],
                'balance' => (int)$biz['balance'],
                'price' => (int)$biz['price'],
                'rentPrice' => (int)$biz['rent_price'],
                'city' => (int)$biz['city'],
            ];
        }, $businesses),
        'houses' => array_map(function($house) {
            return [
                'id' => (int)$house['id'],
                'name' => trim($house['name']) ?: "Дом #{$house['id']}",
                'type' => (int)$house['type'],
                'price' => (int)$house['price'],
                'rentPrice' => (int)$house['rent_price'],
                'city' => (int)$house['city'],
                'improvements' => (int)$house['improvements'],
            ];
        }, $houses),
    ];

    jsonResponse($response);

} catch (Exception $e) {
    error_log("Stats error: " . $e->getMessage());
    jsonResponse(['message' => 'Не удалось получить статистику аккаунта.'], 500);
}
