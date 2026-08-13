---

```markdown
# QyberTech Presenter

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Легковесный PHP-шаблонизатор с компиляцией шаблонов в нативный PHP-код.
Поддерживает переменные, управляющие структуры, подключение фрагментов и кеширование.
```

Если используете отдельно от фреймворка:


```php
require_once 'QyberTech/Presenter/Presenter.php';
```

## Инициализация
(если требуется использовать отдельно от фреймворка или создать новый экземпляр)

```php
$presenter = new \QyberTech\Presenter\Presenter();
```

С пользовательской конфигурацией:

```php
$presenter = new \QyberTech\Presenter\Presenter([
    'compilation' => [
        'caсhe' => false,
        'folder' => '/tmp/templater/'
    ],
    'templates' => [
        'folder' => 'app/views/'
    ]
]);
```

## Быстрый старт

### Использование во фреймворке Quark

В контроллере через фасад `$APP->template`:
(в документации будет использован преимущественно этот вариант)

```php
$APP->template->file('admin/dashboard.html')->display($content);
```


### Вывод шаблона без фреймворка (при использовании, как отдельный компонент)


```php
$presenter->file('page.html')->display([
    'title' => 'Заголовок страницы',
    'text'  => 'Содержимое'
]);
```

### Шаблон page.html

```html
<!DOCTYPE html>
<html>
<head>
    <title>{$title}</title>
</head>
<body>
    <p>{$text}</p>
</body>
</html>
```



С явным указанием базового URL для ресурсов:

```php
$APP->template->themelink(null)->file('error/500/500.htm')->display($content);
```

---

## Полная документация

### Методы API

#### `file(string $file_link): $this`
Указывает файл шаблона для обработки.

```php
$APP->template->file('admin/users/users.list.html');
```

Если в конфиге задан `templates.folder`, путь к файлу указывается относительно него:

```php
// При templates.folder = 'app/views/'
$APP->template->file('admin/dashboard.html');
// Реально откроет: app/views/admin/dashboard.html
```

---

#### `themelink(string|null $link): $this`
Задаёт базовый URL для ресурсов шаблона (CSS, JS, изображения).
Этот URL подставляется вместо тега `~/` в путях к ресурсам.

```php
$APP->template->themelink('/assets/admin/');
```

Передача `null` сбрасывает значение — полезно при обработке ошибок,
если предыдущий шаблон установил некорректный путь:

```php
$APP->template->themelink(null)->file('error/500/500.htm')->display($content);
```

---

#### `display(array $vars_array): bool`
Компилирует шаблон и выводит результат в браузер.
Ключи массива становятся переменными шаблона.

```php
$APP->template->file('page.html')->display([
    'title'   => 'Главная',
    'catalog' => ['users' => ['list' => $users]]
]);
```

---

#### `compile(): string|false`
Компилирует шаблон и возвращает путь к скомпилированному PHP-файлу.
Используется внутри `display()`, но может вызываться напрямую.

```php
$compiled_file = $presenter->compile();
```

---

#### `compile_data(string $tpl_string): string`
Компилирует строку с тегами шаблонизатора и возвращает PHP-код.
Полезно для компиляции фрагментов HTML без файла.

```php
$php_code = $presenter->compile_data('<p>{$name}</p>');
```

---

#### `vars(): array`
Возвращает список переменных, используемых в шаблоне.

```php
$vars = $APP->template->file('page.html')->vars();
// ['$title', '$catalog', ...]
```

---

#### `tag_list(): array`
Возвращает список всех тегов шаблонизатора в файле.

```php
$tags = $APP->template->file('page.html')->tag_list();
```

---

### Синтаксис шаблонов

Все теги шаблонизатора заключаются в фигурные скобки `{` и `}`.

---

#### Переменные

**Экранированный вывод** (защита от XSS, рекомендуется по умолчанию):

```html
{$variable}
{$array['key']}
{$array['nested']['key']}
```

Компилируется в: `<?php echo (htmlspecialchars((string) $variable));?>`

**Сырой вывод** (без экранирования, для HTML-контента):

```html
{$$variable}
{$$graph['head']}
```

Компилируется в: `<?php echo ($variable);?>`

Пример из `app/views/admin/dashboard.html`:

```html
<base href="{$$base}" id='baseurl'>
<span>{$$graph['head']}</span>
```

---

#### Условия `{if}`

```html
{if $condition}
    Контент
{end}
```

С ветвью `{else}`:

```html
{if $_user['logo']}
    <img src="{$_user['logo']}" class="img-circle">
{else}
    <img src="~/img/avatars/profile1.png" class="img-circle">
{end}
```

С ветвью `{elseif}`:

```html
{if $status == 'active'}
    <span class="label-success">Активен</span>
{elseif $status == 'pending'}
    <span class="label-warning">Ожидание</span>
{else}
    <span class="label">Неизвестно</span>
{end}
```

Сложные условия (PHP-выражения):

```html
{if ($section['list']) and (!$section['info'])}
    <span class="fa arrow"></span>
{end}
```

Условие прямо в атрибуте HTML-тега:

```html
<a {if $catalog['link']} href="{$catalog['link']}" {else} disabled {end}>
    {$catalog['head']}
</a>
```

---

#### Цикл `{foreach}`

```html
{foreach $items as $item}
    <li>{$item['name']}</li>
{end}
```

С ключом:

```html
{foreach $catalogs['list'] as $name => $catalog}
    <div class="forum-item">
        <a href="{$catalog['link']}">{$catalog['head']}</a>
    </div>
{end}
```

Вложенные циклы:

```html
{foreach $menu['tools']['list'] as $_elem}
    {if count($_elem['button'])}
        <ul class="dropdown-menu">
            {foreach $_elem['button'] as $_btn}
                <li><a href="{$_btn['link']}">{$_btn['head']}</a></li>
            {end}
        </ul>
    {end}
{end}
```

---

#### Цикл `{while}`

```html
{while $condition}
    Контент
{end}
```

---

#### Цикл `{for}`

```html
{for $i=0; $i<10; $i++}
    <span>{$i}</span>
{end}
```

---

#### Вывод PHP-выражений `{=}`

Позволяет вывести результат произвольного PHP-выражения:

```html
Update on {= date('d.m.Y')}
```

Компилируется в: `<?php echo (date('d.m.Y'));?>`

---

#### Встроенный PHP `{php}` / `{/php}`

Для вставки произвольного PHP-кода:

```html
{php}
    $result = array_sum($values);
{/php}
<p>Итого: {= $result}</p>
```

---

#### Статические теги

| Тег | Результат |
|-----|-----------|
| `{end}` | `<?php } ?>` |
| `{else}` | `<?php } else { ?>` |
| `{php}` | `<?php ` |
| `{/php}` | ` ?>` |

---

### Подключение фрагментов

Теги `{section}` и `{require}` работают идентично — они вставляют содержимое
внешнего HTML-файла прямо в шаблон **до компиляции**.

```html
{section "~section/mainmenu.html"}
{section "~section/head.html"}
{require "../~section/footer.html"}
```

Путь указывается относительно текущего файла шаблона.
Тег `~/` в пути к фрагменту заменяется на директорию текущего шаблона.

Пример структуры страницы из `app/views/admin/catalogs/list.html`:

```html
<div id="wrapper">
    {section "../~section/mainmenu.html"}

    <div id="page-wrapper" class="gray-bg">
        {section "../~section/head.html"}
        {section "../~section/title.html"}

        <div class="wrapper wrapper-content">
            <!-- Контент страницы -->
        </div>

        {section "../~section/footer.html"}
    </div>
</div>
```

---

### Ресурсы и пути `~/`

Тег `~/` в путях к CSS, JS и изображениям автоматически заменяется
на базовый URL, заданный через `themelink()`.

```html
<link href="~/css/bootstrap.min.css" rel="stylesheet">
<script src="~/js/jquery-2.1.1.js"></script>
<img src="~/img/avatars/profile1.png">
```

Если `themelink()` не вызывался, базовый URL вычисляется автоматически
на основе пути к файлу шаблона и `$_SERVER['SCRIPT_NAME']`.

---

### Литеральные зоны

Содержимое внутри следующих блоков **не обрабатывается** шаблонизатором:

| Блок | Описание |
|------|----------|
| `<literal>...</literal>` | Явный литеральный блок |
| `<!-- ... -->` | HTML-комментарии |
| `<script>...</script>` | JavaScript |
| `<style>...</style>` | CSS |
| `<?php ... ?>` | PHP-код |

Это позволяет безопасно использовать фигурные скобки в JavaScript и CSS:

```html
<script>
    // Здесь {$variable} не будет обработан шаблонизатором
    var config = { key: 'value' };
</script>
```

Для явного отключения обработки используйте тег `<literal>`:

```html
<literal>
    Этот {$текст} не будет обработан шаблонизатором
</literal>
```

---

### Жёсткая замена тегов

В конфигурации можно задать простые текстовые замены, которые применяются
до компиляции:

```php
$presenter = new Presenter([
    'replace' => [
        'SITE_NAME' => 'Мой сайт',
        'VERSION'   => '2.0'
    ]
]);
```

```html
<title>SITE_NAME — версия VERSION</title>
```

---

### Свойства объекта

| Свойство | Тип | Описание |
|----------|-----|----------|
| `$base_link` | `string\|null` | Базовый URL для ресурсов (устанавливается через `themelink()`) |
| `$base_html` | `string\|null` | Значение для тега `<base href="...">` (устанавливается напрямую) |
| `$head` | `string\|null` | Дополнительный HTML-код, вставляемый в `<head>` |
| `$config` | `array` | Полная конфигурация экземпляра |

Пример использования `base_html` и `head` из `app/controllers/index.php`:

```php
$APP->template->base_html = $APP->url->home();
$APP->template->head = $APP->page->meta->get([...]);
$APP->template->file($page['html'])->display($content);
```

---

### Конфигурация

Начальный список параметров конфигурации:

```php
$config = [
    // Разделители тегов
    'left_delimiter'  => '{',
    'right_delimiter' => '}',

    // Уровень вывода ошибок PHP при рендеринге
    'error_reporting' => E_ALL & ~E_NOTICE,

    // Тег для замены на базовый URL ресурсов
    'url_link_tag' => '~/',

    // Удалять HTML-комментарии из результата
    'skip_comments' => false,

    // Параметры компиляции
    'compilation' => [
        'caсhe'  => true,    // Кешировать скомпилированные шаблоны
        'folder' => '%TEMP%/templater/', // Директория кеша (%TEMP% → sys_get_temp_dir())
        'extent' => 'php',   // Расширение файлов кеша
        'nobody' => true,    // Транслировать неизвестные теги как PHP-код
        'mutex'  => false    // Объединять соседние <?php ?> блоки
    ],

    // Директория с шаблонами (путь в file() будет относительным)
    'templates' => [
        'folder' => 'app/views/'
    ],

    // Жёсткая замена строк до компиляции
    'replace' => []
];
```

Конфигурация фреймворка Quark хранится в `engine/facades/template.ini`:

```ini
error_reporting = 1

[compilation]
caсhe = false
extent = "php"
nobody = true
folder = "%TEMP%/templater/"

[templates]
folder = app/views/
```

---

### Кеширование

Presenter автоматически кеширует скомпилированные шаблоны.
Имя файла кеша формируется из пути к шаблону и суммы дат изменения
всех файлов, входящих в его состав (включая подключённые через `{section}`).

Формат имени: `[путь-к-шаблону]~[дата].php`

При изменении любого из файлов шаблона старый кеш удаляется
и создаётся новый при следующем обращении.

> **Примечание:** В конфигурации фреймворка кеш отключён (`caсhe = false`),
> что означает перекомпиляцию при каждом запросе — удобно при разработке.

---

## Полный пример шаблона

Пример страницы со списком пользователей (`app/views/admin/users/users.list.html`):

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <base href="{$$base}">
</head>
<body>
    <div id="wrapper">
        {require "../~section/mainmenu.html"}

        <div id="page-wrapper" class="gray-bg">
            {section "../~section/head.html"}
            {require "../~section/title.html"}

            <div class="wrapper wrapper-content">
                <h2>{$menu['context']['head']}</h2>

                {foreach $menu['context']['list'] as $_item}
                    <a href="{$_item['link']}">
                        <button class="btn btn-success">
                            <i class="{$_item['icon']}"></i> {$_item['head']}
                        </button>
                    </a>
                {end}

                {foreach $catalog['users']['list'] as $_login => $_user}
                    <div class="col-lg-4">
                        {if $_user['logo']}
                            <img src="{$_user['logo']}" class="img-circle">
                        {else}
                            <img src="~/../img/avatars/profile1.png" class="img-circle">
                        {end}
                        <strong>{$_user['name']}</strong>
                        <p>{$_user['info']}</p>
                    </div>
                {end}
            </div>

            {require "../~section/footer.html"}
        </div>
    </div>
</body>
</html>
```

Контроллер для этого шаблона:

```php
$content['title'] = 'Пользователи';
$content['catalog']['users']['list'] = $APP->user->all();

foreach ($content['catalog']['users']['list'] as &$user) {
    $user['link'] = 'admin/options/users/edit?login=' . $user['login'];
}

$APP->template->file('admin/users/users.list.html')->display($content);
```

---

Поддержка PHP 7.4+

Лицензия MIT

