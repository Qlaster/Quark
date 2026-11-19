# Фасад Objects  
  
Фасад `$APP->object` — универсальное хранилище объектов в Quark Framework.  
Позволяет сохранять, получать и управлять произвольными PHP-значениями (массивы,  
строки, числа, объекты) под именованными ключами, с группировкой по коллекциям.  
Данные хранятся в базе, по умолчанию SQLite, но можно использовать любую другую. Данные сериализуются автоматически — никакой ручной  
работы с БД не требуется.  
  
---  
  
## Содержание  
  
1. [Для чего нужен](#для-чего-нужен)  
2. [Примеры применения](#примеры-применения)  
3. [Административный интерфейс](#административный-интерфейс)  
4. [Файл предустановки (install.ini)](#файл-предустановки-installini)  
5. [API фасада](#api-фасада)  
6. [История изменений (Timeline)](#история-изменений-timeline)  
7. [Импорт и экспорт](#импорт-и-экспорт)  
8. [Конфигурация (objects.ini)](#конфигурация-objectsini)  
  
---  
  
## Для чего нужен  
  
Фасад Objects решает задачу хранения структурированных данных, которые не вписываются  
в реляционную модель и не требуют отдельной таблицы в БД. Это удобный key-value  
склад с поддержкой вложенных структур и группировки.  
  
**Ключевые возможности:**  
  
- Хранение любых PHP-значений: массивы, строки, числа, вложенные структуры  
- Группировка объектов по именованным коллекциям  
- Автоматическая сериализация и десериализация данных  
- История изменений каждого объекта с возможностью отката  
- Копирование и переименование объектов и коллекций целиком  
- Экспорт и импорт в форматах JSON и INI  
- Предустановка объектов через файл `app/install.ini`  
- Готовый графический интерфейс в административной панели  
  
---  
  
## Примеры применения  
  
### 1. Настройки приложения  
  
Хранение конфигурации сайта, которую администратор может менять через интерфейс:  
  
```php  
// Сохранить настройки  
$APP->object->collection('settings')->set('site', [  
    'title'       => 'Мой сайт',  
    'description' => 'Описание сайта',  
    'email'       => 'admin@example.com',  
    'phone'       => '+7 (999) 123-45-67',  
]);  
  
// Получить настройки в шаблоне  
$settings = $APP->object->collection('settings')->get('site');  
$content['site']['title'] = $settings['title'];  
```  
  
### 2. Навигационное меню  
  
Хранение структуры меню сайта с вложенными пунктами:  
  
```php  
// Сохранить меню  
$APP->object->collection('menu')->set('main', [  
    ['head' => 'Главная',  'link' => '/'],  
    ['head' => 'О нас',    'link' => '/about'],  
    ['head' => 'Контакты', 'link' => '/contacts'],  
]);  
  
// Получить меню  
$content['nav'] = $APP->object->collection('menu')->get('main');  
```  
  
### 3. Галерея изображений  
  
Хранение списка изображений с метаданными:  
  
```php  
// Добавить изображение в галерею  
$gallery = $APP->object->collection('gallery')->get('portfolio') ?? ['list' => []];  
$gallery['list'][] = [  
    'image'   => 'public/gallery/portfolio/photo1.jpg',  
    'caption' => 'Проект 2024',  
];  
$APP->object->collection('gallery')->set('portfolio', $gallery);  
```  
  
### 4. Блоки контента страниц  
  
Хранение текстовых блоков, баннеров и виджетов для разных страниц:  
  
```php  
// Сохранить блок  
$APP->object->collection('blocks')->set('hero', [  
    'title'    => 'Добро пожаловать',  
    'subtitle' => 'Лучший сервис в городе',  
    'button'   => ['text' => 'Узнать больше', 'link' => '/about'],  
    'image'    => 'public/images/hero.jpg',  
]);  
  
// Получить блок в контроллере  
$content['hero'] = $APP->object->collection('blocks')->get('hero');  
```  
  
### 5. Кэш вычисляемых данных  
  
Хранение результатов тяжёлых вычислений или внешних API-запросов:  
  
```php  
$cacheKey = 'weather_' . date('Y-m-d-H');  
$weather  = $APP->object->collection('cache')->get($cacheKey);  
  
if (!$weather) {  
    $weather = fetchWeatherFromAPI();  
    $APP->object->collection('cache')->set($cacheKey, $weather);  
}  
```  
  
### 6. Пользовательские формы  
  
Хранение структуры форм обратной связи, опросов, анкет:  
  
```php  
$APP->object->collection('forms')->set('feedback', [  
    'title'  => 'Обратная связь',  
    'fields' => [  
        ['name' => 'name',    'type' => 'text',  'label' => 'Ваше имя'],  
        ['name' => 'email',   'type' => 'email', 'label' => 'Email'],  
        ['name' => 'message', 'type' => 'textarea', 'label' => 'Сообщение'],  
    ],  
    'action' => 'feedback/send',  
]);  
```  
  
### 7. Справочные данные  
  
Хранение справочников, которые редко меняются, но нужны во многих местах:  
  
```php  
// Сохранить справочник статусов  
$APP->object->collection('dict')->set('order_status', [  
    'new'        => 'Новый',  
    'processing' => 'В обработке',  
    'shipped'    => 'Отправлен',  
    'done'       => 'Выполнен',  
    'cancelled'  => 'Отменён',  
]);  
  
// Использовать в шаблоне  
$statuses = $APP->object->collection('dict')->get('order_status');  
$label    = $statuses[$order['status']] ?? 'Неизвестно';  
```  
  
---  
  
## Административный интерфейс  
  
В административной панели есть готовый графический инструмент для работы  
с объектами, доступный по адресу `admin/constructor/object`.  
  
**Возможности интерфейса:**  
  
- Список всех коллекций с возможностью создания, переименования, копирования и удаления  
- Список объектов внутри коллекции  
- **Графический конструктор** (`admin/constructor/object/edit`) — визуальный редактор  
  структуры объекта: добавление, удаление и редактирование полей без написания кода  
- **Текстовый редактор** (`admin/constructor/object/plaintext/edit`) — редактирование  
  объекта в формате INI напрямую, для опытных пользователей  
- **История изменений** — просмотр всех версий объекта с датой и типом действия  
  (insert / update / delete), возможность отката к любой предыдущей версии  
- Экспорт коллекции или отдельного объекта в JSON-файл  
- Импорт объектов из JSON-файла  
  
---  
  
## Файл предустановки (install.ini)  
  
Файл `app/install.ini` позволяет описать начальный набор объектов, которые должны  
быть установлены в хранилище при развёртывании приложения. Это удобно для поставки  
приложения с готовыми данными: меню, настройками, справочниками.  
  
**Формат файла:**  
  
```ini  
#=======================================================================  
#   Объекты устанавливаемого приложения  [header-1](#header-1)
#=======================================================================  
  
[menu]  
main.0.head = "Главная"  
main.0.link = "/"  
main.1.head = "О нас"  
main.1.link = "/about"  
  
[settings]  
site.title       = "Мой сайт"  
site.email       = "admin@example.com"  
  
[dict]  
order_status.new        = "Новый"  
order_status.processing = "В обработке"  
order_status.done       = "Выполнен"  
```  
  
Каждая секция `[name]` соответствует коллекции. Ключи внутри секции — имена объектов  
и их поля в dot-нотации.  
  
**Установка объектов из файла:**  
  
Через административный интерфейс: `admin/options/install` — открывает редактор  
файла `app/install.ini` и запускает установку одной кнопкой. Каждый объект  
записывается в хранилище командой:  
  
```php  
$APP->object->collection($section)->set($name, $object);  
```  
  
**Установка через консоль:**  
  
```bash  
php quark objects import app/install.ini  
```  
  
---  
  
## API фасада  
  
### `collection($name)`  
  
Выбирает коллекцию для последующих операций. Возвращает `$this` — все методы  
можно вызывать цепочкой.  
  
```php  
$APP->object->collection('menu')  
```  
  
> Все методы ниже работают в контексте выбранной коллекции.  
  
---  
  
### `set($name, $value)`  
  
Сохраняет объект под именем `$name`. Если объект с таким именем уже существует —  
обновляет его, предварительно сохранив предыдущую версию в историю.  
Принимает любое PHP-значение: массив, строку, число, `null`.  
  
```php  
$APP->object->collection('settings')->set('site', ['title' => 'Мой сайт']);  
```  
  
---  
  
### `get($name)`  
  
Возвращает объект по имени. Если объект не найден — возвращает `null`.  
  
```php  
$site = $APP->object->collection('settings')->get('site');  
// ['title' => 'Мой сайт']  
```  
  
---  
  
### `del($name)`  
  
Удаляет объект по имени. Перед удалением сохраняет снимок в историю.  
  
```php  
$APP->object->collection('cache')->del('weather_2024-01-15-12');  
```  
  
---  
  
### `create()`  
  
Создаёт пустую коллекцию (добавляет запись с пустым именем объекта).  
Используется для явного создания коллекции до добавления объектов.  
  
```php  
$APP->object->collection('myapp')->create();  
```  
  
---  
  
### `drop()`  
  
Удаляет коллекцию целиком вместе со всеми объектами в ней.  
  
```php  
$APP->object->collection('cache')->drop();  
```  
  
---  
  
### `names()`  
  
Возвращает массив имён всех объектов в коллекции, отсортированных по алфавиту.  
  
```php  
$names = $APP->object->collection('menu')->names();  
// ['footer', 'main', 'sidebar']  
```  
  
---  
  
### `all()`  
  
Возвращает ассоциативный массив всех объектов коллекции в формате  
`['name' => value, ...]`, отсортированный по имени.  
С выбранным языком — только документы этого языка, с цепочкой —  
первый удачный язык каждой семьи.  
  
```php  
$all = $APP->object->collection('menu')->all();  
// ['footer' => [...], 'main' => [...], 'sidebar' => [...]]  
```  
  
---  

### `lang($lang = null)`  
  
Выбирает язык **следующей** операции. Одноразовый: сбрасывается после  
любой операции с объектом и при каждом вызове `collection()`.  
  
Документ адресуется тройкой `(collection, name, lang)`. `lang = ''` —  
базовый документ (без языка). Каждый язык хранится самостоятельной  
записью — никаких наложений и подмен: нет документа на языке → `null`.  
  
```php  
// Записать / прочитать документ на конкретном языке  
$APP->object->collection('pages')->lang('en')->set('about', $doc);  
$APP->object->collection('pages')->lang('en')->get('about');   // нет en → null  
  
// Каскад: массив — цепочка приоритетов, первая найденная запись  
$APP->object->collection('pages')->lang(['uk','ru',''])->get('about');  
  
// Удаление: язык выбирает адрес  
$APP->object->collection('pages')->lang('en')->del('about');        // только en  
$APP->object->collection('pages')->lang(['en','uk'])->del('about'); // перечисленные  
$APP->object->collection('pages')->del('about');                    // вся семья  
  
// set() с массивом языков — ошибка (пишется одна языковая запись)  
```  
  
---  

### `langs($name)`  
  
Возвращает список языков документа (`''` — базовый).  
Не расходует выбранный `lang()`.  
  
```php  
$langs = $APP->object->collection('pages')->langs('about');  
// ['', 'en', 'uk']  
```  
  
---  
  
### `rename($nameA, $nameB = null)`  
  
Переименовывает объект или коллекцию.  
  
```php  
// Переименовать объект внутри коллекции  
$APP->object->collection('menu')->rename('main', 'primary');  
  
// Переименовать коллекцию целиком  
$APP->object->collection('menu')->rename('navigation');  
```  
  
---  
  
### `copy($nameA, $nameB = null)`  
  
Копирует объект или коллекцию.  
  
```php  
// Скопировать объект  
$APP->object->collection('menu')->copy('main', 'main_backup');  
  
// Скопировать коллекцию целиком под новым именем  
$APP->object->collection('menu')->copy('menu_v2');  
```  
  
---  
  
### `collection_list()`  
  
Возвращает массив имён всех существующих коллекций.  
  
```php  
$collections = $APP->object->collection_list();  
// ['cache', 'gallery', 'menu', 'settings']  
```  
  
---  
  
## История изменений (Timeline)  
  
Фасад автоматически ведёт историю изменений каждого объекта. При каждом вызове  
`set()` или `del()` предыдущее состояние объекта сохраняется в таблицу `timeline`  
с меткой времени и типом действия (`insert`, `update`, `delete`).  
  
**Просмотр истории** доступен в административном интерфейсе:  
`admin/constructor/object/timeline/?collection=...&name=...`  
  
**Откат к предыдущей версии** — через интерфейс или программно:  
  
```php  
// Восстановить объект на состояние конкретной даты  
$APP->object->timeline->restore(  
    ['collection' => 'settings', 'name' => 'site', 'date' => '2024-01-15 10:00:00']  
);  
  
// Восстановить в другое место  
$APP->object->timeline->restore(  
    ['collection' => 'settings', 'name' => 'site'],  
    ['collection' => 'settings', 'name' => 'site_backup']  
);  
```  
  
---  
  
## Импорт и экспорт  
  
Дамп — список строк таблицы один в один, со всеми колонками (`collection`,  
`name`, `lang`, `value`, `date`). Формат схемо-толерантен: колонки дампа,  
отсутствующие в целевой таблице, при импорте игнорируются — дампы проходят  
между версиями с разной схемой.  
  
```json  
[  
  {"collection":"pages","name":"about","lang":"","value":"a:1:{...}","date":"..."},  
  {"collection":"pages","name":"about","lang":"en","value":"a:1:{...}","date":"..."}  
]  
```  
  
### Экспорт в JSON  
  
Фильтры: коллекция — курсором `collection()`, имена — аргументом  
(строка или массив), языки — `->lang()`.  
  
```php  
// Экспортировать всё хранилище  
$json = $APP->object->export();  
  
// Экспортировать одну коллекцию  
$json = $APP->object->collection('menu')->export();  
  
// Экспортировать объект (вся семья языков) или список имён  
$json = $APP->object->collection('menu')->export('main');  
$json = $APP->object->collection('menu')->export(['main', 'footer']);  
  
// Экспортировать срез по языку  
$json = $APP->object->collection('pages')->lang('en')->export();  
```  
  
### Импорт из JSON  
  
Строки сливаются по тройке `(collection, name, lang)` — совпавший адрес  
перезаписывается, перезаписанное значение логируется в историю.  
  
```php  
$json = file_get_contents('backup.json');  
$APP->object->import($json);   // IMPORT_OVERWRITE — дефолт  
  
// Коллизии → исключение до первой записи  
$APP->object->import($json, \App\Facade\Objects::IMPORT_STRICT);  
  
// Существующие адреса пропустить, записать только новые  
$APP->object->import($json, \App\Facade\Objects::IMPORT_SKIP);  
```  
  
### Через консоль  
  
```bash  
# Импорт из JSON или INI  [header-2](#header-2)
php quark objects import app/install.ini  
php quark objects import backup.json  
  
# Экспорт в JSON или INI  [header-3](#header-3)
php quark objects export backup.json  
php quark objects export backup.ini  
```  
  
---  
  
## Конфигурация (objects.ini)  
  
Файл `engine/facades/objects.ini` задаёт подключение к базе данных и имена таблиц.  
  
```ini  
[db]  
pdo      = "sqlite:engine/database/objects.sqlite"  ; Строка подключения PDO  
user     =                                           ; Пользователь (для MySQL)  
password =                                           ; Пароль (для MySQL)  
  
[table]  
items    = "items"     ; Таблица объектов  
timeline = "timeline"  ; Таблица истории изменений  
```  
  
| Параметр         | Описание |  
|------------------|----------|  
| `db.pdo`         | Строка подключения PDO. По умолчанию SQLite. Можно переключить на MySQL: `mysql:host=localhost;dbname=mydb` |  
| `db.user`        | Пользователь БД (для MySQL/PostgreSQL) |  
| `db.password`    | Пароль БД (для MySQL/PostgreSQL) |  
| `table.items`    | Имя таблицы для хранения объектов |  
| `table.timeline` | Имя таблицы для истории изменений |  
  
Таблицы создаются автоматически при первом обращении к фасаду — никакой ручной  
миграции не требуется.  
  
---  
  
## Файлы  
  
| Файл | Назначение |  
|------|-----------|  
| `engine/facades/objects.php`                  | Реализация фасада (`Objects`, `timeline`) |  
| `engine/facades/objects.ini`                  | Конфигурация подключения к БД |  
| `engine/database/objects.sqlite`              | База данных объектов (создаётся автоматически) |  
| `app/install.ini`                             | Файл предустановки объектов |  
| `app/controllers/admin/constructor/object/`   | Контроллеры административного интерфейса |  
| `app/controllers/admin/options/install/`      | Контроллер установки объектов из install.ini |