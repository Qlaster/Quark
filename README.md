**Quark** is a ultra-fast and clear PHP framework built on the theory of interfaces!

********
# Structure
![image](https://user-images.githubusercontent.com/77342137/206212183-95fbe4ec-57a2-476c-bde9-9c64079b0e71.png)


# A simple example:

## ORM
```sql
$orm->where(['id'=>1])->select();
$orm->where('id = ?', 1)->select();

$orm->table('tablename')->insert($data);
$orm->table('tablename')->where(['id'=>6])->delete();

$orm->table('tablename')->where(['id'=>2])->update($data);
$orm->table('tablename')->like($like)->where($where)->select();
```

## Routing
```ini
[alias]
 = index

[route]
admin/* = admin/autoinclude.php
* => controllers/

[hook]
test_controller* = controllers/test
test_provider* = providers/test
````

## Template

Vars (**Double $** sign - output without escaping):
```html
<title> {$title} </title>
<footer> {$$copyright} </footer>
```

Condition:
```html
{if $logo}
	<img src="**{$logo}**" alt="logo" />
{end}
```

Foreach:
```html
{foreach $slider['main']['list'] as $value}
	<li class="slider">
		<a href="{$value['link']}">
			<img src="{$value['image']}" alt="{$value['info']}" />
		</a>
	</li>
{end}
```





# File tree

- 📁 **app**
	- 📁 controllers
	- 📁 models
	- 📁 views
	- 📁 providers
	- 📁 services
	- 📁 logs
	- 🗎 install.ini
	- 🗎 .env

- 📁 **engine**
	- 📁 **core**
		- 🗎 core.php
		- 🗎 ext.array.php
		- 🗎 ext.autoload.php
		- 🗎 ext.error-hidden.php
		- 🗎 ext.string.php

	- 📁 database
		- 🗎 account.sqlite
		- 🗎 objects.sqlite
		- 🗎 page.sqlite
		- 🗎 review.sqlite
		- 🗎 users.dba

	- 📁 vendor
	- 📁 **facades**
		- 🗎 account.php
		- 🗎 cache.php
		- 🗎 [catalog.php](manual/facades/catalog.md)
		- 🗎 [config.php](manual/facades/config.md)
		- 🗎 console.php
		- 🗎 controller.php
		- 🗎 db.php
		- 🗎 dba.php
		- 🗎 [l10n.php](manual/facades/l10n.md)
		- 🗎 [log.php](manual/facades/log.md)
		- 🗎 [objects.php](manual/facades/objects.md)
		- 🗎 page.php
		- 🗎 provider.php
		- 🗎 [route.php](manual/Router.md)
		- 🗎 [talk.php](manual/facades/talk.md)
		- 🗎 [template.php](manual/Presenter-%28Шаблонизатор%29.md)
		- 🗎 [url.php](manual/facades/url.md)
		- 🗎 user.php
		- 🗎 useragent.php
		- 🗎 utils.php
		- 🗎 traffic.php

- 📁 public
	- 🗎 .htaccess

- 🗎 index.php
- 🗎 [console](manual/console.md)
- 🗎 .env
- 🗎 .htaccess

📖 Документация: [manual/Home.md](manual/Home.md) · [Архитектура за 10 минут](manual/Architecture.md)
