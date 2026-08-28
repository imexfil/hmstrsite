<?php
header('Content-Type: application/json; charset=utf-8');

$result = [
    'status' => 'ok',
    'message' => 'PHP is working!',
    'php_version' => phpversion(),
    'server_time' => date('Y-m-d H:i:s'),
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json'),
    ]
];

// Проверяем подключение к БД
try {
    require_once 'config.php';
    $db = getDbConnection();
    
    if ($db) {
        $result['database'] = 'connected';
        
        // Пробуем простой запрос
        $stmt = $db->query("SELECT COUNT(*) as count FROM accounts");
        $row = $stmt->fetch();
        $result['accounts_count'] = (int)$row['count'];
    } else {
        $result['database'] = 'connection failed';
    }
} catch (Exception $e) {
    $result['database'] = 'error';
    $result['db_error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
