# ≡ Сервисы

### Для создания сервиса, следует создать файл в этой директории с произвольном именем, например testsrv.php. Далее, следует написать код самого сервиса. 

### Возьмем в качестве примера код для реализации websocket:

#####
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


Обратится к сервису можно через консоль в корне проекта:

```bash
./console service [servicename]
```

Для сервиса testsrv.php, код запуска будет следующий:
 
```bash
./console service testsrv.php start
```


По умолчанию, фреймворк будет стараться скрыть свое присутствие для запускаемой службы. Он подменит корневое имя файла на название службы (имя файла службы), будет исполнять его в глобальном namespace и т.д. Отключить данный режим можно отредактировав файл .env, указав значение **limpid = 0** в секции **[service]**:

```ini
[service]
path   = "app/services"
limpid = 0
```
