markdown
# QyberTech ORM

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

---

```markdown
# QORM — Query Object Relational Mapper

Лёгкий PHP ORM поверх PDO с текучим интерфейсом (fluent interface). Поддерживает SQLite, PostgreSQL и MySQL.

**Требования:** PHP >= 7.0, расширение PDO.

---

## Подключение
(если требуется использовать отдельно от фреймворка или создать новый экземпляр ORM)

```php
$pdo = new PDO('sqlite:/path/to/database.db');
$orm = new \QyberTech\ORM\QORM($pdo);
```

---

## Быстрый старт

```php
// Вставить запись
$orm->table('users')->insert(['name' => 'Иван', 'email' => 'ivan@example.com']);

// Выбрать все записи
$users = $orm->table('users')->select();

// Выбрать с условием
$user = $orm->table('users')->where(['id' => 1])->select();

// Обновить запись
$orm->table('users')->where(['id' => 1])->update(['name' => 'Пётр']);

// Удалить запись
$orm->table('users')->where(['id' => 1])->delete();
```

---

## Работа с таблицами

### `table(string $tablename)`

Указывает таблицу для запроса. Автоматически экранирует имя. Сбрасывает предыдущее состояние запроса.

```php
$orm->table('users');
```

### `from(string $tablename)`

Аналог `table()`, но без экранирования имени. Используется для передачи сырых выражений.

```php
$orm->from('"users" AS u');
```

### `create(array $columns)`

Создаёт таблицу, если она не существует.

```php
$orm->table('users')->create([
    'id'    => 'INTEGER PRIMARY KEY AUTOINCREMENT',
    'name'  => 'TEXT NOT NULL',
    'email' => 'TEXT UNIQUE',
]);

// С составным первичным ключом
$orm->table('user_roles')
    ->pkey(['user_id', 'role_id'])
    ->create([
        'user_id' => 'INTEGER NOT NULL',
        'role_id' => 'INTEGER NOT NULL',
    ]);
```

### `drop()`

Удаляет таблицу (IF EXISTS).

```php
$orm->table('users')->drop();
```

### `tables()`

Возвращает список всех таблиц базы данных.

```php
$list = $orm->tables(); // ['users', 'orders', ...]
```

### `columns()`

Возвращает список полей таблицы в виде ассоциативного массива.

```php
$fields = $orm->table('users')->columns();
// ['id' => [...], 'name' => [...], 'email' => [...]]
```

### `rename(string $newname)`

Переименовывает таблицу.

```php
$orm->table('old_name')->rename('new_name');
```

---

## Индексы

```php
// Создание индекса
$orm->table('users')->index('idx_email')->create(['email']);
$orm->table('users')->index_create('idx_email', ['email']);

// Удаление индекса
$orm->table('users')->index('idx_email')->drop();
$orm->table('users')->index_drop('idx_email');
```

---

## CRUD операции

### `insert(array $record)`

Вставляет запись в таблицу. На PostgreSQL и MySQL возвращает вставленную запись через `RETURNING *`.

```php
$orm->table('users')->insert([
    'name'  => 'Иван',
    'email' => 'ivan@example.com',
    'age'   => 30,
]);

// Получить ID вставленной записи
$id = $orm->lastInsertId;
```

### `update(array $record)`

Обновляет записи, соответствующие условию `where()`. Без условия запрос не выполняется.

```php
$orm->table('users')
    ->where(['id' => 5])
    ->update(['name' => 'Пётр', 'age' => 31]);
```

### `replace(array $record)`

Заменяет запись (INSERT OR REPLACE). Если запись с таким ключом существует — заменяет, иначе вставляет.

```php
$orm->table('settings')->replace([
    'key'   => 'theme',
    'value' => 'dark',
]);
```

### `delete()`

Удаляет записи, соответствующие условию `where()`. Без условия запрос не выполняется.

```php
$orm->table('users')->where(['id' => 5])->delete();

// Удалить несколько записей
$orm->table('logs')->where(['level' => 'debug'])->delete();
```

---

## Выборка данных

### `select(string|array $columns = '*', int $fetch = null)`

Выполняет SELECT и возвращает все записи в виде массива.

```php
// Все поля
$users = $orm->table('users')->select();

// Конкретные поля (строка)
$users = $orm->table('users')->select('id, name');

// Конкретные поля (массив)
$users = $orm->table('users')->select(['id', 'name', 'email']);

// С режимом выборки PDO
$users = $orm->table('users')->select('*', \PDO::FETCH_OBJ);
```

### `cursor(string|array $columns = '*')`

Выполняет один SELECT запрос и возвращает `Generator`, который читает записи по одной без загрузки всего результата в память. Подходит для обработки больших наборов данных.

```php
// Обработка миллионов записей без переполнения памяти
foreach ($orm->table('logs')->cursor() as $record) {
    process($record);
}

// С условиями
foreach ($orm->table('users')->where(['active' => 1])->OrderBy('id ASC')->cursor() as $user) {
    sendEmail($user['email']);
}

// Конкретные поля
foreach ($orm->table('orders')->cursor(['id', 'total', 'status']) as $order) {
    // ...
}
```

> **Отличие от `select()`:** `select()` загружает весь результат в память сразу через `fetchAll()`. `cursor()` читает по одной записи через `fetch()` + `yield`, что критично при работе с большими таблицами.

---

## Условия выборки

### `where()`

Добавляет условие WHERE. Поддерживает несколько форматов вызова.

**Массив условий (рекомендуется):**

```php
// Простое равенство
$orm->table('users')->where(['status' => 'active'])->select();

// Несколько условий (AND по умолчанию)
$orm->table('users')->where(['status' => 'active', 'role' => 'admin'])->select();

// NULL значение
$orm->table('users')->where(['deleted_at' => NULL])->select();

// IN — передать массив значений
$orm->table('users')->where(['id' => [1, 2, 3]])->select();

// IN с NULL — автоматически добавляет IS NULL
$orm->table('users')->where(['id' => [1, 2, null]])->select();
// Генерирует: WHERE ("id" in (?,?) OR "id" IS NULL)
```

**Строка SQL:**

```php
// Произвольное условие
$orm->table('users')->where('age > 18')->select();

// Подзапрос (без параметризации)
$orm->table('users')->where('id IN (SELECT user_id FROM orders WHERE total > 100)')->select();
```

**Строка с параметрами:**

```php
// Один параметр
$orm->table('users')->where('age > ?', 18)->select();

// Несколько параметров
$orm->table('users')->where('age > ? AND age < ?', 18, 65)->select();

// Параметры массивом
$orm->table('users')->where('age > ? AND age < ?', [18, 65])->select();
```

**Очистка условий:**

```php
$orm->table('users')->where('')->select(); // сбрасывает все условия WHERE
```

### `wheres(array ...$conditions)`

Добавляет несколько условий за один вызов.

```php
$orm->table('users')->wheres(
    [['status' => 'active']],
    ['age > ?', 18]
)->select();
```

### `like()`

Добавляет условие LIKE. Автоматически оборачивает значение в `%...%`.

```php
// Поиск по всем полям таблицы
$orm->table('users')->like('Иван')->select();

// Поиск по конкретным полям
$orm->table('users')->like(['name' => 'Иван', 'email' => 'example'])->select();

// Поиск по полю с несколькими значениями
$orm->table('users')->like(['name' => ['Иван', 'Пётр']])->select();

// Комбинирование с where
$orm->table('users')->like(['name' => 'Иван'])->where(['active' => 1])->select();
```

### `concat(string $method)`

Устанавливает способ объединения условий WHERE. По умолчанию `and`.

```php
// OR между условиями
$orm->table('users')
    ->concat('or')
    ->where(['role' => 'admin'])
    ->where(['role' => 'editor'])
    ->select();
```

---

## Сортировка, группировка, лимиты

### `OrderBy(string|array $order)`

Устанавливает сортировку.

```php
// Строка
$orm->table('users')->OrderBy('name ASC')->select();
$orm->table('users')->OrderBy('created_at DESC')->select();

// Массив
$orm->table('users')->OrderBy(['name' => 'ASC', 'created_at' => 'DESC'])->select();

// Случайный порядок
$orm->table('users')->OrderBy('RANDOM()')->select();
```

### `groupBy(string $group)`

Устанавливает группировку.

```php
$orm->table('orders')
    ->groupBy('status')
    ->select(['status', 'COUNT(*) as total']);
```

### `limit(int $limit, int $offset = null)`

Ограничивает количество возвращаемых записей.

```php
// Первые 10 записей
$orm->table('users')->limit(10)->select();

// Записи 21-30 (пагинация)
$orm->table('users')->limit(10, 20)->select();

// Сброс лимита
$orm->table('users')->limit()->select();
```

### `random(int $limit = 1)`

Возвращает случайные записи.

```php
// Одна случайная запись
$user = $orm->table('users')->random()->select();

// Пять случайных записей
$users = $orm->table('users')->random(5)->select();
```

---

## JOIN

Все методы JOIN принимают имя таблицы (экранируется автоматически) и условие ON.

### `join($table, $on)` — INNER JOIN (по умолчанию)

```php
$orm->table('orders')
    ->join('users', 'orders.user_id = users.id')
    ->select(['orders.id', 'users.name', 'orders.total']);
```

### `joinLeft($table, $on)` — LEFT JOIN

```php
$orm->table('users')
    ->joinLeft('orders', 'users.id = orders.user_id')
    ->select(['users.name', 'orders.total']);
```

### `joinRight($table, $on)` — RIGHT JOIN

```php
$orm->table('orders')
    ->joinRight('users', 'orders.user_id = users.id')
    ->select();
```

### `joinInner($table, $on)` — INNER JOIN (явный)

```php
$orm->table('orders')
    ->joinInner('products', 'orders.product_id = products.id')
    ->select();
```

### `joinCross($table)` — CROSS JOIN

```php
$orm->table('colors')
    ->joinCross('sizes')
    ->select();
```

**Цепочка JOIN:**

```php
$orm->table('orders')
    ->joinLeft('users',    'orders.user_id = users.id')
    ->joinLeft('products', 'orders.product_id = products.id')
    ->where(['orders.status' => 'pending'])
    ->select(['orders.id', 'users.name', 'products.title']);
```

---

## Агрегатные функции

Все агрегатные функции применяют текущие условия `where()`.

```php
// Количество записей
$total = $orm->table('users')->count();
$active = $orm->table('users')->where(['status' => 'active'])->count();
$total = $orm->table('users')->count('id');

// Максимальное значение
$maxAge = $orm->table('users')->max('age');

// Минимальное значение
$minPrice = $orm->table('products')->where(['active' => 1])->min('price');

// Среднее значение
$avgRating = $orm->table('reviews')->avg('rating');

// Сумма значений
$revenue = $orm->table('orders')->where(['status' => 'paid'])->sum('total');
```

---

## Транзакции

```php
$orm->beginTransaction();

try {
    $orm->table('accounts')->where(['id' => 1])->update(['balance' => 900]);
    $orm->table('accounts')->where(['id' => 2])->update(['balance' => 1100]);
    $orm->commit();
} catch (\Exception $e) {
    $orm->rollBack();
    throw $e;
}

// Проверка состояния транзакции
if ($orm->inTransaction()) {
    // транзакция открыта
}
```

---

## JSON поля

Метод `json()` указывает ORM, какие поля содержат JSON. При `insert()` и `update()` они автоматически сериализуются, при `select()` и `cursor()` — десериализуются.

```php
// Указываем JSON поля
$orm->table('users')->json(['settings', 'metadata']);

// insert — settings будет автоматически сериализован в JSON строку
$orm->table('users')->json(['settings'])->insert([
    'name'     => 'Иван',
    'settings' => ['theme' => 'dark', 'lang' => 'ru'],
]);

// select — settings будет автоматически десериализован обратно в массив
$users = $orm->table('users')->json(['settings'])->select();
// $users[0]['settings'] === ['theme' => 'dark', 'lang' => 'ru']

// cursor тоже поддерживает JSON
foreach ($orm->table('users')->json(['settings'])->cursor() as $user) {
    echo $user['settings']['theme']; // 'dark'
}
```

---

## Прямые SQL запросы

### `SQL(string $sql, array $params = null)`

Выполняет произвольный SQL запрос с параметрами.

```php
// Без параметров
$result = $orm->SQL("SELECT * FROM users WHERE active = 1");

// С параметрами (защита от SQL-инъекций)
$result = $orm->SQL(
    "SELECT * FROM users WHERE age > ? AND city = ?",
    [18, 'Москва']
);

// Вызов хранимой функции
$result = $orm->SQL("SELECT demofunction(3, 'John')");
```

---

## Импорт CSV

### `import(string $file, array $options, array $fields)`

Импортирует данные из CSV файла в таблицу. Автоматически определяет BOM. Выполняется в транзакции.

```php
// Базовый импорт (поля берутся из структуры таблицы)
$orm->table('users')->import('/path/to/users.csv');

// С настройками разделителя
$orm->table('products')->import('/path/to/products.csv', [
    'delimiter' => ',',
    'quotes'    => '"',
]);

// С явным указанием порядка полей
$orm->table('users')->import('/path/to/users.csv', [], ['name', 'email', 'age']);
```

> Если транзакция уже открыта до вызова `import()`, метод выполняется внутри неё и не закрывает её самостоятельно.

---

## Вспомогательные методы

### `rowCount()`

Возвращает количество записей, затронутых последним запросом (`INSERT`, `UPDATE`, `DELETE`).

```php
$orm->table('users')->where(['status' => 'inactive'])->delete();
$deleted = $orm->rowCount; // или $orm->rowCount()
```

### `lastInsertId()`

Возвращает ID последней вставленной записи.

```php
$orm->table('users')->insert(['name' => 'Иван']);
$id = $orm->lastInsertId; // или $orm->lastInsertId()
```

### `lastRecord()`

Возвращает данные последней изменённой записи (если СУБД поддерживает `RETURNING *`).

```php
$orm->table('users')->where(['id' => 1])->update(['name' => 'Пётр']);
$record = $orm->lastRecord; // или $orm->lastRecord()
```

### `reset()`

Сбрасывает все условия запроса (WHERE, ORDER BY, LIMIT, JOIN и т.д.). Вызывается автоматически после каждого выполненного запроса.

```php
$orm->table('users')->reset();
```

---

## Свойства объекта

| Свойство | Тип | Описание |
|---|---|---|
| `$orm->PDO` | `PDO` | Прямой доступ к объекту PDO |
| `$orm->lastQuery` | `string` | SQL текст последнего выполненного запроса |
| `$orm->transaction` | `bool` | Флаг открытой транзакции |
| `$orm->rowCount` | `int` | Количество затронутых записей (магический геттер) |
| `$orm->lastInsertId` | `int` | ID последней вставленной записи (магический геттер) |
| `$orm->lastRecord` | `array` | Последняя изменённая запись (магический геттер) |

---

## Первичные ключи

### `pkey()`

Определяет составной первичный ключ при создании таблицы.

```php
// Один ключ
$orm->table('sessions')->pkey('token')->create([...]);

// Составной ключ
$orm->table('user_roles')
    ->pkey(['user_id', 'role_id'])
    ->create([
        'user_id' => 'INTEGER NOT NULL',
        'role_id' => 'INTEGER NOT NULL',
    ]);
// Генерирует: PRIMARY KEY("user_id","role_id")
```

---

## Примечания о совместимости

- **PHP:** >= 7.0
- **СУБД:** SQLite, PostgreSQL, MySQL (частично)
- Синтаксис `list($a, $b) = func()` используется вместо `[$a, $b] = func()` для совместимости с PHP 7.0
- `RETURNING *` поддерживается на PostgreSQL и MySQL; на SQLite не используется
- Метод `tables()` поддерживает SQLite и MySQL; для PostgreSQL требуется доработка
- Метод `columns()` поддерживает SQLite и PostgreSQL

---

## Архитектурные особенности

### Текучий интерфейс (Fluent Interface)

Большинство методов возвращают `$this`, что позволяет строить цепочки вызовов:

```php
$result = $orm
    ->table('orders')
    ->json(['metadata'])
    ->joinLeft('users', 'orders.user_id = users.id')
    ->where(['orders.status' => 'pending'])
    ->where('orders.total > ?', 100)
    ->OrderBy('orders.created_at DESC')
    ->limit(20, 0)
    ->select(['orders.id', 'users.name', 'orders.total']);
```

### Автоматический сброс состояния

После каждого терминального метода (`select()`, `cursor()`, `insert()`, `update()`, `delete()`) состояние запроса сбрасывается автоматически. Это означает, что один объект `$orm` можно переиспользовать для разных запросов.

### `cursor()` vs `select()`

```php
// select() — загружает всё в память, подходит для небольших выборок
$users = $orm->table('users')->select(); // array

// cursor() — один запрос, читает по одной записи, подходит для больших данных
foreach ($orm->table('users')->cursor() as $user) { ... } // Generator
```
```
