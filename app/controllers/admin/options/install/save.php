<?php


	if ($_POST['config'])
	{
		//Запись только внутри разрешённых директорий
		$fn = $APP->files->jailPath($_POST['config']['filename'] ?? '');
		echo ($fn and file_put_contents($fn, $_POST['config']['body']) !== false) ? 'Установленные объекты: ' : exit('Не удалось сохранить файл');

		$config = $APP->config->get($fn);
		foreach ($config as $section => $objects)
			foreach ($objects as $name => $object)
			{
				echo "<br>📦 ".$section.'	🔖 '.$name.''; // ➤ ❱ ↦ ➤ › 🠺 🡂 🢧 ⮞ 🡆 💾 🔰 📦 🔶 📚 🔘
				//~ echo $section.'|'.$name.'<br>';
				$APP->object->collection($section)->set($name, $object);
			}
	}
