<?php
// Отключаем вывод ошибок PHP
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Конфигурация базы данных
// Этот файл ОБЯЗАТЕЛЬНО добавить в .htaccess для защиты!

define('DB_HOST', 'db0.gamehost.itcloud.io');
define('DB_PORT', '3306');
define('DB_USER', 'u85_OpsZ1P83I8');
define('DB_PASSWORD', 'fQ6s8sGJwzA!UA04gSOeo7i!');
define('DB_NAME', 'samp_hamster');

// Функция подключения к БД
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Функция для безопасного ответа JSON
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Функция проверки пароля
function passwordMatches($input, $password, $passwordNew) {
    $saved = array_filter([$password, $passwordNew]);
    $variants = [
        $input,
        md5($input),
        sha1($input),
        hash('sha256', $input)
    ];
    
    $allVariants = [];
    foreach ($variants as $v) {
        $allVariants[] = $v;
        $allVariants[] = strtoupper($v);
        $allVariants[] = strtolower($v);
    }
    
    foreach ($saved as $savedPassword) {
        if (in_array($savedPassword, $allVariants)) {
            return true;
        }
    }
    
    return false;
}
