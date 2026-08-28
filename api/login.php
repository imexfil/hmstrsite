<?php
// Отключаем вывод ошибок в браузер
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'config.php';

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['message' => 'Метод не поддерживается'], 405);
}

// Получаем данные из запроса
$input = json_decode(file_get_contents('php://input'), true);
$nickname = isset($input['nickname']) ? trim($input['nickname']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($nickname) || empty($password)) {
    jsonResponse(['message' => 'Введите никнейм и пароль.'], 400);
}

try {
    $db = getDbConnection();
    if (!$db) {
        jsonResponse(['message' => 'Ошибка подключения к базе данных.'], 500);
    }

    // Получаем данные аккаунта
    $stmt = $db->prepare("
        SELECT
            id, name, password, password_new, email, skin, exp, level, money, bank,
            admin, yadmin, warn, suspect, online, donate, donate_current, donate_total,
            premium, premium_time, phone, phone_balance, driving_lic, weapon_lic,
            voennik, last_login, game_for_hour, game_for_day, game_for_day_prev, rating, health,
            hunger, job, rub, rubs, family, family_rank, kills,
            business, house, house_type, garage, car_slots, biz_slot, home_slot, garage_slot, bp_level,
            skill_colt, skill_deagle, skill_sdpistol, skill_shotgun, skill_mp5,
            skill_ak47, skill_m4, skill_sniper_rifle, skill_sawnoff, skill_combat_sg,
            skill_micro_uzi
        FROM accounts
        WHERE LOWER(name) = LOWER(:nickname)
        LIMIT 1
    ");
    
    $stmt->execute(['nickname' => $nickname]);
    $account = $stmt->fetch();

    if (!$account || !passwordMatches($password, $account['password'], $account['password_new'])) {
        jsonResponse(['message' => 'Никнейм или пароль не подошли.'], 401);
    }

    // Получаем транспорт
    $stmt = $db->prepare("
        SELECT id, model_id, number, region, fuel, mileage, iznos
        FROM ownable_cars
        WHERE owner_id = :owner_id
        ORDER BY id DESC
        LIMIT 12
    ");
    $stmt->execute(['owner_id' => $account['id']]);
    $cars = $stmt->fetchAll();

    // Получаем семью
    $familyInfo = null;
    if ((int)$account['family'] > 0) {
        $stmt = $db->prepare("
            SELECT id, name, rank1, rank2, rank3, rank4, rank5, rank6, rank7, rank8, rank9, rank10
            FROM family
            WHERE id = :family_id
            LIMIT 1
        ");
        $stmt->execute(['family_id' => $account['family']]);
        $familyInfo = $stmt->fetch();
    }

    // Получаем бизнесы
    $stmt = $db->prepare("
        SELECT id, name, type, products, balance, price, rent_price, city
        FROM business
        WHERE owner_id = :owner_id
        ORDER BY id ASC
        LIMIT 20
    ");
    $stmt->execute(['owner_id' => $account['id']]);
    $businesses = $stmt->fetchAll();

    // Получаем дома
    $stmt = $db->prepare("
        SELECT id, name, type, price, rent_price, city, improvements
        FROM houses
        WHERE owner_id = :owner_id
        ORDER BY id ASC
        LIMIT 20
    ");
    $stmt->execute(['owner_id' => $account['id']]);
    $houses = $stmt->fetchAll();

    // Формируем ответ
    $familyRank = (int)$account['family_rank'];
    
    $response = [
        'account' => [
            '_version' => '2.0', // Версия структуры данных
            'id' => (int)$account['id'],
            'name' => $account['name'],
            'email' => $account['email'] ?: 'Не указана',
            'skin' => (int)$account['skin'],
            'level' => (int)$account['level'],
            'exp' => (int)$account['exp'],
            'money' => (int)$account['money'],
            'bank' => (int)$account['bank'],
            'admin' => (int)$account['admin'],
            'yadmin' => (int)$account['yadmin'],
            'warn' => (int)$account['warn'],
            'suspect' => (int)$account['suspect'],
            'online' => (bool)((int)$account['online']),
            'donate' => (int)$account['donate'],
            'donateCurrent' => (int)$account['donate_current'],
            'donateTotal' => (int)$account['donate_total'],
            'premium' => (int)$account['premium'],
            'premiumTime' => $account['premium_time'],
            'phone' => $account['phone'] ?: null,
            'phoneBalance' => (int)$account['phone_balance'],
            'drivingLic' => (int)$account['driving_lic'],
            'weaponLic' => (int)$account['weapon_lic'],
            'voennik' => (int)$account['voennik'],
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
            'rating' => (int)$account['rating'],
            'health' => (int)$account['health'],
            'hunger' => (int)$account['hunger'],
            'job' => (int)$account['job'],
            'rub' => (int)($account['rub'] ?: $account['rubs']),
            'family' => (int)$account['family'],
            'familyRank' => $familyRank,
            'familyName' => $familyInfo ? trim($familyInfo['name']) : null,
            'familyRankName' => $familyInfo && isset($familyInfo["rank{$familyRank}"]) ? $familyInfo["rank{$familyRank}"] : null,
            'kills' => (int)$account['kills'],
            'skills' => [
                'colt' => (int)$account['skill_colt'],
                'deagle' => (int)$account['skill_deagle'],
                'sdPistol' => (int)$account['skill_sdpistol'],
                'shotgun' => (int)$account['skill_shotgun'],
                'mp5' => (int)$account['skill_mp5'],
                'ak47' => (int)$account['skill_ak47'],
                'm4' => (int)$account['skill_m4'],
                'sniperRifle' => (int)$account['skill_sniper_rifle'],
                'sawnoff' => (int)$account['skill_sawnoff'],
                'combatSg' => (int)$account['skill_combat_sg'],
                'uzi' => (int)$account['skill_micro_uzi'],
            ],
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
            }, $cars),
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
        ]
    ];

    jsonResponse($response);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    jsonResponse(['message' => 'Не удалось получить данные аккаунта.'], 500);
}
