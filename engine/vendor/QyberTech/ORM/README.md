markdown
# QyberTech ORM

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Легковесная ORM библиотека для PHP с поддержкой SQLite, PostgreSQL и MySQL.

Если используете отдельно от фреймворка, потребуется установка

```bash
composer require qybertech/orm
```

Или вручную:

```php
require_once 'QyberTech/ORM/QORM.php';
```

## Инициализация
(если требуется использовать отдельно от фреймворка или создать новый экземпляр ORM)

```php
$pdo = new PDO('sqlite:database.db'); // или другой драйвер
$orm = new QyberTech\ORM\QORM($pdo);
```


## Быстрый старт

### Создание таблицы
```php
$orm->table('users')->create([
    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
    'name' => 'TEXT NOT NULL',
    'email' => 'TEXT UNIQUE'
]);
```

### Вставка данных
```php
$orm->table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```


### Выборка данных
```php
$users = $orm->table('users')->where(['status' => 'active'])->select();
```

### Обновление данных
```php
$orm->table('users')
   ->where(['id' => 42])
   ->update(['email' => 'new@email.com']);
```

### Удаление данных
```php
$orm->table('users')->where(['id' => 99])->delete();
```

## Полная документация


### Управление таблицами

```php
// Создание таблицы:
$orm->table('table_name')->create([
    'column1' => 'TYPE OPTIONS',
    'column2' => 'TYPE OPTIONS'
]);

// Переименование
$orm->table('old_name')->rename('new_name');

// Удаление
$orm->table('temp_data')->drop();

// Список таблиц
$tables = $orm->tables();

// Список полей
$columns = $orm->table('users')->columns();
```

### CRUD операции

### Вставка:

```php
// Одиночная вставка
$orm->table('users')->insert($data);

// Вставка с возвратом (PostgreSQL)
$result = $orm->table('users')->insert($data)->lastRecord;
```

### Чтение:

```php
// Все записи
$all = $orm->table('users')->select();


// С фильтрацией
$filtered = $orm->table('users')
              ->where(['status' => 'active'])
              ->select(['id', 'name']);
```

### Обновление:

```php
$orm->table('users')
   ->where($conditions)
   ->update($newData);
```

### Удаление:

```php
$orm->table('users')
   ->where($conditions)
   ->delete();
```

## Условия выборки
### Базовые условия:

```php
$orm->table('users')
   ->where(['field' => 'value'])
   ->where('field > ?', $value)
   ->where(['id' => [1, 2, 3]]) // IN условие
   ->select();
```

### LIKE поиск:

```php
$orm->table('users')
   ->like(['name' => 'John%'])
   ->select();
```

### Сортировка и лимиты:

```php
->orderBy('name ASC')
->orderBy(['name' => 'ASC', 'id' => 'DESC'])
->limit(10)
->limit(10, 20) // Пагинация
```

### JOIN операции:

```php
->join('profiles', 'profiles.user_id = users.id')
->joinLeft('comments', 'comments.user_id = users.id')
```

### Транзакции

```php
$orm->beginTransaction();

try {
    // Операции
    $orm->commit();
} catch (Exception $e) {
    $orm->rollBack();
}
```

## Дополнительные методы

### Прямые SQL запросы:

```php
$results = $orm->SQL('SELECT * FROM users WHERE id = ?', [42]);
```

### Работа с индексами:

```php
$orm->table('users')->index('email_idx')->create(['email']);
$orm->table('users')->index('old_idx')->drop();
```

### Доступ к результатам:

```php
$count = $orm->rowCount; // Затронуто строк
$lastId = $orm->lastInsertId; // Последний ID
$data = $orm->lastRecord; // Последний результат
```

### Комплексная выборка:

```php
$users = $orm->table('users')
            ->join('profiles', 'profiles.user_id = users.id')
            ->where(['status' => 'active'])
            ->orderBy('name ASC')
            ->limit(10)
            ->select();
```

### Пакетное обновление:

```php
$orm->beginTransaction();

try {
    foreach ($updates as $id => $data) {
        $orm->table('products')
           ->where(['id' => $id])
           ->update($data);
    }
    $orm->commit();
} catch (Exception $e) {
    $orm->rollBack();
}
```

Поддержка
* SQLite
* PostgreSQL
* MySQL

Лицензия MIT
