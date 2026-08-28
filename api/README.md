# API для личного кабинета Hamster CRMP

## Безопасность

### Важно! Защита config.php

Файл `config.php` содержит данные для подключения к базе данных и ДОЛЖЕН быть защищен от прямого доступа.

**.htaccess** уже настроен для защиты этого файла, но дополнительно убедитесь:

1. Файл `.htaccess` находится в папке `api/`
2. На хостинге включена поддержка `.htaccess` (обычно включена по умолчанию)
3. Проверьте доступ: откройте в браузере `https://ваш-домен/api/config.php` - должна быть ошибка 403 Forbidden

### Альтернативный метод защиты

Если `.htaccess` не работает, переместите `config.php` на уровень ВЫШЕ корня сайта:

```
/home/user/
    config.php          <- здесь
    public_html/
        api/
            login.php
            stats.php
            ...
```

И измените в каждом PHP файле:
```php
require_once 'config.php';
// на
require_once '../config.php'; // или полный путь
```

## Структура API

### POST /api/login.php
Авторизация пользователя

**Запрос:**
```json
{
  "nickname": "Player_Name",
  "password": "password123"
}
```

**Ответ:**
```json
{
  "account": {
    "id": 1,
    "name": "Player_Name",
    "level": 27,
    "exp": 38,
    "cars": [...],
    "businesses": [...],
    "houses": [...]
  }
}
```

### GET /api/stats.php?id={accountId}
Получение статистики игрока

### GET /api/cars.php?id={accountId}
Получение списка транспорта

### GET /api/family.php?id={accountId}
Получение информации о семье

## Требования хостинга

- PHP 7.4 или выше
- PDO расширение (обычно включено)
- MySQL/MariaDB доступ
- Поддержка .htaccess (для Apache)

## Миграция с Node.js

Все запросы теперь идут к PHP API вместо Node.js сервера:
- `/api/account/login` → `api/login.php`
- `/api/account/{id}/stats` → `api/stats.php?id={id}`
- `/api/account/{id}/cars` → `api/cars.php?id={id}`
- `/api/account/{id}/family` → `api/family.php?id={id}`

Node.js сервер больше не нужен!
