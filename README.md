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











├── public
│     ├── domain_1
│     │     └── hash
│     │           └── filename.png
│     └── domain_2
└── protected
      ├── domain_1
      └── domain_2

