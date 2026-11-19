# Talk — иерархический фасад для сообщений  
  
`Talk` — универсальный фасад для организации иерархических сообщений по схеме **blog → post → message**. Подходит для блогов, систем поддержки, заявок с сайта, рабочих чатов, комментариев к товарам и любых других сценариев, где нужна структура «канал → тема → сообщения».  
  
---  
  
## Содержание  
  
- [Установка и конфигурация](#установка-и-конфигурация)  
- [Схема базы данных](#схема-базы-данных)  
- [Иерархия объектов](#иерархия-объектов)  
- [API](#api)  
  - [Blog](#blog)  
  - [Post](#post)  
  - [Message](#message)  
- [Расширенная фильтрация select()](#расширенная-фильтрация-select)  
- [Polling — получение новых сообщений](#polling--получение-новых-сообщений)  
- [Архивирование и мягкое удаление](#архивирование-и-мягкое-удаление)  
- [Поля files, tags, meta](#поля-files-tags-meta)  
- [Примеры использования](#примеры-использования)  
  - [Блог со статьями](#блог-со-статьями)  
  - [Система поддержки (тикеты)](#система-поддержки-тикеты)  
  - [Заявки с сайта (лиды)](#заявки-с-сайта-лиды)  
  - [Рабочий чат (каналы)](#рабочий-чат-каналы)  
  - [Комментарии к товарам](#комментарии-к-товарам)  
  - [Уведомления и системные сообщения](#уведомления-и-системные-сообщения)  
- [Именование slug](#именование-slug)  
  
---  
  
## Установка и конфигурация  
  
Фасад подключается автоматически через файл `engine/facades/talk.php`. Конфигурация хранится в `engine/facades/talk.ini`.  
  
```ini  
[db]  
name = "review"  
  
[table]  
blogs    = "talk_blogs"  
posts    = "talk_posts"  
messages = "talk_messages"  
```  
  
**Параметры:**  
  
| Параметр | Описание |  
|---|---|  
| `db.name` | Имя подключения к БД (из конфига ORM) |  
| `table.blogs` | Имя таблицы блогов (по умолчанию `talk_blogs`) |  
| `table.posts` | Имя таблицы постов (по умолчанию `talk_posts`) |  
| `table.messages` | Имя таблицы сообщений (по умолчанию `talk_messages`) |  
  
Таблицы создаются автоматически при первом обращении к фасаду (`CREATE TABLE IF NOT EXISTS`).  
  
Доступ к фасаду:  
  
```php  
$APP->talk->blog('support')->...  
```  
  
---  
  
## Схема базы данных  
  
```sql  
-- Блог — верхний уровень иерархии (канал, раздел, пространство)  
CREATE TABLE IF NOT EXISTS talk_blogs (  
    id       INTEGER PRIMARY KEY AUTOINCREMENT,  
    name     TEXT UNIQUE NOT NULL,  -- уникальный slug (напр. 'support', 'general')  
    title    TEXT,                  -- человекочитаемое название  
    tags     TEXT,                  -- JSON-массив меток  
    meta     TEXT,                  -- JSON-объект для расширений  
    created  INTEGER,               -- Unix timestamp создания  
    updated  INTEGER,               -- Unix timestamp последнего изменения  
    archived INTEGER                -- 0 = активен, 1 = в архиве  
);  
  
-- Пост — тема, тикет, статья, заявка  
CREATE TABLE IF NOT EXISTS talk_posts (  
    id       INTEGER PRIMARY KEY AUTOINCREMENT,  
    blog_id  INTEGER NOT NULL REFERENCES talk_blogs(id) ON DELETE CASCADE,  
    name     TEXT NOT NULL,         -- slug поста (уникален в пределах блога)  
    title    TEXT,                  -- заголовок  
    author   TEXT,                  -- автор (email, login, имя)  
    status   TEXT,                  -- произвольный статус: 'open', 'closed', 'pending'  
    files    TEXT,                  -- JSON-массив путей к прикреплённым файлам  
    tags     TEXT,                  -- JSON-массив меток  
    meta     TEXT,                  -- JSON-объект для расширений  
    created  INTEGER,               -- Unix timestamp создания  
    updated  INTEGER,               -- Unix timestamp последнего изменения (обновляется при добавлении сообщения)  
    archived INTEGER,               -- 0 = активен, 1 = в архиве  
    UNIQUE(blog_id, name)  
);  
  
-- Сообщение — единица контента внутри поста  
CREATE TABLE IF NOT EXISTS talk_messages (  
    id       INTEGER PRIMARY KEY AUTOINCREMENT,  
    post_id  INTEGER NOT NULL REFERENCES talk_posts(id) ON DELETE CASCADE,  
    text     TEXT,                  -- текст сообщения  
    author   TEXT,                  -- автор  
    files    TEXT,                  -- JSON-массив путей к прикреплённым файлам  
    tags     TEXT,                  -- JSON-массив меток  
    meta     TEXT,                  -- JSON-объект для расширений (reply_to, is_pinned и т.д.)  
    created  INTEGER,               -- Unix timestamp создания  
    updated  INTEGER,               -- Unix timestamp последнего редактирования  
    archived INTEGER                -- 0 = активно, 1 = удалено/скрыто (мягкое удаление)  
);  
```  
  
`ON DELETE CASCADE` гарантирует, что при удалении блога удаляются все его посты, а при удалении поста — все его сообщения.  
  
---  
  
## Иерархия объектов  
  
```  
Talk (фасад)  
└── BlogContext       $APP->talk->blog('name')  
    └── PostContext   ->post('name')  
        └── MessageContext  ->message(?id)  
```  
  
Каждый уровень — это контекстный объект с методами `create`, `select`, `update`, `delete`, `archive`. Цепочка вызовов не делает запросов к БД до вызова терминального метода — только описывает намерение.  
  
```php  
// Только описание намерения — запросов нет  
$ctx = $APP->talk->blog('support')->post('ticket-123')->message();  
  
// Запрос выполняется здесь  
$messages = $ctx->select();  
```  
  
---  
  
## API  
  
### Blog  
  
```php  
$APP->talk->blog(string $name): BlogContext  
```  
  
Возвращает контекст блога с именем `$name`.  
  
#### `BlogContext::create(array $data): self`  
  
Создаёт новый блог. Автоматически проставляет `created` и `updated`.  
  
```php  
$APP->talk->blog('support')->create([  
    'title' => 'Служба поддержки',  
    'tags'  => ['support', 'tickets'],  
]);  
```  
  
#### `BlogContext::select(...$where): array`  
  
Возвращает данные блога. Без аргументов — возвращает запись блога по его `name`.  
  
```php  
$blog = $APP->talk->blog('support')->select();  
```  
  
#### `BlogContext::update(array $data): self`  
  
Обновляет данные блога. Автоматически проставляет `updated`.  
  
```php  
$APP->talk->blog('support')->update(['title' => 'Техническая поддержка']);  
```  
  
#### `BlogContext::delete(): self`  
  
Удаляет блог и все его посты и сообщения (каскадно).  
  
```php  
$APP->talk->blog('old-blog')->delete();  
```  
  
#### `BlogContext::archive(bool $state = true): self`  
  
Переводит блог в архив (`archived = 1`) или восстанавливает (`archived = 0`).  
  
```php  
$APP->talk->blog('support')->archive();        // в архив  
$APP->talk->blog('support')->archive(false);   // восстановить  
```  
  
#### `BlogContext::post(?string $name = null): PostContext`  
  
Переходит в контекст постов. Без аргумента — контекст всех постов блога.  
  
```php  
$APP->talk->blog('support')->post()              // все посты  
$APP->talk->blog('support')->post('ticket-123')  // конкретный пост  
```  
  
---  
  
### Post  
  
```php  
$APP->talk->blog('support')->post(?string $name = null): PostContext  
```  
  
#### `PostContext::create(array $data): self`  
  
Создаёт новый пост. Требует, чтобы `name` был передан в `post()`. Автоматически проставляет `created`, `updated`, `blog_id`.  
  
```php  
$APP->talk->blog('support')->post('ticket-abc123')->create([  
    'title'  => 'Не работает кнопка оплаты',  
    'author' => 'user@example.com',  
    'status' => 'open',  
    'files'  => ['/uploads/screenshot.png'],  
]);  
```  
  
#### `PostContext::select(...$where): array`  
  
Возвращает список постов. Без `name` — все посты блога. С `name` — конкретный пост.  
  
```php  
// Все посты блога  
$APP->talk->blog('support')->post()->select()  
  
// Только открытые  
$APP->talk->blog('support')->post()->select(['archived' => 0], ['status' => 'open'])  
  
// Конкретный пост  
$APP->talk->blog('support')->post('ticket-123')->select()  
```  
  
#### `PostContext::update(array $data): self`  
  
Обновляет данные поста. Требует `name`. Автоматически проставляет `updated`.  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->update(['status' => 'closed']);  
```  
  
#### `PostContext::delete(): self`  
  
Удаляет пост и все его сообщения (каскадно). Требует `name`.  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->delete();  
```  
  
#### `PostContext::archive(bool $state = true): self`  
  
Переводит пост в архив или восстанавливает. Требует `name`.  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->archive();       // закрыть  
$APP->talk->blog('support')->post('ticket-123')->archive(false);  // переоткрыть  
```  
  
#### `PostContext::message(?int $id = null): MessageContext`  
  
Переходит в контекст сообщений. Без аргумента — контекст всех сообщений поста. С ID — контекст конкретного сообщения.  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->message()    // все сообщения  
$APP->talk->blog('support')->post('ticket-123')->message(42)  // сообщение #42  
```  
  
---  
  
### Message  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->message(?int $id = null): MessageContext  
```  
  
#### `MessageContext::create(array $data): self`  
  
Добавляет новое сообщение. Автоматически проставляет `created`, `updated`, `post_id`. После добавления обновляет `updated` у родительского поста.  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->message()->create([  
    'text'   => 'Проблема воспроизводится на Chrome 124',  
    'author' => 'support@example.com',  
    'files'  => ['/uploads/log.txt'],  
]);  
```  
  
#### `MessageContext::select(...$where): array`  
  
Возвращает список сообщений поста. С ID — возвращает конкретное сообщение.  
  
```php  
// Все сообщения  
$APP->talk->blog('support')->post('ticket-123')->message()->select()  
  
// Только активные  
$APP->talk->blog('support')->post('ticket-123')->message()->select(['archived' => 0])  
  
// Конкретное сообщение  
$APP->talk->blog('support')->post('ticket-123')->message(42)->select()  
```  
  
#### `MessageContext::update(array $data): self`  
  
Обновляет сообщение. Требует ID в `message($id)`. Автоматически проставляет `updated`.  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->message(42)->update([  
    'text' => 'Исправленный текст сообщения',  
]);  
```  
  
#### `MessageContext::delete(): self`  
  
Удаляет сообщение. Требует ID.  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->message(42)->delete();  
```  
  
#### `MessageContext::archive(bool $state = true): self`  
  
Мягкое удаление сообщения (`archived = 1`). Требует ID.  
  
```php  
$APP->talk->blog('support')->post('ticket-123')->message(42)->archive();  
$APP->talk->blog('support')->post('ticket-123')->message(42)->archive(false); // восстановить  
```  
  
#### `MessageContext::slice(int $since): array`  
  
Возвращает сообщения, созданные после `$since` (Unix timestamp). Используется для polling.  
  
```php  
$newMessages = $APP->talk->blog('support')->post('ticket-123')->message()->slice($lastCheck);  
```  
  
---  
  
## Расширенная фильтрация select()  
  
Все методы `select()` принимают variadic-аргументы, каждый из которых передаётся в `wheres()` ORM. Это позволяет передавать как простые условия равенства, так и произвольные SQL-выражения.  
  
**Простые условия (массив ключ → значение):**  
  
```php  
// Только открытые и не архивные посты  
$APP->talk->blog('support')->post()->select(  
    ['archived' => 0],  
    ['status'   => 'open']  
);  
```  
  
**SQL-выражения с параметрами:**  
  
```php  
// Посты, созданные после определённой даты  
$APP->talk->blog('blog')->post()->select(  
    ['created > ?', 1700000000]  
);  
  
// Сообщения конкретного автора  
$APP->talk->blog('support')->post('ticket-123')->message()->select(  
    ['author = ?', 'admin@example.com'],  
    ['archived' => 0]  
);  
```  
  
**Без условий — вернуть всё:**  
  
```php  
$APP->talk->blog('support')->post()->select()  
```  
  
---  
  
## Polling — получение новых сообщений  
  
Для реализации обновления в реальном времени без WebSocket используйте метод `slice()` совместно с `setInterval` на клиенте.  
  
**PHP-контроллер:**  
  
```php  
// controller: /admin/talk/poll  
$since = (int) $_GET['since'];  
$slug  = $_GET['post'];  
  
$messages = $APP->talk->blog('support')->post($slug)->message()->slice($since);  
  
header('Content-Type: application/json');  
echo json_encode($messages);  
```  
  
**JavaScript-клиент:**  
  
```javascript  
var lastCheck = Math.floor(Date.now() / 1000);  
  
setInterval(function() {  
    $.getJSON('/admin/talk/poll', { post: 'ticket-123', since: lastCheck }, function(messages) {  
        if (messages.length) {  
            messages.forEach(function(msg) {  
                $('#chat').append('<div>' + msg.author + ': ' + msg.text + '</div>');  
            });  
            lastCheck = messages[messages.length - 1].created;  
        }  
    });  
}, 3000);  
```  
  
`slice()` возвращает сообщения, отсортированные по `created ASC`, что гарантирует правильный порядок при отображении.  
  
---  
  
## Архивирование и мягкое удаление  
  
Все три уровня иерархии поддерживают поле `archived`. Это позволяет скрывать записи без физического удаления из БД — история сохраняется.  
  
```php  
// Закрыть тикет (перевести в архив)  
$APP->talk->blog('support')->post('ticket-123')->archive();  
  
// Восстановить тикет  
$APP->talk->blog('support')->post('ticket-123')->archive(false);  
  
// Скрыть сообщение (мягкое удаление)  
$APP->talk->blog('support')->post('ticket-123')->message(42)->archive();  
  
// Получить только активные посты  
$APP->talk->blog('support')->post()->select(['archived' => 0]);  
  
// Получить только архивные (закрытые тикеты)  
$APP->talk->blog('support')->post()->select(['archived' => 1]);  
```  
  
Физическое удаление (с каскадом) доступно через `delete()`:  
  
```php  
// Удалить пост и все его сообщения безвозвратно  
$APP->talk->blog('support')->post('ticket-123')->delete();  
```  
  
---  
  
## Поля files, tags, meta  
  
Поля `files`, `tags`, `meta` хранятся в БД как JSON-строки. Фасад автоматически кодирует их при записи и декодирует при чтении — в PHP вы всегда работаете с массивами.  
  
### files  
  
Массив путей к прикреплённым файлам. Доступен на уровне постов и сообщений.  
  
```php  
// Создать пост с вложениями  
$APP->talk->blog('support')->post('ticket-abc')->create([  
    'title' => 'Ошибка при загрузке',  
    'files' => ['/uploads/2024/screenshot.png', '/uploads/2024/log.txt'],  
]);  
  
// Прочитать  
$post = $APP->talk->blog('support')->post('ticket-abc')->select();  
// $post[0]['files'] === ['/uploads/2024/screenshot.png', '/uploads/2024/log.txt']  
```  
  
### tags  
  
Массив строковых меток. Используется для категоризации и фильтрации.  
  
```php  
$APP->talk->blog('support')->post('ticket-abc')->create([  
    'title' => 'Проблема с оплатой',  
    'tags'  => ['billing', 'urgent', 'payment'],  
]);  
```  
  
### meta  
  
JSON-объект для произвольных расширений без изменения схемы БД.  
  
```php  
// Хранить дополнительные данные  
$APP->talk->blog('support')->post('ticket-abc')->message()->create([  
    'text' => 'Статус изменён',  
    'meta' => [  
        'type'       => 'system',       // системное сообщение  
        'old_status' => 'open',  
        'new_status' => 'closed',  
    ],  
]);  
  
// Для сообщений чата  
$APP->talk->blog('workspace')->post('general')->message()->create([  
    'text' => 'Привет!',  
    'meta' => [  
        'reply_to' => 37,   // ID сообщения, на которое отвечаем  
        'is_pinned' => false,  
    ],  
]);  
```  
  
---  
  
## Примеры использования  
  
### Блог со статьями  
  
```php  
// Создать раздел блога  
$APP->talk->blog('company-blog')->create([  
    'title' => 'Блог компании',  
]);  
  
// Опубликовать статью  
$APP->talk->blog('company-blog')->post('release-2-0')->create([  
    'title'  => 'Релиз версии 2.0',  
    'author' => 'editor@example.com',  
    'status' => 'published',  
    'tags'   => ['release', 'news'],  
    'files'  => ['/uploads/banner-2-0.jpg'],  
]);  
  
// Добавить комментарий к статье  
$APP->talk->blog('company-blog')->post('release-2-0')->message()->create([  
    'text'   => 'Отличный релиз!',  
    'author' => 'reader@example.com',  
]);  
  
// Получить все опубликованные статьи  
$posts = $APP->talk->blog('company-blog')->post()->select(  
    ['status'   => 'published'],  
    ['archived' => 0]  
);  
  
// Получить комментарии к статье  
$comments = $APP->talk->blog('company-blog')->post('release-2-0')->message()->select(  
    ['archived' => 0]  
);  
```  
  
---  
  
### Система поддержки (тикеты)  
  
```php  
// Создать канал поддержки  
$APP->talk->blog('support')->create(['title' => 'Служба поддержки']);  
  
// Пользователь создаёт тикет  
$ticketSlug = 'ticket-' . uniqid();  
$APP->talk->blog('support')->post($ticketSlug)->create([  
    'title'  => 'Не работает кнопка оплаты',  
    'author' => 'user@example.com',  
    'status' => 'open',  
    'files'  => ['/uploads/screenshot.png'],  
    'tags'   => ['billing', 'urgent'],  
]);  
  
// Оператор отвечает  
$APP->talk->blog('support')->post($ticketSlug)->message()->create([  
    'text'   => 'Добрый день! Уточните, какой браузер используете?',  
    'author' => 'support@example.com',  
]);  
  
// Пользователь отвечает  
$APP->talk->blog('support')->post($ticketSlug)->message()->create([  
    'text'   => 'Chrome 124 на Windows 11',  
    'author' => 'user@example.com',  
]);  
  
// Закрыть тикет  
$APP->talk->blog('support')->post($ticketSlug)->update(['status' => 'closed']);  
$APP->talk->blog('support')->post($ticketSlug)->archive();  
  
// Список открытых тикетов  
$open = $APP->talk->blog('support')->post()->select(  
    ['status'   => 'open'],  
    ['archived' => 0]  
);  
  
// Список закрытых (архивных)  
$closed = $APP->talk->blog('support')->post()->select(['archived' => 1]);  
```  
  
---  
  
### Заявки с сайта (лиды)  
  
```php  
// Канал для входящих заявок  
$APP->talk->blog('leads')->create(['title' => 'Заявки с сайта']);  
  
// Новая заявка (из формы обратной связи)  
$leadSlug = 'lead-' . uniqid();  
$APP->talk->blog('leads')->post($leadSlug)->create([  
    'title'  => 'Заявка: ' . $_POST['name'],  
    'author' => $_POST['email'],  
    'status' => 'new',  
    'meta'   => [  
        'phone'   => $_POST['phone'],  
        'message' => $_POST['message'],  
        'source'  => 'landing-page',  
        'utm'     => $_GET['utm_source'] ?? null,  
    ],  
]);  
  
// Менеджер берёт заявку в работу  
$APP->talk->blog('leads')->post($leadSlug)->update(['status' => 'in_progress']);  
$APP->talk->blog('leads')->post($leadSlug)->message()->create([  
    'text'   => 'Позвонил клиенту, договорились на встречу 15 мая',  
    'author' => 'manager@example.com',  
]);  
  
// Заявка закрыта  
$APP->talk->blog('leads')->post($leadSlug)->update(['status' => 'won']);  
$APP->talk->blog('leads')->post($leadSlug)->archive();  
  
// Все новые заявки  
$newLeads = $APP->talk->blog('leads')->post()->select(  
    ['status'   => 'new'],  
    ['archived' => 0]  
);  
```  
  
---  
  
### Рабочий чат (каналы)  
  
```php  
// Создать каналы  
$APP->talk->blog('workspace')->create(['title' => 'Рабочее пространство']);  
  
foreach (['general', 'dev', 'design', 'random'] as $channel) {  
    $APP->talk->blog('workspace')->post($channel)->create([  
        'title' => '#' . $channel,  
    ]);  
}  
  
// Отправить сообщение в канал  
$APP->talk->blog('workspace')->post('general')->message()->create([  
    'text'   => 'Всем привет! Сегодня деплой в 18:00',  
    'author' => 'admin@example.com',  
]);  
  
// Ответить на сообщение (через meta)  
$APP->talk->blog('workspace')->post('general')->message()->create([  
    'text'   => '+1, буду готов',  
    'author' => 'dev@example.com',  
    'meta'   => ['reply_to' => 15],  
]);  
  
// Получить последние 50 сообщений канала  
$messages = $APP->talk->blog('workspace')->post('general')->message()->select(  
    ['archived' => 0]  
);  
$messages = array_slice($messages, -50);  
  
// Polling — новые сообщения с момента $lastCheck  
$new = $APP->talk->blog('workspace')->post('general')->message()->slice($lastCheck);  
```  
  
---  
  
### Комментарии к товарам  
  
```php  
// Один блог для всех товаров  
$APP->talk->blog('products')->create(['title' => 'Отзывы к товарам']);  
  
// Пост = товар (slug = product-{id})  
$productSlug = 'product-' . $product['id'];  
$APP->talk->blog('products')->post($productSlug)->create([  
    'title' => $product['name'],  
]);  
  
// Покупатель оставляет отзыв  
$APP->talk->blog('products')->post($productSlug)->message()->create([  
    'text'   => 'Отличный товар, рекомендую!',  
    'author' => $user['email'],  
    'tags'   => ['review'],  
    'meta'   => ['rating' => 5],  
]);  
  
// Модератор скрывает спам  
$APP->talk->blog('products')->post($productSlug)->message(77)->archive();  
  
// Получить все активные отзывы  
$reviews = $APP->talk->blog('products')->post($productSlug)->message()->select(  
    ['archived' => 0]  
);  
  
// Средний рейтинг — считается на уровне приложения из $msg['meta']['rating']  
```  
  
---  
  
### Уведомления и системные сообщения  
  
```php  
// Персональный канал уведомлений для каждого пользователя  
$userSlug = 'notifications-' . $user['id'];  
$APP->talk->blog('notifications')->post($userSlug)->create([]);  
  
// Отправить системное уведомление  
$APP->talk->blog('notifications')->post($userSlug)->message()->create([  
    'text'   => 'Ваш тикет #4821 закрыт',  
    'author' => 'system',  
    'tags'   => ['system'],  
    'meta'   => [  
        'type'     => 'ticket_closed',  
        'ticket'   => 'ticket-abc123',  
        'link'     => '/support/ticket-abc123',  
    ],  
]);  
  
// Получить непрочитанные уведомления (через meta или tags)  
$notifications = $APP->talk->blog('notifications')->post($userSlug)->message()->select(  
    ['archived' => 0]  
);  
  
// Пометить уведомление как прочитанное (мягкое удаление)  
$APP->talk->blog('notifications')->post($userSlug)->message(99)->archive();  
```  
  
---  
  
## Именование slug  
  
Slug (`name`) — строковый идентификатор блога или поста. Должен быть уникальным в пределах родительского блога (но может повторяться в разных блогах).  
  
| Сценарий | Пример slug |  
|---|---|  
| Канал поддержки | `support`, `billing`, `technical` |  
| Канал чата | `general`, `dev`, `design`, `random` |  
| Тикет | `ticket-` + `uniqid()` |  
| Заявка с сайта | `lead-` + `uniqid()` |  
| Статья блога | `release-2-0`, `how-to-install` |  
| Товар | `product-` + `$product['id']` |  
| Задача | `task-` + `$task['id']` |  
| Уведомления пользователя | `notifications-` + `$user['id']` |  
  
**Правила:**  
- Slug уникален в пределах родительского блога  
- Для динамически создаваемых сущностей используйте `uniqid()` или ID из внешней системы  
- Рекомендуется использовать только строчные буквы, цифры и дефис