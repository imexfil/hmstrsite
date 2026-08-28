<?php
/**
 * Скрипт для тестирования API
 * Запуск: php test_api.php
 */

echo "=== Тестирование API Hamster CRMP ===\n\n";

// Проверка подключения к БД
echo "1. Проверка подключения к базе данных...\n";
require_once 'api/config.php';

$db = getDbConnection();
if (!$db) {
    echo "❌ ОШИБКА: Не удалось подключиться к БД\n";
    echo "Проверьте настройки в api/config.php\n";
    exit(1);
}
echo "✅ Подключение к БД успешно\n\n";

// Тест получения аккаунта
echo "2. Проверка получения данных аккаунта...\n";
try {
    $stmt = $db->prepare("SELECT id, name, exp, level FROM accounts LIMIT 1");
    $stmt->execute();
    $account = $stmt->fetch();
    
    if ($account) {
        echo "✅ Найден аккаунт: {$account['name']} (ID: {$account['id']})\n";
        echo "   Уровень: {$account['level']}, Опыт: {$account['exp']}\n\n";
        $testAccountId = $account['id'];
    } else {
        echo "❌ ОШИБКА: Нет аккаунтов в БД\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    exit(1);
}

// Тест получения бизнесов
echo "3. Проверка получения бизнесов...\n";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM business WHERE owner_id = :id");
    $stmt->execute(['id' => $testAccountId]);
    $result = $stmt->fetch();
    echo "✅ Найдено бизнесов: {$result['cnt']}\n\n";
} catch (Exception $e) {
    echo "⚠️  Предупреждение: " . $e->getMessage() . "\n\n";
}

// Тест получения домов
echo "4. Проверка получения домов...\n";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM houses WHERE owner_id = :id");
    $stmt->execute(['id' => $testAccountId]);
    $result = $stmt->fetch();
    echo "✅ Найдено домов: {$result['cnt']}\n\n";
} catch (Exception $e) {
    echo "⚠️  Предупреждение: " . $e->getMessage() . "\n\n";
}

// Тест получения машин
echo "5. Проверка получения транспорта...\n";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM ownable_cars WHERE owner_id = :id");
    $stmt->execute(['id' => $testAccountId]);
    $result = $stmt->fetch();
    echo "✅ Найдено транспорта: {$result['cnt']}\n\n";
} catch (Exception $e) {
    echo "⚠️  Предупреждение: " . $e->getMessage() . "\n\n";
}

echo "=== Тестирование завершено ===\n";
echo "\nВсе основные проверки пройдены!\n";
echo "Теперь можете загрузить файлы на хостинг.\n";
echo "\nДля проверки API откройте в браузере:\n";
echo "- http://ваш-сайт/api/stats.php?id={$testAccountId}\n";
echo "- http://ваш-сайт/api/cars.php?id={$testAccountId}\n";
