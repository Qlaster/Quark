# ≡ Сервисы

Служба — php-файл в этой директории. Имя службы = имя файла без `.php`
(допустимы `a-z 0-9 _ - .`). Управление — командами `service`
консоли `./console` из корня проекта:

```bash
./console service list                    # список служб: ▶️/🔴, файл, дата, pids
./console service run   <name> [args] [--force]   # в текущем процессе (foreground)
./console service start <name> [args] [--force]   # демон в фоне
./console service stop  <name> [pid]              # все процессы или один pid
./console service status <name>                   # running / stopped
./console service output <name> [lines]           # журнал (хвост или целиком)
```

Для `testsrv.php` запуск выглядит так:

```bash
./console service run testsrv 8000 debug port=9000 --force
```

## Контракт службы

Служба — «чужая программа»: её вход — сырой `$argv`, как у обычной
CLI-команды. При `limpid = 1` `argv[0]` — имя службы, дальше все
токены хвоста: позиционные, `k=v`, `--flags` (включая `--force` —
фасад трактует его сам, но службе он тоже виден). При
`limpid = 0` служба видит исходный argv процесса целиком
(см. ниже).

Файл службы включается в изолированном скоупе: видимы только
`$APP` и `$file`. Поэтому `$argv` читается через `global`:

```php
<?php
	global $argv;
	echo json_encode($argv);   // ["testsrv","8000","debug","port=9000","--force"]
```

Порядок сборки: позиционные → `k=v` → `--flags` (внутри канала
порядок сохраняется, перемешивание каналов между собой — нет).
Повторные `k=v`/`--k=v` дедуплицируются — последний выигрывает;
повторы передаются позиционно после терминатора `--`:

```bash
./console service run testsrv -- tag=red tag=blue
```

Вывод `echo`/`print` службы журналируется каналом `service.<name>`
фасада `log` (маркер `[уровень]` в начале строки задаёт уровень);
сырой stdout/stderr демона — `app/logs/services/<name>.log`.
Запущенная не на tty служба пишет только в журнал.

## Режим limpid

`limpid` (по умолчанию 1) — перепись `$argv`: служба видит его как
отдельная команда (`argv[0]` = имя службы + токены хвоста).
При `limpid = 0` `$argv` остаётся исходным для всего процесса:
`['console','service','run','testsrv','8000','debug',...]` —
все токены там и так лежат сырыми (диспетчер их не потребляет).

Сигнатура `quark:service:<name>` в заголовке процесса и
изолированный скоуп файла (`$APP`, `$file`) работают ВСЕГДА —
не от limpid. Отключается в `engine/facades/service.ini`:

```ini
[service]
path    = "app/services"
limpid  = 0
```

## Сторожок «один инстанс»

Повторный `run`/`start` уже работающей службы — отказ
(`Service '<name>' is already running`). Обход — `--force`.

Живость определяется по сигнатуре в `/proc/*/cmdline`:
`service run <name>` или `quark:service:<name>`. Осторожно:
shell-обёртка (`sh -c`, cron, systemd, `ssh "cmd"`), чья cmdline
содержит ту же строку, даёт ложный «already running» —
лечится тем же `--force` или запуском без обёртки.

## Пример: websocket-сервер на Workerman

```php
<?php
use Workerman\Worker;

// Create a Websocket server
$ws_worker = new Worker("websocket://0.0.0.0:8000");

// 4 processes
$ws_worker->count = 4;

// Emitted when new connection come
$ws_worker->onConnect = function($connection)
{
    echo "New connection\n";
};

// Emitted when data received
$ws_worker->onMessage = function($connection, $data)
{
    // Send hello $data
    $connection->send('hello ' . $data);
};

// Emitted when connection closed
$ws_worker->onClose = function($connection)
{
    echo "Connection closed\n";
};

// Run worker
Worker::runAll();
```
