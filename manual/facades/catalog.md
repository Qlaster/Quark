# Фасад Catalog  
  
Фасад `$APP->catalog` — универсальный инструмент для работы со структурированными  
табличными данными в Quark Framework. Он предоставляет единый API для подключения  
к любым таблицам БД, чтения и изменения записей, управления файлами и связями  
между таблицами — без необходимости писать отдельный код для каждого типа контента.  
  
---  
  
## Содержание  
  
1. [Для чего нужен](#для-чего-нужен)  
2. [Примеры применения](#примеры-применения)  
3. [Административный интерфейс](#административный-интерфейс)  
4. [API фасада](#api-фасада)  
5. [Конфигурация (catalog.ini)](#конфигурация-catalogini)  
   - [Секции access и denied — права доступа](#секции-access-и-denied--права-доступа)  
6. [Типы полей](#типы-полей)  
7. [Связи между каталогами](#связи-между-каталогами-relation)  
8. [Импорт и экспорт CSV](#импорт-и-экспорт-csv)  
  
---  
  
## Для чего нужен  
  
Фасад Catalog решает задачу создания CRUD-интерфейсов для любых табличных данных.  
Вместо того чтобы писать SQL-запросы и формы вручную для каждой сущности, достаточно  
описать каталог в конфиге — и фасад предоставит готовый API для работы с данными,  
а административный интерфейс автоматически построит таблицу и формы редактирования.  
  
**Ключевые возможности:**  
  
- Универсальный CRUD для любой таблицы любой БД  
- Типизация полей с автоматическим рендерингом в админке  
- Загрузка и хранение файлов (изображения, аудио, документы, архивы)  
- Связи между таблицами и каталогами (тип `relation`)  
- Фильтрация, сортировка, группировка и пагинация из коробки  
- Импорт и экспорт данных в CSV  
- Готовый графический интерфейс в административной панели  
  
---  
  
## Примеры применения  
  
### 1. Управление пользователями  
  
Каталог `account` с полями login, name, email, аватар (`image`), документы (`files`),  
флаг блокировки (`check`). Администратор видит таблицу пользователей, может редактировать  
любую запись, загружать аватары и блокировать аккаунты одним кликом.  
  
### 2. База знаний / статьи  
  
Каталог `articles` с полями title (`text`), body (`html`), author_id (`relation` → users),  
category_id (`relation` → categories), published (`check`), cover (`image`).  
HTML-редактор Summernote подключается автоматически для поля типа `html`.  
  
### 3. Медиатека  
  
Каталог `media` с полями name (`text`), file (`audio`), cover (`image`), description (`html`).  
В таблице администратор видит иконку воспроизведения и может прослушать аудио прямо  
в браузере, не покидая страницу.  
  
### 4. Каталог товаров  
  
Каталог `products` с полями name (`text`), price (`real`), category_id (`relation`),  
photos (`files`), description (`html`), active (`check`).  
Связь с таблицей категорий позволяет выбирать категорию из выпадающего списка.  
  
### 5. Обратная связь  
  
Каталог `review` с полями name (`text`), email (`text`), message (`html`), voice (`audio`),  
created (`datetime`), processed (`check`). Голосовые сообщения прослушиваются прямо  
в таблице, статус обработки переключается одним кликом.  
  
### 6. Справочники  
  
Каталог `countries` с полями code (`text`), name (`text`), flag (`image`).  
Используется как источник для `relation`-полей в других каталогах.  
  
### 7. Документооборот  
  
Каталог `documents` с полями title (`text`), type (`text`), files (`files`),  
author_id (`relation` → account), signed (`checkdate`), expires (`date`).  
Поле `checkdate` хранит одновременно булев флаг и дату — удобно для отметки  
о подписании с фиксацией даты.  
  
---  
  
## Административный интерфейс  
  
В административной панели уже есть готовый графический инструмент для работы  
с каталогами, доступный по адресу `admin/catalogs`.  
  
**Возможности интерфейса:**  
  
- Список всех каталогов с индикатором доступности БД  
- Табличный просмотр записей с пагинацией и поиском  
- Создание и редактирование записей через модальные формы  
- Загрузка файлов любых типов (изображения, аудио, документы, архивы)  
- Встроенный аудиоплеер — прослушивание аудиозаписей прямо в таблице  
- Просмотр HTML-контента с превью без открытия редактора  
- Превью изображений в таблице  
- Редактирование HTML через встроенный редактор Summernote  
- Выбор связанных записей из других каталогов (`relation`)  
- Импорт данных из CSV с маппингом колонок  
- Экспорт каталога в CSV  
- Быстрый доступ к редактированию конфига каталогов (`catalog.ini`)  
- Визуальный редактор прав доступа (`admin/catalogs/access/edit?name={catalog}`):  
  матрица «операция × поле» для разрешений и запретов, проверка эффективных  
  прав произвольного логина  
  
---  
  
## API фасада  
  
### `$APP->catalog->listing()`  
  
Возвращает массив всех каталогов, описанных в конфиге.  
  
```php  
$catalogs = $APP->catalog->listing();  
// ['account' => [...], 'review' => [...], ...]  
```  
  
---  
  
### `$APP->catalog->get($name)`  
  
Возвращает конфигурацию одного каталога. Бросает исключение, если каталог  
не найден или не указаны `db` / `table`.  
  
```php  
$catalog = $APP->catalog->get('account');  
// ['db' => 'account', 'table' => 'account', 'field' => [...], ...]  
```  
  
---  
  
### `$APP->catalog->fields($name)`  
  
Возвращает описание полей каталога, обогащённое метаданными (в том числе  
разобранные `relation`-связи). Возвращает только те поля, которые реально  
существуют в таблице БД.  
  
```php  
$fields = $APP->catalog->fields('account');  
// ['login' => ['alias' => 'Логин', 'type' => 'text'], ...]  
```  
  
---  
  
### `$APP->catalog->items($name, $params = [])`  
  
Возвращает ORM-объект для низкоуровневой работы с таблицей каталога.  
Применяет настройки `events.view` из конфига, если параметры не переданы явно.  
Поля типа `files` автоматически десериализуются из JSON в массив.  
  
```php  
// Получить все активные записи, отсортированные по имени  
$records = $APP->catalog->items('account', [  
    'where'   => ['disable' => 0],  
    'orderby' => 'name ASC',  
    'limit'   => 20,  
    'offset'  => 0,  
])->select();  
```  
  
Доступные параметры:  
  
| Параметр  | Описание                                      |  
|-----------|-----------------------------------------------|  
| `where`   | Условие WHERE (строка или массив)             |  
| `orderby` | Сортировка (строка или массив)                |  
| `groupby` | Группировка (строка или массив)               |  
| `like`    | Поиск по подстроке                            |  
| `limit`   | Количество записей                            |  
| `offset`  | Смещение (для пагинации)                      |  
  
После вызова `items()` фактически применённые параметры доступны в `$APP->catalog->lastQuery`.  
  
---  
  
### `$APP->catalog->view($name, $params = [])`  
  
Высокоуровневый метод: возвращает готовый массив с данными каталога для передачи  
в шаблон. Включает список записей, количество, поля, лимит и смещение.  
Автоматически разрешает `relation`-поля (заменяет сырой ID на `['id' => ..., 'label' => ...]`).  
  
```php  
$catalog = $APP->catalog->view('account', [  
    'limit'  => 25,  
    'offset' => 0,  
    'where'  => ['disable' => 0],  
]);  
  
// $catalog['list']   — массив записей  
// $catalog['count']  — общее количество записей  
// $catalog['field']  — описание полей  
// $catalog['limit']  — применённый лимит  
// $catalog['offset'] — применённое смещение  
```  
  
---  
  
### `$APP->catalog->create($catalog)`  
  
Создаёт новый каталог (добавляет запись в конфиг). Проверяет существование  
БД и таблицы перед сохранением.  
  
```php  
$APP->catalog->create([  
    'name'  => 'products',  
    'db'    => 'shop',  
    'table' => 'products',  
    'head'  => 'Товары',  
]);  
```  
  
---  
  
### `$APP->catalog->update($catalog)`  
  
Обновляет конфигурацию существующего каталога.  
  
```php  
$APP->catalog->update([  
    'name'  => 'products',  
    'db'    => 'shop',  
    'table' => 'products',  
    'head'  => 'Каталог товаров',  
]);  
```  
  
---  
  
### `$APP->catalog->delete($name)`  
  
Удаляет каталог из конфига (данные в БД не затрагиваются).  
  
```php  
$APP->catalog->delete('products');  
```  
  
---  
  
### `$APP->catalog->config()`  
  
Возвращает полный конфиг фасада в виде массива.  
  
```php  
$config = $APP->catalog->config();  
// ['view' => [...], 'upload' => [...], 'list' => [...]]  
```  
  
---  
  
### `$APP->catalog->patterns()`  
  
Возвращает список доступных типов полей с их человекочитаемыми названиями.  
  
```php  
$patterns = $APP->catalog->patterns();  
// ['text' => ['head' => 'Текст'], 'html' => ['head' => 'HTML код'], ...]  
```  
  
---  
  
### `$APP->catalog->access($name)`  
  
Возвращает объект `QCatalogAccess` — вычислитель прав доступа к записям  
каталога по секциям `[access]` и `[denied]` конфига (см. ниже). Привязка  
субъекта — методом `->as($login)`; без логина вычисляются права «гостя»  
(совпадают только `*`-маски).  
  
```php  
$ACC = $APP->catalog->access('books')->as($login);  
  
$ACC['update']             // bool — операция разрешена  
$ACC['where']['update']    // sql|null — ограничение записей  
$ACC['fields']['update']   // array|null — маски полей (null = все поля)  
$ACC['denied']['update']   // array — маски запрещённых полей  
  
$ACC->fieldAllowed('update', 'price');    // bool  
$data = $ACC->filterRecord('update', $data);  // отрезанные поля в ->dropped  
```  
  
Объект читается как массив (`ArrayAccess`, только чтение). Проверки прав  
делаются явно в контроллере — фасад сам по себе запросы не блокирует:  
  
```php  
$ACC = $APP->catalog->access('books')->as($APP->user->login);  
if (!$ACC['update']) throw new \Exception("Error access", 4);  
  
$orm  = $APP->catalog->items('books');  
$orm->where($ACC['where']['update']);  
$data = $ACC->filterRecord('update', $_POST['record']);  
```  
  
---  
  
## Конфигурация (catalog.ini)  
  
Файл `engine/facades/catalog.ini` — единственное место, где описываются все каталоги  
и глобальные настройки фасада.  
  
---  
  
### Секция `[view]`  
  
Глобальные настройки отображения записей.  
  
```ini  
[view]  
range = 15   ; Количество страниц в навигаторе пагинации  
limit = 50   ; Количество записей на странице по умолчанию  
```  
  
| Параметр | Тип | Описание |  
|----------|-----|----------|  
| `range`  | int | Сколько страниц показывать в пагинаторе |  
| `limit`  | int | Записей на странице (если не переопределено в `items()` / `view()`) |  
  
---  
  
### Секция `[upload]`  
  
Настройки загрузки файлов для полей типа `files`, `image`, `audio`.  
  
```ini  
[upload]  
folder   = public/catalogs/   ; Базовая директория для хранения файлов  
renaming = false               ; Переименовывать файлы в уникальные имена  
```  
  
| Параметр   | Тип  | Описание |  
|------------|------|----------|  
| `folder`   | path | Базовая директория. Файлы сохраняются по пути `{folder}/{catalog}/{id}/{field}/{filename}` |  
| `renaming` | bool | `true` — файл переименовывается в `{field}-{uniqid}.{ext}`, `false` — сохраняется оригинальное имя |  
  
**Пример структуры файлов:**  
  
```  
public/catalogs/  
  account/  
    42/  
      docs/  
        contract.pdf  
        passport.jpg  
      audio/  
        voice-message.mp3  
```  
  
---  
  
### Секция `[patterns]`  
  
Определяет человекочитаемые названия типов полей. Используется в административном  
интерфейсе при создании и редактировании каталогов.  
  
```ini  
[patterns]  
text.head      = "Текст"  
int.head       = "Целое число"  
real.head      = "Целое или дробное число"  
html.head      = "HTML код"  
link.head      = "Ссылка"  
image.head     = "Ссылка на картинку"  
video.head     = "Ссылка на видео"  
audio.head     = "Ссылка на аудио"  
blob.head      = "Бинарные данные"  
check.head     = "Переключатель"  
date.head      = "Дата"  
datetime.head  = "Дата и время"  
checkdate.head = "Флажок с датой"  
input.head     = "Нативный input"  
relation.head  = "Связь с другим каталогом"  
```  
  
---  
  
### Секция `[list]` — описание каталогов  
  
Каждый каталог описывается набором параметров с префиксом `{name}.`.  
  
#### Базовые параметры подключения  
  
```ini  
products.db      = "shop"              ; Имя подключения к БД (из db.ini)  
products.table   = "products"          ; Имя таблицы в БД  
products.head    = "Каталог товаров"   ; Заголовок каталога в интерфейсе  
products.info    = "Товары магазина"   ; Краткое описание  
products.help    = "Подсказка"         ; Подсказка для администратора  
products.icon    = "fa-shopping-cart"  ; Иконка FontAwesome (без префикса fa)  
products.refresh = 30                  ; Автообновление таблицы (секунды, 0 — выкл)  
```  
  
| Параметр  | Обязательный | Описание |  
|-----------|:---:|----------|  
| `db`      | да  | Имя подключения к БД |  
| `table`   | да  | Имя таблицы |  
| `head`    | нет | Заголовок в интерфейсе |  
| `info`    | нет | Описание каталога |  
| `help`    | нет | Подсказка для администратора |  
| `icon`    | нет | Иконка FontAwesome |  
| `refresh` | нет | Интервал автообновления в секундах |  
  
---  
  
#### Настройки отбора по умолчанию (`events.view`)  
  
Определяют, как данные выбираются из таблицы при открытии каталога.  
Могут быть переопределены при вызове `items()` или `view()`.  
  
```ini  
products.events.view.column  = "id,name,price,active"  ; Выбираемые колонки  
products.events.view.orderby = "name ASC"               ; Сортировка по умолчанию  
products.events.view.groupby = "category_id"            ; Группировка  
products.events.view.where   = "active = 1"             ; Условие фильтрации  
```  
  
| Параметр  | Описание |  
|-----------|----------|  
| `column`  | Список колонок через запятую. Если пусто — выбираются все |  
| `orderby` | SQL-выражение сортировки, например `created DESC` |  
| `groupby` | SQL-выражение группировки |  
| `where`   | SQL-условие WHERE или массив `['field' => value]` |  
  
---  
  
#### Описание полей (`field.*`)  
  
Для каждого поля таблицы можно задать псевдоним, тип и дополнительные параметры.  
Фасад возвращает только те поля, которые реально существуют в таблице БД.  
  
```ini  
; Базовый синтаксис  
{catalog}.field.{column}.alias = "Человекочитаемое название"  
{catalog}.field.{column}.type  = "тип"  
```  
  
**Пример полного описания каталога:**  
  
```ini  
products.db    = "shop"  
products.table = "products"  
products.head  = "Товары"  
products.icon  = fa-tag  
  
products.events.view.orderby = "name ASC"  
products.events.view.where   = "active = 1"  
  
products.field.id.type             = "id"  
products.field.name.alias          = "Название"  
products.field.name.type           = "text"  
products.field.price.alias         = "Цена"  
products.field.price.type          = "real"  
products.field.description.alias   = "Описание"  
products.field.description.type    = "html"  
products.field.cover.alias         = "Обложка"  
products.field.cover.type          = "image"  
products.field.photos.alias        = "Фотографии"  
products.field.photos.type         = "files"  
products.field.active.alias        = "Активен"  
products.field.active.type         = "check"  
products.field.created.alias       = "Дата создания"  
products.field.created.type        = "datetime"  
products.field.category_id.alias   = "Категория"  
products.field.category_id.type    = "relation"  
products.field.category_id.source  = "categories::name"  
```  
  
---  
  
#### Специальные параметры полей  
  
**`type = "id"`** — поле первичного ключа. Отображается как кликабельный лейбл  
для открытия формы редактирования.  
  
**`type = "input"` + `input = "..."`** — нативный HTML-input с произвольным типом.  
Позволяет использовать браузерные виджеты: color picker, range, tel и т.д.  
  
```ini  
products.field.color.alias = "Цвет"  
products.field.color.type  = "input"  
products.field.color.input = "color"   ; <input type="color">  
```  
  
**`ignore = 1`** — для полей типа `relation`. Отключает автоматическую подстановку  
связанной записи при выводе (в таблице будет показан сырой ID), но поле остаётся  
доступным для редактирования.  
  
```ini  
articles.field.author_id.alias  = "Автор"  
articles.field.author_id.type   = "relation"  
articles.field.author_id.source = "account::account::login"  
articles.field.author_id.ignore = 1   ; Показывать ID, не делать JOIN  
```  
  
---  
  
### Секции `[access]` и `[denied]` — права доступа  
  
Тонкая настройка прав на записи каталогов: кто и какие операции может выполнять,  
над какими полями и над какими записями. Права проверяются через  
`$APP->catalog->access()` (см. API выше).  
  
#### Синтаксис правила  
  
```ini  
[access]  
{catalog-mask}.{subject}.ops    = '["select","update"]'   ; обязателен  
{catalog-mask}.{subject}.fields = '["name","price"]'      ; поля (по умолчанию ["*"])  
{catalog-mask}.{subject}.where  = 'id > 0'                ; ограничение записей  
  
; По-операционные формы — спецификация для конкретной операции  
{catalog-mask}.{subject}.fields.update = '["name"]'  
{catalog-mask}.{subject}.where.update  = 'created > datetime("now","-1 day")'  
```  
  
| Элемент | Значение |  
|---------|----------|  
| `{catalog-mask}` | Имя каталога или маска: `books`, `news-*`, `*` |  
| `{subject}` | Логин или маска логина: `ivan`, `editor-*`, `*` (все, включая гостя). Точка в логине удваивается: `ivan..petrov` |  
| `ops` | JSON-массив операций: `select` `insert` `update` `delete` `replace`; `["*"]` — все |  
| `fields` | JSON-массив масок полей (`name`, `pr*`). По умолчанию — все поля |  
| `where` | SQL-фрагмент (тот же синтаксис, что `events.view.where`) |  
  
#### Семантика  
  
- `[access]` — whitelist: если секция объявлена, операция разрешена только  
  совпавшим субъектам. **Секций нет вообще → легаси-режим: всё открыто**  
- `[denied]` — та же грамматика, проверяется **первой**: любое совпадение  
  отменяет соответствующее разрешение  
- Несколько правил с разными масками каталога/субъекта **объединяются**:  
  операции и поля — union, `where` разных правил — OR  
- `where.*` и `where.<op>` одного правила складываются через AND;  
  правило без `where` = безусловный грант на все записи  
- `fields.<op>` перекрывает `fields.*` для этой операции  
- Запрет без `where` закрывает операцию целиком; с `where` — только часть записей  
- Запрет по полям **вычитается** из разрешённых:  
  `fields.allow *` + `denied.fields "salary"` → поле `salary` скрыто  
  
#### Пример  
  
```ini  
[access]  
books.*.ops        = '["select"]'              ; все видят  
books.editor-*.ops = '["select","update"]'     ; редакторы правят  
books.editor-*.fields = '["name","price"]'     ; только эти поля  
books.editor-*.where.update = 'created > datetime("now","-1 day")'  ; только свежие  
books.admin-*.ops  = '["*"]'                   ; админам всё  
  
[denied]  
books.ivan-temp.ops    = '["update","delete"]' ; точечный запрет  
books.*.fields         = '["salary","cost"]'   ; поля-табу для всех  
```  
  
Правила удобно редактировать визуально —  
`admin/catalogs/access/edit?name={catalog}` (матрица «операция × поле»,  
проверка эффективных прав по логину). **Внимание:** сохранение через админку  
переписывает `catalog.ini` целиком — комментарии в файле не сохраняются.  
  
---  
  
## Типы полей  
  
| Тип         | Описание                                      | Хранение в БД          |  
|-------------|-----------------------------------------------|------------------------|  
| `id`        | Первичный ключ                                | INTEGER PRIMARY KEY    |  
| `text`      | Однострочный текст                            | TEXT / VARCHAR         |  
| `integer`   | Целое число                                   | INTEGER                |  
| `real`      | Дробное число                                 | REAL / DECIMAL         |  
| `html`      | HTML-контент (редактор Summernote в админке)  | TEXT                   |  
| `link`      | URL-ссылка                                    | TEXT                   |  
| `image`     | Изображение с превью в таблице                | TEXT (путь к файлу)    |  
| `audio`     | Аудиофайл со встроенным плеером               | TEXT (путь к файлу)    |  
| `video`     | Видеофайл                                     | TEXT (путь к файлу)    |  
| `files`     | Множественные файлы (любые типы)              | JSON-массив метаданных |  
| `check`     | Булев переключатель (toggle)                  | BOOLEAN / INTEGER      |  
| `date`      | Дата                                          | DATE                   |  
| `datetime`  | Дата и время                                  | DATETIME               |  
| `checkdate` | Флажок с датой (булев + дата в одном поле)    | TEXT / DATETIME        |  
| `blob`      | Бинарные данные                               | BLOB                   |  
| `input`     | Нативный `<input>` с произвольным типом       | TEXT                   |  
| `relation`  | Связь с другой таблицей (foreign key)         | INTEGER (ID)           |  
| `select`    | Выпадающий список из значений таблицы         | TEXT / INTEGER         |  
  
---  
  
## Связи между каталогами (relation)  
  
Поле типа `relation` связывает запись с другой таблицей. При вызове `view()`  
фасад автоматически делает запрос к связанной таблице и заменяет сырой ID  
на массив `['id' => ..., 'label' => ...]`.  
  
Параметр `source` поддерживает три формата:  
  
### Формат 1: таблица в текущей БД  
  
```ini  
articles.field.category_id.type   = "relation"  
articles.field.category_id.source = "categories::name"  
; Ищет в той же БД, что и articles  
; Показывает поле 'name' из таблицы 'categories'  
```  
  
### Формат 2: явное имя подключения к БД  
  
```ini  
articles.field.author_id.type   = "relation"  
articles.field.author_id.source = "users_db::accounts::login"  
; Подключается к БД 'users_db', таблица 'accounts', показывает поле 'login'  
```  
  
### Формат 3: через другой каталог  
  
```ini  
articles.field.author_id.type   = "relation"  
articles.field.author_id.source = "account::account::login"  
; Находит каталог 'account', берёт его БД, таблицу 'account', показывает 'login'  
```  
  
**Результат в коде:**  
  
```php  
$catalog = $APP->catalog->view('articles');  
  
// Поле author_id в каждой записи будет выглядеть так:  
// $record['author_id'] = ['id' => 5, 'label' => 'john_doe']  
```  
  
---  
  
## Импорт и экспорт CSV  
  
Фасад поддерживает массовый перенос данных через CSV-файлы.  
  
**Экспорт через браузер:**  
  
```  
GET admin/catalogs/transfer/export?catalog=products&delimiter=;&quotes="  
```  
  
**Импорт через браузер:**  
  
```  
POST admin/catalogs/transfer/import  
  catalog   = products  
  delimiter = ;  
  quotes    = "  
  field     = name,price,active  
  document  = [CSV-файл]  
```  
  
**Программный экспорт:**  
  
```php  
$APP->catalog->items('products')->export('php://output', [  
    'delimiter' => ';',  
    'quotes'    => '"',  
]);  
```  
  
**Программный импорт:**  
  
```php  
$APP->catalog->items('products')->import($tmpFile, [  
    'delimiter' => ';',  
    'quotes'    => '"',  
], $fieldMapping);  
```  
  
---  
  
## Файлы  
  
| Файл | Назначение |  
|------|-----------|  
| `engine/facades/catalog.php`        | Реализация фасада (`SimpleCatalog`) |  
| `engine/facades/catalog.ini`        | Конфигурация каталогов |  
| `app/controllers/admin/catalogs/`   | Контроллеры административного интерфейса |  
| `app/views/admin/catalogs/`         | Шаблоны административного интерфейса |