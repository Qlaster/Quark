---

# Quark Router

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Лёгкий и быстрый маршрутизатор для фреймворка Quark.
Поддерживает маски URL, псевдонимы, хуки, каскадный вызов контроллеров,
перенаправление маршрутов и обработку ошибок.

Конфигурация роутера хранится в `engine/facades/route.ini`.
Все маршруты описываются в формате INI без написания PHP-кода.

> [!TIP]
> Маршруты до контроллеров (app/controllers) строятся автоматически и не требуют написания специальных маршрутов. Используйте роутер только если вам нужно какое то специальное поведение.

---

## Принцип работы

Все HTTP-запросы перехватываются через `.htaccess` и направляются в `index.php`.
Фреймворк извлекает текущий URL и передаёт его в роутер:

```php
// 1. Обработка псевдонимов
if ($alias = $APP->route->match($APP->url->page(), ['alias']))
    $APP->url->page = $alias[0];

// 2. Получение списка контроллеров для текущего URL
$controllers = $APP->route->match($APP->url->page());

// 3. Последовательный запуск контроллеров
foreach ($controllers as $action)
    $APP->controller->run($action, ['APP' => $APP]);
```

Метод `match()` возвращает **массив контроллеров**, которые нужно выполнить.
Это позволяет запускать несколько контроллеров на один URL — например,
сначала проверить авторизацию, затем выполнить основной контроллер.

---

## Быстрый старт

Минимальная конфигурация `engine/facades/route.ini`:

```ini
[alias]
 = index

[route]
* =>
```

Это означает:
- Пустой URL (`/`) → псевдоним `index`
- Любой другой URL → автоматически ищется контроллер по пути URL

---

## Полная документация

### Метод `match()`

```php
$APP->route->match(string $url, array $sections = ['hook', 'route']): array
```

Принимает URL и список секций конфига для поиска.
Возвращает массив путей к контроллерам.

```php
// Поиск в секциях hook и route (по умолчанию)
$controllers = $APP->route->match('admin/users');

// Поиск только в секции alias
$alias = $APP->route->match('', ['alias']);

// Поиск обработчика ошибки
$errorCtrl = $APP->route->match('404', ['error'])[0];
$APP->controller->run($errorCtrl, ['APP' => $APP]);
```

---

### Секции конфигурации

Файл `engine/facades/route.ini` содержит следующие секции:

| Секция | Назначение |
|--------|-----------|
| `[alias]` | Псевдонимы URL |
| `[hook]` | Хуки — контроллеры, выполняемые до основных |
| `[route]` | Основные маршруты |
| `[redirect]` | HTTP-редиректы |
| `[blockip]` | Блокировка IP-адресов |
| `[error]` | Обработчики ошибок |

---

### Маски URL

В ключах всех секций поддерживаются glob-маски (функция `fnmatch`):

| Маска | Описание | Пример |
|-------|----------|--------|
| `*` | Любое количество любых символов | `admin/*` |
| `?` | Ровно один любой символ | `page/?` |
| `[abc]` | Один из перечисленных символов | `gr[ae]y` |
| `4??` | Трёхсимвольный код, начинающийся с 4 | `4??` → `404`, `403` |

```ini
[error]
4?? = error/400.php
5?? = error/500.php
```

---

### Операторы правил

Синтаксис правила: `маска_url ОПЕРАТОР контроллер`

#### `=` — добавить контроллер, продолжить

Контроллер добавляется в список выполнения. Поиск по остальным правилам **продолжается**.

```ini
[route]
admin/* = admin/autoinclude.php
```

При запросе `admin/users` сначала выполнится `admin/autoinclude.php`,
затем роутер продолжит поиск и найдёт контроллер `admin/users`.

---

#### `==` — финальный контроллер, остановить

Контроллер добавляется в список, поиск **останавливается**.
Остальные правила не проверяются.

```ini
[route]
maintenance == pages/maintenance.php
```

---

#### `=>` — перенаправить URL в папку, продолжить

Значение URL **добавляется** к указанному пути. Поиск продолжается.

```ini
[route]
* =>
```

При запросе `catalog/phones` контроллер будет искаться по пути `catalog/phones`.
Пустое значение означает: использовать URL как путь к контроллеру напрямую.

```ini
[route]
api/* => controllers/api/
```

При запросе `api/users` контроллер будет `controllers/api/api/users`.

---

#### `==>` — финальное перенаправление в папку, остановить

Комбинация `=>` и остановки поиска.

```ini
[route]
v2/* ==> controllers/v2/
```

---

#### JSON-массив контроллеров

Можно указать несколько контроллеров через JSON-массив:

```ini
; Выполнить оба контроллера, продолжить поиск
admin/* = ["admin/autoinclude", "admin/logger"]

; Выполнить оба контроллера, остановить поиск
admin/dashboard == ["admin/autoinclude", "admin/dashboard/index"]
```

---

### `[alias]` — Псевдонимы URL

Псевдонимы позволяют переименовать URL **до** маршрутизации.
Обрабатываются отдельным вызовом `match($url, ['alias'])`.

```ini
[alias]
 = index
home = index
main = index
```

- Пустой URL (`/`) → заменяется на `index`
- `home` → заменяется на `index`

```php
if ($alias = $APP->route->match($APP->url->page(), ['alias']))
    $APP->url->page = $alias[0];
```

---

### `[hook]` — Хуки

Хуки работают так же, как `[route]`, но проверяются **первыми** (т.к. секция
`hook` идёт перед `route` в массиве по умолчанию `['hook', 'route']`).

Используются для выполнения кода перед основным контроллером:

```ini
[hook]
api/* = middleware/api_auth.php
admin/* = middleware/admin_check.php
```

---

### `[route]` — Маршруты

Основные правила маршрутизации.

#### Автоматическая маршрутизация

```ini
[route]
* =>
```

Это правило означает: для **любого** URL использовать сам URL как путь к контроллеру.
Запрос `catalog/phones` → контроллер `app/controllers/catalog/phones.php`.

Фреймворк автоматически ищет контроллер по следующему алгоритму:
1. `app/controllers/catalog/phones.php` — прямое совпадение
2. `app/controllers/catalog/phones/index.php` — папка с `index.php`
3. `app/controllers/catalog/index.php` — родительская папка с `index.php`

#### Ручные маршруты

```ini
[route]
; Все запросы к admin/* сначала проходят через autoinclude
admin/* = admin/autoinclude.php

; Автоматическая маршрутизация для остальных
* =>
```

---

### `[redirect]` — HTTP-редиректы

Секция для настройки HTTP-редиректов. По умолчанию используется код `302`.
Для указания кода редиректа используется формат `код:новый_адрес`.

```ini
[redirect]
; Временный редирект (302)
old/page = new/page

; Постоянный редирект (301)
old/catalog = 301:new/catalog

; Редирект раздела
blog/* = 301:articles/*
```

---

### `[blockip]` — Блокировка IP

Список IP-адресов, которым запрещён доступ:

```ini
[blockip]
192.168.1.100
10.0.0.5
```

---

### `[error]` — Обработчики ошибок

Маршруты для обработки HTTP-ошибок. Вызываются явно через `match($code, ['error'])`.

```ini
[error]
4?? = error/400.php
5?? = error/500.php
```

```php
// В контроллере при ошибке 404
$errorController = $APP->route->match('404', ['error'])[0];
http_response_code(404);
$APP->controller->run($errorController, ['APP' => $APP]);

// В index.php при исключении
catch (Error $error) {
    $errorController = $APP->route->match('500', ['error'])[0];
    $APP->controller->run($errorController, ['APP' => $APP, 'error' => $error]);
}
```

---

## Типовые примеры

### Авторизация для всего раздела (hook-паттерн)

Самый распространённый паттерн — запуск проверки авторизации перед
основным контроллером через правило `=` (продолжить после выполнения).

```ini
[route]
admin/* = admin/autoinclude.php
* =>
```

`admin/autoinclude.php` проверяет авторизацию и при необходимости делает редирект:

```php
// app/controllers/admin/autoinclude.php
if (! $APP->user->logged()) {
    header('Location: ' . $APP->url->home() . 'admin/login');
    exit();
}
// Возвращает данные для шаблона
return $content;
```

Все контроллеры раздела `admin/*` получают результат `autoinclude` через
`$APP->controller->run('admin/autoinclude', ['APP' => $APP])`:

```php
// app/controllers/admin/users/index.php
$content = $APP->controller->run('admin/autoinclude', ['APP' => $APP]);
// Дальнейшая логика контроллера...
$APP->template->file('admin/users/list.html')->display($content);
```

---

### Авторизация для API

```ini
[hook]
api/* = middleware/api_auth.php

[route]
api/* ==> controllers/api/
```

`middleware/api_auth.php` проверяет токен и при ошибке возвращает JSON:

```php
// app/controllers/middleware/api_auth.php
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (! $APP->user->checkToken($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
```

---

### Перенаправление старых URL (SEO-редиректы)

```ini
[redirect]
; Старые URL блога → новый раздел
news/* = 301:blog/*
about-us = 301:about/company
contacts.html = 301:contacts
```

---

### Версионирование API

```ini
[route]
; v1 API — старая версия
api/v1/* ==> controllers/api/v1/

; v2 API — новая версия
api/v2/* ==> controllers/api/v2/

; Без версии — последняя
api/* ==> controllers/api/v2/
```

---

### Каскадный вызов нескольких контроллеров

Через JSON-массив можно указать цепочку контроллеров:

```ini
[route]
; Для раздела shop: сначала авторизация, потом логирование, потом контроллер
shop/* = ["middleware/auth", "middleware/logger"]
* =>
```

Или через несколько правил с оператором `=`:

```ini
[route]
shop/* = middleware/auth.php
shop/* = middleware/logger.php
* =>
```

Оба варианта дают одинаковый результат: для `shop/catalog` будет выполнено:
1. `middleware/auth.php`
2. `middleware/logger.php`
3. `shop/catalog` (через `* =>`)

---

### Обработка ошибок с кастомными страницами

```ini
[error]
400 = error/400.php
401 = error/401.php
403 = error/403.php
404 = error/404.php
4?? = error/400.php
500 = error/500.php
5?? = error/500.php
```

Более специфичные правила должны идти **раньше** общих, т.к. роутер
возвращает первое совпадение при использовании финального оператора `==`.

---

### Псевдонимы для главной страницы и разделов

```ini
[alias]
 = index
home = index
главная = index
catalog = shop/catalog/index
```

---

### Блокировка по IP

```ini
[blockip]
192.168.100.50
10.10.0.0/24
```

---

## Примерная конфигурация по умолчанию

Полный файл `engine/facades/route.ini` с комментариями:

```ini
#=============================================
#       R O U T E R    C O N F I G
#=============================================
# В ключах возможны подстановки ? * [abc] (например gr[ae]y)
#
# Добавить контроллер, продолжить поиск:
# url = controller
#
# Финальный контроллер, остановить поиск:
# url == controller
#
# Несколько контроллеров, остановить:
# url == ["x","y"]
#
# Несколько контроллеров, продолжить:
# url = ["x","y"]
#
# Перенаправить URL в папку, продолжить:
# url => folder/
#
# Перенаправить URL в папку, остановить:
# url ==> folder/

[alias]
 = index

[hook]

[route]
admin/* = admin/autoinclude.php
* =>

[redirect]

[blockip]

[error]
4?? = error/400.php
5?? = error/500.php
```

---

## Справочник операторов

| Синтаксис в INI | Значение в `$record` | Поведение |
|-----------------|----------------------|-----------|
| `url = ctrl` | `ctrl` | Добавить контроллер, продолжить |
| `url == ctrl` | `= ctrl` | Добавить контроллер, **остановить** |
| `url => folder/` | `> folder/` | Перенаправить URL в папку, продолжить |
| `url ==> folder/` | `=> folder/` | Перенаправить URL в папку, **остановить** |
| `url = ["a","b"]` | `["a","b"]` | Добавить несколько контроллеров, продолжить |
| `url == ["a","b"]` | `= ["a","b"]` | Добавить несколько контроллеров, **остановить** |

> **Примечание:** В INI-файле символ `=` является разделителем ключ/значение.
> Поэтому `url == ctrl` сохраняется как значение `= ctrl` (с ведущим `=`),
> а `url => folder` — как `> folder` (с ведущим `>`).
> Роутер анализирует первые символы значения для определения оператора.
```

---
