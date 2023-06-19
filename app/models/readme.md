# ≡ Модели

### Простая модель с возвратом фасада. Создадим файл absmodel.php


#####
```php
class AbstractModel
{
	function test()
	{

	}
}

return new AbstractModel();
```


Вызвать метод test из контроллера, можно следующим образом

```php
$APP->absmodel->test();
```
