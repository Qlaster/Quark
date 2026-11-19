# Quark Console — консольный интерфейс

Консоль — это «контроллеры без HTTP»: команда = файл, диспетч по пути,
контекст `$APP` + каналы ввода. Никаких классов-команд и реестров.

Архитектура: `./console` — тонкий бутстрап (env + APP + try/catch),
вся механика — в фасаде `console` (`engine/facades/console.php`):
разбор argv, резолв файла, фолбэк на фасады, цепочки команд.

---

## Запуск

```bash
./console <команда> [аргументы] [--флаги] [k=v]
```

Из корня проекта. Бутстрапит `$APP` (то же самое, что и web-роутер —
фасады доступны все, без разницы).

---

## Диспетчер: как резолвится команда

```text
./console a b c ...
│
├─ 1. Команда-файл: самый длинный существующий префикс
│     engine/console/**.php → include в скоупе фасада.
│     (файл на уровне запоминается, но спуск продолжается:
│      objects/import.php глубже objects.php — глубокий выигрывает)
│
├─ 2. Фасад-фолбэк: если файла нет — $APP->{a} существует и
│     у него есть метод {b} → вызов $APP->a->b(c, ...).
│     Аргументы '{'/'[' разворачиваются json_decode.
│     Печать возврата: scalar → строка, array → JSON, null → тишина.
│
├─ 3. Фасад есть, метода нет → оглавление: список public-методов.
│
└─ 4. Ничего не нашлось → команда help (обычный файл-справка).
```

Провал — это **только выброшенное исключение** (stderr + exit≠0,
ASCII-кот в выводе). `return false` из команды — не провал.
Возврат файла-команды диспетчером игнорируется — команда печатает сама.

---

## Контракт команды: $this + $APP

Файл команды выполняется `include` в скоупе метода фасада `console` —
поэтому `$this` внутри файла = фасад консоли (доступны даже его
приватные члены), `$APP` — приложение.

```php
<?php
// engine/console/hello.php — ./console hello Володя --loud lang=ru

$name  = $this->args[0] ?? 'world';      // позиционные: ['Володя']
$loud  = !empty($this->flags['loud']);   // --флаги:  ['loud'=>true]
$lang  = $this->vars['lang'] ?? 'ru';    // k=v:      ['lang'=>'ru']

$APP->service->start('example');          // фасады приложения
$this->service->list();                   // …или сахаром через __get-прокси
$this->dispatch(['service','stop','example']); // цепочки команд
```

Три канала ввода (публичные свойства фасада):

```bash
./console service run ws 8000 debug port=9000 --force
                        │    │      │         │
                        │    │      │         └─ flags['force'] = true
                        │    │      └─────────── vars['port'] = '9000'
                        │    └────────────────── args = ['8000','debug']
                        └─────────────────────── args[0] = 'ws'  (имя команды-службы)

./console cmd -- --force          → args = ['--force']   ('--' выключает разбор)
```

| Токен | Куда | Пример |
|---|---|---|
| `8000` | `$this->args` | позиционные данные |
| `--force`, `--k=v` | `$this->flags` | `--force`→true, `--mode=x`→['mode'=>'x'] |
| `port=8000` | `$this->vars` | `['port'=>'8000']` |
| `--` | терминатор | всё после — только `$args` |

- `k=v` сплитится по первому `=` — значение с `=` внутри не ломается
- `$this->args` **пуст** если токены ушли в путь команды
  (`service start example` → args=['example'])
- Локалки `$args`/`$flags`/`$vars` — выставлены для совместимости со
  старыми файлами; новые команды пишут через `$this->`

### Цепочки команд

`$this->dispatch($tokens)` — публичный вход: команда может вызвать
другую синтаксисом консоли, вход заново парсится:

```php
$this->dispatch(['service', 'stop', $name]);
$this->dispatch(['maintenance', 'on']);
```

---

## Команды-файлы

- Живут в `engine/console/**.php` (путь — `console.path` в `.env`).
- `_`-префикс файла/каталога — не команда (служебные хелперы, данные).
- Вложенность пути = вложенность команды:
  `engine/console/objects/import.php` → `./console objects import`.
- Компаньон-конфиг — `<name>.ini` рядом с файлом команды:
  `update.php` читает `update.ini` через `$APP->config->get(__DIR__.'/update.ini')`.

---

## Автодополнение (bash)

Диспетчер сам ставит дополнение при первом запуске `./console`
(маркер — файл `~/.local/share/bash-completion/completions/console`).
Bash-completion подхватывает его лениво — работает уже в текущей сессии,
если пакет `bash-completion` инициализирован (Ubuntu — по умолчанию).

```bash
./console se<TAB>            → service
./console service <TAB>     → list output run start status stop
./console service run e<TAB> → example     (имена служб)
```

Управление:

```bash
./console completion            # напечатать скрипт
source <(./console completion)  # подключить вручную в сессии
./console completion install    # установить файл самому
```

Отключить автоустановку: `[console] completion = 0` в `.env`.

---

## Команда `service` — управление службами

Служба — php-файл в `app/services/*.php`. Фасад `service` — чистый API
(возвращает данные, ничего не печатает), команды — тонкие обёртки.

```bash
./console service list                    # список: ▶️/🔴, файл, дата, pids
./console service run <name> [args] [--force]   # в текущем процессе (foreground)
./console service start <name> [--force]        # демон в фоне
./console service stop <name> [pid]             # все процессы или один pid
./console service status <name>                 # running / stopped
./console service output <name> [lines]         # журнал (хвост или целиком)
```

`--force` — обойти сторожок «один инстанс».

Механика:

- `start` = spawn нового процесса `php console service run <name>`:
  `nohup … &` на unix, `start /B` на windows. Унаследованные
  дескрипторы родителя (fd≥3) гасятся bash-обёрткой — иначе демон
  удерживает сокет HTTP-запроса и веб-ответ висит до конца службы.
- PHP-бинарь выбирается по SAPI: `PHP_BINARY` в cli, `PHP_BINDIR/php`
  из-под веба — mod_php/FPM в `PHP_BINARY` подсовывают не-cli бинарь.
- Живость — по сигнатуре cmdline (`quark:service:<name>` +
  `service run <name>`). Pid-файлов нет.
- `stop`: SIGTERM → grace (`service.timeout`, дефолт 5с) → SIGKILL.
- Вывод службы → канал `service.<name>` фасада `log`; сырой
  stdout/stderr → `app/logs/services/<name>.log`.

Конфиг — `engine/facades/service.ini`: `path`, `logs`, `title`,
`limpid`, `timeout`.

---

## Команда `objects` — импорт/экспорт объектов БД

```bash
./console objects export                  # JSON в stdout
./console objects export dump.json        # сырой JSON
./console objects export dump.ini         # ini-грамматика [collection]
./console objects export f.json --collection=admin   # одна коллекция

./console objects import dump.json        # авто-формат: json|ini по содержимому
./console objects import dump.json --strict  # коллизии → отказ до записи
./console objects import dump.json --skip    # существующие пропустить
```

По умолчанию импорт перезаписывает молча (легаси). После операции
печатается отчёт: `[коллекция]` + список документов.

---

## Команда `backup` — резервная копия

```bash
./console backup                  # дефолт: app + engine
./console backup engine           # только фреймворк
./console backup engine/console   # одна ветка
./console backup .                # всё (backups/ сам исключается)
```

Копия → `backups/<YYYY.MM.DD_HH-MM-SS>/<цель>`.

---

## Команда `user` — пользователи, policy, роли

Команды — тонкие обёртки над фасадом `user` (`$APP->user`,
`$APP->user->policy`, `$APP->user->role`). Работают напрямую с
`engine/database/users.sqlite` — GUI-логин не нужен, поэтому это же
аварийный инструмент доступа.

```text
пользователи  list add edit del password
policy        policy unlock unban        ← аварийное восстановление
роли          role roles{,export,import,del,rename}
```

### Пользователи

**`user list [--json]`** — таблица пользователей: login, name, email,
disable. `--json` — сырой массив для скриптов.

```bash
./console user list
```

**`user add <login> [password] [--k=v ...]`** — создать пользователя.
Пароль не задан → интерактив без эха (TTY) или первая строка stdin.
Любые `--k=v` → поля пользователя: известные → колонки, прочие → `data`.
Пароль проверяется по `[policy] password.length/classes`.

```bash
./console user add editor1 secret123 --name="Редактор" --email=e@x.ru
echo "secret123" | ./console user add editor2      # пароль из pipe
```

**`user edit <login> --k=v [--k=v ...]`** — установить свойства.
Флаги мержатся поверх записи (hash сохраняется сам).
`--disable=1` отключает пользователя, `--disable=0` — включает.

```bash
./console user edit editor1 --name="Новое имя" --disable=1
```

**`user del <login> [--yes]`** — удалить пользователя.
Без `--yes` на терминале спросит `[y/N]`.

**`user password <login> [пароль]`** — смена пароля.
Интерактив/stdin как у `add`. Счётчики policy не участвуют —
работает и на залоченном/забаненном пользователе (аварийный вход).

### Policy — диагностика и аварийное восстановление

Таблица `policy` в users.sqlite хранит счётчики промахов, локи пар
`ip:login` и баны адресов (см. `[policy]` в `engine/facades/user.ini`).

**`user policy [--json]`** — содержимое таблицы: кто в локе/бане,
`count`, остаток времени. Строка `(ip,'')` показывается как
`(адрес)` — это бан адреса; `БАН навсегда` — перманентный.

```bash
./console user policy
#  1.2.3.4  (адрес)   count=0  БАН навсегда
#  1.2.3.4  admin     count=8  лок ещё 287с
#  5.6.7.8  root      count=3
```

**`user unlock <login> [ip]`** — снять лок/счётчик.
С `ip` — конкретная пара `ip:login`; без — все записи логина.
Строки удаляются целиком: и лок, и count. Маркеры бана не трогает.

**`user unban <ip>`** — снять бан адреса.
Удаляет маркер `(ip,'')` И все пары адреса — иначе суммарный
счётчик пересоздал бы бан на следующем же запросе.

**Кейс: «админ себя залочил».** Веб не пускает даже с правильным
паролем — лечим с консоли:

```bash
./console user unban 1.2.3.4       # снимаем бан адреса
./console user unlock admin        # снимаем лок логина
./console user password admin      # при необходимости — новый пароль
```

### Роли

Роль — именованный набор `denied`-правил (`path = "action"`).
В хранилище — таблица `roles`, в фасаде — `$APP->user->role`
(внутренние имена исторические, наружу — «роль»).
В GUI выбирается на форме пользователя.

**`user role <login> <name>`** — назначить роль пользователю.
**ЗАМЕНЯЕТ** текущие `denied` правилами роли — не мержит!

**`user roles`** — список ролей с правилами.

**`user roles export [файл]`** — все роли → ini.
Без файла → stdout. Формат: `[role.<name>]` → `path = "action"`.

**`user roles import [файл]`** — все секции `[role.*]` (и легаси `[preset.*]`) ← ini.
Без файла → stdin. Одноимённые перезаписываются (set, не merge).

**`user roles del <name> [--yes]`** — удалить (спросит на TTY).
Пользователей с назначенной ролью не трогает — правила уже скопированы.

**`user roles rename <old> <new>`** — переименовать.

**Кейс: перенести роли между инсталляциями.**

```bash
./console user roles export roles.ini   # на старой
./console user roles import roles.ini   # на новой
```

**Кейс: поправить роль руками.** `export` → правим ini → `import`:

```ini
[role.Редактор контента]
app/controllers/admin/options/** = "action"
app/controllers/admin/tools/**   = "action"
```

---

## Команда `maintenance` — режим обслуживания

```bash
./console maintenance on    # сайт отвечает 503 всем
./console maintenance off   # сайт живой
```

Механика: точечная запись `enabled = 0|1` в `.env[maintenance]`
(комментарии/формат не трогаются). `index.php` видит флаг после
`parse_ini_file`, до ядра — инклюдит dependency-free контроллер из
`maintenance.controller` (503 + `Retry-After` + `Refresh`-автоперезагрузка).
Работает и на сломанном фреймворке — bootstrap не нужен.

```ini
[maintenance]
enabled    = 0
controller = "app/controllers/error/maintenance.php"
eta        = 120      ; секунды → Retry-After + Refresh
```

---

## Команда `update` — обновление платформы

```bash
./console update                  # из update.ini[update.distr.zip]
./console update /path/to/distro  # из локальной папки
./console update --dry            # только план, ничего не трогает
./console update --no-backup      # пропустить точечный бэкап
./console update --no-test        # пропустить ворота тестов
```

Пайплайн (прод живой до фазы apply — даунтайм = секунды):

```
1. fetch    — дистрибутив → /tmp/quark-update-<ts>/ (self-clean)
2. plan     — файлы пакета → ignore|merge|db|replace|new по маскам
3. stage    — «релиз под твои данные» в /tmp:
              merge-файлы слиты с продом (тройной replace:
              твои значения + новые ключи), базы — копии живых
              с накатанной схемой пакета (только +колонки/+таблицы)
4. test     — ./console test в стейдже (ворота; нет → skip)
5. maintain — maintenance=1 (сайт в 503)
6. backup   — точечный слепок затрагиваемых файлов → backups/update-<ts>/
7. apply    — staging поверх прода: copy→tmp→rename, из глубины наружу
8. release  — maintenance=0, сайт живой
```

Провал до фазы 5 — прод ни разу не тронут. Провал в apply — откат из
бэкапа, флаг снимается, в журнал `update`-канала пишется причина.

Миграции БД — аддитивная схемо-синхронизация sqlite: недостающие
таблицы/колонки добавляются с типами пакета; конфликт типов — скип
с warning. Данные не трогаются.

Конфиг — `engine/console/update.ini`: `distr`, `rootmarker`,
маски `ignore`/`merge`.

---

## Команда `test` — тесты платформы

```bash
./console test              # = test run — все секции
./console test run [имя]    # все или по именам
./console test list         # список секций
./console test log [имя]    # вывод последнего прогона секции
```

Секция = файл `engine/console/test/run/<name>.php` — **обычная
команда** (`console test run facades` резолвит диспетчер напрямую).
`run` без имени обходит все файлы редиспетчем, пишет вывод в
`app/logs/tests/<name>.log`, exit≠0 при любом провале — ворота
для `console update`.

Секции: `facades` (инстанцирование), `lint` (php -l по коду),
`db` (коннекты → tables), `objects` (round-trip), `env` (целостность).

---

## Команда `help`

```bash
./console help
```

Дерево команд + список фасадов. Сама — обычный файл-команда,
вызывается и при промахе диспетчера (ничего не нашлось → `dispatch(['help'])`).

---

## Фасад-фолбэк: вызов фасадов напрямую

Без файла-команды диспетчер может вызвать метод фасада:

```bash
./console <facade>             # оглавление public-методов
./console <facade> <method> a b   # $APP->facade->method('a','b')
```

Аргументы, начинающиеся с `{` или `[`, декодируются как JSON.

---

## Конфигурация `.env`

```ini
[console]
path       = "engine/console"   ; дерево команд
completion = 1                  ; автоустановка bash-дополнения (0 — выкл)

[maintenance]
enabled    = 0
controller = "app/controllers/error/maintenance.php"
eta        = 120
```

---

## Соглашения вывода

- Команда печатает сама; фасад возвращает данные и молчит.
- Конец вывода команд всегда завершается `\n` — промпт на чистой строке.
- Исключение → кот + сообщение в stderr + exit-код (0 превращается в 12).
- Эмодзи-маркеры (`▶️`/`🔴`) — самодостаточный цвет без ANSI; работают
  и в терминале, и в браузере, и в лог-файле не пачкают escape-кодами.
