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

    // Получаем транспорт
    $stmt = $db->prepare("
        SELECT id, model_id, number, region, fuel, mileage, iznos
        FROM ownable_cars
        WHERE owner_id = :owner_id
        ORDER BY id DESC
        LIMIT 12
    ");
    
    $stmt->execute(['owner_id' => $accountId]);
    $cars = $stmt->fetchAll();

    // Формируем ответ
    $response = [
        'cars' => array_map(function($car) {
            return [
                'id' => (int)$car['id'],
                'modelId' => (int)$car['model_id'],
                'number' => $car['number'] ?: null,
                'region' => $car['region'] ?: null,
                'fuel' => (int)$car['fuel'],
                'mileage' => (int)$car['mileage'],
                'wear' => (int)$car['iznos'],
            ];
        }, $cars)
    ];

    jsonResponse($response);

} catch (Exception $e) {
    error_log("Cars error: " . $e->getMessage());
    jsonResponse(['message' => 'Не удалось получить транспорт.'], 500);
}
