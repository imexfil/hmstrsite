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

    // Получаем данные семьи
    $stmt = $db->prepare("
        SELECT a.family, a.family_rank, f.name, f.rank1, f.rank2, f.rank3, f.rank4, f.rank5,
            f.rank6, f.rank7, f.rank8, f.rank9, f.rank10
        FROM accounts a
        LEFT JOIN family f ON f.id = a.family
        WHERE a.id = :id
        LIMIT 1
    ");
    
    $stmt->execute(['id' => $accountId]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(['message' => 'Аккаунт не найден.'], 404);
    }

    $familyRank = (int)$row['family_rank'];

    // Формируем ответ
    $response = [
        'family' => (int)$row['family'],
        'familyRank' => $familyRank,
        'familyName' => $row['name'] ? trim($row['name']) : null,
        'familyRankName' => isset($row["rank{$familyRank}"]) ? $row["rank{$familyRank}"] : null,
    ];

    jsonResponse($response);

} catch (Exception $e) {
    error_log("Family error: " . $e->getMessage());
    jsonResponse(['message' => 'Не удалось получить семью аккаунта.'], 500);
}
