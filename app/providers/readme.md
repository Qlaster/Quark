# ≡ Провайдеры

Создадим провайдер, который возвращает текущую дату и время. Для этого создадим файл currentdata.php в этой директории
со следующим содержимым:

#####
```php
return new date($mask);
```


Вызвать посавщика данных можно следующим образом

```php
$APP->provider->execute("currentdata", ['mask'=>"Y-m-d H:i:s"]);
```

Здесь вызывается провайдер и передается переменная mask, которая нужна провайдеру
